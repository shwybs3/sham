<?php
/**
 * auto-publish-cron.php — Standalone auto-publisher cron entry point.
 *
 * Set up in cPanel → Cron Jobs as (hourly example):
 *   0 * * * * php /home/user/public_html/auto-publish-cron.php >> /dev/null 2>&1
 *
 * Checks auto_pub_enabled and auto_pub_next_run before doing any work,
 * so it is safe to run frequently — it exits immediately if not due yet.
 */

define('CRON_MODE', true);

$cfgFile = __DIR__ . '/config.php';
if (!file_exists($cfgFile)) { echo "config.php not found\n"; exit(1); }
require_once $cfgFile;

@set_time_limit(300);

$enabled  = get_cfg($pdo, 'auto_pub_enabled', '0') === '1';
if (!$enabled) { echo "Auto-publisher is disabled. Exiting.\n"; exit(0); }

$nextRun  = (int)get_cfg($pdo, 'auto_pub_next_run', '0');
if ($nextRun > time()) {
    $wait = $nextRun - time();
    echo "Not due yet. Next run in {$wait}s. Exiting.\n";
    exit(0);
}

$freq     = get_cfg($pdo, 'auto_pub_frequency', 'daily');
$typesRaw = get_cfg($pdo, 'auto_pub_types', 'article,tutorial');
$types    = array_filter(explode(',', $typesRaw));
if (!$types) $types = ['article'];
$type     = $types[array_rand($types)]; // pick one type randomly this run

echo "[" . date('Y-m-d H:i:s') . "] Auto-publisher starting. Type: {$type}, Freq: {$freq}\n";

/* ── Step 1: Get trending topics from AI ── */
$keys = openrouter_keys(get_cfg($pdo, 'openrouter_key', ''));
if (!$keys) { echo "No OpenRouter keys configured. Exiting.\n"; exit(1); }
$models = build_model_rotation($pdo, true);

$typeLabel = ['article'=>'مقال','tutorial'=>'شرح تعليمي','comparison'=>'مقارنة','review'=>'مراجعة'][$type] ?? 'مقال';

$topicPrompt = "أنت محرر محتوى متخصص في المحتوى التقني العربي. اقترح 5 مواضيع رائجة ومطلوبة جداً الآن في مجال التقنية والذكاء الاصطناعي والتطبيقات، تصلح لكتابة {$typeLabel} بالعربية.

المطلوب: JSON array فقط — 5 مواضيع، كل موضوع:
- title: عنوان المقال بالعربية (جذاب وSEO-friendly)
- slug: slug إنجليزي (lowercase-kebab-case)
- type: نوع المقال (article/tutorial/comparison/review)
- keywords: كلمات مفتاحية (مفصولة بفاصلة)

الرد: JSON array فقط بدون أي نص إضافي.";

$tr = openrouter_call_rotating($keys, $models, $topicPrompt, 800);
if (!$tr['ok']) { echo "Failed to get topics: " . ($tr['error'] ?? 'unknown') . "\n"; exit(1); }

$raw = trim($tr['content']);
$raw = preg_replace('/^```(?:json)?\s*/i','',preg_replace('/\s*```\s*$/i','',$raw));
if (preg_match('/\[.*\]/s',$raw,$m)) $raw=$m[0];
$topics = json_decode($raw, true);
if (!is_array($topics) || empty($topics)) { echo "Failed to parse topics JSON.\n"; exit(1); }

$topic    = $topics[0];
$title    = trim($topic['title'] ?? '');
$slug     = trim($topic['slug'] ?? '');
$keywords = trim($topic['keywords'] ?? '');
$artType  = in_array($topic['type']??'',['article','tutorial','comparison','review'],true) ? $topic['type'] : $type;

if (!$title) { echo "Empty topic title. Exiting.\n"; exit(1); }
echo "Topic selected: {$title}\n";

/* ── Step 2: Generate 3 article chunks ── */
$sections = [
    1 => 'المقدمة + خلفية عامة + لماذا هذا الموضوع مهم الآن',
    2 => 'التفاصيل العملية + الأمثلة التطبيقية + المعلومات التقنية',
    3 => 'خلاصة وتوصيات + أسئلة شائعة (8 أسئلة) + خاتمة مقنعة',
];

$fullHtml = '';
for ($c = 1; $c <= 3; $c++) {
    $prevNote = $fullHtml ? "ما كُتب سابقاً (لا تكرره): [" . mb_strlen($fullHtml) . " حرف مكتوب]" : '';
    $chunkPrompt = "أنت كاتب محتوى تقني عربي محترف. اكتب القسم رقم {$c} من 3 لـ{$typeLabel} بعنوان: «{$title}»
الكلمات المفتاحية: {$keywords}
موضوع هذا القسم: {$sections[$c]}
{$prevNote}

المتطلبات:
- 700-1000 كلمة لهذا القسم
- HTML فقط (لا Markdown)
- <h2> للعناوين الرئيسية، <h3> للفرعية، <p> للفقرات، <ul><li> للقوائم
- لغة عربية فصيحة واضحة ومفيدة — لا ملء ولا تكرار
- محتوى حقيقي ومتعمق";

    $cr = openrouter_call_rotating($keys, $models, $chunkPrompt, 2000);
    if (!$cr['ok']) {
        echo "Chunk {$c} failed: " . ($cr['error'] ?? 'unknown') . "\n";
        exit(1);
    }
    $fullHtml .= "\n" . $cr['content'];
    echo "Chunk {$c} done (" . mb_strlen($cr['content']) . " chars, model: " . ($cr['model']??'?') . ")\n";
}

/* ── Step 3: Build slug ── */
$base = $slug ?: preg_replace('/[^a-z0-9]+/','-',strtolower($slug ?: 'auto-'.time()));
$base = trim($base,'-') ?: 'auto-article-'.time();
$finalSlug = $base; $n = 0;
while ((int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE slug=".json_encode($finalSlug))->fetchColumn()) {
    $n++; $finalSlug = $base.'-'.$n;
    if ($n > 99) { $finalSlug = $base.'-'.time(); break; }
}

/* ── Step 4: Save to DB ── */
$excerpt = mb_substr(strip_tags($fullHtml), 0, 160);
try {
    $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,status) VALUES (?,?,?,?,?,?,?,?,'published')")
        ->execute([$artType, $title, $finalSlug, $title, $excerpt, $keywords, $excerpt, $fullHtml]);
    $newId = (int)$pdo->lastInsertId();
    echo "Saved! ID={$newId}, slug={$finalSlug}, size=" . mb_strlen($fullHtml) . " chars\n";
} catch (Throwable $e) {
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}

/* ── Update cron state ── */
$total = (int)get_cfg($pdo,'auto_pub_total','0') + 1;
set_cfg($pdo, 'auto_pub_total', (string)$total);
set_cfg($pdo, 'auto_pub_last_run', date('Y-m-d H:i:s'));
$intervals = ['hourly'=>3600,'every6h'=>21600,'daily'=>86400,'weekly'=>604800];
set_cfg($pdo, 'auto_pub_next_run', (string)(time() + ($intervals[$freq] ?? 86400)));

echo "[" . date('Y-m-d H:i:s') . "] Done. Total articles: {$total}. Next run: " . date('Y-m-d H:i', time()+($intervals[$freq]??86400)) . "\n";
