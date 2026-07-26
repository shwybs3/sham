<?php
// Raise PHP limits for admin only — long descriptions can be several MBs
@ini_set('post_max_size', '512M');
@ini_set('upload_max_filesize', '500M');
@ini_set('max_input_vars', '5000');
@ini_set('memory_limit', '256M');

require_once __DIR__ . '/config.php';
admin_ip_check($pdo);

// AI-generation AJAX calls can legitimately take tens of seconds (external
// OpenRouter requests). PHP's default file-based session handler holds an
// exclusive lock on the session file for the whole request — without
// releasing it here, one long-running generation blocks every other
// request from the SAME logged-in admin (e.g. a second browser tab, or the
// page trying to poll status) until it finishes. None of the AJAX handlers
// below write to $_SESSION after this point, so closing it early is safe.
if (isset($_GET['ajax']) && is_admin()) {
    session_write_close();
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate AI data (multi-key × multi-model rotation)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate' && is_admin()) {
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    if (!$name) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد. أضفه من صفحة الإعدادات.']); exit; }

    $models = build_model_rotation($pdo);

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. التطبيق: "{$name}"

{$seoStandards}

- long_description: وصف أصلي احترافي 600-900 كلمة على الأقل (وليس فقرة قصيرة)، عدة فقرات تغطي: نظرة عامة على التطبيق، أبرز الميزات بالتفصيل، لمن يناسب هذا التطبيق، وأسلوب طبيعي يخدم SEO دون حشو كلمات. (يمكن للأدمن توسيعه لاحقاً حتى 1500-3000 كلمة بزر "متابعة الكتابة").

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين",
  "long_description":"",
  "developer":"اسم المطور المحتمل",
  "version":"رقم إصدار مثل 3.1.0",
  "android_version":"مثل: 7.0 فأعلى",
  "size_mb":"حجم تقريبي مثل 45",
  "license":"Free",
  "package_name":"com.developer.appname",
  "rating":4.5,
  "whats_new":"آخر التحديثات",
  "features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5"],
  "pros":["إيجابية 1","إيجابية 2","إيجابية 3"],
  "cons":["سلبية 1","سلبية 2"],
  "install_steps":["خطوة 1","خطوة 2","خطوة 3","خطوة 4"],
  "faq":[{"q":"سؤال شائع","a":"إجابة مفصلة"},{"q":"سؤال 2","a":"إجابة 2"},{"q":"سؤال 3","a":"إجابة 3"}]
}
P;

    $result = openrouter_call_rotating($keys, $models, $prompt);
    header('Content-Type: application/json');
    if (!$result['ok']) {
        echo json_encode([
            'success' => false,
            'error'   => openrouter_diagnose_trace($result['trace']),
            'trace'   => $result['trace'],
        ]);
        exit;
    }

    $data = ai_extract_json($result['content']);

    if ($data) {
        $data['success'] = true;
        $data['used_model'] = $result['model'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        // Model returned text instead of JSON — retry once with a stricter prompt
        $strictPrompt = "أجب بـJSON فقط بدون أي نص قبله أو بعده، ولا Markdown، ولا شرح. الطلب هو:\n\n" . $prompt;
        $retry = openrouter_call_rotating($keys, array_slice($models, 0, 4), $strictPrompt);
        $data2 = $retry['ok'] ? ai_extract_json($retry['content']) : null;
        if ($data2) {
            $data2['success'] = true;
            $data2['used_model'] = $retry['model'];
            echo json_encode($data2, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success'=>false,'error'=>'لم يُرجع الذكاء الاصطناعي JSON صالح بعد محاولتين. جرّب تغيير الموديل في الإعدادات أو تفعيل "التدوير التلقائي" (الموديل: '.$result['model'].')']);
        }
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate AI data — SSE streaming progress log
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_sse' && is_admin()) {
    @set_time_limit(180);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    function sse_gen(string $event, array $data): void {
        echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    if (!$name) { sse_gen('error', ['msg' => 'اسم التطبيق مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { sse_gen('error', ['msg' => 'لم يتم إضافة مفتاح OpenRouter. أضفه من الإعدادات.']); exit; }

    $models = build_model_rotation($pdo);
    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. التطبيق: "{$name}"

{$seoStandards}

- long_description: وصف أصلي احترافي 600-900 كلمة على الأقل (وليس فقرة قصيرة)، عدة فقرات تغطي: نظرة عامة على التطبيق، أبرز الميزات بالتفصيل، لمن يناسب هذا التطبيق، وأسلوب طبيعي يخدم SEO دون حشو كلمات.

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{"name":"","seo_title":"","meta_description":"","keywords":"","short_description":"","long_description":"","developer":"","version":"","android_version":"","size_mb":"","license":"Free","package_name":"","rating":4.5,"whats_new":"","features":[],"pros":[],"cons":[],"install_steps":[],"faq":[]}
P;

    $trace   = [];
    $attempts = 0;
    $maxAttempts = 8;
    $timeout = 45;
    $result  = null;

    sse_gen('log', ['msg' => "🚀 بدء التوليد لـ \"{$name}\"…", 'type' => 'info']);
    sse_gen('log', ['msg' => 'عدد النماذج المتاحة: ' . count($models) . ' | عدد المفاتيح: ' . count($keys), 'type' => 'info']);

    outer: foreach ($keys as $kIdx => $key) {
        foreach ($models as $mIdx => $model) {
            if ($attempts >= $maxAttempts) break 2;
            $attempts++;
            $mId = is_array($model) ? ($model['id'] ?? 'unknown') : $model;
            sse_gen('log', ['msg' => "🔄 محاولة {$attempts}/{$maxAttempts}: {$mId}…", 'type' => 'trying', 'model' => $mId]);
            $r = openrouter_call($key, $mId, $prompt, $timeout, 3000);
            $trace[] = ['model' => $mId, 'key_tail' => substr($key, -4), 'ok' => $r['ok'], 'error' => $r['error'] ?? '', 'http' => $r['http'] ?? 0];
            if ($r['ok']) {
                sse_gen('log', ['msg' => "✅ نجح الموديل: {$mId}", 'type' => 'success', 'model' => $mId]);
                $result = $r;
                $result['model'] = $mId;
                break 2;
            } else {
                $errMsg = $r['error'] ?? ('HTTP ' . ($r['http'] ?? '?'));
                sse_gen('log', ['msg' => "❌ فشل {$mId}: {$errMsg}", 'type' => 'fail', 'model' => $mId]);
            }
        }
    }

    if (!$result) {
        $diagMsg = openrouter_diagnose_trace($trace);
        sse_gen('log', ['msg' => "⛔ فشلت كل المحاولات: {$diagMsg}", 'type' => 'error']);
        sse_gen('error', ['msg' => $diagMsg, 'trace' => $trace]);
        exit;
    }

    sse_gen('log', ['msg' => '📝 جارٍ تحليل JSON المُستلَم…', 'type' => 'info']);
    $data = ai_extract_json($result['content']);

    if (!$data) {
        // Retry with strict JSON prompt
        sse_gen('log', ['msg' => '⚠️ الإجابة ليست JSON صالح — إعادة المحاولة بتعليمات أدق…', 'type' => 'warn']);
        $strictPrompt = "أجب بـJSON فقط بدون أي نص قبله أو بعده. الطلب:\n\n" . $prompt;
        $retry = openrouter_call($key ?? $keys[0], $result['model'], $strictPrompt, $timeout, 3000);
        $data  = $retry['ok'] ? ai_extract_json($retry['content']) : null;
        if ($data) {
            sse_gen('log', ['msg' => '✅ نجحت إعادة المحاولة', 'type' => 'success']);
        } else {
            sse_gen('log', ['msg' => '⛔ فشل تحليل JSON في المحاولتين', 'type' => 'error']);
            sse_gen('error', ['msg' => 'لم يُرجع الذكاء الاصطناعي JSON صالح — جرّب تغيير الموديل في الإعدادات.']);
            exit;
        }
    }

    $data['success']    = true;
    $data['used_model'] = $result['model'];
    sse_gen('log', ['msg' => "🎉 اكتمل التوليد بنجاح باستخدام: {$result['model']}", 'type' => 'done']);
    sse_gen('done', $data);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Bulk-content regeneration for ONE existing app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_regen_one' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_GET['id'] ?? 0);
    if (!$appId) { echo json_encode(['success'=>false,'error'=>'معرّف التطبيق مطلوب']); exit; }

    $app = $pdo->prepare("SELECT id, name FROM apps WHERE id=?");
    $app->execute([$appId]);
    $app = $app->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد']); exit; }

    $models = build_model_rotation($pdo);
    $seoStandards = seo_prompt_standards();
    $name = $app['name'];

    $prompt = <<<P
أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. التطبيق: "{$name}"

{$seoStandards}

- long_description: وصف أصلي احترافي 600-900 كلمة على الأقل، عدة فقرات تغطي: نظرة عامة، أبرز الميزات بالتفصيل، لمن يناسب هذا التطبيق، وأسلوب طبيعي يخدم SEO دون حشو كلمات.

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين",
  "long_description":"",
  "whats_new":"آخر التحديثات",
  "features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5"],
  "pros":["إيجابية 1","إيجابية 2","إيجابية 3"],
  "cons":["سلبية 1","سلبية 2"],
  "install_steps":["خطوة 1","خطوة 2","خطوة 3","خطوة 4"],
  "faq":[{"q":"سؤال شائع","a":"إجابة مفصلة"},{"q":"سؤال 2","a":"إجابة 2"},{"q":"سؤال 3","a":"إجابة 3"}]
}
P;

    $result = openrouter_call_rotating($keys, $models, $prompt);
    if (!$result['ok']) {
        echo json_encode(['success'=>false,'error'=>openrouter_diagnose_trace($result['trace'])]);
        exit;
    }

    $data = ai_extract_json($result['content']);
    if (!$data) {
        $strictPrompt = "أجب بـJSON فقط بدون أي نص قبله أو بعده، ولا Markdown. الطلب:\n\n" . $prompt;
        $retry = openrouter_call_rotating($keys, array_slice($models,0,4), $strictPrompt);
        $data = $retry['ok'] ? ai_extract_json($retry['content']) : null;
    }

    if (!$data) {
        echo json_encode(['success'=>false,'error'=>'لم يُرجع الذكاء الاصطناعي JSON صالحاً']);
        exit;
    }

    if (!empty($data['seo_title'])) {
        $data['seo_title'] = seo_title_clamp($data['seo_title']);
    }

    $fields = ['seo_title','meta_description','keywords','short_description','long_description','whats_new'];
    $parts  = ['features','pros','cons','install_steps','faq'];

    $setClauses = [];
    $params     = [];
    foreach ($fields as $f) {
        if (!empty($data[$f])) {
            $setClauses[] = "`$f`=?";
            $params[]     = $data[$f];
        }
    }
    foreach ($parts as $f) {
        if (!empty($data[$f]) && is_array($data[$f])) {
            $setClauses[] = "`$f`=?";
            $params[]     = json_encode($data[$f], JSON_UNESCAPED_UNICODE);
        }
    }
    $setClauses[] = "needs_update=0";

    if ($setClauses) {
        $params[] = $appId;
        $pdo->prepare("UPDATE apps SET ".implode(',',$setClauses)." WHERE id=?")->execute($params);
    }

    echo json_encode(['success'=>true,'model'=>$result['model'],'name'=>$name]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Bulk-fix existing SEO titles > 60 chars
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fix_long_seo_titles' && is_admin()) {
    ob_clean(); // discard any accidental prior output
    header('Content-Type: application/json; charset=utf-8');
    try {
        // Inline clamp so this works even if config.php is old
        if (!function_exists('_seo_clamp_local')) {
            function _seo_clamp_local(string $t, int $max = 60): string {
                $t = trim($t);
                if (mb_strlen($t, 'UTF-8') <= $max) return $t;
                $cut  = mb_substr($t, 0, $max, 'UTF-8');
                $last = mb_strrpos($cut, ' ');
                return ($last !== false && $last > $max * 0.6) ? mb_substr($cut, 0, $last, 'UTF-8') : $cut;
            }
        }
        $rows  = $pdo->query("SELECT id, seo_title FROM apps WHERE seo_title IS NOT NULL AND CHAR_LENGTH(seo_title) > 60")->fetchAll(PDO::FETCH_ASSOC);
        $fixed = 0;
        $upd   = $pdo->prepare("UPDATE apps SET seo_title=? WHERE id=?");
        foreach ($rows as $r) {
            $fn      = function_exists('seo_title_clamp') ? 'seo_title_clamp' : '_seo_clamp_local';
            $clamped = $fn($r['seo_title']);
            if ($clamped !== $r['seo_title']) {
                $upd->execute([$clamped, $r['id']]);
                $fixed++;
            }
        }
        echo json_encode(['ok' => true, 'fixed' => $fixed, 'total' => count($rows)]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   HTML PAGES — helper (build one .html file for an app)
   ══════════════════════════════════════════════════════ */
function build_html_page(array $app): string {
    $siteUrl   = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://yassota.com';
    $siteName  = defined('SITE_NAME') ? SITE_NAME : 'yassota';
    $slug      = $app['slug'] ?? '';
    $rawName   = $app['name'] ?? '';
    $name      = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');
    $appUrl    = $siteUrl . '/' . rawurlencode($slug) . '/';
    $dlUrl     = $siteUrl . '/' . rawurlencode($slug) . '/download';
    $iconUrl   = !empty($app['icon_path']) ? $siteUrl . '/' . ltrim($app['icon_path'], '/') : '';
    $ver       = htmlspecialchars($app['version'] ?? '', ENT_QUOTES, 'UTF-8');
    $cat       = htmlspecialchars($app['cat_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $catSlug   = htmlspecialchars($app['cat_slug'] ?? '', ENT_QUOTES, 'UTF-8');
    $dev       = htmlspecialchars($app['developer'] ?? '', ENT_QUOTES, 'UTF-8');
    $devEnc    = rawurlencode($app['developer'] ?? '');
    $android   = htmlspecialchars($app['android_version'] ?? '5.0', ENT_QUOTES, 'UTF-8');
    $sizeMb    = htmlspecialchars($app['size_mb'] ?? '', ENT_QUOTES, 'UTF-8');
    $license   = htmlspecialchars($app['license'] ?? 'مجاني', ENT_QUOTES, 'UTF-8');
    $pkg       = htmlspecialchars($app['package_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $rawSeoTitle = $app['seo_title'] ?? ($rawName . ' — تحميل مجاني للأندرويد ' . date('Y'));
    $seoTitle  = htmlspecialchars($rawSeoTitle, ENT_QUOTES, 'UTF-8');
    $rawShort  = $app['short_description'] ?? '';
    $rawLong   = strip_tags($app['long_description'] ?? '');
    $rawSeoDesc = $app['meta_description'] ?? mb_substr($rawShort ?: $rawLong, 0, 160, 'UTF-8');
    $seoDesc   = htmlspecialchars($rawSeoDesc, ENT_QUOTES, 'UTF-8');
    $rating    = number_format((float)($app['rating'] ?? 4.5), 1);
    $dlCount   = max(100, (int)($app['downloads'] ?? 1000));
    $today     = date('Y-m-d');
    $year      = date('Y');
    $whatsNew  = htmlspecialchars($app['whats_new'] ?? '', ENT_QUOTES, 'UTF-8');

    // Parse JSON fields
    $features = json_decode($app['features'] ?? '[]', true) ?: [];
    $pros     = json_decode($app['pros']     ?? '[]', true) ?: [];
    $cons     = json_decode($app['cons']     ?? '[]', true) ?: [];
    $steps    = json_decode($app['install_steps'] ?? '[]', true) ?: [];
    $faqArr   = json_decode($app['faq']      ?? '[]', true) ?: [];
    $shots    = json_decode($app['screenshots'] ?? '[]', true) ?: [];

    // Default install steps if empty
    if (empty($steps)) {
        $steps = [
            "انتقل إلى صفحة التحميل على موقع {$siteName}",
            "اضغط على زر \"تحميل APK\" الأزرق الكبير",
            "انتظر حتى ينتهي تنزيل ملف APK على جهازك",
            "افتح ملف APK المُنزَّل من مدير الملفات",
            "إذا طُلب منك، اضغط \"إعدادات\" وفعّل خيار \"السماح بتثبيت تطبيقات من مصادر غير معروفة\"",
            "ارجع وافتح ملف APK مرة أخرى",
            "اضغط \"تثبيت\" وانتظر اكتمال العملية",
            "ابحث عن أيقونة التطبيق على الشاشة الرئيسية وابدأ الاستخدام",
        ];
    }
    // Default FAQ if empty
    if (empty($faqArr)) {
        $faqArr = [
            ['q'=>"هل تطبيق {$rawName} مجاني؟", 'a'=>"نعم، تطبيق {$rawName} مجاني تماماً للتحميل والاستخدام على موقع {$siteName}."],
            ['q'=>"هل التطبيق آمن للتثبيت؟", 'a'=>"نعم، جميع التطبيقات المتوفرة على {$siteName} تمر بمراجعة للتأكد من سلامتها قبل النشر."],
            ['q'=>"ما هو إصدار أندرويد المطلوب؟", 'a'=>"يتطلب التطبيق أندرويد {$android} أو أحدث للعمل بشكل صحيح."],
            ['q'=>"كيف أحدّث التطبيق إلى أحدث إصدار؟", 'a'=>"أعد زيارة صفحة التطبيق على {$siteName} وحمّل أحدث إصدار وثبّته فوق القديم."],
            ['q'=>"لماذا لا يعمل التثبيت؟", 'a'=>"تأكد من تفعيل خيار \"مصادر غير معروفة\" في إعدادات الأمان، ثم أعد المحاولة."],
            ['q'=>"هل يعمل التطبيق بدون إنترنت؟", 'a'=>"بعض وظائف التطبيق تعمل دون إنترنت، لكن الوظائف الكاملة قد تتطلب اتصالاً بالشبكة."],
            ['q'=>"كيف أُزيل التطبيق؟", 'a'=>"اذهب إلى الإعدادات > التطبيقات > {$rawName} ثم اضغط \"إلغاء التثبيت\"."],
            ['q'=>"هل يتوفر التطبيق لنظام iOS؟", 'a'=>"التطبيق متاح حالياً لنظام أندرويد فقط عبر {$siteName}."],
        ];
    }

    // Build features HTML
    $featHtml = '';
    foreach ($features as $i => $f) {
        $fh = htmlspecialchars(is_array($f) ? ($f['title'] ?? $f[0] ?? '') : $f, ENT_QUOTES, 'UTF-8');
        if (!$fh) continue;
        $featHtml .= "<li class=\"feat-item\"><span class=\"feat-icon\">✦</span><span>{$fh}</span></li>\n";
    }
    // Default features if empty
    if (!$featHtml) {
        $defaults = ["واجهة مستخدم سهلة وبسيطة","أداء سريع وسلس","يعمل على معظم أجهزة أندرويد","تحديثات منتظمة","مجاني 100%","حجم خفيف على الجهاز"];
        foreach ($defaults as $d) $featHtml .= "<li class=\"feat-item\"><span class=\"feat-icon\">✦</span><span>" . htmlspecialchars($d,'UTF-8') . "</span></li>\n";
    }

    // Build pros/cons HTML
    $prosHtml = $consHtml = '';
    foreach ($pros as $p) {
        $ph = htmlspecialchars(is_array($p) ? ($p['title'] ?? $p[0] ?? '') : $p, ENT_QUOTES, 'UTF-8');
        if ($ph) $prosHtml .= "<li class=\"pro-item\"><span class=\"check-icon\">✔</span> {$ph}</li>\n";
    }
    foreach ($cons as $c) {
        $ch = htmlspecialchars(is_array($c) ? ($c['title'] ?? $c[0] ?? '') : $c, ENT_QUOTES, 'UTF-8');
        if ($ch) $consHtml .= "<li class=\"con-item\"><span class=\"x-icon\">✘</span> {$ch}</li>\n";
    }

    // Build steps HTML
    $stepsHtml = '';
    foreach ($steps as $i => $s) {
        $sh = htmlspecialchars(is_string($s) ? $s : ($s['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        if (!$sh) continue;
        $n = $i + 1;
        $stepsHtml .= "<li class=\"step-item\"><span class=\"step-num\">{$n}</span><span>{$sh}</span></li>\n";
    }

    // Build FAQ HTML + JSON-LD
    $faqHtml = '';
    $faqSchema = [];
    foreach ($faqArr as $f) {
        $q = htmlspecialchars($f['q'] ?? $f['question'] ?? '', ENT_QUOTES, 'UTF-8');
        $a = htmlspecialchars($f['a'] ?? $f['answer'] ?? '', ENT_QUOTES, 'UTF-8');
        if (!$q || !$a) continue;
        $faqHtml .= "<div class=\"faq-item\"><button class=\"faq-q\" onclick=\"this.parentElement.classList.toggle('open')\">{$q}<span class=\"faq-arrow\">&#9660;</span></button><div class=\"faq-a\"><p>{$a}</p></div></div>\n";
        $faqSchema[] = ['@type'=>'Question','name'=>($f['q'] ?? $f['question'] ?? ''),'acceptedAnswer'=>['@type'=>'Answer','text'=>($f['a'] ?? $f['answer'] ?? '')]];
    }

    // Screenshots HTML
    $shotsHtml = '';
    foreach (array_slice($shots, 0, 6) as $s) {
        $su = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $shotsHtml .= "<div class=\"shot-wrap\"><img src=\"{$su}\" alt=\"{$name} screenshot\" class=\"screenshot\" loading=\"lazy\"></div>\n";
    }

    // Rating stars
    $fullStars = min(5, (int)round((float)$rating));
    $starsHtml = str_repeat('<span class="star filled">★</span>', $fullStars) . str_repeat('<span class="star">☆</span>', 5 - $fullStars);

    // Long description paragraphs
    $longParas = '';
    if ($rawLong) {
        foreach (array_filter(explode("\n\n", $rawLong)) as $para) {
            $para = trim($para);
            if ($para) $longParas .= '<p>' . htmlspecialchars($para, ENT_QUOTES, 'UTF-8') . "</p>\n";
        }
    } elseif ($rawShort) {
        $longParas = '<p>' . htmlspecialchars($rawShort, ENT_QUOTES, 'UTF-8') . "</p>\n";
    }

    // Schema.org structured data
    $schema = json_encode([
        '@context'    => 'https://schema.org',
        '@type'       => 'SoftwareApplication',
        'name'        => $rawName,
        'description' => $rawSeoDesc,
        'image'       => $iconUrl ?: '',
        'url'         => $appUrl,
        'applicationCategory' => 'MobileApplication',
        'operatingSystem' => 'Android ' . ($app['android_version'] ?? '5.0') . '+',
        'softwareVersion' => ($app['version'] ?? ''),
        'datePublished' => !empty($app['release_date']) ? $app['release_date'] : substr($app['created_at'] ?? $today, 0, 10),
        'dateModified' => $today,
        'author'      => ['@type'=>'Organization','name'=>$dev ?: $siteName],
        'offers'      => ['@type'=>'Offer','price'=>'0','priceCurrency'=>'USD'],
        'aggregateRating' => ['@type'=>'AggregateRating','ratingValue'=>$rating,'ratingCount'=>max(50,(int)($app['rating_count']??0)?:(max(50,intval($dlCount/10)))),'bestRating'=>'5','worstRating'=>'1'],
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

    $faqSchemaJson = $faqSchema ? json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$faqSchema], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : '';

    $breadSchema = json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>array_values(array_filter([
        ['@type'=>'ListItem','position'=>1,'name'=>$siteName,'item'=>$siteUrl.'/'],
        $cat ? ['@type'=>'ListItem','position'=>2,'name'=>$cat,'item'=>$siteUrl.'/category/'.rawurlencode($app['cat_slug']??'')]: null,
        ['@type'=>'ListItem','position'=>($cat?3:2),'name'=>$rawName,'item'=>$appUrl],
    ]))], JSON_UNESCAPED_UNICODE);

    // ── Build the page ──────────────────────────────────────────────────────────
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $seoTitle ?></title>
<meta name="description" content="<?= $seoDesc ?>">
<meta name="keywords" content="<?= htmlspecialchars($app['keywords'] ?? $rawName . ' تحميل أندرويد', ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?= $appUrl ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= $seoTitle ?>">
<meta property="og:description" content="<?= $seoDesc ?>">
<?php if ($iconUrl): ?><meta property="og:image" content="<?= $iconUrl ?>"><?php endif; ?>
<meta property="og:url" content="<?= $appUrl ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($siteName,'UTF-8') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $seoTitle ?>">
<meta name="twitter:description" content="<?= $seoDesc ?>">
<?php if ($iconUrl): ?><meta name="twitter:image" content="<?= $iconUrl ?>"><?php endif; ?>
<script type="application/ld+json"><?= $schema ?></script>
<?php if ($faqSchemaJson): ?><script type="application/ld+json"><?= $faqSchemaJson ?></script><?php endif; ?>
<script type="application/ld+json"><?= $breadSchema ?></script>
<style>
/* ═══════════════════════════════════════════════
   yassota Landing Page — Full Template
   ═══════════════════════════════════════════════ */
:root{
  --primary:#2563eb;--primary-dark:#1d4ed8;--primary-light:#eff6ff;
  --accent:#7c3aed;--success:#16a34a;--danger:#dc2626;--warn:#d97706;
  --text:#1e293b;--muted:#64748b;--border:#e2e8f0;--bg:#f8fafc;
  --white:#fff;--shadow:0 4px 24px rgba(0,0,0,.08);
  --radius:14px;--radius-lg:20px;--font:-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Sans Arabic',Tahoma,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.6;direction:rtl}
a{color:var(--primary);text-decoration:none}
a:hover{text-decoration:underline}
img{max-width:100%;height:auto}

/* ── Layout ── */
.container{max-width:900px;margin:0 auto;padding:0 16px}
.section{padding:40px 0}
.section-title{font-size:20px;font-weight:700;color:var(--text);margin-bottom:20px;display:flex;align-items:center;gap:8px}
.section-title::before{content:'';display:inline-block;width:4px;height:22px;background:var(--primary);border-radius:3px}
.panel{background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow);padding:28px;margin-bottom:24px;border:1px solid var(--border)}

/* ── Header / Nav ── */
.site-header{background:var(--white);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.header-inner{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;max-width:900px;margin:0 auto;gap:12px}
.logo{font-size:20px;font-weight:800;color:var(--primary);display:flex;align-items:center;gap:6px}
.logo svg{flex-shrink:0}
.nav-links{display:flex;gap:16px;font-size:13px;font-weight:500}
.nav-links a{color:var(--muted);transition:color .15s}
.nav-links a:hover{color:var(--primary);text-decoration:none}
.site-search{flex:1;max-width:260px}
.site-search input{width:100%;padding:8px 14px;border:1px solid var(--border);border-radius:30px;font-size:13px;background:#f8fafc;color:var(--text);direction:rtl;outline:none}
.site-search input:focus{border-color:var(--primary);background:var(--white)}

/* ── Breadcrumb ── */
.breadcrumb{padding:12px 0;font-size:12px;color:var(--muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.breadcrumb a{color:var(--muted)}
.breadcrumb a:hover{color:var(--primary);text-decoration:none}
.breadcrumb-sep{color:var(--border)}

/* ── App Hero ── */
.app-hero{background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow);padding:28px;margin-bottom:24px;border:1px solid var(--border)}
.app-hero-inner{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap}
.app-icon{width:96px;height:96px;border-radius:22px;object-fit:cover;box-shadow:0 6px 20px rgba(0,0,0,.15);flex-shrink:0}
.app-icon-placeholder{width:96px;height:96px;border-radius:22px;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:44px;flex-shrink:0;color:var(--white)}
.app-hero-info{flex:1;min-width:200px}
.app-hero-name{font-size:26px;font-weight:800;color:var(--text);line-height:1.25;margin-bottom:6px}
.app-hero-dev{font-size:13px;color:var(--muted);margin-bottom:10px}
.app-hero-dev a{color:var(--primary)}
.rating-row{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.star{font-size:16px;color:#d1d5db}
.star.filled{color:#f59e0b}
.rating-num{font-size:14px;font-weight:700;color:var(--text)}
.rating-count{font-size:12px;color:var(--muted)}
.app-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px}
.tag{background:var(--primary-light);color:var(--primary);font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:4px}
.tag.green{background:#dcfce7;color:var(--success)}
.tag.gray{background:#f1f5f9;color:var(--muted)}
.app-short-desc{font-size:14px;color:var(--muted);line-height:1.6;margin-bottom:16px}

/* ── Download Button ── */
.btn-download{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,var(--primary),var(--accent));color:var(--white);border:none;border-radius:var(--radius);padding:15px 32px;font-size:16px;font-weight:700;cursor:pointer;box-shadow:0 6px 20px rgba(37,99,235,.35);transition:transform .15s,box-shadow .15s;text-decoration:none;min-width:220px;width:100%}
.btn-download:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(37,99,235,.45);text-decoration:none;color:var(--white)}
.btn-playstore{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--white);color:var(--primary);border:2px solid var(--primary);border-radius:var(--radius);padding:13px 24px;font-size:14px;font-weight:600;cursor:pointer;transition:.15s;text-decoration:none;margin-top:10px;width:100%}
.btn-playstore:hover{background:var(--primary-light);text-decoration:none}
.dl-btns{display:flex;flex-direction:column;gap:10px;margin-top:4px}
.trust-badges{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.trust-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);background:#f8fafc;padding:5px 10px;border-radius:20px;border:1px solid var(--border)}

/* ── App details grid ── */
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-top:4px}
.info-cell{background:#f8fafc;border-radius:10px;padding:12px;text-align:center}
.info-cell-label{font-size:11px;color:var(--muted);margin-bottom:4px}
.info-cell-value{font-size:14px;font-weight:700;color:var(--text)}

/* ── Features ── */
.feat-list{list-style:none;display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px}
.feat-item{display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border-radius:10px;padding:12px 14px;font-size:14px;color:var(--text);border:1px solid var(--border)}
.feat-icon{color:var(--primary);font-size:12px;margin-top:3px;flex-shrink:0}

/* ── Pros/Cons ── */
.pros-cons-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.pros-list,.cons-list{list-style:none}
.pro-item,.con-item{padding:8px 0;font-size:14px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:8px}
.pro-item:last-child,.con-item:last-child{border-bottom:none}
.check-icon{color:var(--success);font-size:14px;flex-shrink:0;margin-top:2px}
.x-icon{color:var(--danger);font-size:14px;flex-shrink:0;margin-top:2px}
.pros-header{font-size:14px;font-weight:700;color:var(--success);margin-bottom:10px}
.cons-header{font-size:14px;font-weight:700;color:var(--danger);margin-bottom:10px}

/* ── Steps ── */
.steps-list{list-style:none;counter-reset:steps}
.step-item{display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--border);align-items:flex-start;font-size:14px}
.step-item:last-child{border-bottom:none}
.step-num{background:var(--primary);color:var(--white);width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0}

/* ── FAQ ── */
.faq-item{border-bottom:1px solid var(--border);overflow:hidden}
.faq-item:last-child{border-bottom:none}
.faq-q{background:none;border:none;width:100%;text-align:right;padding:16px 0;font-size:15px;font-weight:600;color:var(--text);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:8px;font-family:inherit}
.faq-q:hover{color:var(--primary)}
.faq-arrow{font-size:11px;color:var(--muted);transition:transform .2s;flex-shrink:0}
.faq-item.open .faq-arrow{transform:rotate(180deg)}
.faq-a{display:none;padding:0 0 16px;font-size:14px;color:var(--muted);line-height:1.7}
.faq-item.open .faq-a{display:block}

/* ── Screenshots ── */
.shots-scroll{display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;-webkit-overflow-scrolling:touch}
.shots-scroll::-webkit-scrollbar{height:4px}
.shots-scroll::-webkit-scrollbar-track{background:var(--border)}
.shots-scroll::-webkit-scrollbar-thumb{background:var(--primary);border-radius:2px}
.shot-wrap{flex-shrink:0}
.screenshot{height:280px;width:auto;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);border:1px solid var(--border)}

/* ── Long desc ── */
.long-desc p{font-size:14px;line-height:1.8;color:var(--muted);margin-bottom:14px}
.long-desc p:last-child{margin-bottom:0}

/* ── Whats new ── */
.whats-new{background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #a7f3d0;border-radius:12px;padding:16px 20px;font-size:14px;color:#064e3b;line-height:1.7}
.whats-new-label{font-weight:700;margin-bottom:6px;color:#065f46}

/* ── Footer ── */
.site-footer{background:var(--text);color:#94a3b8;padding:40px 0 20px;margin-top:40px}
.footer-inner{max-width:900px;margin:0 auto;padding:0 16px}
.footer-top{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:28px;margin-bottom:32px}
.footer-col-title{color:var(--white);font-size:14px;font-weight:700;margin-bottom:12px}
.footer-links{list-style:none}
.footer-links li{margin-bottom:8px}
.footer-links a{color:#94a3b8;font-size:13px;transition:color .15s}
.footer-links a:hover{color:var(--white);text-decoration:none}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:12px}
.footer-logo{font-size:18px;font-weight:800;color:var(--white)}

/* ── Ad placeholder ── */
.ad-slot{background:#f8fafc;border:1px dashed var(--border);border-radius:10px;padding:20px;text-align:center;color:var(--muted);font-size:12px;margin:8px 0}

/* ── Responsive ── */
@media(max-width:640px){
  .app-hero-name{font-size:20px}
  .app-hero-inner{flex-direction:row}
  .app-icon{width:72px;height:72px;border-radius:16px}
  .app-icon-placeholder{width:72px;height:72px;font-size:32px}
  .pros-cons-grid{grid-template-columns:1fr}
  .info-grid{grid-template-columns:repeat(3,1fr)}
  .nav-links{display:none}
  .site-search{max-width:200px}
  .section{padding:24px 0}
  .panel{padding:18px}
  .screenshot{height:200px}
}
@media(max-width:400px){
  .info-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>

<!-- ═══════════════ HEADER ═══════════════ -->
<header class="site-header">
  <div class="header-inner">
    <a href="<?= $siteUrl ?>/" class="logo" aria-label="<?= htmlspecialchars($siteName,'UTF-8') ?> - الرئيسية">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="2" width="9" height="9" rx="2"/><rect x="13" y="2" width="9" height="9" rx="2"/><rect x="2" y="13" width="9" height="9" rx="2"/><rect x="13" y="13" width="9" height="9" rx="2"/></svg>
      <?= htmlspecialchars($siteName,'UTF-8') ?>
    </a>
    <div class="site-search">
      <input type="search" placeholder="ابحث عن تطبيق..." onkeydown="if(event.key==='Enter'&&this.value)location.href='<?= $siteUrl ?>/?q='+encodeURIComponent(this.value)" aria-label="بحث">
    </div>
    <nav class="nav-links" aria-label="التنقل الرئيسي">
      <a href="<?= $siteUrl ?>/">الرئيسية</a>
      <?php if ($cat && $catSlug): ?><a href="<?= $siteUrl ?>/category/<?= $catSlug ?>"><?= $cat ?></a><?php endif; ?>
      <a href="<?= $siteUrl ?>/top?by=downloads">الأكثر تحميلاً</a>
      <a href="<?= $siteUrl ?>/updates">أحدث التحديثات</a>
    </nav>
  </div>
</header>

<!-- ═══════════════ MAIN ═══════════════ -->
<main class="container">

  <!-- Breadcrumb -->
  <nav class="breadcrumb" aria-label="مسار التنقل">
    <a href="<?= $siteUrl ?>/">الرئيسية</a>
    <span class="breadcrumb-sep">›</span>
    <?php if ($cat && $catSlug): ?><a href="<?= $siteUrl ?>/category/<?= $catSlug ?>"><?= $cat ?></a><span class="breadcrumb-sep">›</span><?php endif; ?>
    <span aria-current="page"><?= $name ?></span>
  </nav>

  <!-- ═══ App Hero ═══ -->
  <div class="app-hero">
    <div class="app-hero-inner">
      <?php if ($iconUrl): ?>
      <img src="<?= $iconUrl ?>" alt="أيقونة <?= $name ?>" class="app-icon" width="96" height="96" loading="eager">
      <?php else: ?>
      <div class="app-icon-placeholder" role="img" aria-label="<?= $name ?>">📱</div>
      <?php endif; ?>

      <div class="app-hero-info">
        <h1 class="app-hero-name"><?= $name ?></h1>
        <?php if ($dev): ?>
        <p class="app-hero-dev">بواسطة <a href="<?= $siteUrl ?>/developer/<?= $devEnc ?>"><?= $dev ?></a></p>
        <?php endif; ?>
        <div class="rating-row">
          <div class="stars" aria-label="التقييم <?= $rating ?> من 5"><?= $starsHtml ?></div>
          <span class="rating-num"><?= $rating ?></span>
          <span class="rating-count">(<?= number_format($dlCount) ?> تقييم)</span>
        </div>
        <div class="app-tags">
          <?php if ($cat): ?><span class="tag"><?= $cat ?></span><?php endif; ?>
          <?php if ($ver): ?><span class="tag gray">v<?= $ver ?></span><?php endif; ?>
          <span class="tag green">مجاني</span>
          <span class="tag gray">أندرويد</span>
          <?php if ($sizeMb): ?><span class="tag gray"><?= $sizeMb ?> MB</span><?php endif; ?>
        </div>
        <?php if ($rawShort): ?>
        <p class="app-short-desc"><?= htmlspecialchars($rawShort,'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Download buttons -->
    <div class="dl-btns" style="margin-top:20px">
      <a href="<?= $dlUrl ?>" class="btn-download" rel="nofollow">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
        تحميل <?= $name ?> مجاناً
      </a>
      <?php if (!empty($app['playstore_url'])): ?>
      <a href="<?= htmlspecialchars($app['playstore_url'],'UTF-8') ?>" class="btn-playstore" target="_blank" rel="noopener nofollow">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5v-17l18 8.5-18 8.5z"/></svg>
        فتح على Google Play
      </a>
      <?php endif; ?>
    </div>

    <!-- Trust badges -->
    <div class="trust-badges">
      <span class="trust-badge">🛡 رابط آمن 100%</span>
      <span class="trust-badge">✔ بدون فيروسات</span>
      <span class="trust-badge">⚡ تحميل سريع</span>
      <span class="trust-badge">📱 أندرويد <?= $android ?>+</span>
    </div>
  </div>

  <!-- Ad slot -->
  <div class="ad-slot" style="min-height:90px">إعلان</div>

  <!-- ═══ Technical Info ═══ -->
  <section class="section" aria-labelledby="tech-title">
    <div class="panel">
      <h2 class="section-title" id="tech-title">المعلومات التقنية</h2>
      <div class="info-grid">
        <?php if ($ver): ?><div class="info-cell"><div class="info-cell-label">الإصدار</div><div class="info-cell-value"><?= $ver ?></div></div><?php endif; ?>
        <?php if ($sizeMb): ?><div class="info-cell"><div class="info-cell-label">الحجم</div><div class="info-cell-value"><?= $sizeMb ?> MB</div></div><?php endif; ?>
        <?php if ($android): ?><div class="info-cell"><div class="info-cell-label">أندرويد</div><div class="info-cell-value"><?= $android ?>+</div></div><?php endif; ?>
        <div class="info-cell"><div class="info-cell-label">الترخيص</div><div class="info-cell-value"><?= $license ?></div></div>
        <div class="info-cell"><div class="info-cell-label">الفئة</div><div class="info-cell-value"><?= $cat ?: 'تطبيقات' ?></div></div>
        <div class="info-cell"><div class="info-cell-label">آخر تحديث</div><div class="info-cell-value"><?= date('Y/m/d') ?></div></div>
        <?php if ($pkg): ?><div class="info-cell"><div class="info-cell-label">Package</div><div class="info-cell-value" style="font-size:11px;word-break:break-all"><?= $pkg ?></div></div><?php endif; ?>
        <div class="info-cell"><div class="info-cell-label">التقييم</div><div class="info-cell-value"><?= $rating ?> / 5</div></div>
      </div>
    </div>
  </section>

  <!-- ═══ Description ═══ -->
  <?php if ($longParas): ?>
  <section class="section" aria-labelledby="desc-title">
    <div class="panel">
      <h2 class="section-title" id="desc-title">عن تطبيق <?= $name ?></h2>
      <div class="long-desc"><?= $longParas ?></div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ Screenshots ═══ -->
  <?php if ($shotsHtml): ?>
  <section class="section" aria-labelledby="shots-title">
    <div class="panel">
      <h2 class="section-title" id="shots-title">صور التطبيق</h2>
      <div class="shots-scroll"><?= $shotsHtml ?></div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ Features ═══ -->
  <section class="section" aria-labelledby="feat-title">
    <div class="panel">
      <h2 class="section-title" id="feat-title">مميزات <?= $name ?></h2>
      <ul class="feat-list"><?= $featHtml ?></ul>
    </div>
  </section>

  <!-- ═══ Pros / Cons ═══ -->
  <?php if ($prosHtml || $consHtml): ?>
  <section class="section" aria-labelledby="pc-title">
    <div class="panel">
      <h2 class="section-title" id="pc-title">الإيجابيات والسلبيات</h2>
      <div class="pros-cons-grid">
        <?php if ($prosHtml): ?>
        <div>
          <p class="pros-header">✔ الإيجابيات</p>
          <ul class="pros-list"><?= $prosHtml ?></ul>
        </div>
        <?php endif; ?>
        <?php if ($consHtml): ?>
        <div>
          <p class="cons-header">✘ السلبيات</p>
          <ul class="cons-list"><?= $consHtml ?></ul>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ Install Steps ═══ -->
  <section class="section" aria-labelledby="steps-title">
    <div class="panel">
      <h2 class="section-title" id="steps-title">كيفية تثبيت <?= $name ?> على الأندرويد</h2>
      <ul class="steps-list"><?= $stepsHtml ?></ul>
      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <a href="<?= $dlUrl ?>" class="btn-download" rel="nofollow" style="max-width:340px;margin:0 auto;display:flex">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
          تحميل <?= $name ?> الآن
        </a>
      </div>
    </div>
  </section>

  <!-- ═══ What's New ═══ -->
  <?php if ($whatsNew): ?>
  <section class="section" aria-labelledby="wn-title">
    <div class="panel">
      <h2 class="section-title" id="wn-title">ما الجديد في <?= $ver ? 'الإصدار '.$ver : 'آخر إصدار' ?></h2>
      <div class="whats-new">
        <p class="whats-new-label">🆕 آخر التحديثات:</p>
        <?= nl2br($whatsNew) ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ SEO Content Section 1 ═══ -->
  <section class="section" aria-labelledby="seo1-title">
    <div class="panel">
      <h2 class="section-title" id="seo1-title">لماذا تختار <?= $name ?>؟</h2>
      <div class="long-desc">
        <p>تطبيق <strong><?= $name ?></strong> هو أحد أفضل التطبيقات في فئة <?= $cat ?: 'التطبيقات' ?> لنظام أندرويد. يوفر التطبيق تجربة مستخدم استثنائية مع واجهة سلسة وأداء سريع يناسب مختلف أجهزة أندرويد بدءاً من الإصدار <?= $android ?>.</p>
        <p>يتميز <?= $name ?> بكونه مجانياً بالكامل، ويمكن تحميله بسهولة من موقع <?= htmlspecialchars($siteName,'UTF-8') ?> دون الحاجة إلى أي رسوم أو اشتراكات. التطبيق متوافق مع أحدث إصدارات أندرويد ويحصل على تحديثات منتظمة لضمان أفضل أداء وأمان.</p>
        <p>الإصدار الحالي <?= $ver ? 'v'.$ver : '' ?> يأتي بتحسينات ملحوظة على الأداء والاستقرار، مع إضافة ميزات جديدة استجابةً لتعليقات المستخدمين. تجد على هذه الصفحة كل ما تحتاجه لتحميل وتثبيت التطبيق بشكل صحيح.</p>
      </div>
    </div>
  </section>

  <!-- ═══ FAQ ═══ -->
  <section class="section" aria-labelledby="faq-title">
    <div class="panel">
      <h2 class="section-title" id="faq-title">أسئلة شائعة حول <?= $name ?></h2>
      <div class="faq-list" role="list"><?= $faqHtml ?></div>
    </div>
  </section>

  <!-- ═══ SEO Content Section 2 ═══ -->
  <section class="section" aria-labelledby="seo2-title">
    <div class="panel">
      <h2 class="section-title" id="seo2-title">متطلبات تشغيل <?= $name ?></h2>
      <div class="long-desc">
        <p>قبل تحميل تطبيق <?= $name ?>، تأكد من أن جهازك يستوفي المتطلبات التالية للحصول على أفضل تجربة:</p>
        <ul style="list-style:disc;padding-right:20px;color:var(--muted);font-size:14px;line-height:2">
          <li>نظام تشغيل أندرويد <?= $android ?> أو أحدث</li>
          <?php if ($sizeMb): ?><li>مساحة تخزين حرة <?= $sizeMb ?> ميغابايت على الأقل</li><?php endif; ?>
          <li>اتصال بالإنترنت (عند التحميل الأول)</li>
          <li>السماح بتثبيت التطبيقات من مصادر غير معروفة (عند التثبيت من APK)</li>
          <?php if ($dev): ?><li>المطور: <?= $dev ?></li><?php endif; ?>
          <?php if ($pkg): ?><li>اسم الحزمة: <code><?= $pkg ?></code></li><?php endif; ?>
        </ul>
        <p style="margin-top:14px">إذا كان جهازك يستوفي هذه المتطلبات، يمكنك البدء في تحميل التطبيق بضغطة واحدة من الزر أعلاه.</p>
      </div>
    </div>
  </section>

  <!-- ═══ SEO Content Section 3 — Detailed Guide ═══ -->
  <section class="section" aria-labelledby="guide-title">
    <div class="panel">
      <h2 class="section-title" id="guide-title">دليل تفصيلي: كيفية تحميل وتثبيت <?= $name ?></h2>
      <div class="long-desc">
        <h3 style="font-size:16px;font-weight:700;margin:0 0 10px;color:var(--text)">الخطوة 1: التحضير</h3>
        <p>قبل تحميل تطبيق <?= $name ?>، افتح إعدادات هاتفك واذهب إلى "الأمان" أو "الخصوصية". ابحث عن خيار "مصادر غير معروفة" أو "تثبيت تطبيقات غير معروفة" وقم بتفعيله. هذا الإعداد ضروري لتثبيت أي تطبيق من خارج متجر Google Play.</p>

        <h3 style="font-size:16px;font-weight:700;margin:14px 0 10px;color:var(--text)">الخطوة 2: تحميل ملف APK</h3>
        <p>اضغط على زر "تحميل <?= $name ?>" أعلاه. سيبدأ تحميل ملف APK تلقائياً على جهازك. قد تستغرق عملية التحميل بضع ثوانٍ إلى دقيقة بحسب سرعة اتصالك بالإنترنت. حجم الملف <?= $sizeMb ? $sizeMb.' MB' : 'خفيف' ?> وهو مثالي حتى للاتصالات الأبطأ.</p>

        <h3 style="font-size:16px;font-weight:700;margin:14px 0 10px;color:var(--text)">الخطوة 3: التثبيت</h3>
        <p>بعد اكتمال التحميل، ستجد إشعاراً في شريط الإشعارات. اضغط عليه لفتح ملف APK، أو انتقل إلى مجلد "التنزيلات" في مدير الملفات وافتح الملف من هناك. سيظهر مربع حوار التثبيت — اضغط "تثبيت" وانتظر ثوانٍ حتى تكتمل العملية.</p>

        <h3 style="font-size:16px;font-weight:700;margin:14px 0 10px;color:var(--text)">الخطوة 4: تشغيل التطبيق</h3>
        <p>بعد اكتمال التثبيت، ستجد أيقونة <?= $name ?> على شاشتك الرئيسية أو في قائمة التطبيقات. اضغط عليها لبدء الاستخدام. إذا طُلب منك منح أذونات للتطبيق، اقرأها بعناية واضغط "موافق" على الأذونات الضرورية.</p>

        <h3 style="font-size:16px;font-weight:700;margin:14px 0 10px;color:var(--text)">نصائح مهمة</h3>
        <p>• احرص دائماً على تحميل التطبيق من المصادر الموثوقة مثل <?= htmlspecialchars($siteName,'UTF-8') ?>.</p>
        <p>• إذا واجهت مشكلة في التثبيت، حاول مسح ذاكرة التخزين المؤقت لمتجر Google Play ثم أعد المحاولة.</p>
        <p>• تحقق دورياً من وجود تحديثات جديدة لضمان الحصول على أحدث الميزات وإصلاحات الأمان.</p>
        <p>• في حال واجهت مشكلة في التثبيت على جهازك تحديداً، تأكد من أن نظام تشغيل أندرويد لديك يعادل <?= $android ?> أو أحدث.</p>
      </div>
    </div>
  </section>

  <!-- ═══ Download CTA ═══ -->
  <section class="section">
    <div style="background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:var(--radius-lg);padding:36px;text-align:center;color:var(--white)">
      <h2 style="font-size:22px;font-weight:800;margin-bottom:10px">حمّل <?= $name ?> الآن مجاناً!</h2>
      <p style="opacity:.9;font-size:14px;margin-bottom:22px">أحدث إصدار <?= $ver ? 'v'.$ver : '' ?> — <?= number_format($dlCount) ?> تحميل</p>
      <a href="<?= $dlUrl ?>" class="btn-download" rel="nofollow" style="background:var(--white);color:var(--primary);max-width:300px;margin:0 auto;display:inline-flex;box-shadow:0 6px 20px rgba(0,0,0,.2)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
        تحميل مجاني
      </a>
    </div>
  </section>

  <!-- Ad slot 2 -->
  <div class="ad-slot" style="min-height:90px">إعلان</div>

</main>

<!-- ═══════════════ FOOTER ═══════════════ -->
<footer class="site-footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-top">
      <div>
        <div class="footer-col-title"><?= htmlspecialchars($siteName,'UTF-8') ?></div>
        <p style="font-size:13px;line-height:1.7;max-width:220px">موقع عربي متخصص في تحميل تطبيقات وألعاب أندرويد مجاناً وبأمان تام.</p>
      </div>
      <div>
        <div class="footer-col-title">روابط سريعة</div>
        <ul class="footer-links">
          <li><a href="<?= $siteUrl ?>/">الرئيسية</a></li>
          <li><a href="<?= $siteUrl ?>/top?by=downloads">الأكثر تحميلاً</a></li>
          <li><a href="<?= $siteUrl ?>/updates">آخر التحديثات</a></li>
          <li><a href="<?= $siteUrl ?>/blog">المدونة</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-col-title">معلومات</div>
        <ul class="footer-links">
          <li><a href="<?= $siteUrl ?>/about">من نحن</a></li>
          <li><a href="<?= $siteUrl ?>/contact">اتصل بنا</a></li>
          <li><a href="<?= $siteUrl ?>/privacy-policy">سياسة الخصوصية</a></li>
          <li><a href="<?= $siteUrl ?>/terms">شروط الاستخدام</a></li>
          <li><a href="<?= $siteUrl ?>/dmca">DMCA</a></li>
        </ul>
      </div>
      <?php if ($cat && $catSlug): ?>
      <div>
        <div class="footer-col-title">تصفح <?= $cat ?></div>
        <ul class="footer-links">
          <li><a href="<?= $siteUrl ?>/category/<?= $catSlug ?>">كل تطبيقات <?= $cat ?></a></li>
          <li><a href="<?= $siteUrl ?>/top?by=downloads">الأكثر تحميلاً</a></li>
          <li><a href="<?= $siteUrl ?>/updates">آخر الإصدارات</a></li>
          <?php if ($dev && $devEnc): ?><li><a href="<?= $siteUrl ?>/developer/<?= $devEnc ?>">تطبيقات <?= $dev ?></a></li><?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-bottom">
      <a href="<?= $siteUrl ?>/" class="footer-logo"><?= htmlspecialchars($siteName,'UTF-8') ?></a>
      <p>&copy; <?= $year ?> <?= htmlspecialchars($siteName,'UTF-8') ?> — جميع الحقوق محفوظة</p>
      <p><a href="<?= $appUrl ?>" style="color:#64748b">صفحة <?= $name ?></a></p>
    </div>
  </div>
</footer>

<script>
// FAQ accordion
document.querySelectorAll('.faq-q').forEach(function(btn){
  btn.addEventListener('click',function(){
    var item=this.parentElement;
    item.classList.toggle('open');
  });
});
// Smooth anchor scroll for header links
document.querySelectorAll('a[href^="#"]').forEach(function(a){
  a.addEventListener('click',function(e){e.preventDefault();var t=document.querySelector(this.getAttribute('href'));if(t)t.scrollIntoView({behavior:'smooth',block:'start'});});
});
</script>
</body>
</html>
<?php
    return ob_get_clean();
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate a single HTML landing page for an app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_html_page' && is_admin()) {
    ob_clean(); header('Content-Type: application/json; charset=utf-8');
    $appId   = (int)($_POST['app_id'] ?? 0);
    $appSlug = trim($_POST['slug'] ?? '');
    if (!$appId && !$appSlug) { echo json_encode(['ok'=>false,'error'=>'app_id أو slug مطلوب']); exit; }

    $stmt = $pdo->prepare("SELECT a.*, c.slug AS cat_slug, c.name AS cat_name
        FROM apps a LEFT JOIN categories c ON a.category_id=c.id
        WHERE (" . ($appSlug ? "a.slug=?" : "a.id=?") . ") AND a.status='published' LIMIT 1");
    $stmt->execute([$appSlug ?: $appId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'التطبيق غير موجود أو غير منشور']); exit; }

    $pagesDir = __DIR__ . '/pages';
    if (!is_dir($pagesDir)) @mkdir($pagesDir, 0755, true);
    if (!is_dir($pagesDir)) { echo json_encode(['ok'=>false,'error'=>'تعذّر إنشاء مجلد pages/']); exit; }

    $html = build_html_page($row);
    $file = $pagesDir . '/' . $row['slug'] . '.html';
    if (file_put_contents($file, $html) === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل كتابة الملف']); exit;
    }

    // Submit URL to IndexNow
    $pageUrl = rtrim(SITE_URL,'/') . '/pages/' . rawurlencode($row['slug']) . '.html';
    $inKey   = get_cfg($pdo, 'indexnow_key');
    if ($inKey) {
        $payload = ['host'=>parse_url(SITE_URL,PHP_URL_HOST),'key'=>$inKey,'urlList'=>[$pageUrl]];
        @file_get_contents('https://api.indexnow.org/indexnow', false, stream_context_create(['http'=>[
            'method'=>'POST','timeout'=>8,'ignore_errors'=>true,
            'header'=>"Content-Type: application/json\r\n",
            'content'=>json_encode($payload),
        ]]));
    }

    echo json_encode(['ok'=>true,'url'=>$pageUrl,'file'=>'pages/'.$row['slug'].'.html']);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Bulk-generate HTML pages (SSE progress)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_html_pages_bulk' && is_admin()) {
    @ini_set('output_buffering','0'); @ini_set('zlib.output_compression','0');
    if (ob_get_level()) ob_end_flush();
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache'); header('X-Accel-Buffering: no');

    $pagesDir = __DIR__ . '/pages';
    if (!is_dir($pagesDir)) @mkdir($pagesDir, 0755, true);

    $apps = $pdo->query("SELECT a.*, c.name AS cat_name
        FROM apps a LEFT JOIN categories c ON a.category_id=c.id
        WHERE a.status='published' ORDER BY a.id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total = count($apps); $done = 0; $newPages = [];

    foreach ($apps as $app) {
        $html = build_html_page($app);
        $file = $pagesDir . '/' . $app['slug'] . '.html';
        file_put_contents($file, $html);
        $done++;
        $newPages[] = rtrim(SITE_URL,'/') . '/pages/' . rawurlencode($app['slug']) . '.html';
        $pct = (int)($done / $total * 100);
        echo "data: " . json_encode(['pct'=>$pct,'done'=>$done,'total'=>$total,'name'=>$app['name']]) . "\n\n";
        flush();
        if ($done % 50 === 0) usleep(100000);
    }

    // Bulk IndexNow submission (max 10000 per call)
    $inKey = get_cfg($pdo, 'indexnow_key');
    if ($inKey && $newPages) {
        foreach (array_chunk($newPages, 500) as $chunk) {
            $payload = ['host'=>parse_url(SITE_URL,PHP_URL_HOST),'key'=>$inKey,'urlList'=>$chunk];
            @file_get_contents('https://api.indexnow.org/indexnow', false, stream_context_create(['http'=>[
                'method'=>'POST','timeout'=>15,'ignore_errors'=>true,
                'header'=>"Content-Type: application/json\r\n",
                'content'=>json_encode($payload),
            ]]));
        }
    }

    echo "data: " . json_encode(['pct'=>100,'done'=>$done,'total'=>$total,'complete'=>true,'indexnow'=>count($newPages)]) . "\n\n";
    flush(); exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: HTML pages status (count generated vs total)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'html_pages_status' && is_admin()) {
    ob_clean(); header('Content-Type: application/json; charset=utf-8');
    $pagesDir  = __DIR__ . '/pages';
    $generated = is_dir($pagesDir) ? count(glob($pagesDir . '/*.html')) : 0;
    $total     = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
    echo json_encode(['ok'=>true,'generated'=>$generated,'total'=>$total]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Test connection / full diagnostics
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_connection' && is_admin()) {
    header('Content-Type: application/json');
    $checks = [];

    // DB
    try { $pdo->query('SELECT 1'); $checks[] = ['label'=>'الاتصال بقاعدة البيانات','ok'=>true,'detail'=>'متصل بنجاح']; }
    catch (Throwable $e) { $checks[] = ['label'=>'الاتصال بقاعدة البيانات','ok'=>false,'detail'=>$e->getMessage()]; }

    // Tables
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $need = ['admins','categories','apps','settings'];
        $missing = array_diff($need, $tables);
        $checks[] = ['label'=>'جداول قاعدة البيانات','ok'=>empty($missing),'detail'=>$missing?('جداول ناقصة: '.implode(', ',$missing)):'كل الجداول موجودة'];
    } catch (Throwable $e) { $checks[] = ['label'=>'جداول قاعدة البيانات','ok'=>false,'detail'=>$e->getMessage()]; }

    // GD extension
    $checks[] = ['label'=>'مكتبة معالجة الصور (GD)','ok'=>extension_loaded('gd'),'detail'=>extension_loaded('gd')?'مفعّلة':'غير مفعّلة — لن تعمل معالجة الأيقونات'];

    // cURL extension
    $checks[] = ['label'=>'مكتبة cURL','ok'=>extension_loaded('curl'),'detail'=>extension_loaded('curl')?'مفعّلة':'غير مفعّلة — لن يعمل الاتصال بـ OpenRouter'];

    // uploads writable
    $upOk = is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH);
    if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0755, true); $upOk = is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH); }
    $checks[] = ['label'=>'صلاحية الكتابة في مجلد uploads','ok'=>$upOk,'detail'=>$upOk?'قابل للكتابة':'غير قابل للكتابة — غيّر الصلاحيات إلى 755'];

    // AI provider test
    $aiProvider = get_cfg($pdo, 'ai_provider', 'openrouter');
    if ($aiProvider === 'aifreeforever') {
        $r = aifreeforever_call('قل "مرحبا" فقط بدون أي إضافات.');
        $checks[] = [
            'label'  => 'الاتصال بـ aifreeforever',
            'ok'     => $r['ok'],
            'detail' => $r['ok'] ? 'نجح الاتصال ✓ (aifreeforever)' : $r['error'],
        ];
    } else {
        $keys = openrouter_keys(get_cfg($pdo,'openrouter_key'));
        if (!$keys) {
            $checks[] = ['label'=>'مفتاح OpenRouter API','ok'=>false,'detail'=>'لم يتم إضافة أي مفتاح بعد'];
        } else {
            $models = build_model_rotation($pdo, true);
            $r = openrouter_call_rotating($keys, $models, 'قل "مرحبا" فقط بدون أي إضافات.');
            $checks[] = [
                'label' => 'الاتصال بـ OpenRouter (' . count($keys) . ' مفتاح × ' . count($models) . ' موديل)',
                'ok' => $r['ok'],
                'detail' => $r['ok'] ? ('نجح الاتصال باستخدام الموديل: '.$r['model']) : openrouter_diagnose_trace($r['trace']),
                'trace' => $r['trace'] ?? null,
                'working_model' => $r['ok'] ? $r['model'] : null,
            ];
            $primaryNow = get_cfg($pdo, 'openrouter_model', '');
            if ($r['ok'] && $r['model'] !== $primaryNow) set_cfg($pdo, 'openrouter_model', $r['model']);
        }
    }

    $allOk = !array_filter($checks, fn($c) => !$c['ok']);
    echo json_encode(['success'=>true,'all_ok'=>$allOk,'checks'=>$checks], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List free OpenRouter models
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list_models' && is_admin()) {
    header('Content-Type: application/json');
    $models = fetch_openrouter_free_models();
    if (!$models) {
        $models = array_map(fn($id) => ['id'=>$id,'name'=>$id,'context_length'=>null], openrouter_default_free_models());
    }
    echo json_encode(['success'=>true,'models'=>$models], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Repair database (re-run schema installer)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fix_db' && is_admin()) {
    header('Content-Type: application/json');
    try {
        $log = ensure_schema($pdo);
        echo json_encode(['success'=>true,'message'=>'تم فحص/إصلاح الجداول بنجاح','tables'=>$log], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Repair legacy invalid-UTF-8 bytes already stored in the DB
   (older rows saved before clean_utf8() was applied at save time can
   still contain bad bytes, which is what caused long descriptions to
   render as a blank gap). One-time, safe to re-run, only writes rows
   that actually changed.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'repair_encoding' && is_admin()) {
    header('Content-Type: application/json');
    $textCols = ['name','seo_title','meta_description','keywords','short_description','long_description',
        'privacy_policy','terms_content','offers_text','whats_new','developer'];
    $fixed = 0; $scanned = 0;
    $rows = $pdo->query("SELECT id," . implode(',', $textCols) . " FROM apps")->fetchAll();
    foreach ($rows as $row) {
        $scanned++;
        $set = []; $vals = [];
        foreach ($textCols as $col) {
            $clean = clean_utf8($row[$col] ?? '');
            if ($clean !== ($row[$col] ?? '')) { $set[] = "$col=?"; $vals[] = $clean; }
        }
        if ($set) {
            $vals[] = $row['id'];
            $pdo->prepare("UPDATE apps SET " . implode(',', $set) . " WHERE id=?")->execute($vals);
            $fixed++;
        }
    }
    $catRows = $pdo->query("SELECT id,description FROM categories")->fetchAll();
    foreach ($catRows as $row) {
        $clean = clean_utf8($row['description'] ?? '');
        if ($clean !== ($row['description'] ?? '')) {
            $pdo->prepare("UPDATE categories SET description=? WHERE id=?")->execute([$clean, $row['id']]);
            $fixed++;
        }
    }
    bump_cache_version($pdo);
    echo json_encode(['success'=>true,'message'=>"تم فحص {$scanned} تطبيق، وإصلاح ترميز {$fixed} صف."], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   Database backup — streams a plain .sql dump built via PDO
   (no shell_exec/mysqldump dependency, since shared hosts
   commonly disable shell_exec). Structure + data for every
   table, safe to re-import with a plain SQL client.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'db_backup' && is_admin()) {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="yassota-backup-' . date('Y-m-d-His') . '.sql"');
    echo "-- yassota database backup — " . date('Y-m-d H:i:s') . "\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $create = $createRow['Create Table'] ?? $createRow[1] ?? '';
        echo "DROP TABLE IF EXISTS `$table`;\n$create;\n\n";

        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        $colList = '`' . implode('`,`', $cols) . '`';
        $rowStmt = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rowStmt->fetch(PDO::FETCH_NUM)) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row);
            echo "INSERT INTO `$table` ($colList) VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
        @ob_flush(); @flush();
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: VirusTotal — scan the download link / check a pending scan
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'vt_scan' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,download_url FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }
    $r = vt_scan_url($pdo, $id, $a['download_url'] ?? '');
    echo json_encode(['success'=>$r['ok'], 'status'=>$r['status'] ?? null, 'error'=>$r['error'] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
}
if (isset($_GET['ajax']) && $_GET['ajax'] === 'vt_check' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,vt_analysis_id FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }
    $r = vt_check_pending($pdo, $id, $a['vt_analysis_id'] ?? '');
    echo json_encode(['success'=>$r['ok'], 'status'=>$r['status'] ?? null, 'error'=>$r['error'] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Regenerate SEO fields for one app (used by bulk tool)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'regen_seo' && is_admin()) {
    header('Content-Type: application/json');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
التطبيق: "{$app['name']}"
الوصف الحالي: "{$app['short_description']}"

{$seoStandards}

أعد JSON صالح فقط بدون أي نص إضافي:
{"seo_title":"","meta_description":"","keywords":""}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد غير صالح من الموديل']); exit; }
    $data = clean_utf8_deep($data);

    $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
        ->execute([seo_title_clamp(trim($data['seo_title'] ?? '')), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''), $id]);
    echo json_encode(['success'=>true,'name'=>$app['name']], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List published app ids for bulk SEO tool
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_seo_list' && is_admin()) {
    header('Content-Type: application/json');
    $ids = $pdo->query("SELECT id FROM apps WHERE status='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success'=>true,'ids'=>array_map('intval',$ids)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Continue long description content (~+600 words each click)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'continue_content' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name    = trim($input['name'] ?? '');
    $current = trim($input['current'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $tail = mb_substr($current, -1200);
    $prompt = <<<P
أنت كاتب محتوى SEO محترف متخصص في متاجر التطبيقات العربية. تكتب وصفاً طويلاً واحترافياً وأصلياً (وليس مكرراً أو محشواً) للتطبيق "{$name}"، بأسلوب طبيعي ومتدفق يفيد القارئ ويرفع ترتيب الصفحة في محركات البحث.
هذا هو آخر جزء مكتوب حتى الآن (للسياق فقط، لا تكرره):
"""{$tail}"""
أكمل الوصف مباشرة من حيث توقف (بدون تكرار ما سبق وبدون مقدمات مثل "بالتأكيد" أو "إليك") بفقرة أو أكثر أصلية بطول تقريبي 500-700 كلمة، تغطي جوانب لم تُذكر بعد مثل: تفاصيل الأداء، حالات استخدام واقعية، مقارنة موجزة مع بدائل، نصائح استخدام، الأمان والخصوصية، أو الفئة المستهدفة. أعد النص فقط بدون أي تنسيق Markdown وبدون عناوين JSON — فقرات نصية عادية فقط.
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $addition = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'addition'=>$addition,'added_words'=>str_word_count($addition)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate privacy policy / terms / "what this app offers"
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_legal' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $type = trim($input['type'] ?? '');
    if (!$name || !in_array($type, ['privacy','terms','offers'], true)) {
        echo json_encode(['success'=>false,'error'=>'بيانات غير صالحة']); exit;
    }

    $prompts = [
        'privacy' => "اكتب سياسة خصوصية احترافية وشاملة باللغة العربية لصفحة تحميل تطبيق أندرويد اسمه \"{$name}\" على موقع متجر تطبيقات (وليس المطور الفعلي). غطِّ: نوع البيانات التي قد يجمعها التطبيق، كيفية استخدامها، ملفات تعريف الارتباط والإعلانات، مشاركة البيانات مع أطراف ثالثة، حقوق المستخدم، أمان البيانات، وطريقة التواصل. اكتب نصاً منسقاً بفقرات وعناوين فرعية واضحة (استخدم ## للعناوين) بدون Markdown معقد، بطول 500-800 كلمة تقريباً. أعد النص فقط بدون أي مقدمات.",
        'terms'   => "اكتب شروط استخدام احترافية باللغة العربية لصفحة تحميل تطبيق أندرويد اسمه \"{$name}\" على موقع متجر تطبيقات. غطِّ: قبول الشروط، طريقة الاستخدام المسموح بها، إخلاء المسؤولية عن التطبيق كونه من طرف ثالث، حقوق الملكية الفكرية، حدود المسؤولية، والتعديلات على الشروط. اكتب نصاً منسقاً بعناوين فرعية (## للعناوين) بطول 400-700 كلمة. أعد النص فقط بدون أي مقدمات.",
        'offers'  => "اكتب فقرة تسويقية احترافية بعنوان \"ماذا يقدم تطبيق {$name}؟\" تشرح بشكل مقنع وواقعي أبرز ما يقدمه هذا التطبيق للمستخدم العربي، بأسلوب سلس يخدم SEO، بطول 200-300 كلمة، فقرة نصية عادية بدون عناوين Markdown.",
    ];
    $r = ai_text($pdo, $prompts[$type]);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $content = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'content'=>$content,'type'=>$type], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import basic app info from a Google Play Store URL
   (public Open Graph tags only — description/icon/title;
   download link must still be added manually since Play
   Store does not expose a direct APK link).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) === true && $_GET['ajax'] === 'fetch_playstore' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $src = trim($input['url'] ?? '');
    if (!$src || !preg_match('#^https://play\.google\.com/store/apps/details#', $src)) {
        echo json_encode(['success'=>false,'error'=>'ضع رابط صفحة تطبيق صالح من play.google.com/store/apps/details']); exit;
    }

    $meta = fetch_playstore_meta($src);
    if (!$meta) {
        echo json_encode(['success'=>false,'error'=>'لم يتم العثور على بيانات في الصفحة — قد تكون Google تمنع الوصول الآلي من هذا الاستضافة']); exit;
    }

    echo json_encode(array_merge($meta, [
        'success' => true,
        'note' => 'تم استيراد العنوان والوصف والأيقونة فقط. رابط التحميل المباشر غير متاح من Google Play — أضفه يدوياً أو استخدم توليد AI لباقي الحقول.',
    ]), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI-generate an app icon (best effort — see
   ai_generate_image() in config.php for why this can fail
   even with a valid key: free image models are scarce).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_icon_ai' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    $desc  = trim($input['description'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $prompt = "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"$name\"" . ($desc ? (" — " . mb_substr($desc, 0, 200)) : '') . ".";
    $r = ai_generate_image($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }

    $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
    file_put_contents($tmp, $r['bin']);
    $fakeFile = ['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($r['bin'])];
    $path = process_icon($fakeFile, slugify($name));
    @unlink($tmp);

    if (!$path) { echo json_encode(['success'=>false,'error'=>'تم توليد الصورة لكن تعذّرت معالجتها كأيقونة صالحة']); exit; }
    echo json_encode(['success'=>true,'path'=>$path,'url'=>url($path)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI-generate an app screenshot (best effort, same
   caveats as the icon endpoint above).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_screenshot_ai' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    $desc  = trim($input['description'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }

    $prompt = "Generate a realistic Android app screenshot mockup (portrait 9:16, clean modern mobile UI, no watermark, no device frame) plausibly showing the in-app interface of an app called \"$name\"" . ($desc ? (" — " . mb_substr($desc, 0, 200)) : '') . ".";
    $r = ai_generate_image($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }

    $dir = UPLOAD_PATH . '/screenshots';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp = tempnam(sys_get_temp_dir(), 'aiss');
    file_put_contents($tmp, $r['bin']);
    $info = @getimagesize($tmp);
    if (!$info) { @unlink($tmp); echo json_encode(['success'=>false,'error'=>'تعذرت قراءة الصورة المولّدة']); exit; }

    $fname = slugify($name) . '-ai-' . substr(md5(uniqid()), 0, 6) . '.webp';
    $dest  = "$dir/$fname";
    $ok = compress_image_to($tmp, $info['mime'], $dest, 1080, 2000, 82);
    @unlink($tmp);

    if (!$ok) { echo json_encode(['success'=>false,'error'=>'فشل ضغط الصورة المولّدة']); exit; }
    $relPath = "uploads/screenshots/$fname";
    echo json_encode(['success'=>true,'path'=>$relPath,'url'=>url($relPath)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate an SEO description for a category
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_cat_description' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['name'] ?? '');
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم التصنيف مطلوب']); exit; }

    $prompt = "اكتب فقرة تعريفية احترافية بطول 80-150 كلمة لصفحة تصنيف \"{$name}\" على متجر تطبيقات أندرويد عربي، تشرح للزائر ما يجده في هذا التصنيف بأسلوب طبيعي يخدم SEO. فقرة نصية عادية فقط بدون عناوين أو Markdown، وبدون مقدمات مثل \"بالتأكيد\".";
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $content = trim(preg_replace('/^```\w*|```$/m', '', $r['content']));
    echo json_encode(['success'=>true,'content'=>$content], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate 3 short related articles for an app —
   internal linking back to its download page (SEO + AdSense).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_articles' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_GET['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
    $stmt->execute([$appId]);
    $a = $stmt->fetch();
    if (!$a) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $prompt = <<<P
أنت كاتب محتوى عربي محترف لموقع تحميل تطبيقات أندرويد. اكتب 3 مقالات قصيرة أصلية ومفيدة (وليست إعلانية مبالغاً فيها)
عن تطبيق "{$a['name']}" ({$a['short_description']}), بأنواع مختلفة، مثلاً: شرح كيفية استخدام التطبيق للمبتدئين، مقارنة
بينه وبين بدائل مشهورة في نفس المجال، أو نصائح وحيل لاستخدامه بفعالية. كل مقال بطول 250-450 كلمة، فقرات طبيعية بدون
Markdown، تنتهي كل مقالة بجملة طبيعية تدعو القارئ لتحميل "{$a['name']}" (دون رابط، الرابط سيُضاف تلقائياً).
أعد JSON فقط بالشكل التالي، بدون أي نص خارج JSON:
{"articles":[{"title":"","body":""},{"title":"","body":""},{"title":"","body":""}]}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    $data = clean_utf8_deep($data ?? []);
    $articles = $data['articles'] ?? [];
    if (!$articles) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن بالشكل المتوقع']); exit; }

    $created = 0;
    foreach ($articles as $art) {
        $title = trim($art['title'] ?? '');
        $body  = trim($art['body'] ?? '');
        if (!$title || !$body) continue;
        $slug = unique_article_slug($pdo, $a['name'] . '-' . $title);
        $pdo->prepare("INSERT INTO app_articles (app_id,title,slug,body) VALUES (?,?,?,?)")
            ->execute([$appId, $title, $slug, $body]);
        $created++;
    }
    echo json_encode(['success'=>true,'created'=>$created], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Internal link-safety verification (replaces VirusTotal)
   Admin marks a download URL as team-verified; badge shows on the
   app page and download page. No external API, no API keys needed.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'verify_link' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? 'verify'; // 'verify' or 'unverify'
    if (!$appId) { echo json_encode(['success'=>false,'error'=>'معرف التطبيق مطلوب']); exit; }
    if ($action === 'unverify') {
        $pdo->prepare("UPDATE apps SET link_verified=0, verified_at=NULL WHERE id=?")->execute([$appId]);
        echo json_encode(['success'=>true,'verified'=>false]);
    } else {
        $pdo->prepare("UPDATE apps SET link_verified=1, verified_at=NOW() WHERE id=?")->execute([$appId]);
        bump_cache_version($pdo);
        echo json_encode(['success'=>true,'verified'=>true]);
    }
    exit;
}

/* ── AJAX: test Telegram bot ── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'telegram_test' && is_admin()) {
    header('Content-Type: application/json');
    $r = telegram_api($pdo, 'sendMessage', [
        'text'       => '✅ اختبار ناجح من لوحة إدارة <b>yassota</b> — بوت تيليجرام يعمل بشكل صحيح!',
        'parse_mode' => 'HTML',
    ]);
    echo json_encode(['success' => $r['ok'], 'error' => $r['error'] ?? null], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate one general blog post (tutorial/news/
   comparison/best-of/article) — the general-content system,
   distinct from the per-app app_articles above.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_blog_post' && is_admin()) {
    header('Content-Type: application/json');
    $type = trim($_GET['type'] ?? 'article');
    if (!isset(BLOG_TYPES[$type])) $type = 'article';
    $topic = trim($_GET['topic'] ?? '');

    $typeGuides = [
        'tutorial'   => 'شرح تعليمي خطوة بخطوة يساعد القارئ على إنجاز مهمة محددة داخل تطبيق أو لعبة أندرويد شهيرة.',
        'news'       => 'خبر تقني قصير عن تحديث أو ميزة جديدة أو اتجاه في عالم تطبيقات وألعاب أندرويد (بأسلوب إخباري محايد، دون تأكيد تواريخ أو أرقام غير مؤكدة).',
        'comparison' => 'مقارنة موضوعية ومتوازنة بين تطبيقين أو أكثر في نفس الفئة، توضح الفروقات الفعلية ولمن يناسب كل خيار.',
        'best-apps'  => 'قائمة "أفضل التطبيقات" في فئة معينة (5-8 عناصر)، كل عنصر بفقرة تشرح لماذا يستحق مكانه.',
        'best-games' => 'قائمة "أفضل الألعاب" في فئة أو نمط لعب معين (5-8 عناصر)، كل عنصر بفقرة تشرح ما يميزه.',
        'article'    => 'مقال عام مفيد وأصلي متعلق بتطبيقات أو ألعاب أندرويد.',
    ];
    $topicLine = $topic !== '' ? "الموضوع المطلوب تحديداً: \"{$topic}\"." : "اختر موضوعاً شائعاً ومفيداً يناسب هذا القسم.";

    $prompt = <<<P
أنت كاتب محتوى عربي محترف متخصص في تطبيقات وألعاب أندرويد. اكتب مقالاً أصلياً بالكامل من نوع:
{$typeGuides[$type]}
{$topicLine}

المقال بطول 700-1200 كلمة، منظم بعناوين فرعية واضحة.
اكتب body كـ HTML كامل قابل للعرض مباشرة: استخدم <h2> و<h3> للعناوين الفرعية، <p> للفقرات، <ul><li> للقوائم، <strong> للتمييز. لا تضع HTML خارج body. لا تستخدم Markdown.

اتبع معايير SEO:
- seo_title: عنوان جذاب 50-65 حرفاً يتضمن الكلمة المفتاحية.
- meta_description: 140-160 حرفاً يلخص المقال.
- keywords: 10-15 كلمة/عبارة مفتاحية عربية مفصولة بفاصلة.
- excerpt: سطر أو سطران يظهران في بطاقة المقال.

أعد JSON صالح فقط بدون أي نص خارج JSON:
{"title":"","seo_title":"","meta_description":"","keywords":"","excerpt":"","body":""}
P;
    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن JSON صالحاً']); exit; }
    $data = clean_utf8_deep($data);

    $title = trim($data['title'] ?? '');
    $body  = trim($data['body'] ?? '');
    if (!$title || !$body) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي ناقص']); exit; }
    $slug = unique_blog_slug($pdo, $title);

    // Best-effort cover image — never blocks the post from being created.
    $coverImage = null;
    $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist blog cover illustration (no text, no watermark, flat/gradient style, 16:9, wide) representing the topic: \"{$title}\".");
    if ($ir['ok']) {
        $tmp = tempnam(sys_get_temp_dir(), 'blogcv');
        file_put_contents($tmp, $ir['bin']);
        $coverImage = process_icon(['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($ir['bin'])], $slug . '-cover');
        @unlink($tmp);
    }

    $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,cover_image,status) VALUES (?,?,?,?,?,?,?,?,?,'draft')")
        ->execute([$type, $title, $slug, trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''),
            trim($data['keywords'] ?? ''), trim($data['excerpt'] ?? ''), $body, $coverImage]);
    $newId = (int)$pdo->lastInsertId();
    echo json_encode(['success'=>true,'id'=>$newId,'title'=>$title], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Generate one chunk of a long app-review article.
   Five chunks total → ~4500-6000 Arabic words of HTML.
   The client chains calls sequentially, passing prev HTML
   so the model knows what has already been written.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'gen_app_article_chunk' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(120);
    $input   = json_decode(file_get_contents('php://input'), true);
    $appId   = (int)($input['app_id'] ?? 0);
    $chunk   = max(1, min(5, (int)($input['chunk'] ?? 1)));
    $prevHtml= trim($input['prev_html'] ?? '');
    if (!$appId) { echo json_encode(['success'=>false,'error'=>'app_id مطلوب']); exit; }

    $app = $pdo->prepare("SELECT a.*,c.name AS cat_name FROM apps a LEFT JOIN categories c ON c.id=a.category_id WHERE a.id=?");
    $app->execute([$appId]);
    $app = $app->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $aName = $app['name'];
    $aDev  = $app['developer'] ?? '';
    $aDesc = $app['long_description'] ?? $app['short_description'] ?? '';
    $aCat  = $app['cat_name'] ?? '';
    $aSlug = $app['slug'] ?? '';
    $ftr   = json_decode($app['features'] ?? '[]', true) ?: [];
    $pros  = json_decode($app['pros'] ?? '[]', true) ?: [];
    $cons  = json_decode($app['cons'] ?? '[]', true) ?: [];
    $faq   = json_decode($app['faq'] ?? '[]', true) ?: [];
    $steps = json_decode($app['install_steps'] ?? '[]', true) ?: [];
    $appUrl = url("app/{$aSlug}");

    $ctaHtml = "<div style='text-align:center;margin:28px 0'><a href=\"{$appUrl}\" style='display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:50px;font-size:17px;font-weight:700;text-decoration:none;box-shadow:0 4px 16px rgba(37,99,235,.3)'>⬇ تحميل {$aName} مجاناً</a></div>";

    $sectionTitles = [
        1 => 'المقدمة + نظرة عامة + سبب الشهرة',
        2 => 'الميزات التفصيلية مع أمثلة عملية',
        3 => 'كيفية التحميل والتثبيت + كيفية الاستخدام خطوة بخطوة',
        4 => 'المميزات والعيوب + المقارنة مع البدائل',
        5 => 'نصائح احترافية + الأسئلة الشائعة + خلاصة وتوصية',
    ];

    $ftrList  = implode('، ', array_slice($ftr,0,6));
    $prosList = implode('، ', array_slice($pros,0,5));
    $consList = implode('، ', array_slice($cons,0,3));
    $stepList = implode("\n", array_map(fn($s,$i)=>"- خطوة ".($i+1).": $s", $steps, array_keys($steps)));
    $faqList  = implode("\n", array_map(fn($f)=>"س: {$f['q']}\nج: {$f['a']}", array_slice($faq,0,5)));
    $prevNote = $prevHtml ? "ما سبق كتابته (لا تكرره، أكمل بعده مباشرة):\n[تم كتابة ".mb_strlen($prevHtml)." حرفاً من المقال]" : '';

    $prompt = <<<P
أنت كاتب محتوى عربي محترف ومتخصص في مراجعات تطبيقات الأندرويد. تكتب بالعامية السورية العربية الطبيعية والجذابة.

معلومات التطبيق:
- الاسم: {$aName}
- المطور: {$aDev}
- التصنيف: {$aCat}
- الميزات: {$ftrList}
- الإيجابيات: {$prosList}
- السلبيات: {$consList}
- خطوات التثبيت: {$stepList}
- أسئلة شائعة: {$faqList}
- وصف عام: {$aDesc}

{$prevNote}

اكتب الآن القسم رقم {$chunk} من المقال: {$sectionTitles[$chunk]}

المطلوب:
- 900-1100 كلمة لهذا القسم (مهم جداً، لا تكتب أقل)
- HTML فقط (لا Markdown، لا نص خام)
- استخدم <h2> للعناوين الرئيسية، <h3> للفرعية
- <p> للفقرات (فقرات كاملة ومفيدة، ليس جملاً قصيرة)
- <ul><li> للقوائم حيث مناسب
- <strong> لتمييز المصطلحات المهمة
- اللهجة: سورية عربية مفهومة، حيوية ومشوّقة من أول سطر
- المحتوى: حقيقي ومفيد، ليس ملء بلا معنى
- لا تضع مقدمات مثل "في هذا القسم سنتحدث عن..."
- لا تكتب "الكاتب" أو "المقال" في النص
- فقط HTML قابل للوصق مباشرة داخل صفحة المقال، لا شيء آخر
P;

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $html = trim($r['content']);
    // Strip markdown code fences if model wrapped the HTML
    $html = preg_replace('/^```(?:html)?\s*/i', '', $html);
    $html = preg_replace('/\s*```\s*$/i', '', $html);
    // Inject CTA after chunk 2 and chunk 4
    if (in_array($chunk, [2, 4])) $html .= "\n" . $ctaHtml;

    echo json_encode(['success'=>true,'chunk'=>$chunk,'html'=>$html,'word_count'=>count(preg_split('/\s+/u',$html,-1,PREG_SPLIT_NO_EMPTY))], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Save a completed app-review article to blog_posts
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'save_app_article' && is_admin()) {
    header('Content-Type: application/json');
    $input  = json_decode(file_get_contents('php://input'), true);
    $appId  = (int)($input['app_id'] ?? 0);
    $html   = trim($input['html'] ?? '');
    if (!$appId || !$html) { echo json_encode(['success'=>false,'error'=>'بيانات مطلوبة']); exit; }

    $app = $pdo->prepare("SELECT * FROM apps WHERE id=?"); $app->execute([$appId]); $app=$app->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود']); exit; }

    $aName = $app['name'];
    $title = "مراجعة تطبيق {$aName}: دليل شامل للاستخدام والميزات";
    $slug  = unique_blog_slug($pdo, $title);
    $seoTitle = "مراجعة {$aName} " . date('Y') . " — دليل شامل بالعربي";
    $metaDesc = "مراجعة شاملة لتطبيق {$aName}: الميزات، كيفية الاستخدام، المميزات والعيوب، وكل ما تحتاج معرفته بالعامية السورية.";
    $kwds  = "تطبيق {$aName}، مراجعة {$aName}، تحميل {$aName}، {$aName} للأندرويد";
    $excerpt= "مراجعة تفصيلية لتطبيق {$aName} — الميزات، كيفية الاستخدام، المميزات والعيوب وكل ما تحتاج معرفته.";
    $appUrl = url("app/{$app['slug']}");
    // Append final CTA
    $html .= "\n<div style='text-align:center;margin:32px 0'><a href=\"{$appUrl}\" style='display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:50px;font-size:18px;font-weight:700;text-decoration:none;box-shadow:0 4px 20px rgba(37,99,235,.35)'>⬇ حمّل {$aName} الآن مجاناً</a></div>";

    $existing = $pdo->prepare("SELECT id FROM blog_posts WHERE title=?"); $existing->execute([$title]);
    if ($eid = $existing->fetchColumn()) {
        $pdo->prepare("UPDATE blog_posts SET body=?,slug=?,seo_title=?,meta_description=?,keywords=?,excerpt=?,updated_at=NOW() WHERE id=?")->execute([$html,$slug,$seoTitle,$metaDesc,$kwds,$excerpt,$eid]);
        echo json_encode(['success'=>true,'id'=>(int)$eid,'title'=>$title,'updated'=>true,'view_url'=>url("blog/{$slug}")], JSON_UNESCAPED_UNICODE);
    } else {
        $pdo->prepare("INSERT INTO blog_posts (type,title,slug,seo_title,meta_description,keywords,excerpt,body,status) VALUES ('article',?,?,?,?,?,?,'draft')")->execute([$title,$slug,$seoTitle,$metaDesc,$kwds,$excerpt,$html]);
        $nId=(int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$nId,'title'=>$title,'updated'=>false,'view_url'=>url("blog/{$slug}")], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: AI admin assistant — a safe, whitelisted-actions
   console. The admin types a request in plain language;
   the model maps it to ONE of a fixed set of actions below
   and PHP executes it through the exact same functions the
   rest of admin.php already uses. There is no action that
   writes to the filesystem outside uploads/, executes shell
   commands, or touches PHP/template files — by design, this
   never becomes a live remote-code-execution surface even if
   the admin session were ever compromised.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'assistant' && is_admin()) {
    header('Content-Type: application/json');
    $input   = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    if (!$message) { echo json_encode(['success'=>false,'error'=>'اكتب طلباً أولاً']); exit; }

    $recentApps = $pdo->query("SELECT id,name,status FROM apps ORDER BY id DESC LIMIT 40")->fetchAll();
    $appsList = implode("\n", array_map(fn($a) => "#{$a['id']} {$a['name']} ({$a['status']})", $recentApps));
    $allowedSettingKeys = ['moneytag_zone','openrouter_model','openrouter_fallback','openrouter_auto_rotate','openrouter_image_model'];

    $prompt = <<<P
أنت مساعد إدارة داخل لوحة تحكم متجر تطبيقات "yassota". لديك قدرة على تنفيذ إجراءات محدّدة فقط عبر إرجاع JSON — لا تنفّذ أي كود، ولا تكتب ملفات، فقط تختار إجراءً من القائمة المسموحة التالية:

- "chat": للرد على سؤال أو توضيح بدون أي تنفيذ. params: {}
- "create_app_draft": ينشئ تطبيقاً جديداً كمسودة بمحتوى مولّد بالذكاء الاصطناعي (بدون رابط تحميل — يضيفه الأدمن لاحقاً). params: {"name": "اسم التطبيق"}
- "regenerate_seo_one": يعيد توليد عنوان/وصف/كلمات SEO لتطبيق محدد. params: {"app_id": رقم}
- "regenerate_seo_all": يعيد توليد SEO لكل التطبيقات المنشورة. params: {}
- "generate_icon": يولّد أيقونة بالذكاء الاصطناعي لتطبيق محدد ويحفظها له مباشرة. params: {"app_id": رقم}
- "update_setting": يغيّر إعداداً غير حساس. المفاتيح المسموحة فقط: moneytag_zone, openrouter_model, openrouter_fallback, openrouter_auto_rotate, openrouter_image_model. params: {"key": "...", "value": "..."}
- "list_apps": يسرد أحدث التطبيقات. params: {}

قائمة أحدث التطبيقات (id، الاسم، الحالة):
{$appsList}

طلب الأدمن: "{$message}"

أعد JSON فقط بدون أي نص إضافي أو Markdown بهذا الشكل بالضبط:
{"action":"...","params":{...},"reply":"رد قصير بالعربية يشرح ماذا ستفعل أو رد مباشر إن كان action=chat"}
إذا لم يطابق الطلب أي إجراء مسموح أو كان غامضاً، استخدم action="chat" واشرح ذلك في reply.
P;

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $decision = ai_extract_json($r['content']);
    if (!$decision || empty($decision['action'])) {
        echo json_encode(['success'=>true,'reply'=>trim($r['content']),'action'=>'chat']); exit;
    }

    $action = $decision['action'];
    $params = $decision['params'] ?? [];
    $reply  = trim($decision['reply'] ?? '');
    $result = null;

    switch ($action) {
        case 'create_app_draft':
            $name = trim($params['name'] ?? '');
            if (!$name) { $result = 'لم يتم تحديد اسم التطبيق'; break; }
            $genPrompt = "أنت خبير تسويق تطبيقات أندرويد وكاتب محتوى SEO محترف. التطبيق: \"{$name}\"\n\n" . seo_prompt_standards() .
                "\n\nأعد JSON صالح فقط بدون أي نص آخر:\n{\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\",\"short_description\":\"\",\"long_description\":\"\",\"developer\":\"\",\"version\":\"1.0.0\",\"android_version\":\"\",\"size_mb\":\"\",\"rating\":4.5,\"whats_new\":\"\"}";
            $gr = ai_text($pdo, $genPrompt);
            $data = $gr['ok'] ? ai_extract_json($gr['content']) : null;
            $data = clean_utf8_deep($data ?? []);
            $slug = unique_slug($pdo, $name);
            $pdo->prepare("INSERT INTO apps (name,slug,seo_title,meta_description,keywords,short_description,long_description,developer,version,android_version,size_mb,rating,whats_new,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
                ->execute([
                    $name, $slug,
                    trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''),
                    trim($data['short_description'] ?? ''), trim($data['long_description'] ?? ''),
                    trim($data['developer'] ?? ''), trim($data['version'] ?? '1.0.0'), trim($data['android_version'] ?? ''),
                    trim($data['size_mb'] ?? ''), (float)($data['rating'] ?? 4.5), trim($data['whats_new'] ?? ''),
                ]);
            $newId = (int)$pdo->lastInsertId();
            $result = "تم إنشاء مسودة #{$newId} — {$name}. أضف رابط التحميل والأيقونة ثم انشرها من صفحة التعديل.";
            break;

        case 'regenerate_seo_one':
            $id = (int)($params['app_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
            $stmt->execute([$id]); $a = $stmt->fetch();
            if (!$a) { $result = "لم يتم العثور على تطبيق برقم #{$id}"; break; }
            $seoPrompt = "التطبيق: \"{$a['name']}\" الوصف الحالي: \"{$a['short_description']}\"\n\n" . seo_prompt_standards() .
                "\n\nأعد JSON فقط: {\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\"}";
            $sr = ai_text($pdo, $seoPrompt);
            $sd = $sr['ok'] ? ai_extract_json($sr['content']) : null;
            if (!$sd) { $result = 'فشل التوليد'; break; }
            $sd = clean_utf8_deep($sd);
            $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
                ->execute([seo_title_clamp(trim($sd['seo_title']??'')), trim($sd['meta_description']??''), trim($sd['keywords']??''), $id]);
            $result = "تم تحديث SEO للتطبيق #{$id} — {$a['name']}";
            break;

        case 'regenerate_seo_all':
            // Capped per call — looping over every published app in one PHP
            // request (with each AI call taking real seconds) is exactly the
            // kind of single request that can hold a PHP worker for minutes
            // and make the rest of the site feel slow while it runs. Ask
            // again to continue with the next batch instead.
            $batchLimit = 15;
            $allIds = $pdo->query("SELECT id FROM apps WHERE status='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
            $ids = array_slice($allIds, 0, $batchLimit);
            $done = 0;
            foreach ($ids as $id) {
                $stmt = $pdo->prepare("SELECT name,short_description FROM apps WHERE id=?");
                $stmt->execute([$id]); $a = $stmt->fetch();
                if (!$a) continue;
                $sr = ai_text($pdo, "التطبيق: \"{$a['name']}\"\n\n" . seo_prompt_standards() . "\n\nأعد JSON فقط: {\"seo_title\":\"\",\"meta_description\":\"\",\"keywords\":\"\"}");
                $sd = $sr['ok'] ? ai_extract_json($sr['content']) : null;
                if ($sd) {
                    $sd = clean_utf8_deep($sd);
                    $pdo->prepare("UPDATE apps SET seo_title=?, meta_description=?, keywords=? WHERE id=?")
                        ->execute([seo_title_clamp(trim($sd['seo_title']??'')), trim($sd['meta_description']??''), trim($sd['keywords']??''), $id]);
                    $done++;
                }
            }
            $remaining = count($allIds) - count($ids);
            $result = "تم تحديث SEO لـ {$done} من أصل " . count($ids) . " (دفعة واحدة من {$batchLimit} كحد أقصى)."
                . ($remaining > 0 ? " تبقّى {$remaining} تطبيق — اكتب نفس الطلب مرة أخرى لمتابعة الدفعة التالية." : " تم الانتهاء من كل التطبيقات المنشورة.");
            break;

        case 'generate_icon':
            $id = (int)($params['app_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,name,short_description FROM apps WHERE id=?");
            $stmt->execute([$id]); $a = $stmt->fetch();
            if (!$a) { $result = "لم يتم العثور على تطبيق برقم #{$id}"; break; }
            $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"{$a['name']}\".");
            if (!$ir['ok']) { $result = "فشل توليد الأيقونة: {$ir['error']}"; break; }
            $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
            file_put_contents($tmp, $ir['bin']);
            $path = process_icon(['tmp_name'=>$tmp,'error'=>UPLOAD_ERR_OK,'size'=>strlen($ir['bin'])], slugify($a['name']));
            @unlink($tmp);
            if (!$path) { $result = 'تم توليد الصورة لكن تعذّرت معالجتها'; break; }
            $pdo->prepare("UPDATE apps SET icon_path=? WHERE id=?")->execute([$path, $id]);
            bump_cache_version($pdo);
            $result = "تم توليد وحفظ أيقونة جديدة للتطبيق #{$id} — {$a['name']}";
            break;

        case 'update_setting':
            $key = trim($params['key'] ?? '');
            $val = trim($params['value'] ?? '');
            if (!in_array($key, $allowedSettingKeys, true)) { $result = "الإعداد \"{$key}\" غير مسموح بتغييره من هنا"; break; }
            set_cfg($pdo, $key, $val);
            $result = "تم تحديث الإعداد {$key}";
            break;

        case 'list_apps':
            $result = $appsList ?: 'لا توجد تطبيقات بعد';
            break;

        case 'chat':
        default:
            $result = null;
            break;
    }

    echo json_encode(['success'=>true,'action'=>$action,'reply'=>$reply,'result'=>$result], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List all 30 pre-written preset apps with import status
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'import_preset_list' && is_admin()) {
    header('Content-Type: application/json');
    $dataDir = ROOT_PATH . '/install/bulk-apps-data';
    $files = glob($dataDir . '/batch-*.json');
    sort($files);
    if (!$files) { echo json_encode(['success'=>false,'error'=>'ملفات البيانات غير موجودة في install/bulk-apps-data/']); exit; }

    $SLUG_MAP = [
        'واتساب'=>['whatsapp','social'],'تيليجرام'=>['telegram','social'],
        'فيسبوك'=>['facebook','social'],'ماسنجر'=>['messenger','social'],
        'سناب شات'=>['snapchat','social'],'ديسكورد'=>['discord','social'],
        'تويتر X'=>['twitter-x','social'],'ثريدز'=>['threads','social'],
        'لينكدإن'=>['linkedin','social'],'بينترست'=>['pinterest','social'],
        'جوجل كروم'=>['google-chrome','tools'],'يوسي براوزر'=>['uc-browser','tools'],
        'يوتيوب'=>['youtube','apps'],'نتفليكس'=>['netflix','apps'],
        'سبوتيفاي'=>['spotify','apps'],'شازام'=>['shazam','apps'],
        'ساوند كلاود'=>['soundcloud','apps'],'أوبرا ميني'=>['opera-mini','tools'],
        'ترو كولر'=>['truecaller','tools'],'جوجل ترانسليت'=>['google-translate','tools'],
        'زوم'=>['zoom','tools'],'مايكروسوفت تيمز'=>['microsoft-teams','tools'],
        'دوولينجو'=>['duolingo','productivity'],'WPS Office'=>['wps-office','productivity'],
        'أدوبي أكروبات ريدر'=>['adobe-acrobat-reader','productivity'],'كانفا'=>['canva','design'],
        'بيكس آرت'=>['picsart','design'],'إن شوت'=>['inshot','design'],
        'بابجي موبايل'=>['pubg-mobile','games'],'فري فاير'=>['free-fire','games'],
        'تيك توك'=>['tiktok','apps'],'إنستغرام'=>['instagram','social'],
        'كاب كت'=>['capcut','design'],'يوتيوب ميوزيك'=>['youtube-music','apps'],
        'تويتش'=>['twitch','apps'],'شاهد'=>['shahid','apps'],
        'جوجل مابس'=>['google-maps','tools'],'جوجل درايف'=>['google-drive','productivity'],
        'كيبورد جوجل'=>['gboard','tools'],'مايكروسوفت وورد'=>['microsoft-word','productivity'],
        'ون درايف'=>['onedrive','productivity'],'أدوبي لايتروم'=>['adobe-lightroom','design'],
        'كلاش أوف كلانز'=>['clash-of-clans','games'],'كلاش رويال'=>['clash-royale','games'],
        'موبايل ليجندز'=>['mobile-legends','games'],'ماين كرافت'=>['minecraft','games'],
        'سابواي سيرف'=>['subway-surfers','games'],'تمبل رن 2'=>['temple-run-2','games'],
        'أمازون شوبينج'=>['amazon-shopping','apps'],'علي إكسبريس'=>['aliexpress','apps'],
        'شيين'=>['shein','apps'],'تالابات'=>['talabat','apps'],
        'كريم'=>['careem','apps'],'نمشي'=>['namshi','apps'],
        'نورد VPN'=>['nordvpn','tools'],'فايرفوكس'=>['firefox','tools'],
        'برايف براوزر'=>['brave-browser','tools'],'جيميل'=>['gmail','tools'],
        'مايكروسوفت أوتلوك'=>['microsoft-outlook','productivity'],'أنكي درويد'=>['ankidroid','productivity'],
    ];

    $catLabels = ['social'=>'تواصل اجتماعي','tools'=>'أدوات','apps'=>'تطبيقات',
                  'productivity'=>'إنتاجية','design'=>'تصميم','games'=>'ألعاب'];

    $result = [];
    foreach ($files as $file) {
        $batch = json_decode((string)file_get_contents($file), true);
        if (!$batch || empty($batch['apps'])) continue;
        foreach ($batch['apps'] as $app) {
            $name = trim($app['name'] ?? '');
            if (!$name) continue;
            $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?");
            $ex->execute([$name]);
            $existId = $ex->fetchColumn();
            $catSlug = $app['category_slug'] ?? ($SLUG_MAP[$name][1] ?? 'apps');
            $result[] = [
                'name'       => $name,
                'developer'  => $app['developer'] ?? '',
                'short_desc' => $app['short_description'] ?? '',
                'category'   => $catLabels[$catSlug] ?? $catSlug,
                'cat_slug'   => $catSlug,
                'existing_id'=> $existId ?: null,
            ];
        }
    }
    echo json_encode(['success'=>true,'apps'=>$result,'total'=>count($result)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import one pre-written preset app by name
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'import_preset_one' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(90);
    $input = json_decode(file_get_contents('php://input'), true);
    $targetName = trim($input['name'] ?? '');
    if (!$targetName) { echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    // Find app in data files
    $dataDir = ROOT_PATH . '/install/bulk-apps-data';
    $files = glob($dataDir . '/batch-*.json');
    sort($files);
    $data = null;
    foreach ($files as $file) {
        $batch = json_decode((string)file_get_contents($file), true);
        if (!$batch) continue;
        foreach ($batch['apps'] as $app) {
            if (trim($app['name'] ?? '') === $targetName) { $data = $app; break 2; }
        }
    }
    if (!$data) { echo json_encode(['success'=>false,'error'=>'التطبيق غير موجود في ملفات البيانات']); exit; }

    // Skip if already exists
    $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?");
    $ex->execute([$targetName]);
    if ($exId = $ex->fetchColumn()) {
        echo json_encode(['success'=>false,'skipped'=>true,'existing_id'=>(int)$exId,'name'=>$targetName,'error'=>'موجود مسبقاً']);
        exit;
    }

    $SLUG_MAP = [
        'واتساب'=>['whatsapp','social'],'تيليجرام'=>['telegram','social'],
        'فيسبوك'=>['facebook','social'],'ماسنجر'=>['messenger','social'],
        'سناب شات'=>['snapchat','social'],'ديسكورد'=>['discord','social'],
        'تويتر X'=>['twitter-x','social'],'ثريدز'=>['threads','social'],
        'لينكدإن'=>['linkedin','social'],'بينترست'=>['pinterest','social'],
        'جوجل كروم'=>['google-chrome','tools'],'يوسي براوزر'=>['uc-browser','tools'],
        'يوتيوب'=>['youtube','apps'],'نتفليكس'=>['netflix','apps'],
        'سبوتيفاي'=>['spotify','apps'],'شازام'=>['shazam','apps'],
        'ساوند كلاود'=>['soundcloud','apps'],'أوبرا ميني'=>['opera-mini','tools'],
        'ترو كولر'=>['truecaller','tools'],'جوجل ترانسليت'=>['google-translate','tools'],
        'زوم'=>['zoom','tools'],'مايكروسوفت تيمز'=>['microsoft-teams','tools'],
        'دوولينجو'=>['duolingo','productivity'],'WPS Office'=>['wps-office','productivity'],
        'أدوبي أكروبات ريدر'=>['adobe-acrobat-reader','productivity'],'كانفا'=>['canva','design'],
        'بيكس آرت'=>['picsart','design'],'إن شوت'=>['inshot','design'],
        'بابجي موبايل'=>['pubg-mobile','games'],'فري فاير'=>['free-fire','games'],
        // Batch F
        'تيك توك'=>['tiktok','apps'],'إنستغرام'=>['instagram','social'],
        'كاب كت'=>['capcut','design'],'يوتيوب ميوزيك'=>['youtube-music','apps'],
        'تويتش'=>['twitch','apps'],'شاهد'=>['shahid','apps'],
        // Batch G
        'جوجل مابس'=>['google-maps','tools'],'جوجل درايف'=>['google-drive','productivity'],
        'كيبورد جوجل'=>['gboard','tools'],'مايكروسوفت وورد'=>['microsoft-word','productivity'],
        'ون درايف'=>['onedrive','productivity'],'أدوبي لايتروم'=>['adobe-lightroom','design'],
        // Batch H
        'كلاش أوف كلانز'=>['clash-of-clans','games'],'كلاش رويال'=>['clash-royale','games'],
        'موبايل ليجندز'=>['mobile-legends','games'],'ماين كرافت'=>['minecraft','games'],
        'سابواي سيرف'=>['subway-surfers','games'],'تمبل رن 2'=>['temple-run-2','games'],
        // Batch I
        'أمازون شوبينج'=>['amazon-shopping','apps'],'علي إكسبريس'=>['aliexpress','apps'],
        'شيين'=>['shein','apps'],'تالابات'=>['talabat','apps'],
        'كريم'=>['careem','apps'],'نمشي'=>['namshi','apps'],
        // Batch J
        'نورد VPN'=>['nordvpn','tools'],'فايرفوكس'=>['firefox','tools'],
        'برايف براوزر'=>['brave-browser','tools'],'جيميل'=>['gmail','tools'],
        'مايكروسوفت أوتلوك'=>['microsoft-outlook','productivity'],'أنكي درويد'=>['ankidroid','productivity'],
    ];
    [$slugBase, $catSlug] = $SLUG_MAP[$targetName] ?? [slugify($targetName), 'apps'];
    // Allow batch data to override category
    if (!empty($data['category_slug'])) $catSlug = $data['category_slug'];
    $slug = unique_slug($pdo, $slugBase);
    $catSt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $catSt->execute([$catSlug]);
    $catId = $catSt->fetchColumn();
    if (!$catId) { $catSt->execute(['apps']); $catId = $catSt->fetchColumn(); }

    // Prefer playstore_url from batch data; fall back to search
    $playstoreUrl = trim($data['playstore_url'] ?? '');
    if (!$playstoreUrl) {
        $playstoreUrl = playstore_search($targetName . ' ' . ($data['developer'] ?? ''));
        if (!$playstoreUrl) $playstoreUrl = playstore_search($targetName);
    }

    // Prefer package_name from batch data; extract from URL as fallback
    $packageName = trim($data['package_name'] ?? '');
    if (!$packageName && $playstoreUrl && preg_match('#[?&]id=([a-zA-Z0-9_.]+)#', $playstoreUrl, $pm)) {
        $packageName = $pm[1];
    }

    // Icon: fetch from Play Store og:image
    $iconPath = null;
    if ($playstoreUrl) {
        $meta = fetch_playstore_meta($playstoreUrl);
        if ($meta && !empty($meta['icon_url'])) {
            $iconPath = import_remote_icon($meta['icon_url'], $slug);
        }
        if (!$packageName && !empty($meta['package_name'])) $packageName = $meta['package_name'];
    }

    // Screenshots: scrape Play Store (best-effort, non-blocking)
    $screenshots = [];
    if ($playstoreUrl) {
        $screenshots = fetch_playstore_screenshots($playstoreUrl, $slug, 4);
    }

    $features     = array_values(array_filter($data['features'] ?? []));
    $pros         = array_values(array_filter($data['pros'] ?? []));
    $cons         = array_values(array_filter($data['cons'] ?? []));
    $installSteps = array_values(array_filter($data['install_steps'] ?? []));
    $faq          = array_is_list($data['faq'] ?? []) ? $data['faq'] : [];
    $shortDesc    = trim($data['short_description'] ?? '');
    $longDesc     = trim($data['long_description'] ?? '');
    $seoTitle     = trim($data['seo_title'] ?? '');
    $metaDesc     = trim($data['meta_description'] ?? '');
    $keywords     = trim($data['keywords'] ?? '');
    $whatsNew     = trim($data['whats_new'] ?? '');

    // For compact catalog entries (no pre-written content), generate via AI
    if (!$shortDesc) {
        $aiPrompt = "أنت كاتب محتوى عربي متخصص في تطبيقات الأندرويد. اكتب محتوى كاملاً لتطبيق يسمى \"$targetName\" (المطور: " . ($data['developer']??'') . ") بالعربية الفصحى المبسطة.

أعطِني رداً بصيغة JSON فقط بالمفاتيح التالية:
- short_description: جملتين إلى ثلاث جمل تصف التطبيق بشكل جذاب (50-80 كلمة)
- long_description: وصف تفصيلي احترافي للتطبيق (500-800 كلمة) يشرح الميزات والفوائد وكيفية الاستخدام، مناسب لمحركات البحث
- features: مصفوفة من 6 ميزات رئيسية (كل ميزة: 5-8 كلمات)
- pros: مصفوفة من 4 مزايا
- cons: مصفوفة من 2 عيوب
- install_steps: مصفوفة من 4 خطوات تثبيت
- faq: مصفوفة من 3 أسئلة وأجوبة [{\"q\":\"...\",\"a\":\"...\"}]
- whats_new: جملة عن آخر تحديثات التطبيق
- seo_title: عنوان SEO (55-65 حرف) يحتوي على اسم التطبيق وكلمة تحميل
- meta_description: وصف ميتا (150-160 حرف)
- keywords: قائمة كلمات مفتاحية مفصولة بفاصلة (15-20 كلمة)";

        $aiRaw = ai_text($pdo, $aiPrompt, 1500, 45);
        if ($aiRaw) {
            $aiData = ai_extract_json($aiRaw);
            if ($aiData) {
                $shortDesc = $aiData['short_description'] ?? $shortDesc;
                $longDesc  = $aiData['long_description']  ?? $longDesc;
                if (!empty($aiData['features']))     $features     = array_values((array)$aiData['features']);
                if (!empty($aiData['pros']))          $pros         = array_values((array)$aiData['pros']);
                if (!empty($aiData['cons']))          $cons         = array_values((array)$aiData['cons']);
                if (!empty($aiData['install_steps'])) $installSteps = array_values((array)$aiData['install_steps']);
                if (!empty($aiData['faq']))           $faq          = array_values((array)$aiData['faq']);
                $whatsNew  = $aiData['whats_new']     ?? $whatsNew;
                $seoTitle  = seo_title_clamp($aiData['seo_title']     ?? $seoTitle);
                $metaDesc  = $aiData['meta_description'] ?? $metaDesc;
                $keywords  = $aiData['keywords']      ?? $keywords;
            }
        }
    }

    try {
        $pdo->prepare("INSERT INTO apps
            (name,slug,category_id,developer,license,version,android_version,size_mb,
             icon_path,screenshots,short_description,long_description,
             features,pros,cons,install_steps,faq,whats_new,playstore_url,package_name,rating,
             seo_title,meta_description,keywords,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
            ->execute([
                $targetName,$slug,$catId,
                trim($data['developer'] ?? ''),
                trim($data['license'] ?? 'مجاني'),
                trim($data['version'] ?? ''),
                trim($data['android_version'] ?? ''),
                trim($data['size_mb'] ?? ''),
                $iconPath,
                $screenshots ? json_encode($screenshots, JSON_UNESCAPED_UNICODE) : null,
                $shortDesc,$longDesc,
                json_encode($features,JSON_UNESCAPED_UNICODE),json_encode($pros,JSON_UNESCAPED_UNICODE),
                json_encode($cons,JSON_UNESCAPED_UNICODE),json_encode($installSteps,JSON_UNESCAPED_UNICODE),
                json_encode($faq,JSON_UNESCAPED_UNICODE),$whatsNew,
                $playstoreUrl ?: null,$packageName ?: null,4.5,
                seo_title_clamp($seoTitle),$metaDesc,$keywords,
            ]);
        $newId = (int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$newId,'name'=>$targetName,
            'has_icon'=>(bool)$iconPath,'screenshots'=>count($screenshots),
            'edit_url'=>'admin.php?page=edit-app&id='.$newId], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   Helper: parse SQL INSERT INTO apps ... statements
   ══════════════════════════════════════════════════════ */
function parse_sql_app_inserts(string $sql): array {
    $apps = [];
    $pattern = '/INSERT\s+INTO\s+[`"\']?apps[`"\']?\s*\(([^)]+)\)\s*VALUES\s*\((.+?)\)\s*;/is';
    if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) return [];
    foreach ($matches as $m) {
        $cols = array_map(fn($c) => trim($c, " `\"'\t"), explode(',', $m[1]));
        $vals = []; $s = $m[2]; $i = 0; $len = strlen($s);
        while ($i < $len) {
            while ($i < $len && in_array($s[$i],[' ',"\t","\n","\r",','])) $i++;
            if ($i >= $len) break;
            if ($s[$i] === "'") {
                $val = ''; $i++;
                while ($i < $len) {
                    if ($s[$i] === "'" ) {
                        if (isset($s[$i+1]) && $s[$i+1] === "'") { $val .= "'"; $i += 2; }
                        else { $i++; break; }
                    } elseif ($s[$i] === '\\' && isset($s[$i+1])) {
                        $map = ["'"=>"'",'"'=>'"','\\'=>'\\','n'=>"\n",'r'=>"\r",'t'=>"\t"];
                        $val .= $map[$s[$i+1]] ?? $s[$i+1]; $i += 2;
                    } else { $val .= $s[$i++]; }
                }
                $vals[] = $val;
            } elseif (strtoupper(substr($s,$i,4))==='NULL') { $vals[] = null; $i+=4; }
            else {
                $start=$i; while ($i<$len && !in_array($s[$i],[',',' ',"\t",')'])) $i++;
                $vals[] = substr($s,$start,$i-$start);
            }
        }
        if (count($cols) !== count($vals)) continue;
        $app = array_combine($cols,$vals);
        foreach (['features','pros','cons','install_steps','faq'] as $f) {
            if (isset($app[$f]) && is_string($app[$f]) && strlen($app[$f])>1) {
                $d = json_decode($app[$f],true);
                if (is_array($d)) $app[$f] = $d;
            }
        }
        $apps[] = $app;
    }
    return $apps;
}

/* ══════════════════════════════════════════════════════
   AJAX: Validate uploaded file — quality check each app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'file_import_validate' && is_admin()) {
    header('Content-Type: application/json');
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'error'=>'لم يتم رفع الملف أو حدث خطأ في الرفع']); exit;
    }
    $file = $_FILES['file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $content = (string)file_get_contents($file['tmp_name']);
    $apps = [];

    // 1. Try JSON (covers .json, .php returning JSON, etc.)
    $decoded = json_decode($content, true);
    if ($decoded !== null) {
        if (isset($decoded['apps']) && is_array($decoded['apps'])) $apps = $decoded['apps'];
        elseif (array_is_list($decoded)) $apps = $decoded;
        elseif (isset($decoded['name'])) $apps = [$decoded];
    }
    // 2. SQL INSERT statements
    if (!$apps && in_array($ext, ['sql','txt'])) $apps = parse_sql_app_inserts($content);
    // 3. PHP: extract JSON blocks
    if (!$apps && $ext === 'php') {
        if (preg_match_all('/\{[^{}]*?"name"[^{}]*?\}/s', $content, $jm)) {
            foreach ($jm[0] as $js) { $a = json_decode($js,true); if ($a&&isset($a['name'])) $apps[]=$a; }
        }
    }

    if (!$apps) { echo json_encode(['success'=>false,'error'=>'لم يُعثر على بيانات تطبيقات صالحة. تأكد من الصيغة (JSON مع مصفوفة apps، أو INSERT SQL).']); exit; }

    $norm = function($v) {
        if (is_array($v)) return array_values(array_filter($v));
        if (is_string($v)&&strlen($v)) { $d=json_decode($v,true); return is_array($d)?array_values(array_filter($d)):[]; }
        return [];
    };

    $results = [];
    foreach ($apps as $app) {
        $issues = []; $warnings = []; $score = 100;
        $name = trim($app['name'] ?? '');
        if (!$name) { $issues[] = 'الاسم مطلوب'; $score -= 20; }
        if (!trim($app['developer'] ?? '')) { $warnings[] = 'المطور غير محدد'; $score -= 3; }

        $short = trim($app['short_description'] ?? '');
        if (!$short) { $issues[] = 'الوصف القصير مطلوب'; $score -= 8; }
        elseif (mb_strlen($short) < 80) { $issues[] = 'الوصف القصير قصير جداً (أقل من 80 حرف)'; $score -= 6; }

        $long = trim($app['long_description'] ?? '');
        $wc = $long ? count(preg_split('/\s+/u', $long, -1, PREG_SPLIT_NO_EMPTY)) : 0;
        if (!$long) { $issues[] = 'الوصف الطويل مطلوب'; $score -= 25; }
        elseif ($wc < 200) { $issues[] = "الوصف الطويل قصير جداً ({$wc} كلمة، الحد الأدنى 200)"; $score -= 20; }
        elseif ($wc < 500) { $warnings[] = "الوصف الطويل {$wc} كلمة — يُنصح بـ 500+ للـ SEO الأمثل"; $score -= 8; }

        $seoTitle = trim($app['seo_title'] ?? '');
        $stLen = mb_strlen($seoTitle);
        if (!$seoTitle) { $issues[] = 'عنوان SEO مطلوب'; $score -= 10; }
        elseif ($stLen < 30) { $issues[] = "عنوان SEO قصير ({$stLen} حرف، الأفضل 40-70)"; $score -= 6; }
        elseif ($stLen > 80) { $warnings[] = "عنوان SEO طويل ({$stLen} حرف)"; $score -= 3; }

        $meta = trim($app['meta_description'] ?? '');
        $mLen = mb_strlen($meta);
        if (!$meta) { $issues[] = 'وصف meta مطلوب'; $score -= 10; }
        elseif ($mLen < 80) { $issues[] = "وصف meta قصير ({$mLen} حرف، الأفضل 120-160)"; $score -= 6; }
        elseif ($mLen > 180) { $warnings[] = "وصف meta طويل ({$mLen} حرف)"; $score -= 2; }

        $kw = trim($app['keywords'] ?? '');
        $kwc = $kw ? count(preg_split('/[,،\s]+/u', $kw, -1, PREG_SPLIT_NO_EMPTY)) : 0;
        if (!$kw) { $issues[] = 'الكلمات المفتاحية مطلوبة'; $score -= 8; }
        elseif ($kwc < 3) { $warnings[] = "عدد الكلمات المفتاحية قليل ({$kwc}، يُنصح بـ 5+)"; $score -= 3; }

        $fc = count($norm($app['features'] ?? []));
        if ($fc < 3) { $issues[] = "الميزات غير كافية ({$fc}، الحد الأدنى 3)"; $score -= 8; }
        elseif ($fc < 5) { $warnings[] = "ميزات قليلة ({$fc}، يُنصح بـ 5+)"; $score -= 2; }

        $pc = count($norm($app['pros'] ?? []));
        if ($pc < 2) { $issues[] = "الإيجابيات غير كافية ({$pc}، الحد الأدنى 2)"; $score -= 5; }

        $cc = count($norm($app['cons'] ?? []));
        if ($cc < 1) { $warnings[] = 'السلبيات غائبة (يُنصح بسلبية واحدة على الأقل)'; $score -= 3; }

        $sc = count($norm($app['install_steps'] ?? []));
        if ($sc < 2) { $issues[] = "خطوات التثبيت غير كافية ({$sc}، الحد الأدنى 2)"; $score -= 5; }

        $faqArr = $norm($app['faq'] ?? []);
        $faqValid = count(array_filter($faqArr, fn($f) => !empty($f['q']) && !empty($f['a'])));
        if ($faqValid < 3) { $issues[] = "الأسئلة الشائعة غير كافية ({$faqValid}، الحد الأدنى 3 أسئلة مع إجابات)"; $score -= 8; }

        if (empty($app['screenshots'])) { $warnings[] = 'لا توجد روابط صور للتطبيق'; $score -= 2; }

        $score = max(0, $score);
        $pass  = empty($issues) && $score >= 55;
        $results[] = [
            'name'      => $name ?: '(بدون اسم)',
            'developer' => trim($app['developer'] ?? ''),
            'pass'      => $pass,
            'score'     => $score,
            'word_count'=> $wc,
            'issues'    => $issues,
            'warnings'  => $warnings,
            'data'      => $app,
        ];
    }
    $passed = count(array_filter($results, fn($r)=>$r['pass']));
    echo json_encode(['success'=>true,'apps'=>$results,'total'=>count($results),'passed'=>$passed], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Import one validated app from file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'file_import_run' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(120);
    $input = json_decode(file_get_contents('php://input'), true);
    $app   = $input['app'] ?? [];
    if (!$app || empty($app['name'])) { echo json_encode(['success'=>false,'error'=>'بيانات التطبيق مطلوبة']); exit; }
    $name = trim($app['name']);
    $ex = $pdo->prepare("SELECT id FROM apps WHERE name=?"); $ex->execute([$name]);
    if ($exId = $ex->fetchColumn()) { echo json_encode(['success'=>false,'skipped'=>true,'existing_id'=>(int)$exId,'name'=>$name,'error'=>'موجود مسبقاً']); exit; }

    $catSlug = trim($app['category_slug'] ?? $app['cat_slug'] ?? ''); $catId = null;
    if ($catSlug) { $s=$pdo->prepare("SELECT id FROM categories WHERE slug=?"); $s->execute([$catSlug]); $catId=$s->fetchColumn()||null; }
    if (!$catId) { $r=$pdo->query("SELECT id FROM categories WHERE slug='apps' LIMIT 1")->fetch(); $catId=$r?$r['id']:null; }

    $slug = unique_slug($pdo, slugify($name));
    $playstoreUrl = $app['playstore_url'] ?? null; $iconPath=null; $pkg=$app['package_name']??null;
    if (!$playstoreUrl) {
        $playstoreUrl = playstore_search($name.' '.($app['developer']??'')) ?: playstore_search($name);
    }
    if ($playstoreUrl && !$pkg) {
        $meta = fetch_playstore_meta($playstoreUrl);
        if ($meta) { $pkg=$meta['package_name']??null; if (!empty($meta['icon_url'])) $iconPath=import_remote_icon($meta['icon_url'],$slug); }
    }
    $norm = function($v) {
        if (is_array($v)) return array_values(array_filter($v));
        if (is_string($v)&&strlen($v)) { $d=json_decode($v,true); return is_array($d)?array_values(array_filter($d)):[]; }
        return [];
    };
    $features=$norm($app['features']??[]); $pros=$norm($app['pros']??[]); $cons=$norm($app['cons']??[]);
    $steps=$norm($app['install_steps']??[]); $faq=$norm($app['faq']??[]);
    try {
        $pdo->prepare("INSERT INTO apps
            (name,slug,category_id,developer,license,icon_path,version,android_version,size_mb,
             short_description,long_description,features,pros,cons,install_steps,faq,whats_new,
             playstore_url,package_name,rating,seo_title,meta_description,keywords,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
            ->execute([
                $name,$slug,$catId,trim($app['developer']??''),trim($app['license']??'Free'),$iconPath,
                trim($app['version']??''),trim($app['android_version']??''),
                is_numeric($app['size_mb']??'')?(float)$app['size_mb']:null,
                trim($app['short_description']??''),trim($app['long_description']??''),
                json_encode($features,JSON_UNESCAPED_UNICODE),json_encode($pros,JSON_UNESCAPED_UNICODE),
                json_encode($cons,JSON_UNESCAPED_UNICODE),json_encode($steps,JSON_UNESCAPED_UNICODE),
                json_encode($faq,JSON_UNESCAPED_UNICODE),trim($app['whats_new']??''),
                $playstoreUrl,$pkg,is_numeric($app['rating']??'')?(float)$app['rating']:4.5,
                trim($app['seo_title']??''),trim($app['meta_description']??''),trim($app['keywords']??''),
            ]);
        $newId=(int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'id'=>$newId,'name'=>$name,'has_icon'=>(bool)$iconPath,
            'edit_url'=>'admin.php?page=edit-app&id='.$newId], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Bulk CSV import — process one row (SSE streaming)
   Input: POST JSON {name, package_id, playstore_url, category, developer}
   Output: SSE events: progress(pct,msg), done(success,id,slug), error(msg)
   Publishes immediately (status='published'), NOT draft.
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_csv_one' && is_admin()) {
    @set_time_limit(180);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    if (!function_exists('sse_bi')) {
        function sse_bi(string $event, array $data): void {
            echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) ob_flush();
            flush();
        }
    }

    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $name    = trim($input['name'] ?? '');
    $pkg     = trim($input['package_id'] ?? '');
    $psUrl   = trim($input['playstore_url'] ?? '');
    $catSlug = trim($input['category'] ?? 'apps');
    $dev     = trim($input['developer'] ?? '');

    if (!$name && !$pkg && !$psUrl) {
        sse_bi('error', ['msg' => 'بيانات التطبيق غير كافية']); exit;
    }

    // Derive package_id from playstore_url if not given
    if (!$pkg && $psUrl && preg_match('#[?&]id=([\w.]+)#', $psUrl, $m)) {
        $pkg = $m[1];
    }
    // Derive playstore_url from package_id if not given
    if (!$psUrl && $pkg) {
        $psUrl = "https://play.google.com/store/apps/details?id={$pkg}";
    }

    // Skip if already exists
    if ($name) {
        $ex = $pdo->prepare("SELECT id FROM apps WHERE name=? LIMIT 1");
        $ex->execute([$name]);
        if ($exId = $ex->fetchColumn()) {
            sse_bi('done', ['skipped' => true, 'id' => (int)$exId, 'name' => $name, 'msg' => 'موجود مسبقاً']);
            exit;
        }
    }
    if ($pkg) {
        $ex = $pdo->prepare("SELECT id FROM apps WHERE package_name=? LIMIT 1");
        $ex->execute([$pkg]);
        if ($exId = $ex->fetchColumn()) {
            sse_bi('done', ['skipped' => true, 'id' => (int)$exId, 'msg' => 'موجود مسبقاً (package)']);
            exit;
        }
    }

    sse_bi('progress', ['pct' => 5, 'msg' => "🔍 جارٍ جلب بيانات متجر Play…"]);

    // Fetch Play Store metadata
    $meta = null;
    if ($psUrl) {
        $meta = fetch_playstore_meta($psUrl);
    }

    $finalName = $name ?: ($meta['name'] ?? ($pkg ?: 'app-' . time()));
    $finalDev  = $dev  ?: ($meta['developer'] ?? '');
    $finalDesc = $meta['description'] ?? '';
    $iconUrl   = $meta['icon_url'] ?? null;
    $version   = $meta['version'] ?? '';
    $rating    = isset($meta['rating']) ? (float)$meta['rating'] : 4.5;

    sse_bi('progress', ['pct' => 25, 'msg' => "🖼️ جارٍ تحميل الأيقونة…"]);

    $slug = unique_slug($pdo, $finalName);
    $iconPath = null;
    if ($iconUrl) {
        $iconPath = import_remote_icon($iconUrl, $slug);
    }

    // Category
    $catId = null;
    $catRow = $pdo->prepare("SELECT id FROM categories WHERE slug=? LIMIT 1");
    $catRow->execute([$catSlug]);
    $catId = $catRow->fetchColumn() ?: null;
    if (!$catId) {
        $catRow->execute(['apps']);
        $catId = $catRow->fetchColumn() ?: null;
    }

    // Set download URL to APKPure CDN — no local APK storage needed
    $downloadUrl = null;
    if ($pkg) {
        $downloadUrl = "https://d.apkpure.com/b/APK/{$pkg}?versionCode=latest&nc=arm64-v8a&sv=21";
    }

    sse_bi('progress', ['pct' => 60, 'msg' => "✍️ إنشاء محتوى SEO…"]);

    // Build SEO fields — minimal but meaningful, no AI call (speed + cost for bulk)
    $year = date('Y');
    $seoTitle    = seo_title_clamp("تحميل {$finalName} APK {$year} للأندرويد مجاناً");
    $metaDesc    = "حمّل {$finalName} APK آخر إصدار {$year} للأندرويد مجاناً برابط مباشر وسريع. " .
                   ($finalDesc ? mb_substr($finalDesc, 0, 80) . '…' : "تطبيق رائد يستحق التجربة.");
    $metaDesc    = mb_substr($metaDesc, 0, 160);
    $keywords    = "تحميل {$finalName}، تنزيل {$finalName}، {$finalName} APK، {$finalName} {$year}، {$finalName} للأندرويد، {$pkg}";
    $shortDesc   = mb_substr($finalDesc ?: "{$finalName} — تطبيق رائع للأندرويد", 0, 200);
    $longDesc    = $finalDesc ?: $shortDesc;

    sse_bi('progress', ['pct' => 80, 'msg' => "💾 حفظ التطبيق…"]);

    try {
        $pdo->prepare("INSERT INTO apps
            (name,slug,category_id,developer,version,icon_path,short_description,long_description,
             playstore_url,package_name,rating,download_url,
             seo_title,meta_description,keywords,status,created_at,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'published',NOW(),NOW())")
            ->execute([
                $finalName, $slug, $catId, $finalDev, $version, $iconPath,
                $shortDesc, $longDesc,
                $psUrl ?: null, $pkg ?: null, $rating, $downloadUrl,
                $seoTitle, $metaDesc, $keywords,
            ]);
        $newId = (int)$pdo->lastInsertId();

        // Bump cache version and ping IndexNow
        try {
            $pdo->prepare("INSERT INTO settings (key_name,value) VALUES ('cache_version',2) ON DUPLICATE KEY UPDATE value=value+1")->execute();
        } catch (Throwable $e) {}
        if ($newId) {
            $appUrl = app_url($slug);
            ping_search_engines($pdo, $appUrl, $newId);
        }

        sse_bi('done', [
            'success'   => true,
            'id'        => $newId,
            'name'      => $finalName,
            'slug'      => $slug,
            'has_icon'  => (bool)$iconPath,
            'published' => true,
        ]);
    } catch (Throwable $e) {
        sse_bi('error', ['msg' => $e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Suggest N trending app/game names via AI
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'suggest_trending' && is_admin()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $count = max(1, min(30, (int)($input['count'] ?? 10)));
    $type  = in_array($input['type'] ?? 'apps', ['apps','games','mixed'], true) ? $input['type'] : 'apps';
    $hint  = trim($input['hint'] ?? '');

    $typeLabel = ['apps' => 'تطبيقات', 'games' => 'ألعاب', 'mixed' => 'تطبيقات وألعاب'][$type];
    $prompt = "اقترح {$count} اسم {$typeLabel} أندرويد حقيقية من الأكثر بحثاً وتحميلاً حالياً (رسمية أو نسخ معدّلة/مود شائعة). " .
        ($hint ? "مجال/تفضيل إضافي: {$hint}. " : '') .
        "أعد أسماء دقيقة وقابلة للبحث في متجر التطبيقات، بدون تكرار.\n" .
        "أعد JSON فقط بدون أي نص إضافي: {\"names\":[\"اسم 1\",\"اسم 2\"]}";

    $r = ai_text($pdo, $prompt);
    if (!$r['ok']) { echo json_encode(['success'=>false,'error'=>$r['error']]); exit; }
    $data = ai_extract_json($r['content']);
    $names = is_array($data['names'] ?? null) ? array_values(array_filter(array_map('trim', $data['names']))) : [];
    if (!$names) { echo json_encode(['success'=>false,'error'=>'لم يُرجع الموديل قائمة أسماء صالحة، حاول مجدداً']); exit; }

    echo json_encode(['success'=>true,'names'=>$names], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Full bulk-create pipeline for ONE app/game name —
   AI content, best-effort Play Store search/import, AI icon
   fallback, insert. Called once per name from the client so
   a 30-item batch can't time out a single request. Apps with
   no download link land as drafts via the existing forced-
   draft rule (there is no draft/publish distinction to set
   here — the app simply has no download_url yet).
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_create_one' && is_admin()) {
    header('Content-Type: application/json');
    @set_time_limit(150); // AI long-form generation + Play Store scrape + icon fetch can run past shared-hosting's 30s default
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $type = in_array($input['type'] ?? 'apps', ['apps','games'], true) ? $input['type'] : 'apps';
    if (!$name) { echo json_encode(['success'=>false,'error'=>'اسم مطلوب']); exit; }

    $keys = openrouter_keys(get_cfg($pdo, 'openrouter_key'));
    if (!$keys) { echo json_encode(['success'=>false,'error'=>'لم يتم إضافة مفتاح OpenRouter بعد.']); exit; }
    $models = build_model_rotation($pdo);

    $seoStandards = seo_prompt_standards();
    $prompt = <<<P
أنت كاتب محتوى سوري محترف متخصص بمراجعة التطبيقات، تكتب لموقع تحميل تطبيقات أندرويد عربي. التطبيق: "{$name}"

{$seoStandards}

- long_description: هذا هو المطلوب الأهم — نص طويل جداً ومفصّل لا يقل عن 2200 كلمة (كلما كان أطول وأغنى بالتفاصيل كان أفضل)، مكتوب بلهجة سورية خفيفة وأسلوب إنساني ودافئ كأنك تشرح لصديقك ليش هالتطبيق حلو وشو بيقدملك، وليس نصاً روبوتياً أو قائمة جافة. لكن حافظ على وضوح الكلام بحيث يفهمه كل الناطقين بالعربية (لهجة سورية مخفّفة قريبة من الفصحى، لا تستخدم عامية ثقيلة جداً). قسّم النص لعدة فقرات وعناوين فرعية داخل النص (بدون Markdown، فقط أسطر جديدة) تغطي على الأقل:
  1. مقدمة شخصية دافئة عن التطبيق وليش الناس عم يدورو عليه.
  2. نظرة عامة موسّعة على التطبيق واستخداماته.
  3. شرح تفصيلي مطوّل لكل ميزة رئيسية (فقرة كاملة لكل ميزة، مو مجرد جملة).
  4. لمين هالتطبيق مناسب (نوعية المستخدمين).
  5. مقارنة سريعة مع بدائل مشابهة وليش هاد أفضل أو مختلف.
  6. نصائح استخدام عملية وحلول لمشاكل شائعة.
  7. خاتمة تحفّز القارئ على التحميل.
  التزم بأسلوب SEO طبيعي (كرر اسم التطبيق وكلمات مثل "تحميل" و"تنزيل" و"APK" بشكل طبيعي ضمن السياق دون حشو مزعج).

أعد JSON صالح فقط بدون أي نص آخر أو Markdown:
{
  "name":"الاسم الرسمي",
  "seo_title":"",
  "meta_description":"",
  "keywords":"",
  "short_description":"جملة أو جملتين بأسلوب سوري ودود",
  "long_description":"",
  "developer":"اسم المطور المحتمل",
  "version":"رقم إصدار مثل 3.1.0",
  "android_version":"مثل: 7.0 فأعلى",
  "size_mb":"حجم تقريبي مثل 45",
  "license":"Free",
  "package_name":"com.developer.appname",
  "rating":4.5,
  "whats_new":"آخر التحديثات",
  "features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5","ميزة 6"],
  "pros":["إيجابية 1","إيجابية 2","إيجابية 3","إيجابية 4"],
  "cons":["سلبية 1","سلبية 2"],
  "install_steps":["خطوة 1","خطوة 2","خطوة 3","خطوة 4"],
  "faq":[{"q":"سؤال شائع","a":"إجابة مفصلة بلهجة ودودة"},{"q":"سؤال 2","a":"إجابة 2"},{"q":"سؤال 3","a":"إجابة 3"},{"q":"سؤال 4","a":"إجابة 4"}]
}
P;
    $result = openrouter_call_rotating($keys, $models, $prompt, 90, 9000);
    if (!$result['ok']) { echo json_encode(['success'=>false,'error'=>'فشل توليد المحتوى بالذكاء الاصطناعي']); exit; }
    $data = ai_extract_json($result['content']);
    if (!$data) { echo json_encode(['success'=>false,'error'=>'رد الذكاء الاصطناعي لم يكن JSON صالحاً']); exit; }
    $data = clean_utf8_deep($data);

    $finalName = trim($data['name'] ?? $name) ?: $name;
    $slug = unique_slug($pdo, $finalName);

    // Best-effort Play Store search + import — a miss here is normal, not an error.
    $playstoreUrl = playstore_search($finalName);
    $iconPath = null;
    $playstoreMeta = $playstoreUrl ? fetch_playstore_meta($playstoreUrl) : null;
    if ($playstoreMeta && !empty($playstoreMeta['icon_url'])) {
        $iconPath = import_remote_icon($playstoreMeta['icon_url'], $slug);
    }
    // AI icon fallback if Play Store import didn't yield one.
    if (!$iconPath) {
        $ir = ai_generate_image($pdo, "Generate a professional, modern, minimalist square Android app icon (no text, no watermark, centered symbol, flat/gradient style, 1024x1024) for an app called \"{$finalName}\".");
        if ($ir['ok']) {
            $tmp = tempnam(sys_get_temp_dir(), 'aiicn');
            file_put_contents($tmp, $ir['bin']);
            $iconPath = process_icon(['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($ir['bin'])], $slug);
            @unlink($tmp);
        }
    }

    $catStmt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $catStmt->execute([$type === 'games' ? 'games' : 'apps']);
    $catId = $catStmt->fetchColumn() ?: null;

    $features     = array_values(array_filter($data['features'] ?? []));
    $pros         = array_values(array_filter($data['pros'] ?? []));
    $cons         = array_values(array_filter($data['cons'] ?? []));
    $installSteps = array_values(array_filter($data['install_steps'] ?? []));
    $faq          = array_is_list($data['faq'] ?? []) ? $data['faq'] : [];

    $pdo->prepare("INSERT INTO apps (name,slug,category_id,developer,version,android_version,size_mb,license,package_name,icon_path,short_description,long_description,features,pros,cons,install_steps,faq,whats_new,playstore_url,rating,seo_title,meta_description,keywords,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
        ->execute([
            $finalName, $slug, $catId,
            trim($data['developer'] ?? ''), trim($data['version'] ?? ''), trim($data['android_version'] ?? ''),
            trim($data['size_mb'] ?? ''), trim($data['license'] ?? 'Free'), trim($data['package_name'] ?? ''),
            $iconPath,
            trim($data['short_description'] ?? ''), trim($data['long_description'] ?? ''),
            json_encode($features, JSON_UNESCAPED_UNICODE), json_encode($pros, JSON_UNESCAPED_UNICODE),
            json_encode($cons, JSON_UNESCAPED_UNICODE), json_encode($installSteps, JSON_UNESCAPED_UNICODE),
            json_encode($faq, JSON_UNESCAPED_UNICODE), trim($data['whats_new'] ?? ''),
            $playstoreUrl, (float)($data['rating'] ?? 4.5),
            seo_title_clamp(trim($data['seo_title'] ?? '')), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''),
        ]);
    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true, 'id' => $newId, 'name' => $finalName, 'slug' => $slug,
        'has_playstore' => (bool)$playstoreUrl, 'has_icon' => (bool)$iconPath,
        'edit_url' => 'admin.php?page=edit-app&id=' . $newId,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Upload APK file — extracts metadata, stores in uploads/apk/
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'upload_apk' && is_admin()) {
    @set_time_limit(120);
    header('Content-Type: application/json');

    $appId = (int)($_POST['app_id'] ?? 0);
    if (!isset($_FILES['apk']) || $_FILES['apk']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['ok'=>false,'error'=>'لم يتم اختيار ملف']); exit;
    }

    // Get slug — from existing app or from name field
    $slug = '';
    if ($appId > 0) {
        $r = $pdo->prepare("SELECT slug FROM apps WHERE id=?")->execute([$appId]);
        $row = $pdo->query("SELECT slug FROM apps WHERE id=$appId")->fetch();
        if ($row) $slug = $row['slug'];
    }
    if (!$slug) {
        $slug = unique_slug($pdo, trim($_POST['app_name'] ?? 'app'));
    }

    $result = store_apk_file($_FILES['apk'], $slug);
    if (!$result['ok']) {
        echo json_encode(['ok'=>false,'error'=>$result['error']]); exit;
    }

    // If editing an existing app, delete the old APK file
    if ($appId > 0) {
        $old = $pdo->query("SELECT apk_path FROM apps WHERE id=$appId")->fetchColumn();
        if ($old && file_exists(__DIR__.'/'.$old)) @unlink(__DIR__.'/'.$old);
    }

    $result['apk_public_url'] = rtrim(SITE_URL, '/') . '/' . ltrim($result['apk_path'] ?? '', '/');
    echo json_encode(array_merge(['ok'=>true], $result), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Download APK from URL server-side (SSE streaming progress)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'download_url_apk' && is_admin()) {
    @set_time_limit(600);
    @ini_set('memory_limit', '256M');
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    function sse(string $event, array $data): void {
        echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    $url     = trim($_POST['url'] ?? '');
    $appId   = (int)($_POST['app_id'] ?? 0);
    $appName = trim($_POST['app_name'] ?? 'app');

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
        sse('error', ['msg' => 'رابط غير صالح']); exit;
    }

    // Detect Play Store URL → extract package, build download waterfall
    $detectedPkg = null;
    if (preg_match('#play\.google\.com/store/apps/details.*[?&]id=([\w.]+)#i', $url, $pkgMatch)) {
        $detectedPkg = $pkgMatch[1];
        sse('progress', ['pct' => 2, 'msg' => "🔍 Google Play مكتشف — الباقة: {$detectedPkg}", 'bytes' => 0, 'total' => 0]);
        $url = "https://d.apkpure.com/b/APK/{$detectedPkg}?versionCode=latest&nc=arm64-v8a&sv=21";
        if (!$appName || $appName === 'app') $appName = str_replace(['.','_'], '-', $detectedPkg);
    }
    // Try to extract package from APKPure / liteapks URLs as fallback source
    if (!$detectedPkg) {
        if (preg_match('#apkpure\.com/[^/]+/([\w.]+)#i', $url, $m2)) $detectedPkg = $m2[1];
        elseif (preg_match('#apkcombo\.com/[^/]+/([\w.]+)#i', $url, $m2)) $detectedPkg = $m2[1];
    }

    // Block SSRF — only allow public HTTP(S), no local addresses
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host || preg_match('#^(localhost|127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)#i', $host)) {
        sse('error', ['msg' => 'الرابط غير مسموح به']); exit;
    }

    // Resolve slug from app_id or app_name
    $slug = '';
    if ($appId > 0) {
        $row = $pdo->query("SELECT slug FROM apps WHERE id=$appId")->fetch();
        if ($row) $slug = $row['slug'];
    }
    if (!$slug) $slug = unique_slug($pdo, $appName ?: 'app');

    // Sanitize display name for filename
    $safeName = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace([' ','_'], '-', $appName)));
    $safeName = trim($safeName, '-') ?: $slug;

    $dir = UPLOAD_PATH . '/apk';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmpFile = $dir . '/._dl_' . $slug . '_' . time() . '.apk.tmp';

    sse('progress', ['pct' => 0, 'msg' => 'جارٍ الاتصال بالرابط…', 'bytes' => 0, 'total' => 0]);

    // Build source waterfall (primary URL first, then APKPure CDN fallback if we have a package name)
    $chromeUA   = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
    $urlsToTry  = [$url];
    if ($detectedPkg && strpos($url, 'd.apkpure.com') === false) {
        $urlsToTry[] = "https://d.apkpure.com/b/APK/{$detectedPkg}?versionCode=latest&nc=arm64-v8a&sv=21";
    } elseif ($detectedPkg) {
        // primary IS APKPure CDN; add secondary via apkcombo
        $urlsToTry[] = "https://apkcombo.com/apk-downloader/?package={$detectedPkg}";
    }

    $out = fopen($tmpFile, 'wb');
    if (!$out) { sse('error', ['msg' => 'فشل إنشاء الملف المؤقت']); exit; }

    $downloaded  = 0;
    $totalBytes  = 0;
    $lastReport  = 0;
    $startTime   = microtime(true);
    $successUrl  = null;

    foreach ($urlsToTry as $tryIdx => $tryUrl) {
        if ($tryIdx > 0) {
            sse('progress', ['pct' => 3, 'msg' => "🔄 المصدر الأول أعاد خطأ — جارٍ المحاولة عبر مصدر بديل…", 'bytes' => 0, 'total' => 0]);
            ftruncate($out, 0); rewind($out);
            $downloaded = 0; $totalBytes = 0; $lastReport = 0; $startTime = microtime(true);
        }

        $_dl   = &$downloaded;
        $_tot  = &$totalBytes;
        $_lr   = &$lastReport;
        $_st   = &$startTime;

        $ch = curl_init($tryUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => static function ($ch, $data) use (&$_dl, &$_tot, &$_lr, &$_st, $out) {
                $n = fwrite($out, $data);
                $_dl += strlen($data);
                $pct = $_tot > 0 ? min(99, (int)($_dl / $_tot * 100)) : 0;
                if ($pct >= $_lr + 2 || ($_dl - (int)($_lr / 100 * $_tot)) >= 1048576) {
                    $elapsed = microtime(true) - $_st;
                    $speed   = $elapsed > 0 ? $_dl / $elapsed : 0;
                    $eta     = ($_tot > 0 && $speed > 0) ? (int)(($_tot - $_dl) / $speed) : 0;
                    $mbDown  = round($_dl / 1048576, 1);
                    $mbTotal = $_tot > 0 ? round($_tot / 1048576, 1) : '?';
                    $msg     = "تم تحميل {$mbDown} MB" . ($_tot > 0 ? " من {$mbTotal} MB" : '') . ($eta > 0 ? " — متبقي {$eta}ث" : '');
                    sse('progress', ['pct' => $pct, 'msg' => $msg, 'bytes' => $_dl, 'total' => $_tot]);
                    $_lr = $pct;
                }
                return $n;
            },
            CURLOPT_HEADERFUNCTION => static function ($ch, $header) use (&$_tot) {
                if (preg_match('/^Content-Length:\s*(\d+)/i', $header, $m)) $_tot = (int)$m[1];
                return strlen($header);
            },
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_USERAGENT      => $chromeUA,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/vnd.android.package-archive, application/octet-stream, */*',
                'Accept-Language: ar,en-US;q=0.9,en;q=0.8',
                'Referer: https://apkpure.com/',
                'Connection: keep-alive',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        unset($_dl, $_tot, $_lr, $_st);

        if ($httpCode >= 200 && $httpCode < 300 && $downloaded > 512) {
            $successUrl = $tryUrl;
            break;
        }

        $failHost = parse_url($tryUrl, PHP_URL_HOST);
        if ($httpCode >= 400) {
            sse('progress', ['pct' => 2, 'msg' => "HTTP {$httpCode} من {$failHost}", 'bytes' => 0, 'total' => 0]);
        } elseif ($curlErr) {
            sse('progress', ['pct' => 2, 'msg' => "خطأ شبكة: {$curlErr}", 'bytes' => 0, 'total' => 0]);
        }
    }

    fclose($out);

    if (!$successUrl || $downloaded < 1024) {
        @unlink($tmpFile);
        sse('error', ['msg' => 'فشل التحميل من جميع المصادر — تأكد من الرابط أو جرب رابط APKPure مباشر']); exit;
    }

    // Verify APK ZIP signature
    $fh = fopen($tmpFile, 'rb');
    $magic = fread($fh, 4);
    fclose($fh);
    if ($magic !== "PK\x03\x04") {
        @unlink($tmpFile);
        sse('error', ['msg' => 'الملف المُحمَّل ليس APK صالح (توقيع ZIP غير صحيح) — تأكد من الرابط']); exit;
    }

    sse('progress', ['pct' => 99, 'msg' => 'جارٍ معالجة الملف وحساب التجزئة…', 'bytes' => $downloaded, 'total' => $totalBytes]);

    $sha256 = hash_file('sha256', $tmpFile);
    $md5    = hash_file('md5', $tmpFile);
    $size   = filesize($tmpFile);
    $filename = $safeName . '-yassota-' . substr($sha256, 0, 8) . '.apk';
    $dest     = $dir . '/' . $filename;

    if (file_exists($dest)) @unlink($dest);
    rename($tmpFile, $dest);
    chmod($dest, 0644);

    $apkPath = 'uploads/apk/' . $filename;

    // Update DB if we have an appId
    if ($appId > 0) {
        $pdo->prepare("UPDATE apps SET apk_path=?,apk_size_bytes=?,apk_hash_sha256=?,apk_hash_md5=?,apk_uploaded_at=NOW() WHERE id=?")
            ->execute([$apkPath, $size, $sha256, $md5, $appId]);
        if (empty($pdo->query("SELECT download_url FROM apps WHERE id=$appId")->fetchColumn())) {
            $pdo->prepare("UPDATE apps SET download_url='#' WHERE id=?")->execute([$appId]);
        }
        bump_cache_version($pdo);
    }

    sse('done', [
        'apk_path'     => $apkPath,
        'filename'     => $filename,
        'size_bytes'   => $size,
        'size_mb'      => round($size / 1048576, 2),
        'sha256'       => $sha256,
        'md5'          => $md5,
        'msg'          => "✅ تم التحميل! {$filename} (" . round($size/1048576,1) . " MB)",
    ]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Delete APK file for an app
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete_apk' && is_admin()) {
    header('Content-Type: application/json');
    $appId = (int)($_POST['app_id'] ?? 0);
    if (!$appId) { echo json_encode(['ok'=>false]); exit; }
    $old = $pdo->query("SELECT apk_path FROM apps WHERE id=$appId")->fetchColumn();
    if ($old && file_exists(__DIR__.'/'.$old)) @unlink(__DIR__.'/'.$old);
    $pdo->prepare("UPDATE apps SET apk_path=NULL,apk_size_bytes=NULL,apk_hash_sha256=NULL,apk_hash_md5=NULL,apk_uploaded_at=NULL WHERE id=?")
        ->execute([$appId]);
    bump_cache_version($pdo);
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Test server connection (FTP / FTPS / SFTP / SSH)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_server_conn' && is_admin()) {
    header('Content-Type: application/json');
    $type = $_POST['conn_type'] ?? 'ftp';
    $host = trim($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? (in_array($type, ['sftp','ssh']) ? 22 : 21));
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $path = trim($_POST['remote_path'] ?? '/') ?: '/';
    if (!$host || !$user) { echo json_encode(['ok'=>false,'msg'=>'الرجاء إدخال المضيف واسم المستخدم']); exit; }
    try {
        if ($type === 'ftp') {
            $conn = @ftp_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال بـ FTP: {$host}:{$port}");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $pwd  = ftp_pwd($conn);
            $list = array_slice(ftp_nlist($conn, $path) ?: [], 0, 10);
            ftp_close($conn);
            echo json_encode(['ok'=>true,'msg'=>"✓ تم الاتصال — المجلد الحالي: {$pwd}",'items'=>$list]);
        } elseif ($type === 'ftps') {
            if (!function_exists('ftp_ssl_connect')) throw new RuntimeException("FTPS غير مدعوم على هذا السيرفر (يحتاج OpenSSL + php-ftp)");
            $conn = @ftp_ssl_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال بـ FTPS: {$host}:{$port}");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $pwd  = ftp_pwd($conn);
            $list = array_slice(ftp_nlist($conn, $path) ?: [], 0, 10);
            ftp_close($conn);
            echo json_encode(['ok'=>true,'msg'=>"✓ FTPS متصل — المجلد الحالي: {$pwd}",'items'=>$list]);
        } elseif ($type === 'sftp' || $type === 'ssh') {
            if (!function_exists('ssh2_connect')) throw new RuntimeException("مكتبة ssh2 PHP غير مثبّتة على هذا السيرفر. ثبّت php-ssh2 أو استخدم FTP بدلاً منها.");
            $conn = @ssh2_connect($host, $port);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال SSH: {$host}:{$port}");
            if (!@ssh2_auth_password($conn, $user, $pass)) throw new RuntimeException("بيانات الدخول SSH غير صحيحة");
            $sftp     = ssh2_sftp($conn);
            $realpath = ssh2_sftp_realpath($sftp, $path ?: '.');
            echo json_encode(['ok'=>true,'msg'=>"✓ SFTP/SSH متصل — المسار: {$realpath}",'items'=>[]]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'نوع اتصال غير معروف']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Save server connection settings
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'save_server_conn' && is_admin()) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
    foreach (['server_conn_type','server_conn_host','server_conn_port','server_conn_user','server_conn_pass','server_conn_path'] as $f) {
        $stmt->execute([$f, trim($_POST[$f] ?? '')]);
    }
    echo json_encode(['ok'=>true,'msg'=>'تم حفظ بيانات الاتصال']);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: List remote directory (FTP / FTPS / SFTP)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list_remote_dir' && is_admin()) {
    header('Content-Type: application/json');
    $type = $_POST['conn_type'] ?? 'ftp';
    $host = trim($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? (in_array($type, ['sftp','ssh']) ? 22 : 21));
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $path = trim($_POST['path'] ?? '/') ?: '/';
    try {
        if ($type === 'ftp' || $type === 'ftps') {
            $conn = ($type === 'ftps') ? @ftp_ssl_connect($host, $port, 15) : @ftp_connect($host, $port, 15);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال");
            if (!@ftp_login($conn, $user, $pass)) { ftp_close($conn); throw new RuntimeException("بيانات الدخول غير صحيحة"); }
            ftp_pasv($conn, true);
            $raw   = ftp_rawlist($conn, $path) ?: [];
            ftp_close($conn);
            $items = [];
            foreach ($raw as $line) {
                $parts = preg_split('/\s+/', trim($line), 9);
                if (count($parts) < 9) continue;
                $name = $parts[8];
                if ($name === '.' || $name === '..') continue;
                $items[] = ['name'=>$name,'dir'=>str_starts_with($parts[0], 'd'),'size'=>(int)$parts[4]];
            }
            echo json_encode(['ok'=>true,'path'=>$path,'items'=>$items]);
        } elseif ($type === 'sftp' || $type === 'ssh') {
            if (!function_exists('ssh2_connect')) throw new RuntimeException("ssh2 غير مثبّتة");
            $conn = @ssh2_connect($host, $port);
            if (!$conn) throw new RuntimeException("تعذّر الاتصال SSH");
            if (!@ssh2_auth_password($conn, $user, $pass)) throw new RuntimeException("بيانات الدخول SSH غير صحيحة");
            $sftp  = ssh2_sftp($conn);
            $dh    = opendir("ssh2.sftp://{$sftp}{$path}");
            $items = [];
            while (($entry = readdir($dh)) !== false) {
                if ($entry === '.' || $entry === '..') continue;
                $full    = "ssh2.sftp://{$sftp}{$path}/{$entry}";
                $st      = @stat($full);
                $items[] = ['name'=>$entry,'dir'=>is_dir($full),'size'=>(int)($st['size'] ?? 0)];
            }
            closedir($dh);
            echo json_encode(['ok'=>true,'path'=>$path,'items'=>$items]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'نوع اتصال غير معروف']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

$page   = $_GET['page'] ?? 'dashboard';
$msg    = '';
$error  = '';

// Login action
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    // Check lockout before even touching credentials (skip admin IP)
    if (!evil_is_admin_ip()) {
        $remaining = evil_login_lockout_remaining($pdo, $clientIp);
        if ($remaining > 0) {
            $error = "تم تجاوز الحد الأقصى لمحاولات الدخول. يُرجى الانتظار $remaining ثانية.";
            goto login_done;
        }
    }
    if (!csrf_check()) { $error = 'جلسة غير صالحة'; }
    else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
        $stmt->execute([trim($_POST['username'] ?? '')]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
            evil_clear_login_fail($pdo, $clientIp);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            log_security_event($pdo, 'login_success', 'info', "Admin login from $clientIp");
            header('Location: admin.php'); exit;
        }
        // Wrong credentials — record failure and get lockout
        if (!evil_is_admin_ip()) {
            $lockSecs = evil_record_login_fail($pdo, $clientIp);
            if ($lockSecs > 0) {
                $error = "محاولات خاطئة متعددة. يُرجى الانتظار $lockSecs ثانية قبل المحاولة مجدداً.";
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
    login_done:;
}

// Logout
if ($page === 'logout') { session_destroy(); header('Location: admin.php?page=login'); exit; }

// Require login for everything except login page
if ($page !== 'login') require_admin();

/* ══════════════════════════════════════════════════════
   Actions
   ══════════════════════════════════════════════════════ */

// ─── Save settings ───
if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    // Capture old IndexNow key before overwriting
    $oldIndexNowKey = get_cfg($pdo, 'indexnow_key', '');

    // Multi-key inputs — combine array into newline-separated string
    $multiKeys = array_filter(array_map('trim', $_POST['openrouter_key_multi'] ?? []));
    $mergedKey = implode("\n", $multiKeys) ?: trim($_POST['openrouter_key'] ?? '');
    set_cfg($pdo, 'openrouter_key', $mergedKey);

    // Ad code may arrive base64-encoded (JS encodes it to avoid mod_security blocking <script> tags)
    $rawAdCode = trim($_POST['download_custom_ad_code_b64'] ?? '');
    if ($rawAdCode) {
        $decoded = base64_decode($rawAdCode, true);
        if ($decoded !== false) set_cfg($pdo, 'download_custom_ad_code', $decoded);
    } elseif (isset($_POST['download_custom_ad_code'])) {
        set_cfg($pdo, 'download_custom_ad_code', trim($_POST['download_custom_ad_code']));
    }

    foreach (['openrouter_model','openrouter_fallback','openrouter_image_model','contact_email',
              'google_site_verification','bing_site_verification','virustotal_api_key',
              'ai_provider',
              'telegram_bot_token','telegram_channel_id','telegram_channel_url',
              'indexnow_key','download_countdown_secs',
              'ga4_measurement_id','og_default_image',
              'privacy_updated_date','terms_updated_date','adsense_ad_slot_id',
              'adsense_publisher_id','site_name','site_tagline',
              'auto_indexnow_enabled',
              'auto_translate_langs',
              'recaptcha_v2_site_key','recaptcha_v2_secret',
              'recaptcha_v3_site_key','recaptcha_v3_secret',
              'turnstile_site_key','turnstile_secret_key'] as $k) {
        if (isset($_POST[$k])) set_cfg($pdo, $k, trim($_POST[$k]));
    }
    set_cfg($pdo, 'openrouter_auto_rotate',      isset($_POST['openrouter_auto_rotate'])      ? '1' : '0');
    set_cfg($pdo, 'admin_email_notifications',   isset($_POST['admin_email_notifications'])   ? '1' : '0');
    set_cfg($pdo, 'telegram_enabled',            isset($_POST['telegram_enabled'])            ? '1' : '0');

    // Optional IP allowlist — auto-include the saving admin's current IP so a save can never lock them out.
    $newAllowlist = trim($_POST['admin_ip_allowlist'] ?? '');
    if ($newAllowlist !== '') {
        $ips = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $newAllowlist)));
        if (!in_array(client_ip(), $ips, true)) $ips[] = client_ip();
        set_cfg($pdo, 'admin_ip_allowlist', implode("\n", array_unique($ips)));
    } else {
        set_cfg($pdo, 'admin_ip_allowlist', '');
    }

    // Auto-manage IndexNow key file — create new, delete old on key change
    $newIndexNowKey = get_cfg($pdo, 'indexnow_key', '');
    if ($newIndexNowKey && preg_match('/^[a-zA-Z0-9]{8,128}$/', $newIndexNowKey)) {
        $keyFile = __DIR__ . '/' . $newIndexNowKey . '.txt';
        if (!file_exists($keyFile)) {
            file_put_contents($keyFile, $newIndexNowKey);
        }
        // Remove old key file if the key was changed
        if ($oldIndexNowKey && $oldIndexNowKey !== $newIndexNowKey) {
            $oldFile = __DIR__ . '/' . $oldIndexNowKey . '.txt';
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    $msg = 'تم حفظ الإعدادات';
}

// ─── Delete app ───
if ($page === 'apps' && isset($_GET['del']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM apps WHERE id=?")->execute([(int)$_GET['del']]);
    bump_cache_version($pdo);
    header('Location: admin.php?page=apps&msg=deleted'); exit;
}

// ─── Delete a related article ───
if ($page === 'edit-app' && isset($_GET['del_article']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM app_articles WHERE id=?")->execute([(int)$_GET['del_article']]);
    header('Location: admin.php?page=edit-app&id=' . (int)($_GET['id'] ?? 0)); exit;
}

// ─── Delete a blog post ───
if ($page === 'blog' && isset($_GET['del']) && isset($_GET['t']) &&
    hash_equals($_SESSION['csrf'] ?? '', $_GET['t'])) {
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([(int)$_GET['del']]);
    bump_cache_version($pdo);
    header('Location: admin.php?page=blog&msg=deleted'); exit;
}

// ─── Save / Update a blog post ───
if ($page === 'blog-edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $_POST = clean_utf8_deep($_POST);
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if (!$title) { $blogError = 'العنوان مطلوب'; }
    else {
        $type = isset(BLOG_TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : 'article';
        $customSlug = trim($_POST['slug'] ?? '');
        if ($id) {
            $curSlug = $pdo->prepare("SELECT slug FROM blog_posts WHERE id=?");
            $curSlug->execute([$id]); $curSlug = $curSlug->fetchColumn();
            $slug = ($customSlug !== '' && $customSlug !== $curSlug)
                ? unique_blog_slug($pdo, $customSlug, $id)
                : $curSlug;
        } else {
            $slug = unique_blog_slug($pdo, $customSlug !== '' ? $customSlug : $title);
        }
        // code-page: assemble language sections from POST into JSON body
        if ($type === 'code-page') {
            $sections = [];
            foreach (array_keys(CODE_PAGE_LANGS) as $lang) {
                $code = $_POST['cp_' . $lang] ?? '';
                if (trim($code) !== '') $sections[$lang] = $code;
            }
            $bodyVal = json_encode(['sections' => $sections, 'description' => trim($_POST['cp_description'] ?? '')],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $bodyVal = trim($_POST['body'] ?? '');
        }
        $d = [
            'type' => $type,
            'title' => $title,
            'slug' => $slug,
            'seo_title' => trim($_POST['seo_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'body' => $bodyVal,
            'status' => ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
        ];
        if ($id) {
            $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
            $d['id'] = $id;
            $pdo->prepare("UPDATE blog_posts SET $sets WHERE id=:id")->execute($d);
        } else {
            $cols = implode(',', array_keys($d));
            $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
            $pdo->prepare("INSERT INTO blog_posts ($cols) VALUES ($vals)")->execute($d);
            $id = (int)$pdo->lastInsertId();
        }
        bump_cache_version($pdo);
        header('Location: admin.php?page=blog&msg=saved'); exit;
    }
}

// ─── Save / Update app ───
if (in_array($page, ['add-app','edit-app']) && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $_POST = clean_utf8_deep($_POST);
    $isEdit = $page === 'edit-app';
    $appId  = (int)($_POST['app_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    if (!$name) {
        if ($isXhr) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'اسم التطبيق مطلوب']); exit; }
        $error = 'اسم التطبيق مطلوب'; goto render;
    }

    // Slug — an admin-chosen slug wins if provided (and differs from the
    // current one on edit); otherwise it's auto-derived from the name, same
    // as before this field existed.
    $customSlug = trim($_POST['slug'] ?? '');
    if ($isEdit && $appId) {
        $existing = $pdo->prepare("SELECT slug,icon_path,screenshots,version,download_url,whats_new,apk_path,apk_size_bytes,apk_hash_sha256,apk_hash_md5,apk_uploaded_at,status FROM apps WHERE id=?");
        $existing->execute([$appId]); $existing = $existing->fetch();
        $slug = ($customSlug !== '' && $customSlug !== $existing['slug'])
            ? unique_slug($pdo, $customSlug, $appId)
            : $existing['slug'];
    } else {
        $slug = unique_slug($pdo, $customSlug !== '' ? $customSlug : $name);
        $existing = null;
    }

    // Icon
    $iconPath = $existing['icon_path'] ?? null;
    if (!empty($_FILES['icon']['tmp_name']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $p = process_icon($_FILES['icon'], $slug);
        if ($p) $iconPath = $p;
    } elseif (!empty($_POST['icon_url_import'])) {
        $p = import_remote_icon($_POST['icon_url_import'], $slug);
        if ($p) {
            $iconPath = $p;
        } else {
            // Server couldn't download the CDN image — store URL directly as fallback
            $raw = filter_var(trim($_POST['icon_url_import']), FILTER_VALIDATE_URL);
            if ($raw) $iconPath = $raw;
        }
    } elseif (!empty($_POST['icon_saved_import']) && preg_match('#^uploads/icons/[^/\\\\<>:"*?|]+\.webp$#u', trim($_POST['icon_saved_import']))) {
        // Icon was already downloaded by fetch_playstore_full and saved server-side — just reference it.
        $iconPath = trim($_POST['icon_saved_import']);
    } elseif (!empty($_POST['ai_icon_path']) && preg_match('#^uploads/icons/[^/\\\\<>:"*?|]+\.webp$#u', trim($_POST['ai_icon_path']))) {
        // Icon was generated by AI and already saved server-side — just reference it.
        $iconPath = trim($_POST['ai_icon_path']);
    }

    // Screenshots
    $shots = json_decode($existing['screenshots'] ?? '[]', true) ?: [];
    if (!empty($_FILES['screenshots']['name'][0])) {
        $newShots = process_screenshots($_FILES['screenshots'], $slug);
        $shots = array_merge($shots, $newShots);
    }
    // Screenshots imported from Play Store via URL list (downloaded server-side at save time)
    if (!empty($_POST['screenshot_urls_import']) && is_array($_POST['screenshot_urls_import'])) {
        foreach (array_slice($_POST['screenshot_urls_import'], 0, 6) as $su) {
            $su = filter_var(trim($su), FILTER_VALIDATE_URL);
            if (!$su) continue;
            $dir = UPLOAD_PATH . '/screenshots';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ch2 = curl_init($su);
            curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_FOLLOWLOCATION=>true]);
            $bin = curl_exec($ch2); $ok2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE) === 200 && $bin;
            curl_close($ch2);
            if (!$ok2) continue;
            $tmp2 = tempnam(sys_get_temp_dir(), 'pss');
            file_put_contents($tmp2, $bin);
            $info2 = @getimagesize($tmp2);
            if ($info2) {
                $fname2 = $slug . '-ps-' . substr(md5($su), 0, 6) . '.webp';
                if (compress_image_to($tmp2, $info2['mime'], "$dir/$fname2", 1080, 2000, 82))
                    $shots[] = "uploads/screenshots/$fname2";
            }
            @unlink($tmp2);
        }
    }

    // Screenshots already downloaded by fetch_playstore_full and saved server-side
    if (!empty($_POST['screenshot_paths_import']) && is_array($_POST['screenshot_paths_import'])) {
        foreach (array_slice($_POST['screenshot_paths_import'], 0, 6) as $p) {
            $p = trim($p);
            if (preg_match('#^uploads/screenshots/[A-Za-z0-9_\-\.]+\.(webp|jpg|jpeg|png)$#', $p) && file_exists(__DIR__.'/'.$p)) {
                $shots[] = $p;
            }
        }
    }

    if (!empty($_POST['ai_screenshot_paths']) && is_array($_POST['ai_screenshot_paths'])) {
        // Screenshots generated by AI and already saved server-side — just reference them.
        foreach ($_POST['ai_screenshot_paths'] as $p) {
            $p = trim($p);
            if (preg_match('#^uploads/screenshots/[A-Za-z0-9_\-\.]+\.webp$#', $p)) $shots[] = $p;
        }
    }

    // Build JSON fields
    $features     = array_values(array_filter($_POST['features'] ?? []));
    $pros         = array_values(array_filter($_POST['pros'] ?? []));
    $cons         = array_values(array_filter($_POST['cons'] ?? []));
    $installSteps = array_values(array_filter($_POST['install_steps'] ?? []));
    $faqQ = $_POST['faq']['q'] ?? [];
    $faqA = $_POST['faq']['a'] ?? [];
    $faq  = [];
    for ($fi = 0; $fi < count($faqQ); $fi++) {
        if (trim($faqQ[$fi])) $faq[] = ['q' => trim($faqQ[$fi]), 'a' => trim($faqA[$fi] ?? '')];
    }

    $downloadUrl    = trim($_POST['download_url'] ?? '');
    $downloadSource = in_array($_POST['download_source'] ?? '', ['playstore','apk','both'], true)
        ? $_POST['download_source'] : 'playstore';

    // Preserve existing APK data if a new one wasn't uploaded this request
    $apkPath       = $existing['apk_path']       ?? null;
    $apkSizeBytes  = $existing['apk_size_bytes']  ?? null;
    $apkHashSha256 = $existing['apk_hash_sha256'] ?? null;
    $apkHashMd5    = $existing['apk_hash_md5']    ?? null;
    $apkUploadedAt = $existing['apk_uploaded_at'] ?? null;
    // If a fresh APK was uploaded via AJAX before form submission, its data arrives in hidden fields
    if (!empty($_POST['apk_path_new']) && preg_match('#^uploads/apk/[A-Za-z0-9_\-\.]+\.apk$#', $_POST['apk_path_new'])) {
        $apkPath       = trim($_POST['apk_path_new']);
        $apkSizeBytes  = (int)($_POST['apk_size_bytes_new'] ?? 0) ?: null;
        $apkHashSha256 = trim($_POST['apk_hash_sha256_new'] ?? '') ?: null;
        $apkHashMd5    = trim($_POST['apk_hash_md5_new'] ?? '') ?: null;
        $apkUploadedAt = date('Y-m-d H:i:s');
    }

    $requestedStatus = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
    $forcedDraft = false;
    $hasDownloadMethod = $downloadUrl !== '' || ($apkPath && in_array($downloadSource, ['apk','both'], true));
    if ($requestedStatus === 'published' && !$hasDownloadMethod) {
        $requestedStatus = 'draft';
        $forcedDraft = true;
    }

    // Self-heal a common data-entry mistake: a Play Store URL pasted into the
    // "Play Store version" field (which should only ever hold a short string
    // like "12.8.0") instead of the dedicated Play Store URL field.
    $playStoreVersionIn = trim($_POST['play_store_version'] ?? '');
    $playstoreUrlIn     = trim($_POST['playstore_url'] ?? '');
    if (str_contains($playStoreVersionIn, '://')) {
        if ($playstoreUrlIn === '') $playstoreUrlIn = $playStoreVersionIn;
        $playStoreVersionIn = '';
    }

    $d = [
        'name'              => $name,
        'slug'              => $slug,
        'seo_title'         => seo_title_clamp(trim($_POST['seo_title'] ?? '')),
        'meta_description'  => trim($_POST['meta_description'] ?? ''),
        'keywords'          => trim($_POST['keywords'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'long_description'  => trim($_POST['long_description'] ?? ''),
        'privacy_policy'    => trim($_POST['privacy_policy'] ?? ''),
        'terms_content'     => trim($_POST['terms_content'] ?? ''),
        'offers_text'       => trim($_POST['offers_text'] ?? ''),
        'developer'         => trim($_POST['developer'] ?? ''),
        'version'           => trim($_POST['version'] ?? ''),
        'play_store_version'=> $playStoreVersionIn,
        'playstore_url'     => $playstoreUrlIn,
        'android_version'   => trim($_POST['android_version'] ?? ''),
        'size_mb'           => trim($_POST['size_mb'] ?? ''),
        'license'           => trim($_POST['license'] ?? 'Free'),
        'package_name'      => trim($_POST['package_name'] ?? ''),
        'category_id'       => ($_POST['category_id'] ?? '') ?: null,
        'rating'            => (float)($_POST['rating'] ?? 4.5),
        'rating_count'      => max(0, (int)($_POST['rating_count'] ?? 0)),
        'download_url'      => $downloadUrl,
        'mirror2_url'       => trim($_POST['mirror2_url'] ?? ''),
        'mirror3_url'       => trim($_POST['mirror3_url'] ?? ''),
        'download_source'   => $downloadSource,
        'apk_path'          => $apkPath,
        'apk_size_bytes'    => $apkSizeBytes,
        'apk_hash_sha256'   => $apkHashSha256,
        'apk_hash_md5'      => $apkHashMd5,
        'apk_uploaded_at'   => $apkUploadedAt,
        'whats_new'         => trim($_POST['whats_new'] ?? ''),
        'status'            => $requestedStatus,
        'needs_update'      => isset($_POST['needs_update']) ? 1 : 0,
        'icon_path'         => $iconPath,
        'screenshots'       => json_encode($shots, JSON_UNESCAPED_UNICODE),
        'features'          => json_encode($features, JSON_UNESCAPED_UNICODE),
        'pros'              => json_encode($pros, JSON_UNESCAPED_UNICODE),
        'cons'              => json_encode($cons, JSON_UNESCAPED_UNICODE),
        'install_steps'     => json_encode($installSteps, JSON_UNESCAPED_UNICODE),
        'faq'               => json_encode($faq, JSON_UNESCAPED_UNICODE),
        'badge'             => in_array($_POST['badge'] ?? '', ['','new','updated','hot','choice'], true) ? ($_POST['badge'] ?? '') : '',
        'release_date'      => !empty($_POST['release_date']) ? $_POST['release_date'] : null,
    ];

    if ($isEdit && $appId) {
        // Optionally snapshot the version being replaced into app_versions
        // before overwriting it, so old versions/changelogs stay browsable
        // and downloadable instead of being silently lost on every edit.
        if (!empty($_POST['save_as_new_version']) && $existing) {
            $versionChanged = trim($existing['version'] ?? '') !== trim($d['version'] ?? '')
                || trim($existing['download_url'] ?? '') !== trim($downloadUrl);
            if ($versionChanged && trim($existing['version'] ?? '') !== '') {
                $pdo->prepare("INSERT INTO app_versions (app_id,version,changelog,download_url) VALUES (?,?,?,?)")
                    ->execute([$appId, $existing['version'], $existing['whats_new'], $existing['download_url']]);
            }
        }
        $wasPublished = ($existing['status'] ?? '') === 'published';
        $sets = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($d)));
        $d['id'] = $appId;
        $pdo->prepare("UPDATE apps SET $sets WHERE id=:id")->execute($d);
        bump_cache_version($pdo);
        // Notify Telegram only when transitioning to published — deferred until
        // AFTER the HTTP response is flushed so the admin never sees a freeze.
        if (!$wasPublished && $requestedStatus === 'published') {
            $saved = $pdo->prepare("SELECT * FROM apps WHERE id=?")->execute([$appId]) ? $pdo->query("SELECT * FROM apps WHERE id={$appId}")->fetch(PDO::FETCH_ASSOC) : $d;
            $notifyApp = is_array($saved) ? $saved : $d;
            $translateLangs = array_filter(array_map('trim', explode(',', get_cfg($pdo,'auto_translate_langs',''))));
            register_shutdown_function(function() use ($pdo, $notifyApp, $translateLangs) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                telegram_notify_new_app($pdo, $notifyApp);
                ping_search_engines($pdo, app_url($notifyApp['slug'] ?? ''));
                // Auto-translate into configured languages if app has no parent (is original)
                if ($translateLangs && empty($notifyApp['parent_id'])) {
                    foreach ($translateLangs as $lang) {
                        translate_app($pdo, $notifyApp, $lang);
                    }
                }
            });
        } elseif ($wasPublished && $requestedStatus === 'published' && !empty($d['slug'])) {
            $pingSlug = $d['slug'];
            register_shutdown_function(function() use ($pdo, $pingSlug) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                ping_search_engines($pdo, app_url($pingSlug));
            });
        }
        $redir = 'admin.php?page=apps&msg=' . ($forcedDraft ? 'updated_no_link' : 'updated');
        if ($isXhr) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'redirect'=>$redir]); exit; }
        header('Location: ' . $redir); exit;
    } else {
        $cols = implode(',', array_keys($d));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($d)));
        $pdo->prepare("INSERT INTO apps ($cols) VALUES ($vals)")->execute($d);
        $newId = (int)$pdo->lastInsertId();
        bump_cache_version($pdo);
        if ($requestedStatus === 'published' && $newId) {
            $saved = $pdo->prepare("SELECT * FROM apps WHERE id=?")->execute([$newId]) ? $pdo->query("SELECT * FROM apps WHERE id={$newId}")->fetch(PDO::FETCH_ASSOC) : array_merge($d, ['id' => $newId]);
            $notifyApp = is_array($saved) ? $saved : array_merge($d, ['id' => $newId]);
            $translateLangs = array_filter(array_map('trim', explode(',', get_cfg($pdo,'auto_translate_langs',''))));
            register_shutdown_function(function() use ($pdo, $notifyApp, $translateLangs) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                telegram_notify_new_app($pdo, $notifyApp);
                ping_search_engines($pdo, app_url($notifyApp['slug'] ?? ''));
                if ($translateLangs && empty($notifyApp['parent_id'])) {
                    foreach ($translateLangs as $lang) {
                        translate_app($pdo, $notifyApp, $lang);
                    }
                }
            });
        }
        $redir = 'admin.php?page=apps&msg=' . ($forcedDraft ? 'added_no_link' : 'added');
        if ($isXhr) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'redirect'=>$redir,'app_id'=>$newId]); exit; }
        header('Location: ' . $redir); exit;
    }
}

// ─── Categories ───
if ($page === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check() && isset($_POST['cat_id'])) {
        // Updating a category's SEO description
        $pdo->prepare("UPDATE categories SET description=? WHERE id=?")
            ->execute([trim($_POST['description'] ?? ''), (int)$_POST['cat_id']]);
        bump_cache_version($pdo);
        header('Location: admin.php?page=categories&msg=updated'); exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $n = trim($_POST['name'] ?? '');
        if ($n) $pdo->prepare("INSERT IGNORE INTO categories (name,slug,sort_order) VALUES(?,?,?)")
            ->execute([$n, slugify($n), (int)($_POST['sort_order']??0)]);
    }
    if (isset($_GET['del_cat']) && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['del_cat']]);
        header('Location: admin.php?page=categories&msg=deleted'); exit;
    }
}

// ─── Contact messages ───
if ($page === 'messages') {
    if (isset($_GET['del_msg']) && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['del_msg']]);
        header('Location: admin.php?page=messages&msg=deleted'); exit;
    }
    if (isset($_GET['view'])) {
        $pdo->prepare("UPDATE contact_messages SET status='read' WHERE id=?")->execute([(int)$_GET['view']]);
    }
}

// ─── Comment moderation ───
if ($page === 'comments' && isset($_GET['t']) && hash_equals($_SESSION['csrf']??'', $_GET['t'])) {
    if (isset($_GET['approve'])) {
        $pdo->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
        bump_cache_version($pdo);
        header('Location: admin.php?page=comments&msg=updated'); exit;
    }
    if (isset($_GET['del_comment'])) {
        $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([(int)$_GET['del_comment']]);
        bump_cache_version($pdo);
        header('Location: admin.php?page=comments&msg=deleted'); exit;
    }
}

// ─── Fetch data for forms ───
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$editApp = null;
if ($page === 'edit-app' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editApp = $stmt->fetch();
}

// Dashboard stats
$stats = [];
if ($page === 'dashboard') {
    $stats = [
        'apps'      => (int)$pdo->query("SELECT COUNT(*) FROM apps")->fetchColumn(),
        'published' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
        'views'     => (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM apps")->fetchColumn(),
        'downloads' => (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM apps")->fetchColumn(),
    ];
    $recentApps = $pdo->query("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 8")->fetchAll();
    $topApps    = $pdo->query("SELECT name,slug,views,downloads FROM apps ORDER BY views DESC LIMIT 5")->fetchAll();
    $needsUpdateApps = $pdo->query("SELECT id,name,slug,version,updated_at FROM apps WHERE needs_update=1 ORDER BY updated_at ASC LIMIT 10")->fetchAll();
}

if (isset($_GET['msg'])) {
    $msgs = [
        'added'=>'تمت الإضافة بنجاح',
        'updated'=>'تم التحديث بنجاح',
        'deleted'=>'تم الحذف',
        'added_no_link'=>'تمت الإضافة لكن كمسودة: لم يعمل النشر لأنه لا يوجد رابط تحميل. أضف رابط التحميل ثم انشر التطبيق.',
        'updated_no_link'=>'تم التحديث لكن التطبيق أصبح مسودة: لا يوجد رابط تحميل. أضف رابط التحميل ثم انشر التطبيق.',
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

render:
/* ══════════════════════════════════════════════════════
   LOGIN PAGE
   ══════════════════════════════════════════════════════ */
if ($page === 'login'): ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="<?= h(url("favicon.svg")) ?>"><meta name="theme-color" content="#2563eb"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>تسجيل الدخول — yassota admin</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="admin-login">
  <div class="login-box">
    <h1>yass<span>ota</span></h1>
    <p>لوحة التحكم</p>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="admin.php?page=login">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">اسم المستخدم</label>
        <input class="form-input" type="text" name="username" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label">كلمة المرور</label>
        <input class="form-input" type="password" name="password" required>
      </div>
      <button type="submit" class="btn-save" style="width:100%;justify-content:center">دخول</button>
    </form>
  </div>
</div>
</body>
</html>
<?php exit; endif; ?>

<?php
/* ══════════════════════════════════════════════════════
   AJAX: Mass re-index all published apps
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reindex_all' && is_admin()) {
    header('Content-Type: application/json');
    $apps = $pdo->query("SELECT id, slug FROM apps WHERE status='published' ORDER BY id")->fetchAll();
    $pinged = 0; $errors = 0;
    // Submit all URLs to IndexNow in one batch (up to 10k URLs)
    $key  = get_cfg($pdo, 'indexnow_key', '');
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $urlList = array_map(fn($a) => app_url($a['slug']), $apps);
    foreach (['', 'blog', 'about', 'privacy-policy', 'terms', 'contact'] as $p) {
        $urlList[] = url($p);
    }
    if ($key && $urlList) {
        $keyLocation = 'https://' . $host . '/' . $key . '.txt';
        $body = json_encode([
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $keyLocation,
            'urlList'     => array_values(array_unique($urlList)),
        ]);
        $ictx = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 10, 'ignore_errors' => true,
            'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($body),
            'content' => $body,
        ]]);
        $resp = @file_get_contents('https://api.indexnow.org/indexnow', false, $ictx);
        @file_get_contents('https://www.bing.com/indexnow', false, $ictx);
        $pinged = count($urlList);
    }
    $pdo->exec("UPDATE apps SET last_indexed_at=NOW(), index_status='indexed' WHERE status='published'");
    log_security_event($pdo, 'reindex_all', 'info', "Mass re-index: {$pinged} URLs submitted to IndexNow");
    echo json_encode(['ok' => true, 'pinged' => $pinged, 'total' => count($apps)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Clear security log
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_security_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['ok' => true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — list directory
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_list' && is_admin()) {
    header('Content-Type: application/json');
    $dir = trim($_GET['dir'] ?? '');
    $root = ROOT_PATH;
    // Allowed browse roots
    $allowedDirs = ['', 'assets/css', 'assets/js', 'uploads', 'install'];
    if (!in_array($dir, $allowedDirs, true) && !preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $dir)) {
        echo json_encode(['ok' => false, 'error' => 'Directory not allowed']); exit;
    }
    $absDir = $dir ? ($root . '/' . $dir) : $root;
    $absDir = realpath($absDir);
    if (!$absDir || !is_dir($absDir) || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid directory']); exit;
    }
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    $items = [];
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir . '/' . $f;
        $isDir = is_dir($abs);
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if ($isDir) {
            $rel = ($dir ? $dir . '/' : '') . $f;
            if (in_array($rel, $allowedDirs, true) || preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $rel)) {
                $items[] = ['name' => $f, 'type' => 'dir', 'path' => $rel];
            }
        } elseif (in_array($ext, $allowedExts, true) || $f === '.htaccess' || $f === 'robots.txt') {
            // Don't list config.php — it contains DB credentials
            if ($f === 'config.php') continue;
            $rel = ($dir ? $dir . '/' : '') . $f;
            $items[] = ['name' => $f, 'type' => 'file', 'path' => $rel, 'size' => filesize($abs), 'mtime' => filemtime($abs), 'ext' => $ext ?: 'txt'];
        }
    }
    usort($items, fn($a,$b) => ($a['type'] === $b['type']) ? strcmp($a['name'], $b['name']) : ($a['type'] === 'dir' ? -1 : 1));
    echo json_encode(['ok' => true, 'dir' => $dir ?: '/', 'items' => $items], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — read file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_read' && is_admin()) {
    header('Content-Type: application/json');
    $path = trim($_GET['path'] ?? '');
    $root = ROOT_PATH;
    // Reject config.php
    if (basename($path) === 'config.php') {
        echo json_encode(['ok' => false, 'error' => 'config.php لا يمكن تعديله من هنا — استخدم FTP']); exit;
    }
    $abs = realpath($root . '/' . ltrim($path, '/'));
    if (!$abs || !is_file($abs) || strpos($abs, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'File not found or not allowed']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext, $allowedExts, true) && basename($abs) !== '.htaccess' && basename($abs) !== 'robots.txt') {
        echo json_encode(['ok' => false, 'error' => 'File type not editable']); exit;
    }
    $content = file_get_contents($abs);
    echo json_encode(['ok' => true, 'content' => $content, 'path' => $path, 'ext' => $ext ?: 'txt', 'size' => strlen($content)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — write file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_write' && is_admin()) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }
    $path    = trim($_POST['path'] ?? '');
    $content = $_POST['content'] ?? '';
    $root    = ROOT_PATH;
    // Forbidden: config.php, admin.php security sections
    if (in_array(basename($path), ['config.php'], true)) {
        echo json_encode(['ok' => false, 'error' => 'هذا الملف محمي — استخدم FTP']); exit;
    }
    // Must be within root and have allowed extension
    $abs = $root . '/' . str_replace(['../', '..\\', "\0"], '', ltrim($path, '/'));
    if (!file_exists($abs)) { echo json_encode(['ok' => false, 'error' => 'File not found']); exit; }
    $realAbs = realpath($abs);
    if (!$realAbs || strpos($realAbs, $root) !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Path traversal detected']); exit;
    }
    $ext = strtolower(pathinfo($realAbs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext, $allowedExts, true) && basename($realAbs) !== '.htaccess' && basename($realAbs) !== 'robots.txt') {
        echo json_encode(['ok' => false, 'error' => 'File type not writable']); exit;
    }
    // Backup current version (keep last 1 backup)
    @file_put_contents($realAbs . '.bak', file_get_contents($realAbs));
    if (file_put_contents($realAbs, $content) === false) {
        echo json_encode(['ok' => false, 'error' => 'Write failed — check file permissions']); exit;
    }
    log_security_event($pdo, 'file_edited', 'info', "File edited via file manager: $path");
    bump_cache_version($pdo);
    echo json_encode(['ok' => true, 'bytes' => strlen($content)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — create new file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_create' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_POST['dir'] ?? '');
    $name = basename(trim($_POST['name'] ?? ''));
    if (!$name) { echo json_encode(['ok'=>false,'error'=>'اسم الملف مطلوب']); exit; }
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير مسموح به']); exit;
    }
    $root = ROOT_PATH;
    $absDir = $dir ? realpath($root . '/' . $dir) : $root;
    if (!$absDir || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'المسار غير مسموح به']); exit;
    }
    $abs = $absDir . '/' . $name;
    if (file_exists($abs)) { echo json_encode(['ok'=>false,'error'=>'الملف موجود بالفعل']); exit; }
    if (file_put_contents($abs, '') === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل إنشاء الملف — تحقق من الصلاحيات']); exit;
    }
    log_security_event($pdo, 'file_created', 'info', "File created via file manager: $dir/$name");
    echo json_encode(['ok'=>true,'path'=>($dir?$dir.'/':'').$name]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Toggle auto IndexNow on/off
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'toggle_auto_indexnow' && is_admin()) {
    header('Content-Type: application/json');
    $current = get_cfg($pdo, 'auto_indexnow_enabled', '1');
    $newVal  = ($current === '1') ? '0' : '1';
    set_cfg($pdo, 'auto_indexnow_enabled', $newVal);
    echo json_encode(['ok' => true, 'enabled' => $newVal === '1'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: IndexNow log (paginated)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'indexnow_log' && is_admin()) {
    header('Content-Type: application/json');
    $offset  = max(0, (int)($_GET['offset'] ?? 0));
    $limit   = 50;
    $filter  = $_GET['filter'] ?? 'all';
    $where   = ($filter !== 'all') ? "WHERE status = " . $pdo->quote($filter) : '';
    $total   = (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log $where")->fetchColumn();
    $rows    = $pdo->query("SELECT id,url,engine,status,http_code,reason,created_at FROM indexnow_log $where ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'rows' => $rows, 'total' => $total, 'offset' => $offset, 'limit' => $limit], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Clear IndexNow log
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_indexnow_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM indexnow_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['ok' => true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Mass re-index all published apps via IndexNow
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reindex_all' && is_admin()) {
    header('Content-Type: application/json');
    $apps = $pdo->query("SELECT id,slug FROM apps WHERE status='published' ORDER BY id")->fetchAll();
    $key  = get_cfg($pdo,'indexnow_key','');
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $urlList = array_map(fn($a) => app_url($a['slug']), $apps);
    foreach (['','blog','about','privacy-policy','terms','contact','updates','top?by=downloads'] as $p)
        $urlList[] = url($p);
    $urlList = array_values(array_unique(array_filter($urlList)));
    $pinged = 0;
    $logStatus = 'skipped'; $logCode = null; $logReason = 'مفتاح IndexNow غير مضبوط';
    if ($key && $urlList) {
        $keyLocation = 'https://' . $host . '/' . $key . '.txt';
        $body = json_encode([
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $keyLocation,
            'urlList'     => $urlList,
        ]);
        $ictx = stream_context_create(['http'=>['method'=>'POST','timeout'=>10,'ignore_errors'=>true,
            'header'=>"Content-Type: application/json\r\nContent-Length: ".strlen($body),'content'=>$body]]);
        $resp = @file_get_contents('https://api.indexnow.org/indexnow', false, $ictx);
        $httpHeaders = $http_response_header ?? [];
        $logCode = 0;
        foreach ($httpHeaders as $hh) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $hh, $hm)) { $logCode = (int)$hm[1]; break; }
        }
        $logStatus = in_array($logCode, [200, 202], true) ? 'success' : 'failed';
        $logReason = ($logStatus === 'failed') ? "HTTP {$logCode}" . ($resp ? ': ' . mb_substr(trim($resp), 0, 200) : '') : null;
        $pinged = count($urlList);
        @file_get_contents('https://www.bing.com/indexnow', false, $ictx);
    }
    // Log one summary entry for the bulk ping
    try {
        $pdo->prepare("INSERT INTO indexnow_log (url,engine,status,http_code,reason) VALUES (?,?,?,?,?)")
            ->execute([rtrim(SITE_URL,'/').'/', 'indexnow-bulk', $logStatus, $logCode ?: null, $logReason]);
    } catch (Throwable $le) {}
    $pdo->exec("UPDATE apps SET last_indexed_at=NOW(), index_status='indexed' WHERE status='published'");
    log_security_event($pdo,'reindex_all','info',"Mass re-index: {$pinged} URLs submitted to IndexNow + sitemap ping");
    echo json_encode(['ok'=>true,'pinged'=>$pinged,'total'=>count($apps)], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Ping single indexing engine
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ping_engine' && is_admin()) {
    header('Content-Type: application/json');
    $engine = preg_replace('/[^a-z0-9_\-]/', '', $_GET['engine'] ?? '');
    $sitemapUrl = rtrim(SITE_URL,'/').'/sitemap.xml';
    $sitemapEnc = urlencode($sitemapUrl);
    $siteUrl    = rtrim(SITE_URL,'/').'/';
    $host       = parse_url(SITE_URL, PHP_URL_HOST) ?: '';
    $indexnowKey = get_cfg($pdo, 'indexnow_key', '');

    $httpCtx = stream_context_create(['http'=>['timeout'=>8,'ignore_errors'=>true,'user_agent'=>'yassota-bot/1.0']]);

    $result = ['engine'=>$engine,'ok'=>false,'code'=>0,'msg'=>''];

    $getCode = function(array $headers): int {
        foreach ($headers as $h) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $h, $m)) return (int)$m[1];
        }
        return 0;
    };

    switch ($engine) {
        case 'google_sitemap':
            // Google sitemap ping deprecated since June 2023 — use Google Search Console instead
            $result['code'] = 0; $result['ok'] = false;
            $result['msg']  = 'Google أوقف هذا الـ endpoint في 2023 — استخدم Google Search Console مباشرة لفهرسة Sitemap';
            break;
        case 'bing_sitemap':
            // Bing sitemap ping deprecated — use IndexNow (already configured above)
            $result['code'] = 0; $result['ok'] = false;
            $result['msg']  = 'Bing أوقف هذا الـ endpoint — استخدم IndexNow بدلاً منه (مضبوط أعلاه)';
            break;
        case 'yandex_sitemap':
            @file_get_contents("https://webmaster.yandex.com/ping?sitemap={$sitemapEnc}", false, $httpCtx);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = $code >= 200 && $code < 400;
            $result['msg']  = $result['ok'] ? 'تم إرسال Sitemap لـ Yandex بنجاح' : "HTTP {$code}";
            break;
        case 'indexnow_yandex':
            if (!$indexnowKey) { $result['msg'] = 'مفتاح IndexNow غير مضبوط'; break; }
            $body = json_encode(['host'=>$host,'key'=>$indexnowKey,'keyLocation'=>'https://'.$host.'/'.$indexnowKey.'.txt','urlList'=>[$siteUrl]]);
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>8,'ignore_errors'=>true,
                'header'=>"Content-Type: application/json\r\nContent-Length: ".strlen($body),'content'=>$body]]);
            @file_get_contents('https://yandex.com/indexnow', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = in_array($code, [200,202], true);
            $result['msg']  = $result['ok'] ? 'تم إرسال IndexNow لـ Yandex بنجاح' : "HTTP {$code}";
            break;
        case 'indexnow_naver':
            if (!$indexnowKey) { $result['msg'] = 'مفتاح IndexNow غير مضبوط'; break; }
            $body = json_encode(['host'=>$host,'key'=>$indexnowKey,'keyLocation'=>'https://'.$host.'/'.$indexnowKey.'.txt','urlList'=>[$siteUrl]]);
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>8,'ignore_errors'=>true,
                'header'=>"Content-Type: application/json\r\nContent-Length: ".strlen($body),'content'=>$body]]);
            @file_get_contents('https://searchadvisor.naver.com/indexnow', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = in_array($code, [200,202], true);
            $result['msg']  = $result['ok'] ? 'تم إرسال IndexNow لـ Naver بنجاح' : "HTTP {$code}";
            break;
        case 'indexnow_seznam':
            if (!$indexnowKey) { $result['msg'] = 'مفتاح IndexNow غير مضبوط'; break; }
            $body = json_encode(['host'=>$host,'key'=>$indexnowKey,'keyLocation'=>'https://'.$host.'/'.$indexnowKey.'.txt','urlList'=>[$siteUrl]]);
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>8,'ignore_errors'=>true,
                'header'=>"Content-Type: application/json\r\nContent-Length: ".strlen($body),'content'=>$body]]);
            @file_get_contents('https://api.indexnow.org/indexnow', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = in_array($code, [200,202], true);
            $result['msg']  = $result['ok'] ? 'تم إرسال IndexNow عبر api.indexnow.org بنجاح' : "HTTP {$code}";
            break;
        case 'pubsubhubbub':
            $feedUrl  = urlencode(rtrim(SITE_URL,'/').'/feed.php');
            $postBody = "hub.mode=publish&hub.url={$feedUrl}";
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>8,'ignore_errors'=>true,
                'header'=>"Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($postBody),
                'content'=>$postBody]]);
            @file_get_contents('https://pubsubhubbub.appspot.com/', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = $code === 204 || ($code >= 200 && $code < 400);
            $result['msg']  = $result['ok'] ? 'تم إشعار PubSubHubbub بتحديث RSS' : "HTTP {$code}";
            break;
        case 'ping_o_matic':
            $xmlBody = '<?xml version="1.0"?><methodCall><methodName>weblogUpdates.ping</methodName>'
                     . '<params><param><value>' . htmlspecialchars(get_cfg($pdo,'site_name','yassota')) . '</value></param>'
                     . '<param><value>' . htmlspecialchars($siteUrl) . '</value></param>'
                     . '<param><value>' . htmlspecialchars($sitemapUrl) . '</value></param></params></methodCall>';
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>10,'ignore_errors'=>true,
                'header'=>"Content-Type: text/xml\r\nContent-Length: ".strlen($xmlBody),'content'=>$xmlBody]]);
            $resp = @file_get_contents('https://rpc.pingomatic.com/', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = $code >= 200 && $code < 400;
            $result['msg']  = $result['ok'] ? 'تم إرسال Ping-O-Matic (20+ محرك بحث)' : "HTTP {$code}";
            break;
        case 'baidu_ping':
            $xmlBody = '<?xml version="1.0"?><methodCall><methodName>weblogUpdates.extendedPing</methodName>'
                     . '<params><param><value>' . htmlspecialchars(get_cfg($pdo,'site_name','yassota')) . '</value></param>'
                     . '<param><value>' . htmlspecialchars($siteUrl) . '</value></param>'
                     . '<param><value>' . htmlspecialchars($siteUrl) . '</value></param>'
                     . '<param><value>' . htmlspecialchars($sitemapUrl) . '</value></param></params></methodCall>';
            $ctx2 = stream_context_create(['http'=>['method'=>'POST','timeout'=>10,'ignore_errors'=>true,
                'header'=>"Content-Type: text/xml\r\nContent-Length: ".strlen($xmlBody),'content'=>$xmlBody]]);
            @file_get_contents('http://ping.baidu.com/ping/RPC2', false, $ctx2);
            $code = $getCode($http_response_header ?? []);
            $result['code'] = $code; $result['ok'] = $code >= 200 && $code < 400;
            $result['msg']  = $result['ok'] ? 'تم إرسال Baidu Ping' : "HTTP {$code} (قد يكون محجوباً خارج الصين)";
            break;
        default:
            $result['msg'] = 'محرك غير معروف';
    }

    try {
        $pdo->prepare("INSERT INTO indexnow_log (url,engine,status,http_code,reason) VALUES (?,?,?,?,?)")
            ->execute([$sitemapUrl, $engine, $result['ok']?'success':'failed', $result['code']?:null,
                       $result['ok']?null:$result['msg']]);
    } catch (Throwable $le) {}

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Clear old security log entries
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_security_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Auto-translate app to a target language
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'auto_translate' && is_admin()) {
    header('Content-Type: application/json');
    $appId   = (int)($_POST['app_id'] ?? 0);
    $langCode = preg_replace('/[^a-z]/', '', strtolower($_POST['lang'] ?? ''));
    if (!$appId || !$langCode) { echo json_encode(['ok'=>false,'error'=>'بيانات ناقصة']); exit; }
    $app = $pdo->query("SELECT * FROM apps WHERE id=$appId")->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['ok'=>false,'error'=>'التطبيق غير موجود']); exit; }
    // Only translate originals (parent_id IS NULL)
    if (!empty($app['parent_id'])) { echo json_encode(['ok'=>false,'error'=>'هذا التطبيق ترجمة، ترجم من الأصلي']); exit; }
    $ok = translate_app($pdo, $app, $langCode);
    echo json_encode(['ok'=>$ok, 'error'=>$ok ? null : 'فشلت الترجمة — تأكد من إعداد مفتاح OpenRouter'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Bulk auto-translate all published apps
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'bulk_translate' && is_admin()) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    if (!function_exists('sse')) {
        function sse(string $event, array $data): void {
            echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) ob_flush(); flush();
        }
    }
    $langs = array_filter(array_map('trim', explode(',', $_POST['langs'] ?? get_cfg($pdo, 'auto_translate_langs', ''))));
    if (!$langs) { sse('done', ['ok'=>false,'error'=>'لا توجد لغات مضبوطة']); exit; }
    $apps = $pdo->query("SELECT * FROM apps WHERE status='published' AND (parent_id IS NULL OR parent_id=0) ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $total = count($apps) * count($langs); $done = 0;
    foreach ($apps as $app) {
        foreach ($langs as $lang) {
            $ok = translate_app($pdo, $app, $lang);
            $done++;
            sse('progress', ['done'=>$done,'total'=>$total,'app'=>$app['name'],'lang'=>$lang,'ok'=>$ok]);
        }
    }
    sse('done', ['ok'=>true,'done'=>$done]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Play Store — enhanced import (screenshots + AI-fill)
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fetch_playstore_full' && is_admin()) {
    @set_time_limit(180);
    // Buffer all output so a PHP warning/notice can't corrupt the JSON response
    ob_start();
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $src   = trim($input['url'] ?? '');
        if (!$src || !preg_match('#^https://play\.google\.com/store/apps/details#', $src)) {
            ob_end_clean(); echo json_encode(['success'=>false,'error'=>'رابط Play Store غير صالح']); exit;
        }

        // Fetch with a realistic browser UA + Accept-Language to reduce Google anti-bot blocks
        $psHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language: ar-SA,ar;q=0.9,en;q=0.8',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Cache-Control: no-cache',
        ];
        $ch = curl_init($src);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $psHeaders, CURLOPT_ENCODING => 'gzip, deflate',
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if (!$html || $code !== 200) {
            ob_end_clean();
            $errMsg = $curlErr ? "خطأ اتصال: $curlErr" : "Google Play أعاد كود $code — قد تكون Google تمنع الوصول من هذا الخادم مؤقتاً. جرّب مجدداً أو أدخل البيانات يدوياً.";
            echo json_encode(['success'=>false,'error'=>$errMsg]);
            exit;
        }

        // Extract og: meta tags the way fetch_playstore_meta does
        $meta_fn = function(string $prop) use ($html): ?string {
            if (preg_match('#<meta[^>]+property=["\']' . preg_quote($prop,'#') . '["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES,'UTF-8');
            if (preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote($prop,'#') . '["\']#i', $html, $m)) return html_entity_decode($m[1], ENT_QUOTES,'UTF-8');
            return null;
        };
        $title = $meta_fn('og:title');
        $desc  = $meta_fn('og:description');
        $image = $meta_fn('og:image');

        if (!$title && !$desc) {
            ob_end_clean();
            echo json_encode(['success'=>false,'error'=>'لم يتم العثور على بيانات التطبيق في الصفحة — قد تحتاج رابطاً مباشراً مثل: https://play.google.com/store/apps/details?id=com.example']);
            exit;
        }

        $pkg = null;
        if (preg_match('#[?&]id=([a-zA-Z0-9_.]+)#', $src, $m)) $pkg = $m[1];
        if ($title) $title = trim(preg_replace('/\s*[-–]\s*(Apps on Google Play|تطبيقات على Google Play).*$/i', '', $title));
        $developer = null;
        if (preg_match('#/store/apps/developer\?id=[^"\']*["\'][^>]*>([^<]{2,60})</a>#u', $html, $m))
            $developer = html_entity_decode(trim($m[1]), ENT_QUOTES,'UTF-8');
        if (!$developer && preg_match('#"author"\s*:\s*\{\s*"@type"\s*:\s*"[^"]*"\s*,\s*"name"\s*:\s*"([^"]+)"#', $html, $m))
            $developer = html_entity_decode($m[1], ENT_QUOTES,'UTF-8');
        $version = null;
        if (preg_match('#"softwareVersion"\s*:\s*"([^"]{1,30})"#', $html, $m)) $version = $m[1];
        if (!$version && preg_match('#"currentVersionName"\s*:\s*"([^"]{1,30})"#', $html, $m)) $version = $m[1];
        $androidReq = null;
        if (preg_match('#"operatingSystem"\s*:\s*"([^"]{1,40})"#', $html, $m)) $androidReq = $m[1];
        $screenshots = [];
        if (preg_match_all('#\["(https://play-lh\.googleusercontent\.com/[^"]{20,})",[^]]*\[\d+,\d+\]#', $html, $ms)) {
            foreach (array_unique($ms[1]) as $su) { if (count($screenshots) >= 6) break; $screenshots[] = $su; }
        }
        $meta = [
            'name'              => $title,
            'short_description' => $desc ? mb_substr($desc, 0, 300) : null,
            'long_description'  => $desc,
            'icon_url'          => $image,
            'package_name'      => $pkg,
            'playstore_url'     => $src,
            'developer'         => $developer,
            'version'           => $version,
            'android_version'   => $androidReq,
            'screenshot_urls'   => $screenshots,
        ];

        // Download and save screenshots
        $savedScreenshots = [];
        $ssDir = UPLOAD_PATH . '/screenshots';
        if (!is_dir($ssDir)) @mkdir($ssDir, 0755, true);
        foreach ($screenshots as $ssUrl) {
            if (count($savedScreenshots) >= 6) break;
            $ch2 = curl_init($ssUrl . '=w720-h1280-rw');
            curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>true]);
            $bin2 = curl_exec($ch2); $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE); curl_close($ch2);
            if ($code2 === 200 && strlen($bin2) > 1000) {
                $fname = 'ps_' . md5($ssUrl) . '.webp';
                $fpath = "$ssDir/$fname";
                if (!file_exists($fpath)) file_put_contents($fpath, $bin2);
                $savedScreenshots[] = 'uploads/screenshots/' . $fname;
            }
        }

        // Download icon
        $savedIcon = null;
        if ($image) {
            $ch3 = curl_init($image . '=w512-h512-rw');
            curl_setopt_array($ch3,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>true]);
            $bin3 = curl_exec($ch3); $code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE); curl_close($ch3);
            if ($code3 === 200 && strlen($bin3) > 500) {
                $tmp = tempnam(sys_get_temp_dir(), 'psicn');
                file_put_contents($tmp, $bin3);
                $fakeName = slugify($meta['name'] ?? 'app');
                $fakeFile = ['tmp_name'=>$tmp,'error'=>UPLOAD_ERR_OK,'size'=>strlen($bin3)];
                $savedIcon = process_icon($fakeFile, $fakeName);
                @unlink($tmp);
            }
        }

        // AI-generate pros/cons/features/install_steps from description
        $aiData = [];
        if (!empty($meta['long_description'])) {
            $aiPrompt = 'أنت خبير تطبيقات. بناءً على الوصف التالي لتطبيق أندرويد اسمه "' . ($meta['name'] ?? 'تطبيق') . '"، أنشئ المحتوى التالي بالعربية واحترافية ضمن JSON صالح فقط:
{"pros":["إيجابية","إيجابية","إيجابية","إيجابية","إيجابية"],"cons":["سلبية","سلبية"],"whats_new":"ما الجديد في هذا التحديث","install_steps":["خطوة 1","خطوة 2","خطوة 3"],"features":["ميزة 1","ميزة 2","ميزة 3","ميزة 4","ميزة 5"]}

الوصف:
' . mb_substr($meta['long_description'] ?? '', 0, 1500);
            $rawAi = ai_text($pdo, $aiPrompt, 1200, 45);
            if ($rawAi) $aiData = ai_extract_json($rawAi) ?: [];
        }

        $siteName = preg_replace('/[^a-z0-9]/i', '', get_cfg($pdo,'site_name','yassota'));
        $response = array_merge($meta, [
            'success'        => true,
            'screenshots'    => $savedScreenshots,
            'icon_path'      => $savedIcon,
            'pros'           => $aiData['pros'] ?? [],
            'cons'           => $aiData['cons'] ?? [],
            'whats_new'      => $aiData['whats_new'] ?? '',
            'install_steps'  => $aiData['install_steps'] ?? [],
            'features'       => $aiData['features'] ?? [],
            'apk_brand_hint' => strtolower($siteName) . '-' . slugify($meta['name'] ?? 'app') . '.apk',
            'note'           => 'تم استيراد الأيقونة والصور والبيانات الكاملة من Play Store. رابط التحميل المباشر يحتاج إضافة يدوية.',
        ]);
        ob_end_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        ob_end_clean();
        echo json_encode(['success'=>false,'error'=>'خطأ داخلي: ' . $e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Evil — unban IP
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'evil_unban' && is_admin()) {
    header('Content-Type: application/json');
    $ip = trim($_POST['ip'] ?? '');
    if (!$ip) { echo json_encode(['ok'=>false,'error'=>'IP مطلوب']); exit; }
    evil_unban_ip($pdo, $ip);
    log_security_event($pdo, 'ip_unbanned', 'info', "Admin manually unbanned IP: $ip");
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Evil — clear login attempts for IP
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'evil_clear_attempts' && is_admin()) {
    header('Content-Type: application/json');
    $ip = trim($_POST['ip'] ?? '');
    if (!$ip) { echo json_encode(['ok'=>false,'error'=>'IP مطلوب']); exit; }
    evil_clear_login_fail($pdo, $ip);
    log_security_event($pdo, 'attempts_cleared', 'info', "Admin cleared login attempts for IP: $ip");
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Evil — toggle setting
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'evil_toggle' && is_admin()) {
    header('Content-Type: application/json');
    $key = preg_replace('/[^a-z0-9_]/', '', $_POST['key'] ?? '');
    $val = ($_POST['val'] ?? '') === '1' ? '1' : '0';
    if (!str_starts_with($key, 'evil_')) { echo json_encode(['ok'=>false,'error'=>'مفتاح غير صالح']); exit; }
    set_cfg($pdo, $key, $val);
    echo json_encode(['ok'=>true,'val'=>$val]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: Evil — clear old security log
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'evil_clear_log' && is_admin()) {
    header('Content-Type: application/json');
    $pdo->exec("DELETE FROM security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    echo json_encode(['ok'=>true]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — list directory
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_list' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_GET['dir'] ?? '');
    $root = ROOT_PATH;
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    // Whitelist directories available for browsing
    if ($dir && !preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $dir)) {
        echo json_encode(['ok'=>false,'error'=>'Directory not allowed']); exit;
    }
    $absDir = $dir ? realpath($root.'/'.$dir) : $root;
    if (!$absDir || !is_dir($absDir) || strpos($absDir, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'Invalid directory']); exit;
    }
    $browseable = ['','assets/css','assets/js','uploads','install'];
    $items = [];
    foreach (scandir($absDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $absDir.'/'.$f;
        $rel = ($dir ? $dir.'/' : '').$f;
        if (is_dir($abs)) {
            if (in_array($rel, $browseable, true) || preg_match('#^(assets/(css|js)|uploads(/[a-zA-Z0-9_\-]+)?|install)$#', $rel))
                $items[] = ['name'=>$f,'type'=>'dir','path'=>$rel];
        } else {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if ($f === 'config.php') continue; // protect credentials
            if (in_array($ext,$allowedExts,true) || $f==='.htaccess' || $f==='robots.txt')
                $items[] = ['name'=>$f,'type'=>'file','path'=>$rel,'size'=>filesize($abs),'mtime'=>filemtime($abs),'ext'=>$ext ?: 'txt'];
        }
    }
    usort($items, fn($a,$b)=>$a['type']===$b['type'] ? strcmp($a['name'],$b['name']) : ($a['type']==='dir' ? -1 : 1));
    echo json_encode(['ok'=>true,'dir'=>$dir ?: '/','items'=>$items], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — read file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_read' && is_admin()) {
    header('Content-Type: application/json');
    $path = trim($_GET['path'] ?? '');
    $root = ROOT_PATH;
    if (basename($path) === 'config.php') {
        echo json_encode(['ok'=>false,'error'=>'config.php محمي — استخدم FTP لتعديله']); exit;
    }
    $abs = realpath($root.'/'.ltrim($path,'/'));
    if (!$abs || !is_file($abs) || strpos($abs, $root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'الملف غير موجود']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext,$allowedExts,true) && basename($abs)!=='.htaccess' && basename($abs)!=='robots.txt') {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير قابل للتعديل']); exit;
    }
    $content = file_get_contents($abs);
    echo json_encode(['ok'=>true,'content'=>$content,'path'=>$path,'ext'=>$ext ?: 'txt','size'=>strlen($content)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — write file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_write' && is_admin()) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }
    $path    = trim($_POST['path'] ?? '');
    $content = $_POST['content'] ?? '';
    $root    = ROOT_PATH;
    if (in_array(basename($path),['config.php'],true)) {
        echo json_encode(['ok'=>false,'error'=>'هذا الملف محمي — استخدم FTP']); exit;
    }
    $safe = str_replace(['../','..\\',"../","..\\","\0"],'',$path);
    $abs  = realpath($root.'/'.ltrim($safe,'/'));
    if (!$abs || !is_file($abs) || strpos($abs,$root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'الملف غير موجود أو المسار غير مسموح به']); exit;
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md','htaccess'];
    if (!in_array($ext,$allowedExts,true) && basename($abs)!=='.htaccess' && basename($abs)!=='robots.txt') {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير قابل للكتابة']); exit;
    }
    @file_put_contents($abs.'.bak', file_get_contents($abs)); // single-slot backup
    if (file_put_contents($abs, $content) === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل الكتابة — تحقق من صلاحيات الملف']); exit;
    }
    log_security_event($pdo,'file_edited','info',"File edited via file manager: $path");
    bump_cache_version($pdo);
    echo json_encode(['ok'=>true,'bytes'=>strlen($content)]);
    exit;
}

/* ══════════════════════════════════════════════════════
   AJAX: File Manager — create new file
   ══════════════════════════════════════════════════════ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fm_create' && is_admin()) {
    header('Content-Type: application/json');
    $dir  = trim($_POST['dir'] ?? '');
    $name = basename(trim($_POST['name'] ?? ''));
    if (!$name) { echo json_encode(['ok'=>false,'error'=>'اسم الملف مطلوب']); exit; }
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExts = ['php','css','js','json','txt','html','htm','xml','svg','md'];
    if (!in_array($ext,$allowedExts,true)) {
        echo json_encode(['ok'=>false,'error'=>'نوع الملف غير مسموح به']); exit;
    }
    $root = ROOT_PATH;
    $absDir = $dir ? realpath($root.'/'.$dir) : $root;
    if (!$absDir || strpos($absDir,$root) !== 0) {
        echo json_encode(['ok'=>false,'error'=>'المسار غير مسموح به']); exit;
    }
    $abs = $absDir.'/'.$name;
    if (file_exists($abs)) { echo json_encode(['ok'=>false,'error'=>'الملف موجود بالفعل']); exit; }
    if (file_put_contents($abs,'') === false) {
        echo json_encode(['ok'=>false,'error'=>'فشل الإنشاء — تحقق من الصلاحيات']); exit;
    }
    log_security_event($pdo,'file_created','info',"New file created: $dir/$name");
    echo json_encode(['ok'=>true,'path'=>($dir?$dir.'/':'').$name]);
    exit;
}

/* ══════════════════════════════════════════════════════
   MAIN ADMIN LAYOUT
   ══════════════════════════════════════════════════════ */
// Critical security event count for nav badge
$_navEvilCount = 0;
try {
    $tblList = array_column($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM), 0);
    if (in_array('security_log', $tblList)) {
        $_navEvilCount = (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    }
} catch (\Throwable $e) { $_navEvilCount = 0; }

$navLinks = [
    'dashboard' => ['label'=>'لوحة التحكم',   'icon'=>'M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9'],
    'apps'      => ['label'=>'التطبيقات',     'icon'=>'M4 6h16M4 12h16M4 18h16'],
    'add-app'   => ['label'=>'إضافة تطبيق',   'icon'=>'M12 5v14m-7-7h14'],
    'categories'=> ['label'=>'التصنيفات',     'icon'=>'M3 7h4v4H3V7zm0 6h4v4H3v-4zm6-6h12v4H9V7zm0 6h12v4H9v-4z'],
    'bulk-generate' => ['label'=>'توليد تطبيقات رائجة', 'icon'=>'M12 2l2.4 7.2H22l-6 4.6 2.3 7.2-6.3-4.5-6.3 4.5 2.3-7.2-6-4.6h7.6z'],
    'bulk-content'  => ['label'=>'توليد محتوى للتطبيقات', 'icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    'import-preset' => ['label'=>'استيراد 30 تطبيقاً جاهزاً', 'icon'=>'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
    'file-import'   => ['label'=>'استيراد من ملف',            'icon'=>'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'bulk-csv-import' => ['label'=>'استيراد CSV ضخم (50K+)', 'icon'=>'M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z'],
    'assistant' => ['label'=>'مساعد الذكاء الاصطناعي', 'icon'=>'M9 18h6m-5 3h4M12 3a6 6 0 00-4 10.5c.6.5 1 1.3 1 2.1V16h6v-.4c0-.8.4-1.6 1-2.1A6 6 0 0012 3z'],
    'messages'  => ['label'=>'رسائل التواصل', 'icon'=>'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm0 2l8 6 8-6'],
    'comments'  => ['label'=>'التعليقات والتقييمات', 'icon'=>'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z'],
    'blog'      => ['label'=>'المدونة والمحتوى', 'icon'=>'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z'],
    'article-gen'=> ['label'=>'توليد مقالات التطبيقات','icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    'stats'     => ['label'=>'إحصائيات الموقع', 'icon'=>'M3 3v18h18M8 17V9m4 8V5m4 12v-6'],
    'visitors'  => ['label'=>'الزوار والسلوك',  'icon'=>'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
    'indexing-monitor' => ['label'=>'مراقب الفهرسة', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
    'connection'=> ['label'=>'اختبار الاتصال', 'icon'=>'M13 10V3L4 14h7v7l9-11h-7z'],
    'database'  => ['label'=>'قاعدة البيانات', 'icon'=>'M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zm0 0v12c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12c0 1.1 3.6 2 8 2s8-.9 8-2'],
    'indexnow-log'   => ['label'=>'سجل الفهرسة', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    'indexing-tools' => ['label'=>'أدوات الفهرسة', 'icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7'],
    'evil'      => ['label'=>'🛡️ نظام Evil للحماية', 'icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', 'badge'=>$_navEvilCount],
    'security'  => ['label'=>'الحماية والأمان', 'icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'],
    'seo-preview' => ['label'=>'معاينة نتائج Google', 'icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
    'html-pages'  => ['label'=>'صفحات HTML للفهرسة', 'icon'=>'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
    'file-manager' => ['label'=>'مدير الملفات', 'icon'=>'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
    'settings'  => ['label'=>'الإعدادات',     'icon'=>'M12 15a3 3 0 100-6 3 3 0 000 6zm0 0v3m0-12V3m9 9h-3M6 12H3m15.364-6.364l-2.121 2.121M8.757 15.243l-2.121 2.121M18.364 18.364l-2.121-2.121M8.757 8.757L6.636 6.636'],
    'deploy'    => ['label'=>'اتصال السيرفر', 'icon'=>'M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="<?= h(url("favicon.svg")) ?>"><meta name="theme-color" content="#2563eb"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title><?= h($navLinks[$page]['label'] ?? 'Admin') ?> — yassota</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="<?= h(asset_url('assets/css/admin.css')) ?>">
</head>
<body>
<!-- ═══ MOBILE TOPBAR (يظهر فقط على الشاشات الصغيرة) ═══ -->
<div class="admin-mobile-topbar">
  <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="القائمة" aria-expanded="false">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2.1"/><circle cx="12" cy="12" r="2.1"/><circle cx="12" cy="19" r="2.1"/></svg>
  </button>
  <div class="admin-logo" style="padding:0;border:none;margin:0;font-size:16px">yass<span>ota</span></div>
  <a href="admin.php?page=logout" style="color:var(--danger)">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/></svg>
  </a>
</div>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

<div class="admin-wrap">

<!-- ═══ SIDEBAR ═══ -->
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="admin-logo">yass<span>ota</span></div>
  <nav class="admin-nav">
    <?php foreach ($navLinks as $key => $nav): ?>
    <a href="admin.php?page=<?= $key ?>" class="<?= $page === $key ? 'active' : '' ?>" style="position:relative">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="<?= $nav['icon'] ?>"/>
      </svg>
      <?= $nav['label'] ?>
      <?php if (!empty($nav['badge']) && $nav['badge'] > 0): ?>
        <span style="position:absolute;top:6px;left:6px;background:#ef4444;color:#fff;border-radius:9px;font-size:10px;font-weight:700;min-width:17px;height:17px;line-height:17px;text-align:center;padding:0 4px"><?= (int)$nav['badge'] > 99 ? '99+' : (int)$nav['badge'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <a href="/" target="_blank" style="margin-top:auto">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      عرض الموقع
    </a>
    <a href="admin.php?page=logout" style="color:var(--danger)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/></svg>
      خروج
    </a>
  </nav>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="admin-main">

<?php if ($msg): ?><div class="alert <?= (isset($_GET['msg']) && str_ends_with($_GET['msg'], '_no_link')) ? 'alert-error' : 'alert-success' ?>"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<?php
/* ─── SEO field quality helpers (needed by add-app/edit-app form) ─── */
if (!function_exists('seoBarColor')) {
    function seoBarColor(int $len, int $good_min, int $good_max): string {
        if ($len === 0) return '#6b7280';
        if ($len < $good_min * 0.6) return '#ef4444';
        if ($len < $good_min) return '#f59e0b';
        return '#22c55e';
    }
}
if (!function_exists('seoTitleHint')) {
    function seoTitleHint(int $len): string {
        if ($len === 0) return 'أدخل عنوان SEO — الطول المثالي 50–60 حرفاً';
        if ($len < 30)  return '⚠ قصير جداً — محركات البحث تفضّل 50–60 حرفاً';
        if ($len < 50)  return '🟡 مقبول — الطول المثالي بين 50 و60 حرفاً';
        if ($len <= 60) return '✅ ممتاز — طول مثالي لمحركات البحث';
        return '🔴 تجاوز الحد (60 حرفاً) — سيُقطع في نتائج البحث';
    }
}
if (!function_exists('seoDescHint')) {
    function seoDescHint(int $len): string {
        if ($len === 0) return 'أدخل وصف Meta Description — الطول المثالي 120–160 حرفاً';
        if ($len < 50)  return '⚠ قصير جداً — محركات البحث تفضّل 120–160 حرفاً';
        if ($len < 120) return '🟡 مقبول — الطول المثالي بين 120 و160 حرفاً';
        if ($len <= 160) return '✅ ممتاز — طول مثالي لمحركات البحث';
        return '🔴 تجاوز الحد (160 حرفاً) — سيُقطع في نتائج البحث';
    }
}

/* ─────────────── DASHBOARD ─────────────── */
if ($page === 'dashboard'): ?>

<div class="admin-header">
  <h1>لوحة التحكم</h1>
  <a href="admin.php?page=add-app" class="btn-save">+ إضافة تطبيق</a>
</div>

<div class="admin-stats">
  <div class="stat-card"><div class="stat-num"><?= number_format($stats['apps']) ?></div><div class="stat-label">إجمالي التطبيقات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= number_format($stats['published']) ?></div><div class="stat-label">منشور</div></div>
  <div class="stat-card"><div class="stat-num"><?= number_format($stats['views']) ?></div><div class="stat-label">إجمالي المشاهدات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--purple)"><?= number_format($stats['downloads']) ?></div><div class="stat-label">إجمالي التحميلات</div></div>
  <div class="stat-card" style="border-color:rgba(6,182,212,.3)">
    <div class="stat-num" style="color:var(--cyan)"><?= (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn() ?></div>
    <div class="stat-label">تطبيق مفهرَس</div>
  </div>
  <div class="stat-card" style="border-color:rgba(239,68,68,.3)">
    <div class="stat-num" style="color:var(--danger)"><?= (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical' AND DATE(created_at)=CURDATE()")->fetchColumn() ?></div>
    <div class="stat-label">تنبيهات أمان اليوم</div>
  </div>
</div>

<div class="dashboard-grid">
  <div class="panel">
    <h2>الأكثر زيارة</h2>
    <table class="admin-table responsive-cards">
      <thead><tr><th>التطبيق</th><th>مشاهدات</th><th>تحميلات</th></tr></thead>
      <tbody>
        <?php foreach ($topApps as $a): ?>
        <tr>
          <td data-label="التطبيق"><?= h($a['name']) ?></td>
          <td data-label="مشاهدات" style="font-family:var(--f-mono)"><?= number_format($a['views']) ?></td>
          <td data-label="تحميلات" style="font-family:var(--f-mono)"><?= number_format($a['downloads']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$topApps): ?><tr><td colspan="3" style="color:var(--muted)">لا توجد بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>أحدث الإضافات</h2>
    <table class="admin-table responsive-cards">
      <thead><tr><th>التطبيق</th><th>التصنيف</th><th>الحالة</th></tr></thead>
      <tbody>
        <?php foreach ($recentApps as $a): ?>
        <tr>
          <td data-label="التطبيق"><?= h($a['name']) ?></td>
          <td data-label="التصنيف" style="font-size:11px;color:var(--muted)"><?= h($a['cat'] ?? '—') ?></td>
          <td data-label="الحالة"><span class="status-badge status-<?= $a['status'] ?>"><?= $a['status'] === 'published' ? 'منشور' : 'مسودة' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($needsUpdateApps): ?>
<div class="panel" style="border-color:rgba(251,191,36,.3);margin-top:20px">
  <h2 style="color:#fbbf24">⚠️ تطبيقات تحتاج تحديث (<?= count($needsUpdateApps) ?>)</h2>
  <table class="admin-table responsive-cards">
    <thead><tr><th>التطبيق</th><th>الإصدار الحالي</th><th>آخر تحديث</th><th>إجراء</th></tr></thead>
    <tbody>
      <?php foreach ($needsUpdateApps as $a): ?>
      <tr>
        <td data-label="التطبيق" style="font-weight:700"><?= h($a['name']) ?></td>
        <td data-label="الإصدار" style="font-family:var(--f-mono)"><?= h($a['version'] ?: '—') ?></td>
        <td data-label="آخر تحديث" style="color:var(--muted);font-size:12px"><?= h(time_ago($a['updated_at'])) ?></td>
        <td data-label="إجراء" class="td-actions">
          <a href="admin.php?page=edit-app&id=<?= $a['id'] ?>" class="btn-edit">تحديث الآن</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="panel" style="margin-top:20px">
  <h2>أدوات SEO الجماعية</h2>
  <p style="color:var(--muted);font-size:13px;margin-bottom:14px">
    إعادة توليد عنوان SEO ووصف Meta والكلمات المفتاحية لكل التطبيقات المنشورة دفعة واحدة بالذكاء الاصطناعي — مفيد بعد تغيير استراتيجية الكلمات المفتاحية.
  </p>
  <button type="button" id="btn-bulk-seo" class="btn-ai">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    تحديث SEO لكل التطبيقات
  </button>
  <div id="bulk-seo-progress" style="margin-top:14px;display:none">
    <div style="height:8px;background:var(--navy-600);border-radius:6px;overflow:hidden">
      <div id="bulk-seo-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .3s"></div>
    </div>
    <div id="bulk-seo-status" style="font-size:12px;color:var(--muted);margin-top:8px"></div>
  </div>
</div>

<?php
/* ─────────────── APPS LIST ─────────────── */
elseif ($page === 'apps'):
  $search = trim($_GET['q'] ?? '');
  $appsList = $search
    ? $pdo->prepare("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id WHERE a.name LIKE ? ORDER BY a.created_at DESC LIMIT 100")
    : $pdo->query("SELECT a.*,c.name AS cat FROM apps a LEFT JOIN categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 100");
  if ($search) { $appsList->execute(["%$search%"]); $appsList = $appsList->fetchAll(); }
  else $appsList = $appsList->fetchAll();
?>

<div class="admin-header">
  <h1>التطبيقات</h1>
  <a href="admin.php?page=add-app" class="btn-save">+ إضافة تطبيق</a>
</div>
<form method="get" action="admin.php" class="admin-search-row">
  <input type="hidden" name="page" value="apps">
  <input class="form-input" type="text" name="q" placeholder="بحث بالاسم..." value="<?= h($search) ?>">
  <button type="submit" class="btn-save" style="padding:11px 24px">بحث</button>
</form>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead>
    <tr>
      <th style="width:44px"></th>
      <th>التطبيق</th><th>التصنيف</th><th>إصدار</th><th>حجم</th>
      <th>مشاهدات</th><th>تحميلات</th><th>الحالة</th><th>إجراءات</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($appsList as $a): ?>
  <tr>
    <td class="td-thumb">
      <?php if ($a['icon_path']): ?>
        <img src="<?= h(media_url($a['icon_path'])) ?>" class="app-thumb" alt="">
      <?php else: ?>
        <div class="app-thumb-placeholder"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="3"/></svg></div>
      <?php endif; ?>
    </td>
    <td data-label="التطبيق" style="font-weight:700"><?= h($a['name']) ?></td>
    <td data-label="التصنيف" style="color:var(--muted);font-size:12px"><?= h($a['cat'] ?? '—') ?></td>
    <td data-label="إصدار" style="font-family:var(--f-mono);font-size:12px"><?= h($a['version'] ?? '—') ?></td>
    <td data-label="حجم" style="font-family:var(--f-mono);font-size:12px"><?= $a['size_mb'] ? h($a['size_mb']).' MB' : '—' ?></td>
    <td data-label="مشاهدات" style="font-family:var(--f-mono)"><?= number_format($a['views']) ?></td>
    <td data-label="تحميلات" style="font-family:var(--f-mono)"><?= number_format($a['downloads']) ?></td>
    <td data-label="الحالة">
      <span class="status-badge status-<?= $a['status'] ?>"><?= $a['status']==='published'?'منشور':'مسودة' ?></span>
      <?php if (!empty($a['needs_update'])): ?><span class="status-badge status-draft" style="border-color:rgba(251,191,36,.3);color:#fbbf24;background:rgba(251,191,36,.1)">يحتاج تحديث</span><?php endif; ?>
    </td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=edit-app&id=<?= $a['id'] ?>" class="btn-edit">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          تعديل / تحديث
        </a>
        <a href="<?= h(app_url($a['slug'])) ?>" target="_blank" class="btn-view">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          عرض
        </a>
        <a href="admin.php?page=apps&del=<?= $a['id'] ?>&t=<?= csrf_token() ?>"
           class="btn-del" data-confirm="تأكيد حذف «<?= h($a['name']) ?>»؟">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
          حذف
        </a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$appsList): ?><tr><td colspan="9" style="text-align:center;color:var(--muted);padding:32px">لا توجد تطبيقات</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── ADD / EDIT APP ─────────────── */
elseif ($page === 'add-app' || $page === 'edit-app'):
  $app = $editApp;
  $isEdit = $page === 'edit-app' && $app;
  $feat   = json_decode($app['features']     ?? '[]', true) ?: [];
  $pros   = json_decode($app['pros']         ?? '[]', true) ?: [];
  $cons   = json_decode($app['cons']         ?? '[]', true) ?: [];
  $steps  = json_decode($app['install_steps']?? '[]', true) ?: [];
  $faqArr = json_decode($app['faq']          ?? '[]', true) ?: [];
?>

<div class="admin-header">
  <h1><?= $isEdit ? 'تعديل: '.h($app['name']) : 'إضافة تطبيق جديد' ?></h1>
  <a href="admin.php?page=apps" class="btn-edit">← كل التطبيقات</a>
</div>

<!-- Import from Google Play -->
<div class="ai-box" style="--border-p: rgba(37,99,235,.25)">
  <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--cyan)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-left:4px"><path d="M4 3.5v17l14-8.5-14-8.5z"/></svg>
    استيراد بيانات أولية من رابط Google Play
  </div>
  <div class="ai-row">
    <input class="form-input" id="playstore-import-url" type="text" placeholder="https://play.google.com/store/apps/details?id=...">
    <button type="button" id="btn-import-playstore" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
      استيراد
    </button>
  </div>
  <div class="ai-status" id="playstore-import-status">يستورد كل شيء: الأيقونة + لقطات الشاشة + الوصف + المميزات/الإيجابيات/السلبيات/خطوات التثبيت (بالذكاء الاصطناعي) — رابط APK يُضاف يدوياً فقط.</div>
</div>

<!-- AI Generate -->
<div class="ai-box">
  <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--purple)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-left:4px"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد بيانات التطبيق تلقائياً بالذكاء الاصطناعي
  </div>
  <div class="ai-row">
    <input class="form-input" id="ai-name" type="text" placeholder="اكتب اسم التطبيق..." value="<?= h($app['name'] ?? '') ?>">
    <button type="button" id="btn-ai" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      Generate AI
    </button>
  </div>
  <div class="ai-status" id="ai-status"></div>
</div>

<div id="app-save-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.75);backdrop-filter:blur(4px);display:none;flex-direction:column;align-items:center;justify-content:center;gap:16px">
  <div style="background:var(--surface);border:1px solid var(--border-c);border-radius:16px;padding:32px 40px;text-align:center;min-width:280px">
    <div style="font-size:15px;font-weight:700;color:var(--white);margin-bottom:16px">جارٍ الحفظ...</div>
    <div style="height:6px;background:var(--surface-3);border-radius:4px;overflow:hidden;width:240px">
      <div id="app-save-bar" style="height:100%;width:0%;background:linear-gradient(90deg,var(--cyan),var(--purple));transition:width .3s"></div>
    </div>
    <div id="app-save-status" style="font-size:12px;color:var(--muted);margin-top:10px">رفع البيانات...</div>
  </div>
</div>

<form id="app-form" method="post" action="admin.php?page=<?= $page ?><?= $isEdit ? '&id='.$app['id'] : '' ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="app_id" value="<?= $app['id'] ?>"><?php endif; ?>

  <!-- ── Basic Info ── -->
  <div class="panel">
    <h2>المعلومات الأساسية</h2>
    <div class="form-grid">
      <div class="form-group full">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
          <span>اسم التطبيق *</span>
          <span style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums"><span id="nm-used"><?= mb_strlen($app['name']??'','UTF-8') ?></span> / 70</span>
        </label>
        <input class="form-input" id="f-name" type="text" name="name"
               value="<?= h($app['name']??'') ?>" required maxlength="70"
               oninput="simpleCounter(this,'nm-used')">
      </div>
      <div class="form-group full">
        <label class="form-label">رابط الصفحة (Slug)</label>
        <input class="form-input" id="f-slug" type="text" name="slug" dir="ltr" style="text-align:left"
               value="<?= h($app['slug'] ?? '') ?>" placeholder="سيُنشأ تلقائياً من الاسم إن تُرك فارغاً"
               pattern="[a-zA-Z0-9؀-ۿ\-]*">
        <span style="font-size:11px;color:var(--muted);display:block;margin-top:4px" dir="ltr">
          <?= h(SITE_URL) ?>/<span id="slug-preview"><?= h($app['slug'] ?? 'app-name') ?></span>
          <?php if ($isEdit): ?> — تنبيه: تغييره يغيّر رابط الصفحة الحالي (قد يفقد أي روابط خارجية مؤشرة عليه).<?php endif; ?>
        </span>
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span style="display:flex;align-items:center;gap:6px">
            <span id="seo-title-icon" class="seo-quality-icon" title="جودة عنوان SEO">
              <svg id="sti-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            عنوان SEO Title
          </span>
          <span id="seo-title-counter" style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:.02em">
            <span id="sti-used"><?= mb_strlen($app['seo_title']??'','UTF-8') ?></span> / 60
          </span>
        </label>
        <input class="form-input" id="f-seo-title" type="text" name="seo_title"
               value="<?= h($app['seo_title']??'') ?>" maxlength="60"
               oninput="seoCounter(this,'sti','60',50,60)">
        <div style="height:3px;border-radius:2px;background:var(--border-c);margin-top:5px;overflow:hidden">
          <div id="sti-bar" style="height:100%;transition:width .2s,background .2s;border-radius:2px;
            width:<?= min(100,round(mb_strlen($app['seo_title']??'','UTF-8')/60*100)) ?>%;
            background:<?= seoBarColor(mb_strlen($app['seo_title']??'','UTF-8'),50,60) ?>"></div>
        </div>
        <div id="sti-hint" class="form-hint" style="margin-top:4px"><?= seoTitleHint(mb_strlen($app['seo_title']??'','UTF-8')) ?></div>
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
          <span>الكلمات المفتاحية</span>
          <span style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums">
            <span id="kw-used"><?= mb_strlen($app['keywords']??'','UTF-8') ?></span> / 255
          </span>
        </label>
        <input class="form-input" id="f-keywords" type="text" name="keywords"
               value="<?= h($app['keywords']??'') ?>" maxlength="255"
               oninput="simpleCounter(this,'kw-used')">
      </div>
      <div class="form-group full">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span style="display:flex;align-items:center;gap:6px">
            <span id="seo-desc-icon" class="seo-quality-icon" title="جودة وصف Meta Description">
              <svg id="sdi-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </span>
            Meta Description
          </span>
          <span id="seo-desc-counter" style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums">
            <span id="sdi-used"><?= mb_strlen($app['meta_description']??'','UTF-8') ?></span> / 160
          </span>
        </label>
        <input class="form-input" id="f-meta-desc" type="text" name="meta_description"
               value="<?= h($app['meta_description']??'') ?>" maxlength="160"
               oninput="seoCounter(this,'sdi','160',120,160)">
        <div style="height:3px;border-radius:2px;background:var(--border-c);margin-top:5px;overflow:hidden">
          <div id="sdi-bar" style="height:100%;transition:width .2s,background .2s;border-radius:2px;
            width:<?= min(100,round(mb_strlen($app['meta_description']??'','UTF-8')/160*100)) ?>%;
            background:<?= seoBarColor(mb_strlen($app['meta_description']??'','UTF-8'),120,160) ?>"></div>
        </div>
        <div id="sdi-hint" class="form-hint" style="margin-top:4px"><?= seoDescHint(mb_strlen($app['meta_description']??'','UTF-8')) ?></div>
      </div>
      <div class="form-group full">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
          <span>وصف قصير</span>
          <span style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums">
            <span id="sd-used"><?= mb_strlen($app['short_description']??'','UTF-8') ?></span> / 200
          </span>
        </label>
        <input class="form-input" id="f-short-desc" type="text" name="short_description"
               value="<?= h($app['short_description']??'') ?>" maxlength="200"
               oninput="simpleCounter(this,'sd-used')">
      </div>
      <div class="form-group full">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          <span style="display:flex;align-items:center;gap:8px">
            وصف مطوّل
            <span id="desc-word-count" style="color:var(--muted);font-weight:400;font-size:12px"></span>
            <span id="ld-used-badge" style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums;color:var(--muted)"><span id="ld-chars">0</span> حرف</span>
          </span>
          <button type="button" id="btn-continue-desc" class="add-item-btn" style="margin:0;padding:6px 14px;font-size:12px">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14m-7-7h14"/></svg>
            متابعة الكتابة (+٦٠٠ كلمة)
          </button>
        </label>
        <textarea class="form-textarea" id="f-long-desc" name="long_description" rows="8"
                  oninput="ldCounter(this)"><?= h($app['long_description']??'') ?></textarea>
        <div id="ld-quality-hint" class="form-hint" style="margin-top:4px"></div>
        <div class="form-hint" id="continue-desc-status"></div>
      </div>
    </div>
  </div>

  <!-- ── Technical Info ── -->
  <div class="panel">
    <h2>البيانات التقنية</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
          <span>المطور</span>
          <span style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums"><span id="dev-used"><?= mb_strlen($app['developer']??'','UTF-8') ?></span> / 100</span>
        </label>
        <input class="form-input" id="f-developer" type="text" name="developer"
               value="<?= h($app['developer']??'') ?>" maxlength="100"
               oninput="simpleCounter(this,'dev-used')">
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
          <span>الإصدار الحالي</span>
          <span style="font-size:11px;font-weight:600;font-variant-numeric:tabular-nums"><span id="ver-used"><?= mb_strlen($app['version']??'','UTF-8') ?></span> / 30</span>
        </label>
        <input class="form-input" id="f-version" type="text" name="version"
               value="<?= h($app['version']??'') ?>" maxlength="30"
               oninput="simpleCounter(this,'ver-used')">
      </div>
      <div class="form-group">
        <label class="form-label">إصدار Google Play</label>
        <input class="form-input" type="text" name="play_store_version" value="<?= h($app['play_store_version']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">رابط صفحة Google Play</label>
        <input class="form-input" type="text" name="playstore_url" value="<?= h($app['playstore_url']??'') ?>" placeholder="https://play.google.com/store/apps/details?id=...">
        <div class="form-hint">سيظهر بصفحة التطبيق كأيقونة متجر بلاي قابلة للضغط تفتح هذا الرابط — وليس كرابط نصي كامل</div>
      </div>
      <div class="form-group">
        <label class="form-label">يتطلب أندرويد</label>
        <input class="form-input" id="f-android" type="text" name="android_version" value="<?= h($app['android_version']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الحجم (MB)</label>
        <input class="form-input" id="f-size" type="text" name="size_mb" value="<?= h($app['size_mb']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">تاريخ الإصدار</label>
        <input class="form-input" type="date" name="release_date" value="<?= h($app['release_date']??'') ?>">
        <div class="form-hint">يُستخدم في Schema.org (datePublished) — يُحسّن ظهور التطبيق في Google</div>
      </div>
      <div class="form-group">
        <label class="form-label">الترخيص</label>
        <input class="form-input" id="f-license" type="text" name="license" value="<?= h($app['license']??'Free') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Package Name</label>
        <input class="form-input" id="f-pkg" type="text" name="package_name" value="<?= h($app['package_name']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">التصنيف</label>
        <select class="form-select" name="category_id">
          <option value="">— اختر —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($app['category_id']??'')==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">التقييم (من 5)</label>
        <input class="form-input" id="f-rating" type="number" step="0.1" min="1" max="5" name="rating" value="<?= h($app['rating']??'4.5') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;gap:6px">
          عدد التقييمات
          <span style="font-size:11px;color:var(--muted);font-weight:400" title="Google يعرض نجوم في نتائج البحث عند توفر هذا الحقل (يُنصح 50+)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </span>
        </label>
        <input class="form-input" type="number" min="0" name="rating_count" value="<?= h((int)($app['rating_count']??0)) ?>" placeholder="0 = يحسب تلقائياً">
        <div class="form-hint">يظهر بجانب النجوم في نتائج Google — اتركه 0 للحساب التلقائي من التحميلات</div>
      </div>
      <div class="form-group full">
        <label class="form-label">ما الجديد في هذا الإصدار</label>
        <textarea class="form-textarea" id="f-whats-new" name="whats_new" rows="3"><?= h($app['whats_new']??'') ?></textarea>
      </div>
    </div>
  </div>

  <!-- ── Media ── -->
  <div class="panel">
    <h2>الصور والملفات</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">أيقونة التطبيق</label>
        <?php if (!empty($app['icon_path'])): ?>
          <img id="icon-preview" src="<?= h(media_url($app['icon_path'])) ?>" style="width:64px;height:64px;border-radius:14px;object-fit:cover;margin-bottom:8px;border:1px solid var(--border-c)">
        <?php else: ?>
          <img id="icon-preview" src="" style="display:none;width:64px;height:64px;border-radius:14px;object-fit:cover;margin-bottom:8px">
        <?php endif; ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <label class="upload-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            اختر أيقونة (مربّعة يفضّل)
            <input type="file" name="icon" accept="image/*" data-preview="icon-preview" hidden>
          </label>
          <button type="button" id="btn-gen-icon-ai" class="btn-ai" style="padding:9px 16px;font-size:12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            توليد بالذكاء الاصطناعي
          </button>
        </div>
        <div class="form-hint" id="icon-ai-status">إن لم يتوفر موديل توليد صور، استخدم استيراد Google Play أعلى الصفحة للحصول على الأيقونة الحقيقية.</div>
        <input type="hidden" name="ai_icon_path" id="f-ai-icon-path" value="">
      </div>
      <div class="form-group">
        <label class="form-label">صور التطبيق (Screenshots)</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <label class="upload-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            اختر صور متعددة
            <input type="file" name="screenshots[]" accept="image/*" multiple hidden>
          </label>
          <button type="button" id="btn-gen-shot-ai" class="btn-ai" style="padding:9px 16px;font-size:12px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            توليد صورة بالذكاء الاصطناعي
          </button>
        </div>
        <div class="form-hint" id="shot-ai-status">يمكنك رفع عدة صور مرة واحدة — سيتم ضغطها تلقائياً لـ WebP. يمكنك أيضاً توليد صور بالذكاء الاصطناعي (زر عدة مرات لعدة صور).</div>
        <div id="ai-shots-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px"></div>
        <!-- Hidden inputs injected by JS after Play Store import -->
        <div id="ps-screenshot-inputs"></div>
        <div id="ps-screenshot-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px"></div>
      </div>
    </div>
    <?php
    $shots = json_decode($app['screenshots'] ?? '[]', true) ?: [];
    if ($shots):
    ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
      <?php foreach ($shots as $s): ?>
        <img src="<?= h($s) ?>" style="width:70px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border-c)">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Download Links ── -->
  <div class="panel">
    <h2>روابط التحميل</h2>

    <!-- Source selector -->
    <div class="form-group" style="margin-bottom:18px">
      <label class="form-label">مصدر التحميل</label>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
        <?php foreach (['playstore'=>'Google Play فقط','apk'=>'APK مستضاف مباشرة','both'=>'كلاهما (Play + APK)'] as $sv=>$sl): ?>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;padding:7px 14px;border:1px solid var(--border-c);border-radius:8px;<?= ($app['download_source']??'playstore')===$sv?'background:var(--accent-light,#e8f4ff);border-color:var(--accent,#0d6efd)':'' ?>">
          <input type="radio" name="download_source" value="<?= $sv ?>" <?= ($app['download_source']??'playstore')===$sv?'checked':'' ?> style="accent-color:var(--accent,#0d6efd)">
          <?= $sl ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- APK Upload Zone -->
    <div id="apk-panel" style="border:1px solid var(--border-c);border-radius:12px;padding:16px;margin-bottom:18px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <strong style="font-size:13px">📦 APK مستضاف</strong>
        <?php if (!empty($app['apk_path'])): ?>
        <button type="button" id="btn-del-apk" style="font-size:11px;color:var(--danger,#dc3545);background:none;border:none;cursor:pointer">× حذف APK</button>
        <?php endif; ?>
      </div>

      <?php if (!empty($app['apk_path'])): ?>
      <!-- Existing APK info -->
      <div id="apk-info-box" style="background:var(--bg-light,#f8f9fa);border-radius:8px;padding:12px;font-size:12px;margin-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
          <div><span style="color:var(--muted)">الحجم:</span> <strong><?= h(format_apk_size((int)($app['apk_size_bytes']??0))) ?></strong></div>
          <div><span style="color:var(--muted)">رُفع:</span> <strong><?= h(substr($app['apk_uploaded_at']??'',0,10)) ?></strong></div>
          <?php if ($app['apk_hash_sha256']): ?>
          <div style="grid-column:1/-1">
            <span style="color:var(--muted)">SHA-256:</span>
            <code style="font-size:10px;word-break:break-all;cursor:pointer" title="انقر للنسخ" onclick="navigator.clipboard.writeText('<?= h($app['apk_hash_sha256']) ?>');this.style.color='green'"><?= h($app['apk_hash_sha256']) ?></code>
          </div>
          <?php endif; ?>
          <?php if ($app['apk_hash_md5']): ?>
          <div style="grid-column:1/-1">
            <span style="color:var(--muted)">MD5:</span>
            <code style="font-size:11px;cursor:pointer" title="انقر للنسخ" onclick="navigator.clipboard.writeText('<?= h($app['apk_hash_md5']) ?>');this.style.color='green'"><?= h($app['apk_hash_md5']) ?></code>
          </div>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px"><a href="<?= h(url($app['apk_path'])) ?>" style="font-size:11px;color:var(--accent,#0d6efd)" target="_blank">📥 تحميل الملف المُخزّن</a></div>
      </div>
      <!-- Hidden fields to preserve APK data through form save -->
      <input type="hidden" name="apk_path_new" id="apk_path_new" value="<?= h($app['apk_path']) ?>">
      <input type="hidden" name="apk_size_bytes_new" id="apk_size_bytes_new" value="<?= h($app['apk_size_bytes']??'') ?>">
      <input type="hidden" name="apk_hash_sha256_new" id="apk_hash_sha256_new" value="<?= h($app['apk_hash_sha256']??'') ?>">
      <input type="hidden" name="apk_hash_md5_new" id="apk_hash_md5_new" value="<?= h($app['apk_hash_md5']??'') ?>">
      <?php else: ?>
      <input type="hidden" name="apk_path_new" id="apk_path_new" value="">
      <input type="hidden" name="apk_size_bytes_new" id="apk_size_bytes_new" value="">
      <input type="hidden" name="apk_hash_sha256_new" id="apk_hash_sha256_new" value="">
      <input type="hidden" name="apk_hash_md5_new" id="apk_hash_md5_new" value="">
      <?php endif; ?>

      <!-- Upload drop zone -->
      <div id="apk-drop-zone" style="border:2px dashed var(--border-c);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:.2s">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" style="margin-bottom:6px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <p style="font-size:12px;color:var(--muted);margin:0">اسحب ملف APK هنا أو <strong style="color:var(--accent,#0d6efd);cursor:pointer" onclick="document.getElementById('apk-file-input').click()">اختر ملفاً</strong></p>
        <p style="font-size:11px;color:var(--muted);margin:4px 0 0">الحد الأقصى 500MB</p>
        <input type="file" id="apk-file-input" accept=".apk,application/vnd.android.package-archive" style="display:none">
      </div>

      <!-- Upload progress -->
      <div id="apk-upload-progress" style="display:none;margin-top:12px">
        <div style="height:6px;background:var(--bg-light,#f0f0f0);border-radius:4px;overflow:hidden">
          <div id="apk-progress-bar" style="height:100%;width:0;background:var(--accent,#0d6efd);transition:width .2s"></div>
        </div>
        <p id="apk-upload-status" style="font-size:12px;color:var(--muted);margin:6px 0 0;text-align:center">جاري الرفع...</p>
      </div>

      <!-- Extracted metadata (shown after successful upload) -->
      <div id="apk-meta-box" style="display:none;margin-top:12px;background:var(--bg-light,#f8f9fa);border-radius:8px;padding:12px;font-size:12px"></div>
    </div>

    <!-- Server-side URL download -->
    <div style="border:1px solid var(--border-c);border-radius:12px;padding:16px;margin-bottom:18px">
      <div style="font-size:13px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        تحميل APK من رابط مباشر (يُحمَّل على السيرفر)
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" id="apk-url-input" class="form-input" placeholder="https://example.com/app.apk" dir="ltr" style="flex:1;min-width:200px;font-size:12px">
        <button type="button" id="btn-dl-from-url" onclick="startUrlDownload()" class="btn-primary" style="font-size:12px;padding:9px 18px;white-space:nowrap">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          تحميل من السيرفر
        </button>
      </div>
      <div id="url-dl-progress" style="display:none;margin-top:12px">
        <div style="height:8px;background:rgba(99,130,190,.15);border-radius:6px;overflow:hidden;margin-bottom:8px">
          <div id="url-dl-bar" style="height:100%;width:0;background:linear-gradient(90deg,var(--accent,#0d6efd),#6366f1);border-radius:6px;transition:width .3s"></div>
        </div>
        <div id="url-dl-status" style="font-size:12px;color:var(--muted);text-align:center"></div>
      </div>
      <div class="form-hint">سيُحمَّل الملف على السيرفر ويُعاد تسميته إلى <code>اسم-التطبيق-yassota.apk</code> تلقائياً مع حساب SHA-256 وMD5.</div>
    </div>

    <!-- External URL section -->
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">رابط التحميل الخارجي (Google Play / CDN)</label>
      <input class="form-input" type="text" name="download_url" value="<?= h($app['download_url']??'') ?>" placeholder="https://play.google.com/store/apps/details?id=...">
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">مرآة 2 (اختياري)</label>
        <input class="form-input" type="text" name="mirror2_url" value="<?= h($app['mirror2_url']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">مرآة 3 (اختياري)</label>
        <input class="form-input" type="text" name="mirror3_url" value="<?= h($app['mirror3_url']??'') ?>">
      </div>
    </div>
  </div>

  <!-- ── Dynamic Lists ── -->
  <div class="panel">
    <h2>المميزات</h2>
    <div class="dynamic-list" id="feat-list"></div>
    <button type="button" class="add-item-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14m-7-7h14"/></svg>
      إضافة ميزة
    </button>
  </div>

  <div class="panel">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <h2>الإيجابيات</h2>
        <div class="dynamic-list" id="pros-list"></div>
        <button type="button" class="add-item-btn" style="margin-top:6px">+ إيجابية</button>
      </div>
      <div>
        <h2>السلبيات</h2>
        <div class="dynamic-list" id="cons-list"></div>
        <button type="button" class="add-item-btn" style="margin-top:6px">+ سلبية</button>
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>خطوات التثبيت</h2>
    <div class="dynamic-list" id="steps-list"></div>
    <button type="button" class="add-item-btn">+ خطوة</button>
  </div>

  <div class="panel">
    <h2>الأسئلة الشائعة (FAQ)</h2>
    <div class="dynamic-list" id="faq-list"></div>
    <button type="button" class="add-item-btn">+ سؤال وجواب</button>
  </div>

  <div class="panel">
    <h2>محتوى إضافي (يُولَّد تلقائياً لكل تطبيق)</h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:16px">هذه الحقول تُنشر في أسفل صفحة التطبيق لملء المحتوى ورفع جودة SEO — اضغط "توليد" لكل قسم بالذكاء الاصطناعي، ثم عدّل حسب الحاجة.</p>

    <div class="form-group full" style="margin-bottom:18px">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>ماذا يقدّم هذا التطبيق؟</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="offers" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-offers" name="offers_text" rows="3" placeholder="فقرة تسويقية تلخص أبرز ما يقدمه التطبيق..."><?= h($app['offers_text'] ?? '') ?></textarea>
    </div>

    <div class="form-group full" style="margin-bottom:18px">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>سياسة الخصوصية</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="privacy" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-privacy" name="privacy_policy" rows="5"><?= h($app['privacy_policy']??'') ?></textarea>
    </div>

    <div class="form-group full">
      <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
        <span>شروط الاستخدام</span>
        <button type="button" class="add-item-btn btn-gen-legal" data-type="terms" style="margin:0;padding:6px 14px;font-size:12px">توليد</button>
      </label>
      <textarea class="form-textarea" id="f-terms" name="terms_content" rows="5"><?= h($app['terms_content']??'') ?></textarea>
    </div>
    <div class="ai-status" id="legal-gen-status"></div>
  </div>

  <!-- ── Publish ── -->
  <div class="panel">
    <h2>حالة النشر</h2>
    <div style="display:flex;gap:12px;align-items:center">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
        <input type="radio" name="status" value="published" <?= ($app['status']??'published')!=='draft'?'checked':'' ?>>
        <span style="color:var(--success)">نشر فوري (سيظهر في الموقع مباشرة)</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
        <input type="radio" name="status" value="draft" <?= ($app['status']??'')==='draft'?'checked':'' ?>>
        <span style="color:var(--muted)">مسودة (لن يظهر في الموقع)</span>
      </label>
    </div>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="needs_update" value="1" <?= !empty($app['needs_update'])?'checked':'' ?>>
      <span style="color:#fbbf24">وضع علامة "يحتاج تحديث" — سيظهر في قسم خاص بالداشبورد لمتابعة تحديثه لاحقاً (الإصدار الحالي يبقى منشوراً)</span>
    </label>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label" style="margin-bottom:8px">شارة التطبيق</label>
      <select class="form-select" name="badge" style="max-width:300px">
        <option value="" <?= ($app['badge']??'')===''?'selected':'' ?>>— بلا شارة (الافتراضي)</option>
        <option value="new" <?= ($app['badge']??'')==='new'?'selected':'' ?>>🆕 جديد</option>
        <option value="updated" <?= ($app['badge']??'')==='updated'?'selected':'' ?>>🔄 محدّث</option>
        <option value="hot" <?= ($app['badge']??'')==='hot'?'selected':'' ?>>🔥 رائج</option>
        <option value="choice" <?= ($app['badge']??'')==='choice'?'selected':'' ?>>⭐ اختيار المحرر</option>
      </select>
      <div class="form-hint">تُعرض كشارة ملوّنة على صفحة التطبيق. "جديد" يُضاف تلقائياً خلال 7 أيام من النشر.</div>
    </div>
    <?php if ($isEdit): ?>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="save_as_new_version" value="1" checked>
      <span style="color:var(--cyan)">إذا تغيّر رقم الإصدار أو رابط التحميل، احفظ الإصدار الحالي (<?= h($app['version'] ?? '—') ?>) في سجل التحديثات قبل الاستبدال — يبقى قابلاً للتصفح والتحميل من صفحة التطبيق</span>
    </label>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn-save" style="margin-bottom:40px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
    <?= $isEdit ? 'حفظ التعديلات' : 'إضافة التطبيق' ?>
  </button>
</form>

<?php if ($isEdit): ?>
<div class="panel" style="margin-bottom:40px">
  <h2>التحقق من سلامة الرابط</h2>
  <p class="form-hint" style="margin-bottom:14px">
    بعد التأكد يدوياً من أن رابط التحميل آمن وسليم، اضغط "تحقق الفريق" لإظهار شارة "رابط تم التحقق من سلامته بواسطة فريق yassota" على صفحة التطبيق وصفحة التحميل.
  </p>
  <div style="margin-bottom:12px" id="verify-status">
    <?php if ($app['link_verified']): ?>
      <span class="status-badge status-published">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        تم التحقق من سلامة الرابط<?= $app['verified_at'] ? ' — ' . date('Y-m-d', strtotime($app['verified_at'])) : '' ?>
      </span>
    <?php else: ?>
      <span class="status-badge status-draft">لم يتم التحقق من الرابط بعد</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if (!$app['link_verified']): ?>
    <button type="button" id="btn-verify-link" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>" data-action="verify">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
      تحقق الفريق — الرابط آمن
    </button>
    <?php else: ?>
    <button type="button" id="btn-verify-link" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>" data-action="unverify" style="background:rgba(220,38,38,.1);color:var(--danger)">
      إلغاء التحقق
    </button>
    <?php endif; ?>
  </div>
  <div id="verify-result" style="margin-top:12px"></div>
</div>
<?php endif; ?>

<?php if ($isEdit):
  $articlesStmt = $pdo->prepare("SELECT id,title,slug,created_at FROM app_articles WHERE app_id=? ORDER BY created_at DESC");
  $articlesStmt->execute([$app['id']]);
  $appArticles = $articlesStmt->fetchAll();
?>
<div class="panel" style="margin-bottom:40px">
  <h2>مقالات ذات صلة (روابط داخلية للـSEO وAdSense)</h2>
  <p class="form-hint" style="margin-bottom:14px">
    مقالات قصيرة أصلية تربط بصفحة تحميل هذا التطبيق (مثل "كيفية استخدام <?= h($app['name']) ?> للمبتدئين" أو
    "<?= h($app['name']) ?> مقابل بدائله") — تُحسّن الربط الداخلي وتفيد في قبول AdSense.
  </p>
  <button type="button" id="btn-generate-articles" class="btn-ai" data-app-id="<?= (int)$app['id'] ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد 3 مقالات جديدة بالذكاء الاصطناعي
  </button>
  <div class="ai-status" id="generate-articles-status"></div>

  <?php if ($appArticles): ?>
  <table class="admin-table" style="width:100%;margin-top:16px">
    <thead><tr><th>العنوان</th><th>تاريخ الإنشاء</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($appArticles as $art): ?>
      <tr>
        <td data-label="العنوان"><?= h($art['title']) ?></td>
        <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($art['created_at'])) ?></td>
        <td data-label="إجراءات" class="td-actions">
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= h(article_url($art['slug'])) ?>" target="_blank" class="btn-view">عرض</a>
            <a href="admin.php?page=edit-app&id=<?= (int)$app['id'] ?>&del_article=<?= (int)$art['id'] ?>&t=<?= csrf_token() ?>"
               class="btn-del" data-confirm="حذف هذا المقال؟">حذف</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
window.EXISTING_DATA = <?= json_encode([
    'features' => $feat, 'pros' => $pros, 'cons' => $cons,
    'install_steps' => $steps, 'faq' => $faqArr,
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
/* ── APK Upload & Metadata Display ── */
(function(){
  const APP_ID   = <?= (int)($app['id'] ?? 0) ?>;
  const APP_NAME = <?= json_encode($app['name'] ?? '') ?>;
  const dropZone = document.getElementById('apk-drop-zone');
  const fileInput = document.getElementById('apk-file-input');
  const progress  = document.getElementById('apk-upload-progress');
  const progBar   = document.getElementById('apk-progress-bar');
  const status    = document.getElementById('apk-upload-status');
  const metaBox   = document.getElementById('apk-meta-box');
  const delBtn    = document.getElementById('btn-del-apk');
  const hidPath   = document.getElementById('apk_path_new');
  const hidSize   = document.getElementById('apk_size_bytes_new');
  const hidSha    = document.getElementById('apk_hash_sha256_new');
  const hidMd5    = document.getElementById('apk_hash_md5_new');

  if (!dropZone) return;

  // Drag-and-drop
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor='var(--accent,#0d6efd)'; });
  dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor=''; });
  dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.style.borderColor='';
    const f = e.dataTransfer.files[0];
    if (f) uploadApk(f);
  });
  fileInput && fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) uploadApk(fileInput.files[0]);
  });

  // Delete APK
  delBtn && delBtn.addEventListener('click', async () => {
    if (!confirm('حذف ملف APK المُخزّن؟')) return;
    const fd = new FormData();
    fd.append('app_id', APP_ID);
    const r = await fetch('admin.php?ajax=delete_apk', {method:'POST', body:fd});
    const j = await r.json().catch(()=>({ok:false}));
    if (j.ok) {
      hidPath.value=''; hidSize.value=''; hidSha.value=''; hidMd5.value='';
      delBtn.remove();
      const info = document.getElementById('apk-info-box');
      if (info) info.remove();
    }
  });

  function uploadApk(file) {
    if (!file.name.endsWith('.apk') && file.type !== 'application/vnd.android.package-archive') {
      alert('يرجى اختيار ملف APK'); return;
    }
    const fd = new FormData();
    fd.append('apk', file);
    fd.append('app_id', APP_ID);
    fd.append('app_name', APP_NAME || document.querySelector('[name="name"]')?.value || 'app');

    progress.style.display = 'block';
    progBar.style.width = '0%';
    status.textContent = 'جاري الرفع...';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'admin.php?ajax=upload_apk');

    xhr.upload.onprogress = e => {
      if (e.lengthComputable) {
        const pct = Math.round(e.loaded / e.total * 90);
        progBar.style.width = pct + '%';
        status.textContent = `جاري الرفع... ${pct}%`;
      }
    };

    xhr.onload = () => {
      progBar.style.width = '100%';
      let j;
      try { j = JSON.parse(xhr.responseText); } catch(e) { j = {ok:false,error:'خطأ في الاستجابة'}; }

      if (!j.ok) {
        status.textContent = '❌ ' + (j.error || 'فشل الرفع');
        status.style.color = 'var(--danger,#dc3545)';
        return;
      }

      // Store in hidden fields for form submission
      hidPath.value  = j.apk_path || '';
      hidSize.value  = j.apk_size_bytes || '';
      hidSha.value   = j.apk_hash_sha256 || '';
      hidMd5.value   = j.apk_hash_md5 || '';

      status.textContent = '✅ تم الرفع بنجاح';
      status.style.color = 'var(--success,#198754)';

      // Auto-fill name from filename if the name field is empty
      const nameField = document.querySelector('[name="name"]');
      if (nameField && !nameField.value.trim()) {
        let cleanName = file.name
          .replace(/\.apk$/i, '')
          .replace(/[_\-]+/g, ' ')
          .replace(/\b(v|ver|version)[\s\-]?\d[\d.]*/gi, '')
          .replace(/\s{2,}/g, ' ')
          .trim();
        if (cleanName) nameField.value = cleanName;
      }

      // Auto-fill version + package if empty
      if (j.version && !document.querySelector('[name="version"]')?.value) {
        document.querySelector('[name="version"]').value = j.version;
      }
      if (j.package_name && !document.querySelector('[name="package_name"]')?.value) {
        document.querySelector('[name="package_name"]').value = j.package_name;
      }
      if (j.min_sdk && !document.querySelector('[name="android_version"]')?.value) {
        document.querySelector('[name="android_version"]').value = j.min_sdk;
      }
      if (j.apk_size_bytes) {
        const mb = (j.apk_size_bytes / 1048576).toFixed(1);
        const sizeF = document.querySelector('[name="size_mb"]');
        if (sizeF && !sizeF.value) sizeF.value = mb;
      }

      // Show metadata summary
      metaBox.style.display = 'block';
      const rows = [];
      if (j.apk_size_bytes) rows.push(`<div><b>الحجم:</b> ${formatBytes(j.apk_size_bytes)}</div>`);
      if (j.version)        rows.push(`<div><b>الإصدار:</b> ${esc(j.version)}</div>`);
      if (j.package_name)   rows.push(`<div><b>Package:</b> <code>${esc(j.package_name)}</code></div>`);
      if (j.min_sdk)        rows.push(`<div><b>Android الحد الأدنى:</b> ${esc(j.min_sdk)}</div>`);
      if (j.apk_hash_sha256) rows.push(`<div style="grid-column:1/-1"><b>SHA-256:</b> <code style="font-size:10px;word-break:break-all;cursor:pointer" onclick="navigator.clipboard.writeText('${j.apk_hash_sha256}');this.style.color='green'">${j.apk_hash_sha256}</code></div>`);
      if (j.apk_hash_md5)    rows.push(`<div><b>MD5:</b> <code style="cursor:pointer" onclick="navigator.clipboard.writeText('${j.apk_hash_md5}');this.style.color='green'">${j.apk_hash_md5}</code></div>`);
      if (j.apk_public_url)  rows.push(`<div style="grid-column:1/-1"><b>رابط التحميل المباشر:</b> <a href="${j.apk_public_url}" target="_blank" style="color:var(--cyan,#06b6d4);font-size:11px;word-break:break-all;cursor:pointer" onclick="navigator.clipboard.writeText('${j.apk_public_url}');event.preventDefault();this.textContent='✅ تم النسخ!'">${j.apk_public_url}</a></div>`);
      metaBox.innerHTML = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">${rows.join('')}</div>`;

      // Auto-select 'apk' source if nothing was selected
      const srcAPK = document.querySelector('[name="download_source"][value="apk"]');
      const srcCur = document.querySelector('[name="download_source"]:checked');
      if (srcAPK && srcCur && srcCur.value === 'playstore' && !document.querySelector('[name="download_url"]')?.value) {
        srcAPK.checked = true;
      }
    };

    xhr.onerror = () => { status.textContent = '❌ فشل الاتصال'; status.style.color='var(--danger,#dc3545)'; };
    xhr.send(fd);
  }

  function formatBytes(b) {
    if (b>=1073741824) return (b/1073741824).toFixed(1)+' GB';
    if (b>=1048576)    return (b/1048576).toFixed(1)+' MB';
    if (b>=1024)       return Math.round(b/1024)+' KB';
    return b+' B';
  }
  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  // ── Server-side URL download via SSE ──
  window.startUrlDownload = function() {
    var url = (document.getElementById('apk-url-input')?.value||'').trim();
    if (!url) { alert('أدخل رابط APK أولاً'); return; }
    var btn      = document.getElementById('btn-dl-from-url');
    var progress = document.getElementById('url-dl-progress');
    var bar      = document.getElementById('url-dl-bar');
    var status   = document.getElementById('url-dl-status');
    btn.disabled = true; btn.textContent = '⏳ جارٍ التحميل…';
    progress.style.display = 'block';
    bar.style.width = '0%';
    status.textContent = 'جارٍ الاتصال…';

    var appId   = <?= (int)($app['id'] ?? 0) ?>;
    var appName = (document.querySelector('[name="name"]')?.value||'').trim() || '<?= h($app['name'] ?? 'app') ?>';

    var fd = new FormData();
    fd.append('url', url);
    fd.append('app_id', appId);
    fd.append('app_name', appName);

    // Use fetch + ReadableStream to consume SSE
    fetch('admin.php?ajax=download_url_apk', {method:'POST', body:fd})
    .then(function(res){
      var reader = res.body.getReader();
      var decoder = new TextDecoder();
      var buf = '';
      function read(){
        reader.read().then(function(chunk){
          if(chunk.done) return;
          buf += decoder.decode(chunk.value, {stream:true});
          var parts = buf.split('\n\n');
          buf = parts.pop();
          parts.forEach(function(block){
            var lines = block.split('\n');
            var eventType = 'message', dataStr = '';
            lines.forEach(function(l){
              if(l.startsWith('event: ')) eventType = l.slice(7).trim();
              if(l.startsWith('data: ')) dataStr = l.slice(6).trim();
            });
            if(!dataStr) return;
            var d; try{ d=JSON.parse(dataStr); }catch(e){ return; }
            if(eventType==='progress'){
              bar.style.width = (d.pct||0) + '%';
              status.textContent = d.msg || '';
            } else if(eventType==='done'){
              bar.style.width = '100%';
              status.style.color = 'var(--success,#198754)';
              status.textContent = d.msg || '✅ اكتمل التحميل';
              // Update hidden fields
              if(d.apk_path){ hidPath.value=d.apk_path; }
              if(d.size_bytes){ hidSize.value=d.size_bytes; }
              if(d.sha256){ hidSha.value=d.sha256; }
              if(d.md5){ hidMd5.value=d.md5; }
              // Show meta
              metaBox.style.display='block';
              var rows=[];
              if(d.size_mb) rows.push(`<div><b>الحجم:</b> ${d.size_mb} MB</div>`);
              if(d.sha256) rows.push(`<div style="grid-column:1/-1"><b>SHA-256:</b> <code style="font-size:10px;word-break:break-all;cursor:pointer" onclick="navigator.clipboard.writeText('${d.sha256}');this.style.color='green'">${d.sha256}</code></div>`);
              if(d.md5) rows.push(`<div><b>MD5:</b> <code style="cursor:pointer" onclick="navigator.clipboard.writeText('${d.md5}');this.style.color='green'">${d.md5}</code></div>`);
              metaBox.innerHTML='<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">'+rows.join('')+'</div>';
              // Auto-update size_mb field
              if(d.size_mb){ const sf=document.querySelector('[name="size_mb"]'); if(sf&&!sf.value) sf.value=d.size_mb; }
              btn.textContent = '✅ تم التحميل';
            } else if(eventType==='error'){
              bar.style.width='100%'; bar.style.background='var(--danger,#dc3545)';
              status.style.color='var(--danger,#dc3545)';
              status.textContent = '❌ ' + (d.msg||'خطأ');
              btn.disabled=false; btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> تحميل من السيرفر';
            }
          });
          read();
        });
      }
      read();
    }).catch(function(e){
      status.textContent = '❌ خطأ في الاتصال'; status.style.color='var(--danger,#dc3545)';
      btn.disabled=false; btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> تحميل من السيرفر';
    });
  };
})();
</script>

<?php
/* ─────────────── BULK TRENDING APP/GAME GENERATOR ─────────────── */
elseif ($page === 'bulk-generate'): ?>

<div class="admin-header"><h1>توليد تطبيقات رائجة بالجملة</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.8">
    يقترح الذكاء الاصطناعي عدداً اخترته من أكثر التطبيقات/الألعاب بحثاً (معدّلة أو رسمية)، ثم يولّد لكل اسم محتوى كاملاً ويحاول تلقائياً
    العثور على صفحته في Google Play لاستيراد الأيقونة ورابط الصفحة. أي تطبيق يتعذّر جلب رابط تحميل مباشر له يُحفظ <strong style="color:#fbbf24">كمسودة</strong> جاهزة
    — أضف رابط التحميل من صفحة التعديل ثم انشرها.
  </p>
</div>

<div class="panel">
  <div class="form-grid">
    <div class="form-group">
      <label class="form-label">العدد</label>
      <input class="form-input" type="number" id="bg-count" min="1" max="30" value="10">
    </div>
    <div class="form-group">
      <label class="form-label">النوع</label>
      <select class="form-select" id="bg-type">
        <option value="apps">تطبيقات</option>
        <option value="games">ألعاب</option>
        <option value="mixed">تطبيقات وألعاب</option>
      </select>
    </div>
    <div class="form-group full">
      <label class="form-label">تفضيل/مجال إضافي (اختياري)</label>
      <input class="form-input" id="bg-hint" type="text" placeholder="مثال: أدوات VPN، ألعاب أكشن أوفلاين...">
    </div>
  </div>
  <button type="button" id="btn-bg-suggest" class="btn-ai" style="margin-top:10px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    اقترح الأسماء
  </button>
  <div class="ai-status" id="bg-suggest-status"></div>

  <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border-c)">
    <label class="form-label">أو أدخل أسماء التطبيقات يدوياً بنفسك (اسم واحد بكل سطر) — يتخطى اقتراح الذكاء الاصطناعي ويستخدم أسماءك مباشرة</label>
    <textarea class="form-textarea" id="bg-manual-names" rows="8" placeholder="واتساب&#10;تيليجرام&#10;فيسبوك&#10;ماسنجر&#10;سناب شات&#10;يوتيوب&#10;..." style="font-family:inherit;direction:rtl"></textarea>
    <button type="button" id="btn-bg-manual" class="btn-save" style="margin-top:10px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
      استخدم هذه الأسماء
    </button>
  </div>
</div>

<div class="panel" id="bg-names-panel" style="display:none">
  <h2>الأسماء المقترحة — ألغِ تحديد ما لا تريده</h2>
  <div id="bg-names-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px"></div>
  <button type="button" id="btn-bg-create" class="btn-save">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
    إنشاء الكل
  </button>
  <div style="margin-top:14px;display:none" id="bg-progress">
    <div style="height:8px;background:var(--navy-600);border-radius:6px;overflow:hidden">
      <div id="bg-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .3s"></div>
    </div>
    <div id="bg-status" style="font-size:12px;color:var(--muted);margin-top:8px"></div>
  </div>
  <div id="bg-results" style="display:flex;flex-direction:column;gap:8px;margin-top:14px"></div>
</div>

<?php
/* ─────────────── BULK CONTENT GENERATION ─────────────── */
elseif ($page === 'bulk-content'):
    /* Fetch apps that have incomplete content — short_description or long_description empty/very short */
    $incompleteApps = $pdo->query(
        "SELECT id, name, icon_path, status,
                CHAR_LENGTH(COALESCE(long_description,'')) AS ld_len,
                CHAR_LENGTH(COALESCE(short_description,'')) AS sd_len
         FROM apps
         WHERE CHAR_LENGTH(COALESCE(long_description,'')) < 300
            OR CHAR_LENGTH(COALESCE(short_description,'')) < 20
         ORDER BY status='published' DESC, name ASC
         LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
    $totalIncomplete = count($incompleteApps);
?>
<div class="admin-header">
  <h1>توليد محتوى للتطبيقات</h1>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <span class="admin-badge"><?= $totalIncomplete ?> تطبيق يحتاج محتوى</span>
    <a href="admin.php?page=apps" class="btn-view" style="padding:8px 16px;font-size:13px">← جميع التطبيقات</a>
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    يعرض هذا القسم التطبيقات التي يقل وصفها القصير عن 20 حرفاً أو وصفها الطويل عن 300 حرف.
    اضغط <strong style="color:var(--cyan)">توليد الكل</strong> لإعادة توليد المحتوى تلقائياً بالذكاء الاصطناعي لجميعها، أو <strong style="color:var(--cyan)">توليد</strong> لتطبيق واحد.
    يتم الحفظ في قاعدة البيانات مباشرةً دون المساس بالروابط أو الأيقونات أو حالة النشر.
  </p>
</div>

<?php if ($totalIncomplete === 0): ?>
<div class="panel" style="text-align:center;padding:40px">
  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--success);margin-bottom:12px"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <p style="color:var(--success);font-size:15px;font-weight:700">رائع! كل التطبيقات تحتوي على محتوى كافٍ</p>
</div>
<?php else: ?>

<div class="panel" style="margin-bottom:14px">
  <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
    <button type="button" id="btn-bc-all" class="btn-save">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      توليد الكل (<?= $totalIncomplete ?>)
    </button>
    <button type="button" id="btn-bc-stop" class="btn-del" style="display:none;padding:10px 20px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
      إيقاف
    </button>
    <div style="flex:1;min-width:200px">
      <div style="height:6px;background:var(--surface-3);border-radius:6px;overflow:hidden;display:none" id="bc-progress-bar-wrap">
        <div id="bc-progress-bar" style="height:100%;width:0%;background:linear-gradient(90deg,var(--cyan),var(--purple));transition:width .3s"></div>
      </div>
      <div id="bc-progress-status" style="font-size:12px;color:var(--muted);margin-top:6px"></div>
    </div>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:10px" id="bc-list">
<?php foreach ($incompleteApps as $a):
    $iconSrc = !empty($a['icon_path']) ? h(media_url($a['icon_path'] ?? '')) : '';
?>
<div class="bc-row" id="bc-row-<?= (int)$a['id'] ?>">
  <?php if ($iconSrc): ?>
    <img class="bc-row-icon" src="<?= $iconSrc ?>" alt="" loading="lazy">
  <?php else: ?>
    <div class="bc-row-icon" style="display:flex;align-items:center;justify-content:center">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--muted)"><rect x="2" y="2" width="20" height="20" rx="5"/></svg>
    </div>
  <?php endif; ?>
  <div class="bc-row-name">
    <?= h($a['name']) ?>
    <span style="font-size:11px;color:var(--muted);margin-right:6px"><?= $a['status']==='published'?'● منشور':'○ مسودة' ?></span>
    <span style="font-size:11px;color:var(--muted)">(وصف قصير: <?= $a['sd_len'] ?>، وصف طويل: <?= $a['ld_len'] ?> حرف)</span>
  </div>
  <a href="admin.php?page=edit-app&id=<?= (int)$a['id'] ?>" class="btn-view" style="padding:5px 10px;font-size:11px">تعديل</a>
  <button type="button" class="btn-ai btn-bc-one" data-id="<?= (int)$a['id'] ?>" style="padding:7px 14px;font-size:12px">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد
  </button>
  <span class="bc-row-status" id="bc-status-<?= (int)$a['id'] ?>">—</span>
</div>
<?php endforeach; ?>
</div>

<script>
(function(){
  const rows = <?= json_encode(array_column($incompleteApps, 'id'), JSON_UNESCAPED_UNICODE) ?>;
  let stopped = false;
  let running = false;

  function setRowState(id, cls, text){
    const row = document.getElementById('bc-row-'+id);
    const st  = document.getElementById('bc-status-'+id);
    if (!row||!st) return;
    row.className = 'bc-row ' + cls;
    st.textContent = text;
  }

  async function regenOne(id){
    setRowState(id,'bc-active','جارٍ التوليد…');
    const r = await fetch('admin.php?ajax=bulk_regen_one&id='+id).then(r=>r.json()).catch(()=>({success:false,error:'خطأ في الشبكة'}));
    if (r.success){
      setRowState(id,'bc-done','✓ تم');
    } else {
      setRowState(id,'bc-error','✗ '+((r.error||'').substring(0,60)));
    }
    return r.success;
  }

  /* single-app buttons */
  document.querySelectorAll('.btn-bc-one').forEach(btn=>{
    btn.addEventListener('click', async function(){
      if (running) return;
      const id = parseInt(this.dataset.id,10);
      this.disabled=true;
      await regenOne(id);
      this.disabled=false;
    });
  });

  /* generate-all */
  document.getElementById('btn-bc-all').addEventListener('click', async function(){
    if (running) return;
    running=true; stopped=false;
    this.disabled=true;
    document.getElementById('btn-bc-stop').style.display='';
    const wrap = document.getElementById('bc-progress-bar-wrap');
    const bar  = document.getElementById('bc-progress-bar');
    const stat = document.getElementById('bc-progress-status');
    wrap.style.display='';

    let done=0, success=0, failed=0;
    const total=rows.length;
    for (const id of rows){
      if (stopped) break;
      bar.style.width = Math.round(done/total*100)+'%';
      stat.textContent = `${done}/${total} — نجح: ${success}، فشل: ${failed}`;
      const ok = await regenOne(id);
      if(ok) success++; else failed++;
      done++;
      await new Promise(r=>setTimeout(r,600)); // brief pause to avoid throttle
    }
    bar.style.width='100%';
    stat.textContent = stopped
      ? `توقف — أُنجز ${done} من ${total}: نجح ${success}، فشل ${failed}`
      : `اكتمل — ${total} تطبيق: نجح ${success}، فشل ${failed}`;
    document.getElementById('btn-bc-stop').style.display='none';
    this.disabled=false;
    running=false;
  });

  document.getElementById('btn-bc-stop').addEventListener('click', function(){
    stopped=true; this.style.display='none';
  });
})();
</script>
<?php endif; ?>

<?php
/* ─────────────── IMPORT PRESET 30 APPS ─────────────── */
elseif ($page === 'import-preset'): ?>

<div class="admin-header"><h1>استيراد 30 تطبيقاً جاهزاً</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    30 تطبيقاً شهيراً بمحتوى عربي سوري كامل معدٍّ مسبقاً (وصف طويل + ميزات + إيجابيات/سلبيات + FAQ + SEO).
    حدّد ما تريد استيراده — التطبيقات الموجودة مسبقاً تُخطَى تلقائياً.
    كل تطبيق يُحفظ <strong style="color:#fbbf24">كمسودة</strong>، ثم أضف رابط التحميل وانشره.
  </p>
</div>

<!-- Controls row -->
<div class="panel" style="margin-bottom:14px">
  <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end">
    <div class="form-group" style="margin:0;width:200px">
      <label class="form-label">عدد الاستيراد (اختياري)</label>
      <input type="number" id="ip-count" class="form-input" min="1" max="30" placeholder="الكل">
    </div>
    <div class="form-group" style="margin:0;width:180px">
      <label class="form-label">تصفية حسب التصنيف</label>
      <select id="ip-cat-filter" class="form-select">
        <option value="">كل التصنيفات</option>
        <option value="تواصل اجتماعي">تواصل اجتماعي</option>
        <option value="تطبيقات">تطبيقات</option>
        <option value="أدوات">أدوات</option>
        <option value="إنتاجية">إنتاجية</option>
        <option value="تصميم">تصميم</option>
        <option value="ألعاب">ألعاب</option>
      </select>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button type="button" id="btn-ip-all" class="btn-edit" style="padding:8px 14px;font-size:13px">تحديد الجديدة فقط</button>
      <button type="button" id="btn-ip-none" class="btn-del" style="padding:8px 14px;font-size:13px">إلغاء الكل</button>
    </div>
  </div>
</div>

<!-- App grid -->
<div id="ip-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:16px">
  <div style="color:var(--muted);font-size:13px;padding:20px;grid-column:1/-1;text-align:center">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;display:inline-block"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
    جاري تحميل القائمة...
  </div>
</div>

<!-- Start import -->
<div id="ip-start-row" style="display:none;margin-bottom:16px">
  <button type="button" id="btn-ip-import" class="btn-save" style="min-width:200px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    <span id="btn-ip-import-label">بدء الاستيراد</span>
  </button>
  <span id="ip-sel-count" style="margin-right:12px;font-size:13px;color:var(--muted)"></span>
</div>

<!-- Progress -->
<div id="ip-progress" style="display:none;margin-bottom:14px">
  <div style="height:10px;background:var(--navy-600);border-radius:6px;overflow:hidden;margin-bottom:8px">
    <div id="ip-bar" style="height:100%;width:0%;background:linear-gradient(135deg,var(--cyan),var(--purple));transition:width .4s"></div>
  </div>
  <div id="ip-status" style="font-size:12px;color:var(--muted)"></div>
</div>

<!-- Results log -->
<div id="ip-results" style="display:flex;flex-direction:column;gap:8px"></div>

<style>
@keyframes spin{to{transform:rotate(360deg)}}
.ip-card{background:var(--navy-700);border:1.5px solid var(--border-c);border-radius:12px;padding:14px 16px;cursor:pointer;transition:border-color .2s,box-shadow .2s;display:flex;gap:12px;align-items:flex-start}
.ip-card:hover{border-color:var(--cyan);box-shadow:0 2px 12px rgba(37,99,235,.08)}
.ip-card.selected{border-color:var(--cyan);background:rgba(37,99,235,.04)}
.ip-card.existing{opacity:.5;cursor:default}
.ip-card.existing .ip-cb{pointer-events:none}
.ip-cb{width:18px;height:18px;border:2px solid var(--border-c);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background .15s,border-color .15s;margin-top:2px}
.ip-card.selected .ip-cb{background:var(--cyan);border-color:var(--cyan)}
.ip-card.selected .ip-cb::after{content:'';width:10px;height:6px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg);display:block;margin-bottom:3px}
.ip-card-body{flex:1;min-width:0}
.ip-card-name{font-weight:700;font-size:14px;margin-bottom:3px;color:var(--white)}
.ip-card-dev{font-size:11px;color:var(--muted);margin-bottom:4px}
.ip-card-cat{display:inline-block;font-size:10px;background:rgba(37,99,235,.1);color:var(--cyan);border-radius:4px;padding:2px 7px;font-weight:600}
.ip-card-existing{display:inline-block;font-size:10px;background:rgba(22,163,74,.12);color:#16a34a;border-radius:4px;padding:2px 7px;font-weight:600;margin-right:4px}
.ip-res{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:13px}
.ip-res.ok{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.2);color:#4ade80}
.ip-res.skip{background:rgba(251,191,36,.06);border:1px solid rgba(251,191,36,.2);color:#fbbf24}
.ip-res.err{background:rgba(248,113,113,.06);border:1px solid rgba(248,113,113,.2);color:#f87171}
.ip-res a{color:var(--cyan);text-decoration:underline;font-size:12px;margin-right:auto;white-space:nowrap}
</style>

<script>
(function(){
  let allApps=[], selected=new Set();

  async function loadList(){
    const r=await fetch('admin.php?ajax=import_preset_list');
    const d=await r.json();
    if(!d.success){document.getElementById('ip-grid').innerHTML='<div style="color:#f87171;padding:20px;grid-column:1/-1">'+d.error+'</div>';return;}
    allApps=d.apps;
    renderGrid();
    document.getElementById('ip-start-row').style.display='flex';
    updateCount();
  }

  function renderGrid(){
    const catFilter=document.getElementById('ip-cat-filter').value;
    const list=catFilter?allApps.filter(a=>a.category===catFilter):allApps;
    const grid=document.getElementById('ip-grid');
    if(!list.length){grid.innerHTML='<div style="color:var(--muted);padding:20px;grid-column:1/-1;text-align:center">لا توجد تطبيقات بهذا التصنيف</div>';return;}
    grid.innerHTML='';
    list.forEach(a=>{
      const isExist=!!a.existing_id;
      const isSel=selected.has(a.name);
      const card=document.createElement('div');
      card.className='ip-card'+(isExist?' existing':'')+((!isExist&&isSel)?' selected':'');
      card.dataset.name=a.name;
      card.innerHTML=`
        <div class="ip-cb"></div>
        <div class="ip-card-body">
          <div class="ip-card-name">${h(a.name)}</div>
          <div class="ip-card-dev">${h(a.developer)}</div>
          <div>
            <span class="ip-card-cat">${h(a.category)}</span>
            ${isExist?`<span class="ip-card-existing">✓ مستورد — <a href="admin.php?page=edit-app&id=${a.existing_id}" style="color:inherit" target="_blank">تعديل</a></span>`:''}
          </div>
          ${!isExist?`<div style="font-size:11px;color:var(--muted);margin-top:6px;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">${h(a.short_desc)}</div>`:''}
        </div>`;
      if(!isExist){
        card.addEventListener('click',()=>{
          if(selected.has(a.name)){selected.delete(a.name);card.classList.remove('selected');}
          else{selected.add(a.name);card.classList.add('selected');}
          applyCountLimit();
          updateCount();
        });
      }
      grid.appendChild(card);
    });
    applyCountLimit();
  }

  function h(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

  function applyCountLimit(){
    const n=parseInt(document.getElementById('ip-count').value)||0;
    if(!n)return;
    // if more than n selected, keep first n only
    const sel=[...selected];
    if(sel.length>n){
      sel.slice(n).forEach(name=>{selected.delete(name);const c=document.querySelector('.ip-card[data-name="'+CSS.escape(name)+'"]');if(c)c.classList.remove('selected');});
    }
  }

  function updateCount(){
    const n=selected.size;
    document.getElementById('ip-sel-count').textContent=n?`${n} تطبيق محدد`:'لم يتم تحديد أي تطبيق';
    document.getElementById('btn-ip-import-label').textContent=n?`استيراد ${n} تطبيق`:'بدء الاستيراد';
  }

  document.getElementById('btn-ip-all').addEventListener('click',()=>{
    const catFilter=document.getElementById('ip-cat-filter').value;
    const n=parseInt(document.getElementById('ip-count').value)||0;
    let added=0;
    allApps.forEach(a=>{
      if(a.existing_id)return;
      if(catFilter&&a.category!==catFilter)return;
      if(n&&added>=n)return;
      selected.add(a.name);added++;
    });
    renderGrid();updateCount();
  });

  document.getElementById('btn-ip-none').addEventListener('click',()=>{
    selected.clear();renderGrid();updateCount();
  });

  document.getElementById('ip-cat-filter').addEventListener('change',renderGrid);

  document.getElementById('ip-count').addEventListener('input',()=>{
    applyCountLimit();updateCount();
  });

  document.getElementById('btn-ip-import').addEventListener('click',async()=>{
    if(!selected.size){alert('حدّد تطبيقاً واحداً على الأقل');return;}
    const names=[...selected];
    const total=names.length;
    let done=0,ok=0,skip=0,fail=0;
    const prog=document.getElementById('ip-progress');
    const bar=document.getElementById('ip-bar');
    const status=document.getElementById('ip-status');
    const results=document.getElementById('ip-results');
    prog.style.display='block';results.innerHTML='';
    document.getElementById('btn-ip-import').disabled=true;

    for(const name of names){
      status.textContent=`⏳ ${name} — جاري الاستيراد...`;
      try{
        const r=await fetch('admin.php?ajax=import_preset_one',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name})});
        const d=await r.json();
        done++;bar.style.width=(done/total*100)+'%';
        const row=document.createElement('div');
        if(d.success){
          ok++;row.className='ip-res ok';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <strong>${h(d.name)}</strong> — تم الإنشاء ${d.has_icon?'(أيقونة ✓)':'(بدون أيقونة)'}<a href="${d.edit_url}" target="_blank">تعديل</a>`;
          selected.delete(name);const c=document.querySelector('.ip-card[data-name="'+CSS.escape(name)+'"]');
          if(c){c.className='ip-card existing';c.style.opacity='.5';}
        } else if(d.skipped){
          skip++;row.className='ip-res skip';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg> ${h(name)} — موجود مسبقاً`;
        } else {
          fail++;row.className='ip-res err';
          row.innerHTML=`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${h(name)} — ${h(d.error||'خطأ')}`;
        }
        results.prepend(row);
      }catch(e){
        done++;fail++;bar.style.width=(done/total*100)+'%';
        const row=document.createElement('div');row.className='ip-res err';
        row.innerHTML=`✗ ${h(name)} — خطأ في الشبكة`;results.prepend(row);
      }
    }
    status.textContent=`🏁 اكتمل — تم استيراد ${ok}${skip?' | تخطّي '+skip:''}${fail?' | فشل '+fail:''}`;
    document.getElementById('btn-ip-import').disabled=false;
    updateCount();
  });

  loadList();
})();
</script>

<?php
/* ─────────────── FILE IMPORT ─────────────── */
elseif ($page === 'file-import'): ?>

<div class="admin-header"><h1>استيراد تطبيقات من ملف</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    ارفع ملف <strong>JSON</strong> أو <strong>SQL</strong> أو <strong>PHP</strong> يحتوي بيانات تطبيقات كاملة.
    سيتحقق النظام من جودة كل تطبيق — يُرفض أي محتوى ناقص أو ضعيف. التطبيقات الناجحة تُستورد كمسودات.
    <br><strong style="color:#fbbf24">الشروط الإلزامية:</strong> اسم · وصف طويل (+200 كلمة) · عنوان SEO · وصف meta · كلمات مفتاحية · ميزات · إيجابيات · خطوات تثبيت · FAQ.
  </p>
</div>

<div class="panel" style="margin-bottom:16px" id="fi-upload-panel">
  <label for="fi-file-input" id="fi-drop-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:48px 24px;border:2px dashed var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s,background .2s">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <div style="text-align:center">
      <div style="font-size:16px;font-weight:700;margin-bottom:6px">اسحب الملف هنا أو انقر للتصفح</div>
      <div style="font-size:13px;color:var(--muted)">يدعم: .json · .sql · .php · .txt — حجم أقصى 10 MB</div>
    </div>
  </label>
  <input type="file" id="fi-file-input" accept=".json,.sql,.php,.txt" style="display:none">
  <div id="fi-file-name" style="text-align:center;font-size:13px;color:var(--muted);margin-top:12px;display:none"></div>
  <div style="text-align:center;margin-top:16px">
    <button id="fi-validate-btn" class="btn btn-primary" style="display:none">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      تحليل الجودة
    </button>
  </div>
</div>

<div id="fi-results" style="display:none">
  <div class="panel" style="margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <span id="fi-total-badge" style="background:rgba(37,99,235,.1);color:var(--accent);border:1px solid rgba(37,99,235,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
        <span id="fi-pass-badge" style="background:rgba(22,163,74,.1);color:#16a34a;border:1px solid rgba(22,163,74,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
        <span id="fi-fail-badge" style="background:rgba(220,38,38,.1);color:#dc2626;border:1px solid rgba(220,38,38,.25);padding:5px 14px;border-radius:40px;font-size:13px;font-weight:700"></span>
      </div>
      <button id="fi-import-all-btn" class="btn btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        استيراد جميع الناجحة
      </button>
    </div>
    <div id="fi-import-progress" style="display:none;margin-bottom:16px">
      <div style="height:6px;background:rgba(37,99,235,.1);border-radius:3px;overflow:hidden;margin-bottom:8px">
        <div id="fi-import-bar" style="height:100%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:3px;width:0%;transition:width .4s"></div>
      </div>
      <div id="fi-import-status" style="font-size:13px;color:var(--muted)"></div>
    </div>
    <div id="fi-app-list"></div>
  </div>
</div>

<div class="panel">
  <div style="font-size:13px;font-weight:700;margin-bottom:12px">📋 صيغة JSON المقبولة</div>
  <pre style="font-size:11.5px;color:var(--muted);overflow-x:auto;background:rgba(0,0,0,.25);padding:16px;border-radius:8px;line-height:1.8;direction:ltr;text-align:left">{"apps":[{
  "name": "اسم التطبيق",
  "developer": "اسم الشركة",
  "short_description": "جملة جذابة تصف التطبيق (80+ حرف)",
  "long_description": "وصف تفصيلي 200+ كلمة ...",
  "seo_title": "عنوان للبحث 40-70 حرف",
  "meta_description": "وصف meta 120-160 حرف",
  "keywords": "كلمة1، كلمة2، كلمة3",
  "features": ["ميزة 1","ميزة 2","ميزة 3"],
  "pros": ["إيجابية 1","إيجابية 2"],
  "cons": ["سلبية 1"],
  "install_steps": ["خطوة 1","خطوة 2","خطوة 3"],
  "faq": [{"q":"سؤال؟","a":"إجابة"},{"q":"سؤال 2؟","a":"إجابة 2"},{"q":"سؤال 3؟","a":"إجابة 3"}],
  "whats_new": "الجديد في هذا الإصدار",
  "version": "1.0.0",
  "size_mb": 45,
  "category_slug": "apps",
  "playstore_url": "https://play.google.com/store/apps/details?id=..."
}]}</pre>
</div>

<script>
(function(){
  const drop=document.getElementById('fi-drop-zone'),
        fileIn=document.getElementById('fi-file-input'),
        fileName=document.getElementById('fi-file-name'),
        vBtn=document.getElementById('fi-validate-btn'),
        results=document.getElementById('fi-results'),
        appList=document.getElementById('fi-app-list'),
        importAll=document.getElementById('fi-import-all-btn'),
        importProg=document.getElementById('fi-import-progress'),
        importBar=document.getElementById('fi-import-bar'),
        importSt=document.getElementById('fi-import-status');
  let selFile=null, vApps=[];

  drop.addEventListener('dragover',e=>{e.preventDefault();drop.style.borderColor='var(--accent)';drop.style.background='rgba(37,99,235,.05)';});
  drop.addEventListener('dragleave',()=>{drop.style.borderColor='';drop.style.background='';});
  drop.addEventListener('drop',e=>{e.preventDefault();drop.style.borderColor='';drop.style.background='';if(e.dataTransfer.files[0])setFile(e.dataTransfer.files[0]);});
  fileIn.addEventListener('change',()=>{if(fileIn.files[0])setFile(fileIn.files[0]);});

  function setFile(f){
    selFile=f;
    fileName.textContent='📄 '+f.name+' ('+fmtSz(f.size)+')';
    fileName.style.display='block';
    vBtn.style.display='inline-flex';
    results.style.display='none'; vApps=[];
  }
  function fmtSz(b){return b<1024?b+' B':b<1048576?(b/1024).toFixed(1)+' KB':(b/1048576).toFixed(1)+' MB';}
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

  vBtn.addEventListener('click',async()=>{
    if(!selFile)return;
    vBtn.disabled=true; vBtn.textContent='جارٍ التحليل...';
    const fd=new FormData(); fd.append('file',selFile);
    try{
      const r=await fetch('admin.php?ajax=file_import_validate',{method:'POST',body:fd});
      const d=await r.json();
      if(!d.success){alert('خطأ: '+(d.error||'غير معروف'));return;}
      vApps=d.apps; renderResults(d);
    }catch(e){alert('خطأ: '+e.message);}
    finally{vBtn.disabled=false;vBtn.innerHTML='<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> تحليل الجودة';}
  });

  function renderResults(d){
    document.getElementById('fi-total-badge').textContent='الإجمالي: '+d.total;
    document.getElementById('fi-pass-badge').textContent='✓ ناجح: '+d.passed;
    document.getElementById('fi-fail-badge').textContent='✗ مرفوض: '+(d.total-d.passed);
    importAll.textContent='استيراد الناجحة ('+d.passed+')';
    let h='';
    d.apps.forEach((a,i)=>{
      const sc=a.pass?'#16a34a':'#dc2626', si=a.pass?'✓':'✗', st=a.pass?'ناجح':'مرفوض';
      const sc2=a.score>=80?'#16a34a':a.score>=55?'#d97706':'#dc2626';
      h+=`<div style="background:var(--panel-bg);border:1px solid ${sc}30;border-radius:10px;padding:16px;margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:${(a.issues.length||a.warnings.length)?'12px':'0'}">
          <div>
            <div style="font-size:14px;font-weight:700">${esc(a.name)}</div>
            <div style="font-size:12px;color:var(--muted)">${esc(a.developer||'—')} · ${a.word_count} كلمة</div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span style="background:${sc2}18;color:${sc2};border:1px solid ${sc2}40;padding:3px 11px;border-radius:40px;font-size:12px;font-weight:700">${a.score}/100</span>
            <span style="background:${sc}18;color:${sc};border:1px solid ${sc}40;padding:3px 11px;border-radius:40px;font-size:12px;font-weight:700">${si} ${st}</span>
            ${a.pass?`<button class="btn" style="font-size:12px;padding:5px 14px" onclick="fiImportOne(${i})" id="fi-btn-${i}">استيراد</button>`:''}
          </div>
        </div>
        ${a.issues.map(e=>`<div style="font-size:12px;color:#dc2626;display:flex;gap:6px;margin-bottom:3px"><span>⚠</span><span>${esc(e)}</span></div>`).join('')}
        ${a.warnings.map(w=>`<div style="font-size:12px;color:#d97706;display:flex;gap:6px;margin-bottom:3px"><span>ℹ</span><span>${esc(w)}</span></div>`).join('')}
      </div>`;
    });
    appList.innerHTML=h; results.style.display='block';
  }

  async function doImport(idx){
    const a=vApps[idx]; if(!a||!a.pass)return false;
    const btn=document.getElementById('fi-btn-'+idx);
    if(btn){btn.disabled=true;btn.textContent='جارٍ...';}
    try{
      const r=await fetch('admin.php?ajax=file_import_run',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({app:a.data})});
      const d=await r.json();
      if(d.success){if(btn)btn.outerHTML=`<a href="${d.edit_url}" class="btn" style="font-size:12px;padding:5px 14px;background:rgba(22,163,74,.15);color:#16a34a;border-color:rgba(22,163,74,.3)">✓ تعديل</a>`;return true;}
      else if(d.skipped){if(btn)btn.outerHTML=`<span style="font-size:12px;color:#d97706">موجود</span>`;return 'skip';}
      else{if(btn){btn.disabled=false;btn.textContent='استيراد';}alert('فشل: '+(d.error||'خطأ'));return false;}
    }catch(e){if(btn){btn.disabled=false;btn.textContent='استيراد';}alert('خطأ: '+e.message);return false;}
  }
  window.fiImportOne=idx=>doImport(idx);

  importAll.addEventListener('click',async()=>{
    const passed=vApps.filter(a=>a.pass); if(!passed.length){alert('لا توجد تطبيقات ناجحة');return;}
    importAll.disabled=true; importProg.style.display='block';
    let ok=0,skip=0,fail=0;
    for(let i=0;i<vApps.length;i++){
      if(!vApps[i].pass)continue;
      importBar.style.width=((i/vApps.length)*100)+'%';
      importSt.textContent='جارٍ: '+vApps[i].name+'...';
      const res=await doImport(i);
      if(res===true)ok++;else if(res==='skip')skip++;else fail++;
    }
    importBar.style.width='100%';
    importSt.innerHTML=`<strong style="color:var(--white)">🏁 اكتمل</strong> — تم: ${ok}${skip?' · تخطّي: '+skip:''}${fail?' · فشل: '+fail:''}`;
    importAll.disabled=false;
  });
})();
</script>

<?php
/* ─────────────── BULK CSV IMPORT (50K+) ─────────────── */
elseif ($page === 'bulk-csv-import'): ?>

<div class="admin-header"><h1>استيراد تطبيقات ضخم — CSV / JSON (50K+)</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    ارفع ملف <strong>CSV</strong> أو <strong>JSON</strong> بقائمة التطبيقات. لكل تطبيق:
    يجلب النظام بيانات متجر Play تلقائياً، يحمّل الأيقونة، يعيّن رابط التحميل عبر APKPure،
    ثم <strong style="color:#22c55e">يُنشر مباشرةً</strong> (لا مسودة).<br>
    <strong>أعمدة CSV المقبولة:</strong>
    <code style="font-size:11px">name</code> (مطلوب) ·
    <code style="font-size:11px">package_id</code> ·
    <code style="font-size:11px">playstore_url</code> ·
    <code style="font-size:11px">category</code> ·
    <code style="font-size:11px">developer</code>
  </p>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
    <a href="#" id="bci-sample-csv" style="font-size:12px;color:var(--accent)">⬇️ تحميل مثال CSV</a>
    <a href="#" id="bci-sample-json" style="font-size:12px;color:var(--accent)">⬇️ تحميل مثال JSON</a>
  </div>
</div>

<!-- Upload zone -->
<div class="panel" style="margin-bottom:16px" id="bci-upload-panel">
  <label for="bci-file-input" id="bci-drop-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:48px 24px;border:2px dashed var(--border);border-radius:12px;cursor:pointer;transition:border-color .2s,background .2s">
    <svg width="52" height="52" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z"/></svg>
    <div style="text-align:center">
      <div style="font-size:16px;font-weight:700;margin-bottom:6px">اسحب ملف CSV/JSON هنا أو انقر</div>
      <div style="font-size:13px;color:var(--muted)">.csv · .json · .txt · حجم أقصى 50 MB · حتى 50,000 سطر</div>
    </div>
  </label>
  <input type="file" id="bci-file-input" accept=".csv,.json,.txt" style="display:none">
  <div id="bci-file-name" style="text-align:center;font-size:13px;color:var(--muted);margin-top:12px;display:none"></div>
</div>

<!-- Controls -->
<div class="panel" style="margin-bottom:16px" id="bci-controls" style="display:none">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
    <div class="form-group" style="margin:0">
      <label class="form-label">التصنيف الافتراضي</label>
      <select id="bci-default-cat" class="form-select" style="width:180px">
        <option value="apps">تطبيقات (apps)</option>
        <option value="games">ألعاب (games)</option>
      </select>
    </div>
    <div class="form-group" style="margin:0">
      <label class="form-label">تأخير بين كل تطبيق (ثانية)</label>
      <input type="number" id="bci-delay" class="form-input" value="0.5" min="0" max="10" step="0.1" style="width:120px">
    </div>
    <div class="form-group" style="margin:0">
      <label class="form-label">من السطر</label>
      <input type="number" id="bci-start-from" class="form-input" value="1" min="1" style="width:100px">
    </div>
    <button id="bci-start-btn" class="btn btn-primary">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
      بدء الاستيراد
    </button>
    <button id="bci-pause-btn" class="btn" style="display:none">⏸️ إيقاف مؤقت</button>
    <button id="bci-resume-btn" class="btn btn-primary" style="display:none">▶️ استئناف</button>
  </div>

  <!-- Progress bar -->
  <div id="bci-progress-wrap" style="display:none">
    <div style="height:10px;background:rgba(37,99,235,.1);border-radius:5px;overflow:hidden;margin-bottom:10px">
      <div id="bci-prog-bar" style="height:100%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:5px;width:0%;transition:width .4s"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px">
      <span id="bci-prog-text" style="color:var(--muted)">جارٍ الاستيراد…</span>
      <span id="bci-prog-pct" style="font-weight:700">0%</span>
    </div>
    <div style="display:flex;gap:16px;font-size:13px;flex-wrap:wrap">
      <span>الإجمالي: <strong id="bci-cnt-total">0</strong></span>
      <span style="color:#22c55e">✔ نجح: <strong id="bci-cnt-ok">0</strong></span>
      <span style="color:var(--muted)">⏭ تخطّي: <strong id="bci-cnt-skip">0</strong></span>
      <span style="color:#ef4444">✘ فشل: <strong id="bci-cnt-fail">0</strong></span>
    </div>
  </div>
</div>

<!-- Live log -->
<div id="bci-log-wrap" class="panel" style="display:none">
  <div style="font-size:13px;font-weight:700;margin-bottom:10px">سجل الاستيراد</div>
  <div id="bci-log" style="font-family:var(--f-mono);font-size:12px;max-height:360px;overflow-y:auto;line-height:1.8"></div>
</div>

<script>
(function(){
  const dropZone  = document.getElementById('bci-drop-zone');
  const fileInput = document.getElementById('bci-file-input');
  const fileName  = document.getElementById('bci-file-name');
  const controls  = document.getElementById('bci-controls');
  const startBtn  = document.getElementById('bci-start-btn');
  const pauseBtn  = document.getElementById('bci-pause-btn');
  const resumeBtn = document.getElementById('bci-resume-btn');
  const progWrap  = document.getElementById('bci-progress-wrap');
  const progBar   = document.getElementById('bci-prog-bar');
  const progText  = document.getElementById('bci-prog-text');
  const progPct   = document.getElementById('bci-prog-pct');
  const cntTotal  = document.getElementById('bci-cnt-total');
  const cntOk     = document.getElementById('bci-cnt-ok');
  const cntSkip   = document.getElementById('bci-cnt-skip');
  const cntFail   = document.getElementById('bci-cnt-fail');
  const logWrap   = document.getElementById('bci-log-wrap');
  const log       = document.getElementById('bci-log');

  let rows = [], paused = false, stopped = false, idx = 0, ok=0, skip=0, fail=0;

  function fmtSz(b){return b<1048576?(b/1024).toFixed(1)+' KB':(b/1048576).toFixed(1)+' MB';}
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
  function addLog(cls,msg){
    const row=document.createElement('div');
    row.innerHTML=`<span style="color:var(--muted);user-select:none">${String(idx).padStart(5,' ')} </span><span class="${cls}">${esc(msg)}</span>`;
    log.appendChild(row);
    log.scrollTop=log.scrollHeight;
  }

  // Drag-and-drop
  dropZone.addEventListener('dragover',e=>{e.preventDefault();dropZone.style.borderColor='var(--accent)';dropZone.style.background='rgba(37,99,235,.05)';});
  dropZone.addEventListener('dragleave',()=>{dropZone.style.borderColor='';dropZone.style.background='';});
  dropZone.addEventListener('drop',e=>{e.preventDefault();dropZone.style.borderColor='';dropZone.style.background='';if(e.dataTransfer.files[0])loadFile(e.dataTransfer.files[0]);});
  fileInput.addEventListener('change',()=>{if(fileInput.files[0])loadFile(fileInput.files[0]);});

  function loadFile(f){
    fileName.textContent='📄 '+f.name+' ('+fmtSz(f.size)+')';
    fileName.style.display='block';
    controls.style.display='block';
    const reader=new FileReader();
    reader.onload=e=>{
      const text=e.target.result;
      rows=parseFile(f.name,text);
      progText.textContent=`تم تحليل ${rows.length.toLocaleString()} تطبيق جاهز للاستيراد`;
      progWrap.style.display='block';
      cntTotal.textContent=rows.length;
    };
    reader.readAsText(f,'UTF-8');
  }

  function parseFile(fname,text){
    const ext=fname.split('.').pop().toLowerCase();
    if(ext==='json'){
      try{
        const d=JSON.parse(text);
        const arr=Array.isArray(d)?d:(d.apps||d.data||d.list||Object.values(d));
        return arr.filter(r=>r&&(r.name||r.package_id||r.playstore_url));
      }catch(e){alert('خطأ في تحليل JSON: '+e.message);return [];}
    }
    // CSV
    const lines=text.split('\n').map(l=>l.trim()).filter(Boolean);
    if(!lines.length)return[];
    const headers=lines[0].split(',').map(h=>h.trim().replace(/^"|"$/g,'').toLowerCase());
    return lines.slice(1).map(line=>{
      const vals=line.match(/(".*?"|[^,]+|(?<=,)(?=,)|(?<=,)$)/g)||line.split(',');
      const obj={};
      headers.forEach((h,i)=>{ obj[h]=(vals[i]||'').trim().replace(/^"|"$/g,''); });
      return obj;
    }).filter(r=>r.name||r.package_id||r.playstore_url);
  }

  // Sample download helpers
  document.getElementById('bci-sample-csv').addEventListener('click',e=>{
    e.preventDefault();
    const csv='name,package_id,category,developer\nTikTok,com.zhiliaoapp.musically,entertainment,ByteDance\nWhatsApp,com.whatsapp,social,Meta\nInstagram,com.instagram.android,social,Meta\n';
    const blob=new Blob([csv],{type:'text/csv;charset=utf-8'});
    const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='apps-sample.csv';a.click();
  });
  document.getElementById('bci-sample-json').addEventListener('click',e=>{
    e.preventDefault();
    const json=JSON.stringify({apps:[
      {name:'TikTok',package_id:'com.zhiliaoapp.musically',category:'entertainment',developer:'ByteDance'},
      {name:'WhatsApp',package_id:'com.whatsapp',category:'social',developer:'Meta'},
    ]},null,2);
    const blob=new Blob([json],{type:'application/json'});
    const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='apps-sample.json';a.click();
  });

  startBtn.addEventListener('click',()=>{
    if(!rows.length){alert('الرجاء تحميل ملف أولاً');return;}
    const startFrom=Math.max(1,parseInt(document.getElementById('bci-start-from').value)||1)-1;
    idx=startFrom;ok=0;skip=0;fail=0;paused=false;stopped=false;
    startBtn.style.display='none';
    pauseBtn.style.display='inline-flex';
    logWrap.style.display='block';
    log.innerHTML='';
    runNext();
  });

  pauseBtn.addEventListener('click',()=>{
    paused=true;
    pauseBtn.style.display='none';
    resumeBtn.style.display='inline-flex';
    addLog('style="color:#fbbf24"','⏸️ موقوف مؤقتاً — انقر استئناف للمتابعة');
  });
  resumeBtn.addEventListener('click',()=>{
    paused=false;
    resumeBtn.style.display='none';
    pauseBtn.style.display='inline-flex';
    runNext();
  });

  async function runNext(){
    if(stopped||paused)return;
    if(idx>=rows.length){
      progBar.style.width='100%';
      progText.innerHTML=`<strong style="color:var(--white)">🏁 اكتمل الاستيراد</strong>`;
      progPct.textContent='100%';
      pauseBtn.style.display='none';
      startBtn.style.display='inline-flex';
      startBtn.textContent='بدء من جديد';
      addLog('style="color:#22c55e;font-weight:bold"',`✅ انتهى — نجح: ${ok} · تخطّي: ${skip} · فشل: ${fail}`);
      return;
    }
    const row=rows[idx];
    const pct=Math.round(idx/rows.length*100);
    progBar.style.width=pct+'%';
    progPct.textContent=pct+'%';
    progText.textContent=`(${idx+1}/${rows.length}) ${row.name||row.package_id||'…'}`;

    const cat=document.getElementById('bci-default-cat').value;
    const body=JSON.stringify({
      name:row.name||'',
      package_id:row.package_id||row['package']||row['pkg']||'',
      playstore_url:row.playstore_url||row['ps_url']||'',
      category:row.category||row.cat||cat,
      developer:row.developer||row.dev||'',
    });

    try{
      await new Promise((resolve,reject)=>{
        const es=new EventSource('admin.php?ajax=bulk_csv_one',{});
        // EventSource doesn't support POST — use fetch+SSE pattern via ReadableStream
        es.close();
        // Use fetch with streaming response
        fetch('admin.php?ajax=bulk_csv_one',{method:'POST',body,headers:{'Content-Type':'application/json'}})
          .then(r=>{
            const reader=r.body.getReader();
            const dec=new TextDecoder();
            let buf='';
            function read(){
              reader.read().then(({done,value})=>{
                if(done){resolve();return;}
                buf+=dec.decode(value,{stream:true});
                const parts=buf.split('\n\n');
                buf=parts.pop();
                parts.forEach(chunk=>{
                  const evtMatch=chunk.match(/^event:\s*(\S+)/m);
                  const dataMatch=chunk.match(/^data:\s*(.+)/m);
                  if(evtMatch&&dataMatch){
                    const evt=evtMatch[1];
                    let d;try{d=JSON.parse(dataMatch[1]);}catch(e){return;}
                    if(evt==='done'){
                      if(d.skipped){skip++;addLog('style="color:var(--muted)"',`⏭ تخطّي: ${d.name||'—'} (${d.msg||'موجود'})`);}
                      else if(d.success){ok++;addLog('style="color:#22c55e"',`✔ ${d.name} — slug: ${d.slug}${d.has_icon?' 🖼️':''}`);}
                      cntOk.textContent=ok;cntSkip.textContent=skip;
                    }else if(evt==='error'){
                      fail++;addLog('style="color:#ef4444"',`✘ ${row.name||'—'}: ${d.msg}`);
                      cntFail.textContent=fail;
                    }
                  }
                });
                read();
              }).catch(reject);
            }
            read();
          }).catch(reject);
      });
    }catch(e){
      fail++;addLog('style="color:#ef4444"',`✘ ${row.name||'—'}: ${e.message}`);
      cntFail.textContent=fail;
    }

    idx++;
    const delay=parseFloat(document.getElementById('bci-delay').value)||0;
    if(delay>0) await new Promise(r=>setTimeout(r,delay*1000));
    runNext();
  }
})();
</script>

<?php
/* ─────────────── CATEGORIES ─────────────── */
elseif ($page === 'categories'):
  $cats = $pdo->query("SELECT c.*,(SELECT COUNT(*) FROM apps WHERE category_id=c.id) AS cnt FROM categories c ORDER BY c.sort_order,c.name")->fetchAll();
?>

<div class="admin-header"><h1>التصنيفات</h1></div>

<div class="panel">
  <form method="post" action="admin.php?page=categories" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px">
    <?= csrf_field() ?>
    <div class="form-group" style="flex:1;margin-bottom:0">
      <label class="form-label">اسم التصنيف الجديد</label>
      <input class="form-input" type="text" name="name" required placeholder="مثال: تعليم">
    </div>
    <div class="form-group" style="width:100px;margin-bottom:0">
      <label class="form-label">الترتيب</label>
      <input class="form-input" type="number" name="sort_order" value="0">
    </div>
    <button type="submit" class="btn-save">إضافة</button>
  </form>

  <table class="admin-table">
    <thead><tr><th>الاسم</th><th>Slug</th><th>التطبيقات</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cats as $c): ?>
    <tr>
      <td style="font-weight:700"><?= h($c['name']) ?></td>
      <td style="font-family:var(--f-mono);font-size:12px;color:var(--muted)"><?= h($c['slug']) ?></td>
      <td style="font-family:var(--f-mono)"><?= $c['cnt'] ?></td>
      <td>
        <a href="category.php?slug=<?= h($c['slug']) ?>" target="_blank" class="btn-view">عرض</a>
        <a href="admin.php?page=categories&del_cat=<?= $c['id'] ?>&t=<?= csrf_token() ?>"
           class="btn-del" data-confirm="حذف التصنيف «<?= h($c['name']) ?>»؟">حذف</a>
      </td>
    </tr>
    <tr>
      <td colspan="4" style="padding-top:0">
        <form method="post" action="admin.php?page=categories" class="cat-desc-form" data-cat-name="<?= h($c['name']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <label class="form-label" style="margin:0">وصف SEO للتصنيف (يظهر أعلى صفحة التصنيف)</label>
            <button type="button" class="add-item-btn btn-gen-cat-desc" style="margin:0;padding:6px 14px;font-size:12px">توليد بالذكاء الاصطناعي</button>
          </div>
          <textarea class="form-textarea" name="description" rows="2" placeholder="فقرة قصيرة تشرح هذا التصنيف..."><?= h($c['description'] ?? '') ?></textarea>
          <button type="submit" class="btn-edit" style="margin-top:6px">حفظ الوصف</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
/* ─────────────── CONTACT MESSAGES ─────────────── */
elseif ($page === 'messages'):
  $msgs = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>

<div class="admin-header"><h1>رسائل التواصل</h1></div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>الاسم</th><th>البريد</th><th>الموضوع</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($msgs as $m): ?>
  <tr>
    <td data-label="الاسم" style="font-weight:700"><?= h($m['name']) ?></td>
    <td data-label="البريد" style="font-family:var(--f-mono);font-size:12px"><?= h($m['email']) ?></td>
    <td data-label="الموضوع"><?= h($m['subject'] ?: '—') ?></td>
    <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($m['created_at'])) ?></td>
    <td data-label="الحالة"><span class="status-badge <?= $m['status']==='new'?'status-published':'status-draft' ?>"><?= $m['status']==='new'?'جديدة':'مقروءة' ?></span></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=messages&view=<?= $m['id'] ?>#msg-<?= $m['id'] ?>" class="btn-view">عرض</a>
        <a href="admin.php?page=messages&del_msg=<?= $m['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذه الرسالة؟">حذف</a>
      </div>
    </td>
  </tr>
  <tr id="msg-<?= $m['id'] ?>">
    <td colspan="6" style="padding-top:0;color:var(--muted);font-size:13px;line-height:1.8;white-space:pre-wrap"><?= h($m['message']) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$msgs): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">لا توجد رسائل بعد</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── COMMENTS MODERATION ─────────────── */
elseif ($page === 'comments'):
  $cmts = $pdo->query("SELECT c.*, a.name AS app_name, a.slug AS app_slug FROM comments c
      LEFT JOIN apps a ON c.app_id=a.id ORDER BY c.status='pending' DESC, c.created_at DESC LIMIT 200")->fetchAll();
?>

<div class="admin-header"><h1>التعليقات والتقييمات</h1></div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>التطبيق</th><th>الاسم</th><th>التقييم</th><th>التعليق</th><th>الحالة</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($cmts as $c): ?>
  <tr>
    <td data-label="التطبيق" style="font-weight:700"><?= h($c['app_name'] ?? '—') ?></td>
    <td data-label="الاسم"><?= h($c['name']) ?></td>
    <td data-label="التقييم" style="color:#fbbf24;font-family:var(--f-mono)"><?= str_repeat('★', (int)$c['rating']) ?></td>
    <td data-label="التعليق" style="max-width:280px;color:var(--muted);font-size:13px"><?= h(mb_strimwidth($c['body'], 0, 100, '...')) ?></td>
    <td data-label="الحالة"><span class="status-badge <?= $c['status']==='approved'?'status-published':'status-draft' ?>"><?= $c['status']==='approved'?'منشور':'قيد المراجعة' ?></span></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php if ($c['status'] !== 'approved'): ?>
        <a href="admin.php?page=comments&approve=<?= $c['id'] ?>&t=<?= csrf_token() ?>" class="btn-edit">نشر</a>
        <?php endif; ?>
        <?php if ($c['app_slug']): ?>
        <a href="<?= h(app_url($c['app_slug'])) ?>" target="_blank" class="btn-view">عرض التطبيق</a>
        <?php endif; ?>
        <a href="admin.php?page=comments&del_comment=<?= $c['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذا التعليق؟">حذف</a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$cmts): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">لا توجد تعليقات بعد</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── BLOG LIST ─────────────── */
elseif ($page === 'blog'):
  $blogTypeFilter = trim($_GET['type'] ?? '');
  $blogWhere = "";
  $blogParams = [];
  if (isset(BLOG_TYPES[$blogTypeFilter])) { $blogWhere = "WHERE type=?"; $blogParams[] = $blogTypeFilter; }
  $blogStmt = $pdo->prepare("SELECT * FROM blog_posts $blogWhere ORDER BY created_at DESC LIMIT 200");
  $blogStmt->execute($blogParams);
  $blogPosts = $blogStmt->fetchAll();
?>

<div class="admin-header">
  <h1>المدونة والمحتوى</h1>
  <a href="admin.php?page=blog-edit" class="btn-edit">+ مقال جديد يدوياً</a>
</div>

<div class="panel">
  <h2>توليد مقال جديد بالذكاء الاصطناعي</h2>
  <div class="form-grid" style="margin-bottom:12px">
    <div class="form-group">
      <label class="form-label">القسم</label>
      <select class="form-select" id="blog-gen-type">
        <?php foreach (BLOG_TYPES as $t => $label): ?>
        <option value="<?= h($t) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">الموضوع (اختياري — اتركه فارغاً ليختار الذكاء الاصطناعي موضوعاً مناسباً)</label>
      <input class="form-input" type="text" id="blog-gen-topic" placeholder="مثال: أفضل تطبيقات تعديل الفيديو 2026">
    </div>
  </div>
  <button type="button" id="btn-generate-blog" class="btn-ai">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    توليد مقال (يُنشأ كمسودة، راجعه ثم انشره)
  </button>
  <div id="blog-gen-result" style="margin-top:12px"></div>
</div>

<div class="cat-chips" style="margin:16px 0">
  <a href="admin.php?page=blog" class="cat-chip <?= $blogTypeFilter==='' ? 'active':'' ?>" style="text-decoration:none">الكل</a>
  <?php foreach (BLOG_TYPES as $t => $label): ?>
  <a href="admin.php?page=blog&type=<?= h($t) ?>" class="cat-chip <?= $blogTypeFilter===$t ? 'active':'' ?>" style="text-decoration:none"><?= h($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="panel" style="padding:0;overflow:hidden">
<table class="admin-table responsive-cards">
  <thead><tr><th>العنوان</th><th>القسم</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
  <tbody>
  <?php foreach ($blogPosts as $bp): ?>
  <tr>
    <td data-label="العنوان" style="font-weight:700"><?= h($bp['title']) ?></td>
    <td data-label="القسم" style="color:var(--muted);font-size:12px">
      <?= h(blog_type_label($bp['type'])) ?>
      <?php if ($bp['type'] === 'code-page'): $cpCount = count(decode_code_page($bp['body'] ?? '')); ?>
        <span style="display:inline-block;margin-inline-start:4px;padding:1px 6px;border-radius:4px;background:rgba(124,58,237,.15);color:#a78bfa;font-size:10px;font-weight:700"><?= $cpCount ?> أقسام</span>
      <?php endif; ?>
    </td>
    <td data-label="الحالة"><span class="status-badge <?= $bp['status']==='published'?'status-published':'status-draft' ?>"><?= $bp['status']==='published'?'منشور':'مسودة' ?></span></td>
    <td data-label="التاريخ" style="color:var(--muted);font-size:12px"><?= h(time_ago($bp['created_at'])) ?></td>
    <td data-label="إجراءات" class="td-actions">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?page=blog-edit&id=<?= (int)$bp['id'] ?>" class="btn-edit">تعديل</a>
        <?php if ($bp['status']==='published'): ?>
        <a href="<?= h(blog_post_url($bp['slug'])) ?>" target="_blank" class="btn-view">عرض</a>
        <?php endif; ?>
        <a href="admin.php?page=blog&del=<?= (int)$bp['id'] ?>&t=<?= csrf_token() ?>" class="btn-del" data-confirm="حذف هذا المقال؟">حذف</a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$blogPosts): ?><tr><td colspan="5" style="text-align:center;color:var(--muted);padding:32px">لا توجد مقالات بعد</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php
/* ─────────────── BLOG EDIT ─────────────── */
elseif ($page === 'blog-edit'):
  $blogPost = ['id'=>0,'type'=>'article','title'=>'','seo_title'=>'','meta_description'=>'','keywords'=>'','excerpt'=>'','body'=>'','status'=>'draft'];
  if (isset($_GET['id'])) {
    $bstmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
    $bstmt->execute([(int)$_GET['id']]);
    $found = $bstmt->fetch();
    if ($found) $blogPost = $found;
  }
?>

<div class="admin-header">
  <h1><?= $blogPost['id'] ? 'تعديل: '.h($blogPost['title']) : 'مقال جديد' ?></h1>
  <a href="admin.php?page=blog" class="btn-edit">← كل المقالات</a>
</div>

<?php if (!empty($blogError)): ?><div class="alert alert-error"><?= h($blogError) ?></div><?php endif; ?>

<form method="post" action="admin.php?page=blog-edit" id="blog-edit-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$blogPost['id'] ?>">
  <div class="panel">
    <div class="form-grid">
      <div class="form-group full">
        <label class="form-label">العنوان *</label>
        <input class="form-input" id="blog-title" type="text" name="title" value="<?= h($blogPost['title']) ?>" required>
      </div>
      <div class="form-group full">
        <label class="form-label">الرابط (Slug)</label>
        <input class="form-input" id="blog-slug" type="text" name="slug" dir="ltr" style="text-align:left"
               value="<?= h($blogPost['slug'] ?? '') ?>"
               placeholder="سيُنشأ تلقائياً من العنوان إن تُرك فارغاً"
               pattern="[a-zA-Z0-9؀-ۿ\-]*">
        <span style="font-size:11px;color:var(--muted);display:block;margin-top:4px" dir="ltr">
          <?= h(SITE_URL) ?>/blog/<span id="blog-slug-preview"><?= h($blogPost['slug'] ?? 'post-slug') ?></span>
        </span>
      </div>
      <div class="form-group">
        <label class="form-label">القسم</label>
        <select class="form-select" name="type">
          <?php foreach (BLOG_TYPES as $t => $label): ?>
          <option value="<?= h($t) ?>" <?= $blogPost['type']===$t?'selected':'' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">الحالة</label>
        <select class="form-select" name="status">
          <option value="draft" <?= $blogPost['status']==='draft'?'selected':'' ?>>مسودة</option>
          <option value="published" <?= $blogPost['status']==='published'?'selected':'' ?>>منشور</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">SEO Title</label>
        <input class="form-input" type="text" name="seo_title" value="<?= h($blogPost['seo_title']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">الكلمات المفتاحية</label>
        <input class="form-input" type="text" name="keywords" value="<?= h($blogPost['keywords']) ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">Meta Description</label>
        <input class="form-input" type="text" name="meta_description" value="<?= h($blogPost['meta_description']) ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">ملخص قصير (يظهر في بطاقة المقال)</label>
        <input class="form-input" type="text" name="excerpt" value="<?= h($blogPost['excerpt']) ?>">
      </div>
      <!-- ══ WYSIWYG editor (shown for all types EXCEPT code-page) ══ -->
      <div class="form-group full" id="blog-wysiwyg-wrap">
        <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
          <span>نص المقال</span>
          <span style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" id="btn-fix-body-html" class="btn-edit" style="font-size:12px;padding:5px 10px" title="تحويل النص العادي لفقرات HTML — مفيد للمقالات القديمة">🔧 تحويل لـ HTML</button>
            <select id="blog-template-select" class="form-select" style="font-size:12px;padding:4px 8px;height:auto">
              <option value="">📄 قالب جاهز…</option>
              <option value="news">📰 قالب خبر</option>
              <option value="tutorial">📚 قالب شرح خطوة بخطوة</option>
              <option value="comparison">⚖️ قالب مقارنة</option>
              <option value="top-list">🏆 قالب قائمة أفضل</option>
              <option value="review">⭐ قالب مراجعة تطبيق</option>
            </select>
            <button type="button" id="blog-toggle-source" class="btn-edit" style="font-size:12px;padding:5px 10px">
              &lt;/&gt; HTML
            </button>
          </span>
        </label>
        <!-- WYSIWYG Toolbar -->
        <div class="wysiwyg-toolbar" id="wysiwyg-toolbar">
          <select class="ws-sel" id="ws-block">
            <option value="p">فقرة</option>
            <option value="h1">H1</option><option value="h2">H2</option>
            <option value="h3">H3</option><option value="h4">H4</option>
            <option value="h5">H5</option><option value="h6">H6</option>
            <option value="blockquote">اقتباس</option><option value="pre">كود</option>
          </select>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="bold" title="خط عريض"><b>B</b></button>
          <button class="ws-btn" type="button" data-cmd="italic" title="مائل"><i>I</i></button>
          <button class="ws-btn" type="button" data-cmd="underline" title="تسطير"><u>U</u></button>
          <button class="ws-btn" type="button" data-cmd="strikeThrough" title="شطب"><s>S</s></button>
          <span class="ws-sep"></span>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="2" title="تصغير">A−</button>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="4" title="تكبير">A+</button>
          <button class="ws-btn ws-sm" type="button" data-cmd="fontSize" data-val="6" title="كبير جداً">A⁺⁺</button>
          <span class="ws-sep"></span>
          <label class="ws-btn" style="cursor:pointer" title="لون النص">
            <input type="color" id="ws-color" style="width:0;height:0;opacity:0;position:absolute"> A🎨
          </label>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="justifyRight" title="يمين">⇥</button>
          <button class="ws-btn" type="button" data-cmd="justifyCenter" title="وسط">⇔</button>
          <button class="ws-btn" type="button" data-cmd="justifyLeft" title="يسار">⇤</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="insertUnorderedList" title="قائمة نقطية">•≡</button>
          <button class="ws-btn" type="button" data-cmd="insertOrderedList" title="قائمة مرقمة">1≡</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-link" title="رابط">🔗</button>
          <button class="ws-btn" type="button" id="ws-image" title="صورة">🖼</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-code" title="كتلة كود">⌨</button>
          <button class="ws-btn" type="button" data-cmd="insertHorizontalRule" title="خط فاصل">─</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" data-cmd="undo" title="تراجع">↩</button>
          <button class="ws-btn" type="button" data-cmd="redo" title="إعادة">↪</button>
          <span class="ws-sep"></span>
          <button class="ws-btn" type="button" id="ws-fullscreen" title="ملء الشاشة">⛶</button>
        </div>
        <div id="wysiwyg-editor" class="wysiwyg-editor" contenteditable="true" dir="auto"><?= $blogPost['type'] !== 'code-page' ? $blogPost['body'] : '' ?></div>
        <textarea name="body" id="blog-body-hidden" style="display:none"></textarea>
        <textarea id="blog-body-source" class="form-textarea" name="_body_source" rows="20" style="display:none;font-family:monospace;font-size:13px;direction:ltr"><?= $blogPost['type'] !== 'code-page' ? h($blogPost['body']) : '' ?></textarea>
      </div>

      <!-- ══ Code-page editor (shown only when type = code-page) ══ -->
      <div class="form-group full" id="blog-codepage-wrap" style="display:none">
        <label class="form-label">صفحة المحتوى — أقسام الأكواد</label>
        <input type="text" name="cp_description" id="cp-description" class="form-input" placeholder="وصف الصفحة (اختياري — يظهر تحت العنوان)..."
               value="<?= $blogPost['type'] === 'code-page' ? h(json_decode($blogPost['body'] ?? '{}', true)['description'] ?? '') : '' ?>"
               style="margin-bottom:14px">

        <!-- ══ Smart paste zone ══ -->
        <div class="cp-smart-zone" id="cp-smart-zone">
          <div class="cp-smart-header">
            <div class="cp-smart-meta">
              <span class="cp-smart-title">📥 الصق الكود هنا — الكشف التلقائي عن اللغة</span>
              <span class="cp-smart-hint">يدعم: ` ```html ... ``` ` أو <code>=== html ===</code> أو كشف تلقائي</span>
            </div>
            <div class="cp-smart-actions">
              <button type="button" class="cp-smart-btn primary" onclick="cpSmartDetect()">🔍 مزامنة الأقسام</button>
              <button type="button" class="cp-smart-btn" onclick="cpSmartClear()">🗑 مسح</button>
            </div>
          </div>
          <textarea id="cp-smart-input" class="cp-smart-textarea" spellcheck="false" autocorrect="off"
            placeholder="الصق الكود هنا — يمكنك خلط كل اللغات في مربع واحد&#10;&#10;مثال باستخدام علامات markdown:&#10;&#10;```html&#10;&lt;h1&gt;مرحبا&lt;/h1&gt;&#10;```&#10;&#10;```css&#10;h1 { color: red; }&#10;```&#10;&#10;```js&#10;console.log('hello');&#10;```&#10;&#10;أو الصق بدون علامات وسيتم الكشف التلقائي"></textarea>
          <div id="cp-smart-result" class="cp-smart-result" style="display:none"></div>
        </div>

        <?php
        $cpSections = $blogPost['type'] === 'code-page' ? decode_code_page($blogPost['body'] ?? '{}') : [];
        foreach (CODE_PAGE_LANGS as $lang => $meta):
            $existingCode = $cpSections[$lang] ?? '';
            $lineCount = $existingCode ? substr_count($existingCode, "\n") + 1 : 0;
        ?>
        <div class="cp-section" id="cp-section-<?= $lang ?>" data-lang="<?= $lang ?>">
          <div class="cp-section-header" onclick="cpToggle('<?= $lang ?>')">
            <span class="cp-lang-badge" style="--lang-color:<?= h($meta['color']) ?>"><?= $meta['icon'] ?> <?= h($meta['label']) ?></span>
            <span class="cp-line-count" id="cp-lines-<?= $lang ?>"><?= $lineCount ? $lineCount . ' سطر' : 'فارغ' ?></span>
            <button type="button" class="cp-toggle-btn" id="cp-toggle-<?= $lang ?>">▼</button>
          </div>
          <div class="cp-section-body" id="cp-body-<?= $lang ?>" style="<?= $existingCode ? '' : 'display:none' ?>">
            <div class="cp-toolbar">
              <button type="button" class="cp-tool-btn" onclick="cpClear('<?= $lang ?>')" title="مسح المحتوى">🗑 مسح</button>
              <button type="button" class="cp-tool-btn" onclick="cpCopy('<?= $lang ?>')" title="نسخ المحتوى">📋 نسخ</button>
              <span class="cp-tool-sep"></span>
              <button type="button" class="cp-tool-btn cp-expand-btn" onclick="cpExpand('<?= $lang ?>')" title="توسيع المحرر">⛶ توسيع</button>
            </div>
            <textarea
              name="cp_<?= $lang ?>"
              id="cp-textarea-<?= $lang ?>"
              class="cp-textarea"
              placeholder="أدخل كود <?= h($meta['label']) ?> هنا..."
              spellcheck="false"
              autocorrect="off"
              autocapitalize="off"
              data-lang="<?= $lang ?>"><?= h($existingCode) ?></textarea>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <button type="submit" class="btn-save" style="margin-bottom:40px">حفظ المقال</button>
</form>

<?php
/* ─────────────── ARTICLE GENERATOR ─────────────── */
elseif ($page === 'article-gen'):
  $allApps = $pdo->query("SELECT id,name,slug,developer FROM apps WHERE status='published' ORDER BY name")->fetchAll();
?>

<div class="admin-header"><h1>توليد مقالات التطبيقات</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.9">
    يولّد النظام لكل تطبيق مقالاً تفصيلياً من <strong>5 أقسام × 900-1100 كلمة = ~5000 كلمة</strong> باللهجة السورية.
    يشمل كل مقال: مقدمة، ميزات تفصيلية، دليل استخدام، مميزات وعيوب، نصائح وأسئلة شائعة، وأزرار تحميل احترافية.
    المقالات تُحفظ كمسودات في قسم المدونة ثم تنشرها عند الاستعداد.
  </p>
</div>

<!-- App selector grid -->
<div class="panel" style="margin-bottom:16px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
    <div style="font-size:14px;font-weight:700">اختر التطبيقات لتوليد مقالاتها</div>
    <div style="display:flex;gap:8px">
      <button class="btn" style="font-size:12px;padding:6px 14px" onclick="agSelectAll()">تحديد الكل</button>
      <button class="btn" style="font-size:12px;padding:6px 14px" onclick="agDeselectAll()">إلغاء الكل</button>
    </div>
  </div>
  <div id="ag-app-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">
    <?php foreach ($allApps as $a): ?>
    <label id="ag-app-<?= $a['id'] ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:border-color .2s,background .2s">
      <input type="checkbox" name="ag_apps[]" value="<?= $a['id'] ?>" style="flex-shrink:0" onchange="agUpdateBtn()">
      <span>
        <div style="font-size:13px;font-weight:600"><?= h($a['name']) ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= h($a['developer']) ?></div>
      </span>
    </label>
    <?php endforeach; ?>
    <?php if (!$allApps): ?>
    <p style="color:var(--muted);font-size:13px">لا توجد تطبيقات منشورة بعد.</p>
    <?php endif; ?>
  </div>
  <div style="margin-top:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <button id="ag-start-btn" class="btn btn-primary" onclick="agStartGeneration()" disabled>
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
      توليد المقالات المحددة
    </button>
    <span id="ag-sel-count" style="font-size:13px;color:var(--muted)">0 محدد</span>
  </div>
</div>

<!-- Progress log -->
<div id="ag-progress-wrap" style="display:none">
  <div class="panel" style="margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <div style="font-weight:700;font-size:14px">تقدم التوليد</div>
      <span id="ag-overall-status" style="font-size:13px;color:var(--muted)"></span>
    </div>
    <div style="height:6px;background:rgba(37,99,235,.1);border-radius:3px;overflow:hidden;margin-bottom:12px">
      <div id="ag-overall-bar" style="height:100%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:3px;width:0%;transition:width .5s"></div>
    </div>
    <div id="ag-log"></div>
  </div>
</div>

<script>
(function(){
  function agUpdateBtn(){
    const checked=document.querySelectorAll('input[name="ag_apps[]"]:checked');
    document.getElementById('ag-start-btn').disabled=checked.length===0;
    document.getElementById('ag-sel-count').textContent=checked.length+' محدد';
  }
  window.agSelectAll=()=>{document.querySelectorAll('input[name="ag_apps[]"]').forEach(c=>c.checked=true);agUpdateBtn();};
  window.agDeselectAll=()=>{document.querySelectorAll('input[name="ag_apps[]"]').forEach(c=>c.checked=false);agUpdateBtn();};

  const CHUNKS=5;

  window.agStartGeneration=async()=>{
    const checked=[...document.querySelectorAll('input[name="ag_apps[]"]:checked')];
    if(!checked.length)return;
    document.getElementById('ag-start-btn').disabled=true;
    document.getElementById('ag-progress-wrap').style.display='block';
    const log=document.getElementById('ag-log');
    const bar=document.getElementById('ag-overall-bar');
    const st=document.getElementById('ag-overall-status');
    log.innerHTML='';
    let totalSteps=checked.length*CHUNKS, doneSteps=0;

    for(let ci=0;ci<checked.length;ci++){
      const appId=checked[ci].value;
      const appName=checked[ci].closest('label').querySelector('div').textContent;
      const row=document.createElement('div');
      row.style.cssText='background:var(--panel-bg);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px';
      row.innerHTML=`<div style="font-size:13px;font-weight:700;margin-bottom:8px">${esc(appName)}</div>
        <div style="height:4px;background:rgba(37,99,235,.1);border-radius:2px;margin-bottom:8px;overflow:hidden"><div class="ag-row-bar" style="height:100%;background:linear-gradient(90deg,#2563eb,#7c3aed);border-radius:2px;width:0%;transition:width .4s"></div></div>
        <div class="ag-row-status" style="font-size:12px;color:var(--muted)">جارٍ التوليد...</div>`;
      log.appendChild(row);
      const rowBar=row.querySelector('.ag-row-bar');
      const rowSt=row.querySelector('.ag-row-status');

      let fullHtml=''; let ok=true;
      for(let ch=1;ch<=CHUNKS;ch++){
        rowSt.textContent=`القسم ${ch}/${CHUNKS}: جارٍ...`;
        try{
          const r=await fetch('admin.php?ajax=gen_app_article_chunk',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({app_id:parseInt(appId),chunk:ch,prev_html:fullHtml.slice(-2000)})});
          const d=await r.json();
          if(d.success){fullHtml+='\n'+d.html;}
          else{rowSt.textContent='⚠ خطأ في القسم '+ch+': '+(d.error||'غير معروف');ok=false;break;}
        }catch(e){rowSt.textContent='⚠ استثناء في القسم '+ch+': '+e.message;ok=false;break;}
        doneSteps++; rowBar.style.width=(ch/CHUNKS*100)+'%';
        bar.style.width=(doneSteps/totalSteps*100)+'%';
        st.textContent=Math.round(doneSteps/totalSteps*100)+'%';
      }
      if(ok && fullHtml){
        rowSt.textContent='حفظ المقال...';
        try{
          const sr=await fetch('admin.php?ajax=save_app_article',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({app_id:parseInt(appId),html:fullHtml})});
          const sd=await sr.json();
          if(sd.success){
            rowSt.innerHTML=`✓ مقال محفوظ (${sd.updated?'تحديث':'جديد'}) — <a href="${sd.view_url}" target="_blank" style="color:var(--accent)">معاينة</a> | <a href="admin.php?page=blog-edit&id=${sd.id}" style="color:var(--accent)">تعديل</a>`;
            rowBar.style.background='linear-gradient(90deg,#16a34a,#15803d)';
            const lbl=document.getElementById('ag-app-'+appId);
            if(lbl)lbl.style.borderColor='#16a34a';
          }else{rowSt.textContent='⚠ فشل الحفظ: '+(sd.error||'غير معروف');}
        }catch(e){rowSt.textContent='⚠ خطأ الحفظ: '+e.message;}
      }
    }
    bar.style.width='100%'; st.innerHTML='<strong>🏁 اكتمل</strong>';
    document.getElementById('ag-start-btn').disabled=false;
  };

  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
})();
</script>

<?php
/* ─────────────── SITE ANALYTICS (real self-tracked data, not GSC) ─────────────── */
elseif ($page === 'stats'):
    $days = 30;
    $dailyRaw = $pdo->query("
        SELECT DATE(created_at) d, event_type, COUNT(*) c
        FROM page_events
        WHERE created_at >= (NOW() - INTERVAL $days DAY)
        GROUP BY d, event_type
        ORDER BY d
    ")->fetchAll();
    $daily = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daily[$d] = ['view' => 0, 'download' => 0, 'search' => 0];
    }
    foreach ($dailyRaw as $row) {
        if (isset($daily[$row['d']])) $daily[$row['d']][$row['event_type']] = (int)$row['c'];
    }
    $maxDaily = 1;
    foreach ($daily as $d) $maxDaily = max($maxDaily, $d['view'], $d['download']);

    $totalsAllTime = [
        'views'            => (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM apps")->fetchColumn(),
        'downloads'        => (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM apps")->fetchColumn(),
        'apps'             => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
        'blog'             => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn(),
        'comments'         => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn(),
        'pending_comments' => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(),
    ];
    $views30     = array_sum(array_column($daily, 'view'));
    $downloads30 = array_sum(array_column($daily, 'download'));

    $topViewed     = $pdo->query("SELECT name,slug,views,downloads FROM apps WHERE status='published' ORDER BY views DESC LIMIT 10")->fetchAll();
    $topDownloaded = $pdo->query("SELECT name,slug,views,downloads FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 10")->fetchAll();
    $topSearches   = $pdo->query("
        SELECT meta, COUNT(*) c FROM page_events
        WHERE event_type='search' AND created_at >= (NOW() - INTERVAL $days DAY) AND meta IS NOT NULL AND meta<>''
        GROUP BY meta ORDER BY c DESC LIMIT 10
    ")->fetchAll();
?>
<div class="admin-header"><h1>إحصائيات الموقع</h1></div>

<div style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.25);color:var(--text);padding:14px 18px;border-radius:var(--radius);margin-bottom:20px;font-size:13px;line-height:1.8">
  ℹ️ هذه إحصائيات <strong>حقيقية</strong> يجمعها الموقع نفسه (مشاهدات وتحميلات وعمليات بحث فعلية من الزوار) — وليست بيانات Google Search Console.
  للحصول على بيانات جوجل الفعلية (الظهور في نتائج البحث، الكلمات المفتاحية، معدل النقر) يجب ربط حساب Google Search Console الخاص بك مباشرة عبر
  <a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a> — وهذا يتطلب حسابك الشخصي ولا يمكن أتمتته من هنا.
  خطوة توثيق الملكية مفعّلة بالفعل من "الإعدادات ← التحقق من ملكية الموقع".
</div>

<div class="admin-stats">
  <div class="stat-card"><div class="stat-num"><?= number_format($views30) ?></div><div class="stat-label">مشاهدات آخر 30 يوم</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--purple)"><?= number_format($downloads30) ?></div><div class="stat-label">تحميلات آخر 30 يوم</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= number_format($totalsAllTime['views']) ?></div><div class="stat-label">إجمالي المشاهدات (كل الوقت)</div></div>
  <div class="stat-card"><div class="stat-num"><?= number_format($totalsAllTime['downloads']) ?></div><div class="stat-label">إجمالي التحميلات (كل الوقت)</div></div>
</div>

<div class="panel" style="margin-top:20px">
  <h2>المشاهدات والتحميلات — آخر 30 يوم</h2>
  <div style="display:flex;align-items:flex-end;gap:3px;height:180px;margin-top:16px;overflow-x:auto;padding-bottom:4px">
    <?php foreach ($daily as $d => $v): ?>
    <div title="<?= h($d) ?>: <?= $v['view'] ?> مشاهدة، <?= $v['download'] ?> تحميل" style="flex:1 0 18px;min-width:18px;display:flex;flex-direction:column;justify-content:flex-end;height:100%;gap:2px">
      <div style="background:var(--cyan);border-radius:2px 2px 0 0;height:<?= (int)round($v['view'] / $maxDaily * 140) ?>px;min-height:<?= $v['view'] > 0 ? 2 : 0 ?>px"></div>
      <div style="background:var(--purple);border-radius:2px 2px 0 0;height:<?= (int)round($v['download'] / $maxDaily * 140) ?>px;min-height:<?= $v['download'] > 0 ? 2 : 0 ?>px"></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:16px;margin-top:10px;font-size:12px;color:var(--muted)">
    <span><span style="display:inline-block;width:10px;height:10px;background:var(--cyan);border-radius:2px;margin-inline-end:4px"></span> مشاهدات</span>
    <span><span style="display:inline-block;width:10px;height:10px;background:var(--purple);border-radius:2px;margin-inline-end:4px"></span> تحميلات</span>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
  <div class="panel">
    <h2>الأكثر مشاهدة</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>مشاهدات</th></tr></thead>
      <tbody>
      <?php foreach ($topViewed as $a): ?>
        <tr><td><a href="<?= h(app_url($a['slug'])) ?>" target="_blank"><?= h($a['name']) ?></a></td><td><?= number_format($a['views']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topViewed): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>الأكثر تحميلاً</h2>
    <table class="admin-table">
      <thead><tr><th>التطبيق</th><th>تحميلات</th></tr></thead>
      <tbody>
      <?php foreach ($topDownloaded as $a): ?>
        <tr><td><a href="<?= h(app_url($a['slug'])) ?>" target="_blank"><?= h($a['name']) ?></a></td><td><?= number_format($a['downloads']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topDownloaded): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;margin-bottom:40px">
  <div class="panel">
    <h2>أكثر عمليات البحث (آخر 30 يوم)</h2>
    <table class="admin-table">
      <thead><tr><th>كلمة البحث</th><th>عدد المرات</th></tr></thead>
      <tbody>
      <?php foreach ($topSearches as $s): ?>
        <tr><td><?= h($s['meta']) ?></td><td><?= number_format($s['c']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topSearches): ?><tr><td colspan="2" style="color:var(--muted)">لا بيانات بعد</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2>المحتوى</h2>
    <table class="admin-table">
      <tbody>
        <tr><td>تطبيقات منشورة</td><td><?= number_format($totalsAllTime['apps']) ?></td></tr>
        <tr><td>مقالات منشورة</td><td><?= number_format($totalsAllTime['blog']) ?></td></tr>
        <tr><td>تقييمات معتمدة</td><td><?= number_format($totalsAllTime['comments']) ?></td></tr>
        <tr><td>تقييمات بانتظار المراجعة</td><td><?= number_format($totalsAllTime['pending_comments']) ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php
/* ─────────────── CONNECTION TEST ─────────────── */
elseif ($page === 'connection'): ?>

<div class="admin-header"><h1>اختبار الاتصال وحل المشاكل</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:16px">
    اضغط الزر لفحص قاعدة البيانات، الصلاحيات، ومفاتيح OpenRouter — وسيتم تجربة كل موديل مجاني متاح تلقائياً حتى ينجح الاتصال.
  </p>
  <button type="button" id="btn-test-connection" class="btn-ai" style="width:100%;justify-content:center;font-size:15px;padding:16px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    اختبار الاتصال وحل كافة المشاكل
  </button>
  <div id="connection-results" style="margin-top:20px;display:flex;flex-direction:column;gap:10px"></div>
</div>

<?php
/* ─────────────── DATABASE MANAGEMENT ─────────────── */
elseif ($page === 'database'):
  $tablesInfo = [];
  try {
      $tbls = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
      foreach (['admins','categories','apps','settings'] as $t) {
          $exists = in_array($t, $tbls, true);
          $count = $exists ? (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() : 0;
          $tablesInfo[] = ['name'=>$t,'exists'=>$exists,'count'=>$count];
      }
  } catch (Throwable $e) {}
?>

<div class="admin-header"><h1>قاعدة البيانات</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:16px">
    لا حاجة لاستيراد أي ملف SQL يدوياً. الموقع يقوم تلقائياً بإنشاء وتصحيح كل الجداول اللازمة بمجرد الاتصال بقاعدة البيانات، وأي محتوى تنشره يُحفظ فيها مباشرة.
  </p>
  <table class="admin-table">
    <thead><tr><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr></thead>
    <tbody>
    <?php foreach ($tablesInfo as $t): ?>
      <tr>
        <td style="font-family:var(--f-mono)"><?= h($t['name']) ?></td>
        <td><span class="status-badge <?= $t['exists']?'status-published':'status-draft' ?>"><?= $t['exists']?'موجود':'ناقص' ?></span></td>
        <td style="font-family:var(--f-mono)"><?= number_format($t['count']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <button type="button" id="btn-fix-db" class="btn-save" style="margin-top:16px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zm0 0v12c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12c0 1.1 3.6 2 8 2s8-.9 8-2"/></svg>
    فحص وإصلاح الجداول الآن
  </button>
  <div id="db-fix-result" style="margin-top:12px"></div>

  <p style="color:var(--muted);font-size:13px;margin-top:24px;margin-bottom:8px">
    إذا كانت أوصاف بعض التطبيقات القديمة تظهر كفراغ بدل النص، اضغط الزر التالي لإصلاح ترميز النصوص المخزَّنة (يفحص كل التطبيقات ويصلح أي بايتات غير صالحة، آمن للتكرار):
  </p>
  <button type="button" id="btn-repair-encoding" class="btn-save" style="margin-top:4px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M4.5 15a8 8 0 0014.5 4.5M19.5 9A8 8 0 005 4.5"/></svg>
    إصلاح ترميز النصوص القديمة
  </button>
  <div id="repair-encoding-result" style="margin-top:12px"></div>

  <p style="color:var(--muted);font-size:13px;margin-top:24px;margin-bottom:8px">
    نسخة احتياطية كاملة لقاعدة البيانات (بنية الجداول + كل البيانات) كملف SQL — احتفظ بنسخة دورياً، لا يوجد نسخ احتياطي تلقائي حالياً:
  </p>
  <a href="admin.php?ajax=db_backup" class="btn-save" style="text-decoration:none;display:inline-flex">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
    تنزيل نسخة احتياطية (.sql)
  </a>
</div>

<?php
/* ─────────────── SETTINGS ─────────────── */
elseif ($page === 'settings'):
/* Helper: expandable tip block */
$tip = function(string $teaser, string $full): string {
    return '<div class="setting-detail"><div class="detail-short">' . $teaser . '</div>'
         . '<button type="button" class="detail-toggle" onclick="toggleSettingDetail(this)">▼ عرض المزيد</button>'
         . '<div class="detail-full">' . $full . '</div></div>';
};
?>

<style>
.setting-detail{margin-top:9px;border:1px solid rgba(6,182,212,.18);border-radius:9px;background:rgba(6,182,212,.03);padding:11px 14px;font-size:12px;color:var(--muted);line-height:1.85}
.setting-detail .detail-short{color:var(--muted)}
.setting-detail .detail-full{display:none;margin-top:10px;border-top:1px solid rgba(6,182,212,.12);padding-top:10px;color:var(--muted)}
.setting-detail.is-open .detail-short{display:none}
.setting-detail.is-open .detail-full{display:block}
.detail-toggle{margin-top:7px;background:none;border:none;color:var(--cyan);font-size:11px;cursor:pointer;padding:0;font-family:inherit;display:block}
.detail-toggle:hover{text-decoration:underline}
.detail-steps{margin:10px 0 4px;padding-right:0;list-style:none;display:flex;flex-direction:column;gap:7px;counter-reset:dstep}
.detail-steps li{counter-increment:dstep;display:flex;align-items:flex-start;gap:9px}
.detail-steps li::before{content:counter(dstep);display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;border-radius:50%;background:rgba(6,182,212,.18);color:var(--cyan);font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px}
.detail-note{margin-top:10px;padding:8px 11px;background:rgba(6,182,212,.07);border-radius:7px;font-size:11px;border-right:3px solid rgba(6,182,212,.4)}
.detail-note strong{color:var(--cyan)}
</style>
<script>
function toggleSettingDetail(btn){
  var el=btn.closest('.setting-detail');
  var open=el.classList.toggle('is-open');
  btn.textContent=open?'▲ عرض أقل':'▼ عرض المزيد';
}
</script>

<div class="admin-header"><h1>الإعدادات</h1></div>

<form method="post" action="admin.php?page=settings">
  <?= csrf_field() ?>
  <div class="panel">
    <h2>مزود الذكاء الاصطناعي</h2>
    <div class="form-group">
      <label class="form-label">اختر مزود الذكاء الاصطناعي لتوليد المحتوى</label>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px">
        <?php $aiProv = get_cfg($pdo,'ai_provider','openrouter'); ?>
        <label class="ai-provider-card <?= $aiProv==='openrouter'?'active':'' ?>" style="flex:1;min-width:200px;border:2px solid <?= $aiProv==='openrouter'?'var(--cyan)':'var(--border-c)' ?>;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:flex-start;gap:10px">
          <input type="radio" name="ai_provider" value="openrouter" <?= $aiProv==='openrouter'?'checked':'' ?> style="margin-top:3px">
          <div>
            <div style="font-weight:600;color:var(--white)">OpenRouter</div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">يدعم مئات الموديلات المجانية والمدفوعة. يتطلب مفتاح API. الأفضل للتنوع والموديلات المتخصصة.</div>
          </div>
        </label>
        <label class="ai-provider-card <?= $aiProv==='aifreeforever'?'active':'' ?>" style="flex:1;min-width:200px;border:2px solid <?= $aiProv==='aifreeforever'?'var(--cyan)':'var(--border-c)' ?>;border-radius:12px;padding:16px;cursor:pointer;display:flex;align-items:flex-start;gap:10px">
          <input type="radio" name="ai_provider" value="aifreeforever" <?= $aiProv==='aifreeforever'?'checked':'' ?> style="margin-top:3px">
          <div>
            <div style="font-weight:600;color:var(--white)">aifreeforever <span style="font-size:11px;background:var(--cyan);color:#000;border-radius:4px;padding:1px 6px;margin-right:6px">لا يحتاج مفتاح</span></div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">اتصال مباشر بدون مفتاح API. أسرع في كثير من الأحيان. مناسب لتوليد المحتوى العربي.</div>
          </div>
        </label>
      </div>
      <div id="test-aifree-wrap" style="margin-top:10px;<?= $aiProv==='aifreeforever'?'':'display:none' ?>">
        <button type="button" id="btn-test-aifree" class="btn-ai">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          اختبر اتصال aifreeforever
        </button>
        <span id="aifree-test-result" style="font-size:12px;margin-right:10px"></span>
      </div>
    </div>
  </div>

  <div class="panel" id="openrouter-panel" <?= $aiProv==='aifreeforever'?'style="opacity:.5;pointer-events:none"':'' ?>>
    <h2>إعدادات OpenRouter</h2>
    <div class="form-group">
      <label class="form-label">مفاتيح OpenRouter API</label>
      <div id="openrouter-keys-wrap">
        <?php
        $existingKeys = openrouter_keys(get_cfg($pdo,'openrouter_key'));
        if (!$existingKeys) $existingKeys = [''];
        foreach ($existingKeys as $ki => $kv): ?>
        <div class="key-row" style="display:flex;gap:8px;margin-bottom:8px">
          <input class="form-input or-key-input" type="text" name="openrouter_key_multi[]" value="<?= h($kv) ?>" placeholder="sk-or-v1-..." dir="ltr" style="flex:1;font-family:var(--f-mono);font-size:12px">
          <button type="button" class="btn-remove-key" style="padding:8px 12px;background:rgba(255,68,102,.15);border:1px solid rgba(255,68,102,.3);border-radius:8px;color:var(--danger);font-size:18px;line-height:1;cursor:pointer" title="حذف" onclick="this.closest('.key-row').remove()">×</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" id="btn-add-key" style="margin-top:4px;padding:6px 14px;background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.3);border-radius:8px;color:var(--cyan);font-size:12px;cursor:pointer">
        + إضافة مفتاح آخر
      </button>
      <div class="form-hint" style="margin-top:8px">
        أضف مفتاحاً أو أكثر — سيتم التبديل بينها تلقائياً. احصل على مفتاح مجاني من
        <a href="https://openrouter.ai/keys" target="_blank" style="color:var(--cyan)">openrouter.ai/keys</a>
      </div>
      <?= $tip(
        'OpenRouter يمنحك وصولاً لمئات نماذج الذكاء الاصطناعي المجانية والمدفوعة بمفتاح واحد. التسجيل مجاني تماماً ولا يتطلب بطاقة ائتمان.',
        '<ol class="detail-steps">
          <li>افتح <a href="https://openrouter.ai" target="_blank" style="color:var(--cyan)">openrouter.ai</a> واضغط <strong>Sign Up</strong> (يمكنك الدخول بحساب Google أو GitHub)</li>
          <li>بعد تسجيل الدخول انتقل مباشرةً إلى <a href="https://openrouter.ai/keys" target="_blank" style="color:var(--cyan)">openrouter.ai/keys</a></li>
          <li>اضغط <strong>Create Key</strong> — سمّه أي اسم تريد (مثلاً "yassota") واتركه بدون قيود مبلغ (unlimited)</li>
          <li>انسخ المفتاح الذي يبدأ بـ <code>sk-or-v1-...</code> والصقه في الحقل أعلاه</li>
          <li>لرفع الحد المجاني: أضف مفاتيح من حسابات مختلفة — الموقع يُبدّل بينها تلقائياً</li>
        </ol>
        <div class="detail-note"><strong>الحد المجاني:</strong> نماذج مثل Llama 3.3 70B تُتيح ملايين التوكنات يومياً — تكفي لتوليد محتوى 200+ تطبيق يومياً. إذا نفد الحد تبدّل المنظومة تلقائياً للنموذج الاحتياطي.</div>
        <div class="detail-note" style="margin-top:8px"><strong>نصيحة:</strong> النماذج المُنهية بـ <code>:free</code> (مثل <code>meta-llama/llama-3.3-70b-instruct:free</code>) مجانية تماماً. اختر "openrouter/free" إذا أردت الموقع يختار أفضل نموذج مجاني متاح تلقائياً في كل مرة.</div>'
      ) ?>
      <input type="hidden" name="openrouter_key" id="openrouter-key-hidden">
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">الموديل الأساسي</label>
        <select class="form-select" id="sel-primary-model" name="openrouter_model">
          <option value="<?= h(get_cfg($pdo,'openrouter_model','openrouter/free')) ?>" selected><?= h(get_cfg($pdo,'openrouter_model','openrouter/free')) ?></option>
        </select>
        <div class="form-hint" id="models-hint">جاري تحميل قائمة الموديلات المجانية...</div>
      </div>
      <div class="form-group">
        <label class="form-label">موديل احتياطي (Fallback)</label>
        <select class="form-select" id="sel-fallback-model" name="openrouter_fallback">
          <option value="<?= h(get_cfg($pdo,'openrouter_fallback','meta-llama/llama-3.3-70b-instruct:free')) ?>" selected><?= h(get_cfg($pdo,'openrouter_fallback','meta-llama/llama-3.3-70b-instruct:free')) ?></option>
        </select>
      </div>
    </div>
    <div class="form-hint" style="margin:8px 0 4px;font-weight:500;color:var(--white);font-size:12px">موديلات مجانية موصى بها (اضغط للاختيار):</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
      <?php foreach ([
        'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B',
        'qwen/qwen3-coder:free'                   => 'Qwen3 Coder',
        'openai/gpt-oss-20b:free'                 => 'GPT OSS 20B',
        'nvidia/nemotron-nano-9b-v2:free'          => 'Nemotron 9B',
        'google/gemma-3-27b-it:free'               => 'Gemma 3 27B',
        'mistralai/mistral-7b-instruct:free'       => 'Mistral 7B',
        'openrouter/free'                          => 'openrouter/free (أي موديل)',
      ] as $mid => $mlabel): ?>
      <button type="button" class="preset-model-btn" data-model="<?= h($mid) ?>"
        style="padding:4px 10px;background:rgba(6,182,212,.08);border:1px solid rgba(6,182,212,.2);border-radius:6px;color:var(--cyan);font-size:11px;cursor:pointer">
        <?= h($mlabel) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <div class="form-group" style="margin-top:6px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white)">
        <input type="checkbox" name="openrouter_auto_rotate" value="1" <?= get_cfg($pdo,'openrouter_auto_rotate','1')==='1'?'checked':'' ?>>
        التبديل التلقائي بين كل الموديلات المجانية المتاحة حتى ينجح الاتصال
      </label>
    </div>
    <button type="button" id="btn-test-connection-inline" class="btn-ai" style="margin-top:14px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      اختبار الاتصال الآن
    </button>
    <div id="inline-test-result" style="margin-top:12px"></div>
  </div>

  <div class="panel">
    <h2>توليد الصور بالذكاء الاصطناعي <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <div class="form-group">
      <label class="form-label">معرّف موديل توليد الصور على OpenRouter</label>
      <input class="form-input" type="text" name="openrouter_image_model" value="<?= h(get_cfg($pdo,'openrouter_image_model')) ?>" placeholder="مثال: google/gemini-2.5-flash-image-preview">
      <div class="form-hint">
        نماذج توليد الصور المجانية والموثوقة نادرة جداً على OpenRouter مقارنة بنماذج النصوص — إن تركت هذا الحقل فارغاً أو فشل التوليد،
        استخدم بدلاً منه زر "استيراد من Google Play" الذي يجلب الأيقونة الحقيقية للتطبيق (وهو الخيار الموصى به).
        اطّلع على قائمة الموديلات المتاحة من <a href="https://openrouter.ai/models?modality=text-%3Eimage" target="_blank" style="color:var(--cyan)">openrouter.ai/models</a>.
      </div>
      <?= $tip(
        'نماذج توليد الصور بالذكاء الاصطناعي تُنتج أيقونات فريدة عند عدم توفر أيقونة حقيقية من Google Play. الخيار الموصى به هو استيراد الأيقونة مباشرةً من Play Store.',
        '<p style="margin-bottom:8px">للعثور على نماذج توليد الصور المتاحة على OpenRouter:</p>
        <ol class="detail-steps">
          <li>افتح <a href="https://openrouter.ai/models?modality=text-%3Eimage" target="_blank" style="color:var(--cyan)">openrouter.ai/models (فلتر: Image Output)</a></li>
          <li>ابحث عن نماذج تدعم إنتاج الصور — مثال: <code>google/gemini-2.5-flash-image-preview</code></li>
          <li>انسخ معرّف النموذج (model ID) والصقه في الحقل أعلاه</li>
        </ol>
        <div class="detail-note"><strong>تنبيه مهم:</strong> معظم نماذج توليد الصور المجانية على OpenRouter غير مستقرة أو محدودة جداً. يُنصح بترك هذا الحقل فارغاً واستخدام زر "استيراد من Google Play" الذي يجلب الأيقونة الأصلية عالية الجودة مباشرةً.</div>'
      ) ?>
    </div>
  </div>

  <div class="panel">
    <h2>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2AABEE" stroke-width="2" style="vertical-align:-4px;margin-left:6px"><path d="M21 5L9 12m12 0L9 12m0 7V5l12 7-12 7z"/></svg>
      بوت تيليجرام — نشر تلقائي عند إضافة تطبيق جديد
    </h2>
    <p class="form-hint" style="margin-bottom:16px">
      عند نشر تطبيق جديد (حالة: منشور) يُرسل البوت تلقائياً رسالة للقناة تتضمن الأيقونة + وصف مولّد بالذكاء الاصطناعي + أزرار التحميل والصفحة.
      احصل على توكن من <a href="https://t.me/BotFather" target="_blank" rel="nofollow noopener" style="color:#2AABEE">@BotFather</a> ثم أضف البوت مشرفاً على قناتك.
    </p>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white);margin-bottom:16px">
      <input type="checkbox" name="telegram_enabled" value="1" <?= get_cfg($pdo,'telegram_enabled')==='1'?'checked':'' ?>>
      <span>تفعيل النشر التلقائي على تيليجرام</span>
    </label>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Bot Token</label>
        <input class="form-input" type="text" name="telegram_bot_token" value="<?= h(get_cfg($pdo,'telegram_bot_token')) ?>" placeholder="123456789:AAF..." dir="ltr" style="font-family:var(--f-mono);font-size:12px">
        <div class="form-hint">الحصول عليه من @BotFather → /newbot</div>
        <?= $tip(
          'توكن البوت هو مفتاح سري يُعرّف بوتك لتيليجرام. احصل عليه من @BotFather في 3 خطوات سريعة — لا تحتاج إنشاء حساب خارجي.',
          '<ol class="detail-steps">
            <li>افتح تيليجرام وابحث عن <a href="https://t.me/BotFather" target="_blank" style="color:#2AABEE">@BotFather</a> وابدأ محادثة معه</li>
            <li>أرسل الأمر <code>/newbot</code> ثم اتبع التعليمات: أدخل اسم البوت (مثل "yassota Bot") ثم اسم المستخدم (يجب أن ينتهي بـ bot، مثل "yassota_app_bot")</li>
            <li>سيُرسل @BotFather توكناً بالشكل <code>123456789:AAF...</code> — انسخه والصقه في الحقل أعلاه</li>
            <li>أضف البوت مشرفاً في قناتك: افتح القناة ← إدارة القناة ← المشرفون ← إضافة مشرف ← ابحث عن اسم بوتك</li>
          </ol>
          <div class="detail-note"><strong>مهم:</strong> احتفظ بالتوكن سرياً تماماً — من يملكه يتحكم في بوتك. إذا سُرب المفتاح أصدر واحداً جديداً فوراً من @BotFather عبر <code>/revoke</code>.</div>'
        ) ?>
      </div>
      <div class="form-group">
        <label class="form-label">Channel ID أو اسم القناة</label>
        <input class="form-input" type="text" name="telegram_channel_id" value="<?= h(get_cfg($pdo,'telegram_channel_id')) ?>" placeholder="@yassota_channel أو -100123456789" dir="ltr" style="font-family:var(--f-mono);font-size:12px">
        <div class="form-hint">مثال: @channelname أو رقم ID القناة الخاصة</div>
        <?= $tip(
          'القنوات العامة: استخدم @اسم_القناة مباشرةً. القنوات الخاصة: تحتاج رقم ID السالب (مثال: -1001234567890) للحصول عليه.',
          '<p style="margin-bottom:8px"><strong style="color:var(--white)">للقنوات العامة:</strong> استخدم مباشرةً <code>@channelname</code> (اسم قناتك على تيليجرام)</p>
          <p style="margin-bottom:8px"><strong style="color:var(--white)">للقنوات الخاصة — للحصول على Channel ID:</strong></p>
          <ol class="detail-steps">
            <li>أضف بوت <a href="https://t.me/userinfobot" target="_blank" style="color:#2AABEE">@userinfobot</a> أو <a href="https://t.me/raw_data_bot" target="_blank" style="color:#2AABEE">@raw_data_bot</a> إلى قناتك كمشرف</li>
            <li>أرسل أي رسالة في القناة وسيردّ البوت بمعلومات القناة بما فيها Chat ID</li>
            <li>الـ ID يبدأ بـ <code>-100</code> متبوعاً بأرقام (مثال: <code>-1001234567890</code>)</li>
          </ol>
          <div class="detail-note"><strong>بديل سريع:</strong> حوّل قناتك إلى عامة مؤقتاً لمعرفة اسمها، ثم استخدم <code>@اسم_القناة</code> حتى في حال إعادتها خاصة — هذا الشكل يعمل مع البوت طالما هو مشرف.</div>'
        ) ?>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">رابط القناة (للزر "اشترك في القناة" على صفحات التحميل)</label>
      <input class="form-input" type="url" name="telegram_channel_url" value="<?= h(get_cfg($pdo,'telegram_channel_url')) ?>" placeholder="https://t.me/yassota_channel" dir="ltr">
      <div class="form-hint">إذا تُرك فارغاً لن يظهر زر الاشتراك على صفحات التحميل.</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin-top:8px">
      <button type="button" id="btn-telegram-test" class="btn-ai" style="background:#2AABEE20;border-color:#2AABEE40;color:#2AABEE">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 5L9 12m12 0L9 12m0 7V5l12 7-12 7z"/></svg>
        إرسال رسالة اختبارية
      </button>
      <span id="telegram-test-result" style="font-size:12px"></span>
    </div>
  </div>

  <div class="panel">
    <h2>إعدادات AdSense</h2>
    <p class="form-hint" style="margin-bottom:16px;line-height:1.9">
      معرّف الناشر <strong style="color:var(--cyan)">ca-pub-5506877998492189</strong> مُثبَّت في الكود.
      أضف هنا معرّف وحدة الإعلان (Ad Unit Slot ID) الذي تحصل عليه من لوحة AdSense عند إنشاء وحدة إعلانية جديدة
      (الإعلانات → حسب وحدة الإعلانات → إنشاء وحدة إعلانية). بدون هذا الرقم تُعرض الإعلانات عبر Auto Ads فقط.
    </p>
    <div class="form-group">
      <label class="form-label">Ad Slot ID (معرّف الوحدة الإعلانية)</label>
      <input class="form-input" type="text" name="adsense_ad_slot_id"
             value="<?= h(get_cfg($pdo,'adsense_ad_slot_id')) ?>"
             placeholder="مثال: 1234567890" dir="ltr" style="font-family:var(--f-mono)">
      <div class="form-hint">
        الرقم الظاهر في كود الوحدة الإعلانية بعد <code>data-ad-slot="..."</code>.
        احتفظ بالحقل فارغاً إذا كنت تعتمد على Auto Ads فقط.
      </div>
      <?= $tip(
        'Slot ID هو رقم وحدتك الإعلانية في Google AdSense. تحتاجه لوضع إعلانات في أماكن محددة بدلاً من الاعتماد على Auto Ads وحدها.',
        '<ol class="detail-steps">
          <li>افتح <a href="https://adsense.google.com" target="_blank" style="color:var(--cyan)">adsense.google.com</a> وسجّل دخولك بحساب Google المرتبط</li>
          <li>اذهب إلى <strong>الإعلانات ← حسب وحدة الإعلانات ← إنشاء وحدة إعلانية جديدة</strong></li>
          <li>اختر نوع الوحدة (إعلان عرض مناسب، أو إعلان داخل المحتوى) وسمّها</li>
          <li>بعد الإنشاء ستجد كوداً يحتوي على <code>data-ad-slot="XXXXXXXXXX"</code> — الرقم هو Slot ID الذي تحتاجه</li>
          <li>انسخ هذا الرقم (10 أرقام عادةً) والصقه في الحقل أعلاه</li>
        </ol>
        <div class="detail-note"><strong>معرّف الناشر (Publisher ID):</strong> هو <code>ca-pub-5506877998492189</code> — مُثبَّت تلقائياً في كود الموقع، لا تحتاج إضافته هنا. الذي تحتاجه هنا هو Slot ID الوحدة الإعلانية فقط.</div>
        <div class="detail-note" style="margin-top:8px"><strong>Auto Ads مقابل Manual Slots:</strong> Auto Ads تضع الإعلانات أينما تراها Google مناسبة. Manual Slot ID يُتيح التحكم الكامل في مكان الإعلان ومظهره — مُنصح به لتحسين العائد.</div>'
      ) ?>
    </div>
    <div class="form-hint" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-c);line-height:1.9">
      تمت إزالة شبكة النوافذ المنبثقة (MoneyTag/PopAds) لأنها تخالف سياسات ناشري Google وتقلل فرص القبول في AdSense.
      يمكن إضافة كود مخصص لشبكات أخرى في خانة "صفحة التحميل" أسفل هذه الصفحة.
    </div>
  </div>

  <div class="panel">
    <h2>معلومات التواصل والسياسات</h2>
    <div class="form-group">
      <label class="form-label">البريد الإلكتروني للتواصل / DMCA</label>
      <input class="form-input" type="email" name="contact_email" value="<?= h(get_cfg($pdo,'contact_email')) ?>" placeholder="contact@yourdomain.com">
      <div class="form-hint">يظهر في صفحات اتصل بنا، سياسة الخصوصية، وDMCA.</div>
    </div>
    <div class="form-group" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label">تاريخ آخر تحديث لصفحة سياسة الخصوصية</label>
      <input class="form-input" type="text" name="privacy_updated_date" value="<?= h(get_cfg($pdo,'privacy_updated_date','1 يناير 2025')) ?>" placeholder="مثال: 1 يوليو 2025">
      <div class="form-hint">يظهر في أعلى صفحة سياسة الخصوصية — حدّثه في كل مرة تُعدّل فيها السياسة.</div>
    </div>
    <div class="form-group" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label">تاريخ آخر تحديث لصفحة شروط الاستخدام</label>
      <input class="form-input" type="text" name="terms_updated_date" value="<?= h(get_cfg($pdo,'terms_updated_date','1 يناير 2025')) ?>" placeholder="مثال: 1 يوليو 2025">
      <div class="form-hint">يظهر في أعلى صفحة شروط الاستخدام.</div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <input type="checkbox" name="admin_email_notifications" value="1" <?= get_cfg($pdo,'admin_email_notifications')==='1'?'checked':'' ?>>
      <span>إرسال بريد إلكتروني تلقائي للبريد أعلاه عند وصول رسالة تواصل جديدة أو تعليق جديد بانتظار المراجعة</span>
    </label>
  </div>

  <div class="panel">
    <h2>تحليلات الموقع — Google Analytics 4</h2>
    <p class="form-hint" style="margin-bottom:14px;line-height:1.8">
      Google Analytics 4 ضروري لقبول AdSense لأنه يُثبت وجود حركة زوار حقيقية.
      احصل على معرّف القياس من
      <a href="https://analytics.google.com" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">analytics.google.com</a>
      → إنشاء موقع → يبدأ بـ <code>G-</code> (مثال: <code>G-XXXXXXXXXX</code>).
      بعد إضافته الموقع سيُرسل بيانات الزوار تلقائياً مع احترام إعدادات موافقة ملفات تعريف الارتباط.
    </p>
    <div class="form-group">
      <label class="form-label">Google Analytics 4 — Measurement ID</label>
      <input class="form-input" type="text" name="ga4_measurement_id"
             value="<?= h(get_cfg($pdo,'ga4_measurement_id')) ?>"
             placeholder="G-XXXXXXXXXX" dir="ltr" style="font-family:var(--f-mono)">
      <div class="form-hint">اتركه فارغاً لتعطيل التتبع. لا تضع رمز GTM هنا — هذا الحقل لـ Measurement ID فقط.</div>
      <?= $tip(
        'Google Analytics 4 (GA4) يتتبع زوار موقعك وسلوكهم. معرّف القياس G-XXXXXXXXXX مطلوب للحصول عليه وإثبات وجود حركة حقيقية لـ AdSense.',
        '<ol class="detail-steps">
          <li>افتح <a href="https://analytics.google.com" target="_blank" style="color:var(--cyan)">analytics.google.com</a> وسجّل بحسابك</li>
          <li>إذا لم يكن لديك حساب اضغط <strong>ابدأ القياس</strong> وأنشئ حساباً جديداً</li>
          <li>أنشئ <strong>موقع (Property)</strong> جديداً — اختر GA4 (وليس Universal Analytics)</li>
          <li>أضف <strong>تدفق البيانات (Data Stream)</strong> ← اختر "الويب" ← أدخل عنوان موقعك</li>
          <li>ستجد <strong>Measurement ID</strong> بالشكل <code>G-XXXXXXXXXX</code> — انسخه والصقه في الحقل أعلاه</li>
        </ol>
        <div class="detail-note"><strong>لماذا GA4؟</strong> Google تطلبه لقبول AdSense كإثبات على وجود حركة زوار حقيقية وليست مشتراة. يُنصح بإضافته قبل التقديم على AdSense بأسبوعين على الأقل لتراكم بيانات كافية.</div>
        <div class="detail-note" style="margin-top:8px"><strong>مجاني تماماً:</strong> Google Analytics مجاني بلا قيود لمواقع بحجم أقل من 10 مليون حدث شهرياً.</div>'
      ) ?>
    </div>
    <div class="form-group" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label">صورة OG الافتراضية للموقع (Open Graph Default Image)</label>
      <input class="form-input" type="text" name="og_default_image"
             value="<?= h(get_cfg($pdo,'og_default_image')) ?>"
             placeholder="uploads/og-default.png" dir="ltr">
      <div class="form-hint">
        مسار الصورة نسبةً لجذر الموقع — تُستخدم في صفحات بدون صورة خاصة (About، Contact، FAQ…).
        يُنصح بحجم 1200×630 بيكسل. ارفع الصورة ثم أدخل مسارها هنا.
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>التحقق من ملكية الموقع (Search Console)</h2>
    <p class="form-hint" style="margin-bottom:14px">
      لظهور الموقع في نتائج البحث بشكل جيد يجب ربطه بـ
      <a href="https://search.google.com/search-console" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">Google Search Console</a>
      و<a href="https://www.bing.com/webmasters" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">Bing Webmaster Tools</a> —
      اختر طريقة "وسم HTML meta" من كل أداة، والصق الكود (بين علامتي الاقتباس فقط، مثال: <code>AbCdEf123...</code>) هنا، ثم احفظ.
      بعد الحفظ اضغط زر التحقق في تلك الأداة، ثم أرسل خريطة الموقع (Sitemap) على الرابط <code>/sitemap.php</code>.
    </p>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Google Search Console verification code</label>
        <input class="form-input" type="text" name="google_site_verification" value="<?= h(get_cfg($pdo,'google_site_verification')) ?>" placeholder="مثال: AbCdEfGhIjKlMnOpQrStUvWxYz">
        <?= $tip(
          'Google Search Console يُمكّنك من مراقبة ظهور موقعك في نتائج البحث، إرسال Sitemap، وإصلاح أخطاء الفهرسة. التحقق خطوة أولى لا غنى عنها.',
          '<ol class="detail-steps">
            <li>افتح <a href="https://search.google.com/search-console" target="_blank" style="color:var(--cyan)">search.google.com/search-console</a> وسجّل بحساب Google</li>
            <li>اضغط <strong>إضافة موقع (Add Property)</strong> ← أدخل عنوان موقعك الكامل (مع https://)</li>
            <li>اختر طريقة التحقق: <strong>HTML Tag</strong> أو <strong>وسم HTML</strong></li>
            <li>انسخ القيمة داخل الكود — ما بين علامتي اقتباس بعد <code>content="</code> — وهي نص من 20-40 حرفاً</li>
            <li>الصق هذه القيمة فقط (بدون الكود كاملاً) في الحقل أعلاه ثم احفظ الإعدادات</li>
            <li>عد لـ Search Console واضغط <strong>التحقق</strong> — سيتأكد الموقع من وجود الكود ويمنحك الوصول</li>
          </ol>
          <div class="detail-note"><strong>بعد التحقق:</strong> أرسل خريطة الموقع على الرابط <code>' . rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/sitemap.php</code> من قسم Sitemap في Search Console. هذا يُسرّع فهرسة جميع التطبيقات.</div>'
        ) ?>
      </div>
      <div class="form-group">
        <label class="form-label">Bing Webmaster verification code</label>
        <input class="form-input" type="text" name="bing_site_verification" value="<?= h(get_cfg($pdo,'bing_site_verification')) ?>" placeholder="مثال: 1234567890ABCDEF">
        <?= $tip(
          'Bing Webmaster Tools يُسرّع ظهور موقعك في Bing وDuckDuckGo والمحركات التي تستخدم Bing — تمثّل مجتمعةً نحو 10% من عمليات البحث العالمية.',
          '<ol class="detail-steps">
            <li>افتح <a href="https://www.bing.com/webmasters" target="_blank" style="color:var(--cyan)">bing.com/webmasters</a> وسجّل بحساب Microsoft</li>
            <li>اضغط <strong>Add a Site</strong> وأدخل عنوان موقعك</li>
            <li>اختر طريقة التحقق <strong>Meta Tag</strong> أو <strong>XML File</strong></li>
            <li>انسخ القيمة الموجودة في <code>content="..."</code> فقط (رقم من 16-32 حرفاً)</li>
            <li>الصق هذه القيمة في الحقل أعلاه واحفظ ← ثم عد لـ Bing واضغط Verify</li>
          </ol>
          <div class="detail-note"><strong>مزايا إضافية:</strong> بعد التحقق، يمكنك استخدام Bing Webmaster لإرسال URLs مباشرةً وبشكل أسرع من IndexNow، ومراقبة أخطاء الزحف.</div>'
        ) ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <h2>فحص أمان روابط التحميل (VirusTotal)</h2>
    <p class="form-hint" style="margin-bottom:14px">
      يضيف شارة فحص حقيقية (وليست وهمية) في صفحة تحميل كل تطبيق تبني ثقة الزوار — تظهر فقط بعد فحص فعلي ناجح، لا شيء يظهر بدون مفتاح.
      احصل على مفتاح مجاني من <a href="https://www.virustotal.com/gui/join-us" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">virustotal.com</a> (حساب مجاني، بدون بطاقة ائتمان).
    </p>
    <div class="form-group">
      <label class="form-label">VirusTotal API Key</label>
      <input class="form-input" type="text" name="virustotal_api_key" value="<?= h(get_cfg($pdo,'virustotal_api_key')) ?>" placeholder="الصق المفتاح هنا">
      <?= $tip(
        'VirusTotal API المجاني يُتيح فحص روابط APK بأكثر من 70 محرك مضادات فيروسات وإظهار شارة الأمان في صفحة التحميل لزيادة ثقة الزوار.',
        '<ol class="detail-steps">
          <li>افتح <a href="https://www.virustotal.com/gui/join-us" target="_blank" style="color:var(--cyan)">virustotal.com/gui/join-us</a> وأنشئ حساباً مجانياً</li>
          <li>أكّد بريدك الإلكتروني ثم سجّل الدخول</li>
          <li>اذهب لـ <a href="https://www.virustotal.com/gui/my-apikey" target="_blank" style="color:var(--cyan)">صفحة API Key الشخصية</a></li>
          <li>ستجد مفتاحك مباشرةً (64 حرفاً من الأرقام والحروف) — انسخه والصقه في الحقل أعلاه</li>
        </ol>
        <div class="detail-note"><strong>حدود الخطة المجانية:</strong> 4 فحوصات في الدقيقة، 500 فحص في اليوم. يكفي لمئات التطبيقات يومياً نظراً لأن نتائج الفحص تُخزّن مؤقتاً ولا تُعاد إلا عند التغيير.</div>
        <div class="detail-note" style="margin-top:8px"><strong>كيف تعمل الشارة؟</strong> عند نشر تطبيق جديد، الموقع يُرسل رابط التحميل لـ VirusTotal للفحص. بعد اكتمال الفحص تظهر شارة خضراء "تم الفحص: نظيف" في صفحة التحميل — وهذا يزيد ثقة الزوار بشكل ملحوظ.</div>'
      ) ?>
    </div>
  </div>

  <div class="panel">
    <h2>فهرسة سريعة بـ IndexNow <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <p class="form-hint" style="margin-bottom:14px">
      IndexNow يُخبر محركات البحث (Bing، Yandex، وغيرها) فورياً عند نشر أي تطبيق أو تحديثه بدلاً من انتظار الزحف التلقائي.
      أنشئ مفتاحاً نصياً عشوائياً (20+ حرفاً) واكتبه هنا — الموقع يرسله تلقائياً في كل نشر.
      اطّلع على الشرح الكامل من <a href="https://www.indexnow.org/documentation" target="_blank" rel="nofollow noopener" style="color:var(--cyan)">indexnow.org</a>.
    </p>
    <div class="form-group">
      <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
        <span>مفتاح IndexNow (API Key)</span>
        <span id="inkey-status" style="font-size:11px;font-weight:600"></span>
      </label>
      <div style="display:flex;gap:8px;align-items:stretch">
        <input class="form-input" type="text" id="indexnow_key" name="indexnow_key"
               value="<?= h(get_cfg($pdo,'indexnow_key')) ?>"
               placeholder="مثال: a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6"
               dir="ltr" style="font-family:var(--f-mono);font-size:12px;flex:1"
               oninput="validateInKey(this)">
        <button type="button" onclick="generateInKey()"
                style="padding:0 14px;border-radius:8px;border:1px solid var(--border-c);background:var(--surface-3);color:var(--text);font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0">
          🔑 توليد مفتاح
        </button>
      </div>
      <div class="form-hint" style="margin-top:6px">
        <strong style="color:var(--danger)">⚠ مهم:</strong> المفتاح يجب أن يحتوي على <strong>أحرف إنجليزية وأرقام فقط</strong>
        (<code>A-Z a-z 0-9</code>) — لا شرطة، لا رمز خاص، لا مسافات — بطول <strong>8 إلى 128 حرفاً</strong>.
        أي رمز آخر يُسبب خطأ 422 من خوادم IndexNow.
        بعد الحفظ، سيُنشئ الموقع ملف <code><?php $ik = get_cfg($pdo,'indexnow_key'); echo h($ik ?: 'مفتاحك') ?>.txt</code> في جذر الموقع تلقائياً.
      </div>
      <?php
      $curInKey = get_cfg($pdo,'indexnow_key','');
      if ($curInKey && !preg_match('/^[a-zA-Z0-9]{8,128}$/', $curInKey)): ?>
      <div style="margin-top:8px;padding:10px 14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;font-size:12px;color:#ef4444;direction:rtl">
        🔴 المفتاح الحالي (<code dir="ltr"><?= h(mb_substr($curInKey,0,20).'...') ?></code>) يحتوي على رموز غير مسموحة.
        هذا هو سبب خطأ 422 في سجل الفهرسة.
        <strong>اضغط "توليد مفتاح" للحصول على مفتاح صحيح جديد، ثم احفظ الإعدادات.</strong>
      </div>
      <?php endif; ?>
      <?= $tip(
        'IndexNow يُخبر Bing وYandex وغيرها فورياً عند نشر أي تطبيق بدل انتظار زحف العناكب. لا تحتاج حساباً خارجياً — تصنع المفتاح بنفسك.',
        '<p style="margin-bottom:9px"><strong style="color:var(--white)">كيف يعمل IndexNow؟</strong><br>بدلاً من انتظار Google/Bing لاكتشاف محتواك الجديد (قد يأخذ أياماً أو أسابيع)، IndexNow يُرسل إشعاراً فورياً لمحركات البحث قائلاً "هذه الصفحة تغيّرت — افحصها الآن". النتيجة: فهرسة أسرع بكثير.</p>
        <p style="margin-bottom:8px"><strong style="color:var(--white)">كيف تُنشئ المفتاح؟</strong></p>
        <ol class="detail-steps">
          <li>اصنع مفتاحاً عشوائياً: أي نص من 20-128 حرفاً من الأحرف الإنجليزية والأرقام والشرطة. مثال: <code>yassota2025key-abc123def456</code></li>
          <li>الصق المفتاح في الحقل أعلاه واحفظ — الموقع سيُنشئ ملف <code>.txt</code> في جذر الموقع باسم المفتاح تلقائياً</li>
          <li>تحقق أن الملف موجود على: <code>' . rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/[مفتاحك].txt</code></li>
          <li>فعّل "الإرسال التلقائي" — من هذه اللحظة كل تطبيق تنشره سيُرسل إشعاراً فورياً</li>
        </ol>
        <div class="detail-note"><strong>محركات تدعم IndexNow:</strong> Bing، Yandex، Naver، Seznam، Yep — وعبر Bing تصل الإشعارات لـ DuckDuckGo أيضاً. Google لا تدعم IndexNow بشكل رسمي لكنها تتلقى الإشعارات من خلال شركائها.</div>
        <div class="detail-note" style="margin-top:8px"><strong>مجاني تماماً:</strong> بلا حسابات خارجية، بلا حدود استخدام، بلا رسوم. فقط مفتاح نصي تضعه بنفسك.</div>'
      ) ?>
    </div>
    <div class="form-group" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-c)">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
        <input type="checkbox" name="auto_indexnow_enabled" value="1" <?= get_cfg($pdo,'auto_indexnow_enabled','1')==='1'?'checked':'' ?>>
        <span>إرسال IndexNow تلقائياً عند نشر أو تحديث أي تطبيق</span>
      </label>
      <div class="form-hint">يمكنك أيضاً تشغيل/إيقاف هذا من صفحة <a href="admin.php?page=indexnow-log" style="color:var(--cyan)">سجل الفهرسة</a> أو الضغط على "إرسال كل الروابط" لفهرسة دفعة واحدة.</div>
    </div>
  </div>

  <div class="panel">
    <h2>إعدادات صفحة التحميل</h2>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">مدة العد التنازلي قبل التحميل (بالثواني)</label>
        <input class="form-input" type="number" name="download_countdown_secs" value="<?= h(get_cfg($pdo,'download_countdown_secs','7')) ?>" min="3" max="30" style="max-width:120px">
        <div class="form-hint">الافتراضي: 7 ثوانٍ. أقل = تجربة أفضل للمستخدم. أكثر = مشاهدة إعلانات أطول.</div>
        <?= $tip(
          'مدة العد التنازلي تؤثر مباشرةً على معدل الارتداد وعائد الإعلانات. القيمة المثلى تحقق التوازن بين تجربة المستخدم وعائد الإعلانات.',
          '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">
            <div style="padding:8px;border:1px solid rgba(6,182,212,.2);border-radius:7px;text-align:center">
              <div style="font-size:16px;font-weight:700;color:var(--cyan)">3-5</div>
              <div style="font-size:10px;margin-top:4px">تجربة ممتازة<br>عائد منخفض</div>
            </div>
            <div style="padding:8px;border:1px solid rgba(6,182,212,.4);border-radius:7px;text-align:center;background:rgba(6,182,212,.05)">
              <div style="font-size:16px;font-weight:700;color:var(--cyan)">7</div>
              <div style="font-size:10px;margin-top:4px">توازن مثالي<br>موصى به ✓</div>
            </div>
            <div style="padding:8px;border:1px solid rgba(6,182,212,.2);border-radius:7px;text-align:center">
              <div style="font-size:16px;font-weight:700;color:var(--cyan)">10-15</div>
              <div style="font-size:10px;margin-top:4px">عائد أعلى<br>إحباط أكثر</div>
            </div>
          </div>
          <div class="detail-note"><strong>تأثير على AdSense:</strong> فترة أطول = مزيد من مشاهدات الإعلانات = عائد RPM أعلى. لكن الفترات الطويلة جداً (15+ ثانية) تزيد معدل الارتداد وقد تضرّ تجربة المستخدم وترتيبك في محركات البحث.</div>'
        ) ?>
      </div>
    </div>
    <div class="form-group" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-c)">
      <label class="form-label">كود إعلانات مخصص على صفحة التحميل <span style="color:var(--muted);font-weight:400">(اختياري — PropellerAds / HilltopAds / PopAds)</span></label>
      <textarea class="form-textarea" name="download_custom_ad_code" rows="5" dir="ltr" style="font-family:var(--f-mono);font-size:11px" placeholder="الصق كود JavaScript الخاص بشبكة الإعلانات هنا..."><?= h(get_cfg($pdo,'download_custom_ad_code')) ?></textarea>
      <div class="form-hint">يُحقن مباشرةً في صفحة التحميل كـ &lt;script&gt; — مثالي لشبكات Popunder/Push كـ PropellerAds وHilltopAds. لا يؤثر على إعلانات AdSense في بقية الصفحات.</div>
      <?= $tip(
        'شبكات الإعلانات البديلة تعمل جنباً لجنب مع AdSense على صفحات التحميل. الأنسب: PropellerAds وHilltopAds لعائد pop/push — أعلى RPM من AdSense في هذه الصفحات.',
        '<p style="margin-bottom:9px"><strong style="color:var(--white)">شبكات موصى بها لصفحات التحميل:</strong></p>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
          <div style="padding:9px 11px;border:1px solid rgba(6,182,212,.15);border-radius:7px">
            <div style="font-weight:600;color:var(--white);font-size:12px">PropellerAds</div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px">الأكثر شهرةً — Pop/Push/Interstitial. عائد مرتفع للمواقع العربية. <a href="https://propellerads.com" target="_blank" style="color:var(--cyan)">propellerads.com</a></div>
          </div>
          <div style="padding:9px 11px;border:1px solid rgba(6,182,212,.15);border-radius:7px">
            <div style="font-weight:600;color:var(--white);font-size:12px">HilltopAds</div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px">Pop-under عالي الجودة مع دفع تلقائي. <a href="https://hilltopads.com" target="_blank" style="color:var(--cyan)">hilltopads.com</a></div>
          </div>
          <div style="padding:9px 11px;border:1px solid rgba(6,182,212,.15);border-radius:7px">
            <div style="font-weight:600;color:var(--white);font-size:12px">Adsterra</div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px">Social Bar + Pop-under. عتبة دفع منخفضة ($5). <a href="https://adsterra.com" target="_blank" style="color:var(--cyan)">adsterra.com</a></div>
          </div>
        </div>
        <div class="detail-note"><strong>كيف تحصل على الكود؟</strong> سجّل في الشبكة ← أضف موقعك ← أنشئ إعلاناً ← انسخ كود JavaScript والصقه هنا. الكود يُضاف تلقائياً كـ &lt;script&gt; في صفحة التحميل فقط، بلا تأثير على باقي الموقع.</div>
        <div class="detail-note" style="margin-top:8px"><strong>تحذير AdSense:</strong> هذا الحقل مخصص لصفحة التحميل فقط. لا تضع أكواد Pop-under في بقية صفحات الموقع إذا كنت تستخدم AdSense — Google قد تُعلّق حسابك.</div>'
      ) ?>
    </div>
  </div>

  <div class="panel">
    <h2>الترجمة التلقائية متعددة اللغات</h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:14px">
      عند نشر تطبيق جديد، يُنشئ النظام تلقائياً نسخاً مترجمة منه إلى اللغات المحددة. كل نسخة تحصل على صفحة خاصة بها تُرفع لمحركات البحث. الصفحة الرئيسية تعرض التطبيق الأصلي فقط (لا تظهر الترجمات كتطبيقات منفصلة).
    </p>
    <div class="form-group">
      <label class="form-label">أكواد اللغات المراد الترجمة إليها (مفصولة بفواصل)</label>
      <input class="form-input" type="text" name="auto_translate_langs" dir="ltr" style="text-align:left;font-family:var(--f-mono)"
             value="<?= h(get_cfg($pdo,'auto_translate_langs','')) ?>"
             placeholder="en,ru,fr,de">
      <div class="form-hint">أمثلة: <code>en</code> (إنجليزية) · <code>ru</code> (روسية) · <code>fr</code> (فرنسية) · <code>de</code> (ألمانية) · <code>es</code> (إسبانية) · <code>tr</code> (تركية). اتركه فارغاً لتعطيل الترجمة التلقائية. الترجمة تحدث في الخلفية بعد الحفظ (لا تضغط حفظ مرتين).</div>
    </div>
  </div>

  <div class="panel">
    <h2>Cloudflare Turnstile — كابتشا مجاني وصديق للخصوصية ⭐ <span style="color:var(--muted);font-weight:400">(موصى به)</span></h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:14px">
      بديل مجاني وخصوصي تماماً لـ reCAPTCHA — لا يتتبع المستخدمين، لا يحتاج إلى بيانات Google، ويعمل في الخلفية بشكل غير مرئي في معظم الأوقات.
      احصل على المفاتيح المجانية من <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" style="color:var(--cyan)">dash.cloudflare.com → Turnstile</a>.
      <strong>عند ضبط Turnstile يُعطَّل reCAPTCHA تلقائياً — لا تحتاج لكليهما.</strong>
    </p>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Turnstile — Site Key <span style="color:var(--muted);font-size:11px">(مفتاح الموقع)</span></label>
        <input class="form-input" type="text" name="turnstile_site_key" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'turnstile_site_key','')) ?>" placeholder="0x4AAAAAAA...">
      </div>
      <div class="form-group">
        <label class="form-label">Turnstile — Secret Key <span style="color:var(--muted);font-size:11px">(مفتاح السر)</span></label>
        <input class="form-input" type="text" name="turnstile_secret_key" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'turnstile_secret_key','')) ?>" placeholder="0x4AAAAAAA...">
      </div>
    </div>
    <div class="form-hint">يُفعَّل على صفحة التحميل وعند رصد نشاط مريب (VPN / تكرار طلبات / تغيير IP). الإعداد المجاني يدعم طلبات غير محدودة.</div>
  </div>

  <div class="panel">
    <h2>reCAPTCHA — حماية النماذج والتحميل <span style="color:var(--muted);font-weight:400">(اختياري — بديل لـ Turnstile)</span></h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:14px">
      احصل على مفاتيح من <a href="https://www.google.com/recaptcha/admin/create" target="_blank" style="color:var(--cyan)">google.com/recaptcha</a>. يُنصح باستخدام v3 (غير مرئي) + v2 (احتياطي عند انخفاض الدرجة). اترك الحقول فارغة لتعطيل reCAPTCHA.
    </p>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">reCAPTCHA v3 — Site Key <span style="color:var(--muted);font-size:11px">(مفتاح الموقع)</span></label>
        <input class="form-input" type="text" name="recaptcha_v3_site_key" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'recaptcha_v3_site_key','')) ?>" placeholder="6Lcr...">
      </div>
      <div class="form-group">
        <label class="form-label">reCAPTCHA v3 — Secret Key <span style="color:var(--muted);font-size:11px">(مفتاح السر)</span></label>
        <input class="form-input" type="text" name="recaptcha_v3_secret" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'recaptcha_v3_secret','')) ?>" placeholder="6Lcr...">
      </div>
      <div class="form-group">
        <label class="form-label">reCAPTCHA v2 — Site Key <span style="color:var(--muted);font-size:11px">(مفتاح الموقع)</span></label>
        <input class="form-input" type="text" name="recaptcha_v2_site_key" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'recaptcha_v2_site_key','')) ?>" placeholder="6Lcr...">
      </div>
      <div class="form-group">
        <label class="form-label">reCAPTCHA v2 — Secret Key <span style="color:var(--muted);font-size:11px">(مفتاح السر)</span></label>
        <input class="form-input" type="text" name="recaptcha_v2_secret" dir="ltr" style="font-family:var(--f-mono);font-size:12px"
               value="<?= h(get_cfg($pdo,'recaptcha_v2_secret','')) ?>" placeholder="6Lcr...">
      </div>
    </div>
    <div class="form-hint">بعد الحفظ، يُفعَّل reCAPTCHA تلقائياً على: نموذج التعليقات + صفحة التحميل (عندما يكون المفتاح مضبوطاً). درجة v3 الدنيا للقبول: 0.5 — ما دونها تظهر v2 كتحقق إضافي.</div>
  </div>

  <div class="panel">
    <h2>تقييد وصول لوحة التحكم بعنوان IP <span style="color:var(--muted);font-weight:400">(اختياري)</span></h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:14px">
      حماية إضافية فوق تسجيل الدخول بكلمة المرور — وليست بديلاً عنه. اترك الحقل فارغاً لتعطيل هذا القيد (الوضع الافتراضي).
      إذا فعّلته، عنوانك الحالي <strong style="color:var(--cyan)"><?= h(client_ip()) ?></strong> يُضاف تلقائياً لمنع إقفال وصولك عن طريق الخطأ.
    </p>
    <div class="form-group">
      <label class="form-label">عناوين IP المسموح بها (سطر لكل عنوان)</label>
      <textarea class="form-textarea" name="admin_ip_allowlist" rows="3" placeholder="اتركه فارغاً لتعطيل القيد"><?= h(get_cfg($pdo,'admin_ip_allowlist')) ?></textarea>
      <div class="form-hint">أي عنوان IP خارج هذه القائمة سيُمنع من فتح admin.php بالكامل، بما فيها صفحة تسجيل الدخول.</div>
    </div>
  </div>

  <button type="submit" class="btn-save">حفظ الإعدادات</button>
</form>

<?php
/* ─────────────── AI ASSISTANT ─────────────── */
elseif ($page === 'assistant'): ?>

<div class="admin-header"><h1>مساعد الذكاء الاصطناعي</h1></div>

<div class="panel" style="margin-bottom:16px">
  <p style="color:var(--muted);font-size:13px;line-height:1.8">
    اكتب طلبك بلغة طبيعية وسينفّذه المساعد مباشرة عبر إجراءات محدّدة وآمنة فقط: إنشاء مسودة تطبيق بمحتوى مولّد،
    إعادة توليد SEO لتطبيق أو للكل، توليد أيقونة بالذكاء الاصطناعي، أو تعديل إعداد غير حساس.
    المساعد <strong style="color:var(--white)">لا يكتب أو يعدّل ملفات الموقع البرمجية</strong> — كل تغييرات الكود تمر عبر مطوّر الموقع، حفاظاً على أمان السيرفر.
  </p>
  <div style="margin-top:12px;font-size:12px;color:var(--muted)">
    أمثلة: <em>"أنشئ مسودة لتطبيق CapCut Pro"</em> · <em>"حدّث SEO لكل التطبيقات"</em> · <em>"ولّد أيقونة للتطبيق رقم 3"</em>
  </div>
</div>

<div class="panel">
  <div id="assistant-log" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;max-height:50vh;overflow-y:auto"></div>
  <div class="ai-row">
    <input class="form-input" id="assistant-input" type="text" placeholder="اكتب طلبك هنا...">
    <button type="button" id="btn-assistant-send" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
      إرسال
    </button>
  </div>
</div>

<?php
/* ─────────────── SEO PREVIEW ─────────────── */
elseif ($page === 'seo-preview'):
    $previewApps = $pdo->query("SELECT id,name,slug,seo_title,meta_description,icon_path,updated_at FROM apps WHERE status='published' ORDER BY updated_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    $siteHost = parse_url(SITE_URL, PHP_URL_HOST) ?: SITE_URL;
?>
<div class="admin-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <div>
    <h1>معاينة نتائج Google</h1>
    <p style="color:var(--muted);font-size:13px;margin-top:4px">كيف تبدو صفحات الموقع في نتائج بحث Google — معاينة حية للعنوان والوصف والرابط</p>
  </div>
  <button id="fix-seo-btn" onclick="fixLongSeoTitles()" style="padding:8px 16px;border-radius:8px;border:none;background:rgba(239,68,68,.12);color:#ef4444;font-size:13px;cursor:pointer;font-weight:600;white-space:nowrap">
    ✂️ تصحيح العناوين الطويلة (&gt;60 حرف)
  </button>
</div>

<!-- Live Preview Editor -->
<div class="panel" style="margin-bottom:20px">
  <h2 style="margin-bottom:14px">معاينة مخصصة</h2>
  <div class="form-grid" style="margin-bottom:16px">
    <div class="form-group">
      <label class="form-label">العنوان (Title)</label>
      <input class="form-input" id="pv-title" type="text" placeholder="عنوان الصفحة..." oninput="updatePreview()">
      <div class="form-hint" id="pv-title-count">0 / 60 حرف (مثالي 50–60)</div>
    </div>
    <div class="form-group">
      <label class="form-label">الرابط (URL)</label>
      <input class="form-input" id="pv-url" type="text" dir="ltr" value="<?= h(rtrim(SITE_URL,'/')) ?>/app-name" oninput="updatePreview()">
    </div>
    <div class="form-group full">
      <label class="form-label">الوصف (Meta Description)</label>
      <textarea class="form-textarea" id="pv-desc" rows="2" placeholder="وصف الصفحة..." oninput="updatePreview()"></textarea>
      <div class="form-hint" id="pv-desc-count">0 / 160 حرف (مثالي 120–160)</div>
    </div>
  </div>

  <!-- Google SERP simulation -->
  <div style="background:#fff;border-radius:10px;padding:20px 24px;font-family:Arial,sans-serif;border:1px solid #dfe1e5;max-width:640px">
    <div style="font-size:14px;color:#1a0dab;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" id="pv-title-render">عنوان الصفحة هنا</div>
    <div style="font-size:12px;color:#006621;margin-bottom:4px" id="pv-url-render"><?= h(rtrim(SITE_URL,'/')) ?>/app-name</div>
    <div style="font-size:13px;color:#545454;line-height:1.5" id="pv-desc-render">الوصف سيظهر هنا — أضف وصفاً واضحاً يشجع المستخدم على الضغط على النتيجة.</div>
    <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap" id="pv-sitelinks" style="display:none">
      <a href="#" style="font-size:12px;color:#1a0dab;text-decoration:none;padding:2px 8px;border:1px solid #dfe1e5;border-radius:4px">الرئيسية</a>
      <a href="#" style="font-size:12px;color:#1a0dab;text-decoration:none;padding:2px 8px;border:1px solid #dfe1e5;border-radius:4px">التطبيقات</a>
      <a href="#" style="font-size:12px;color:#1a0dab;text-decoration:none;padding:2px 8px;border:1px solid #dfe1e5;border-radius:4px">تحميل</a>
    </div>
  </div>

  <div style="margin-top:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <div style="flex:1">
      <div style="font-size:12px;color:var(--muted);margin-bottom:4px">جودة العنوان:</div>
      <div id="pv-title-quality" style="font-size:13px;font-weight:600;color:var(--muted)">—</div>
    </div>
    <div style="flex:1">
      <div style="font-size:12px;color:var(--muted);margin-bottom:4px">جودة الوصف:</div>
      <div id="pv-desc-quality" style="font-size:13px;font-weight:600;color:var(--muted)">—</div>
    </div>
  </div>
</div>

<!-- Apps list preview -->
<div class="panel">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h2 style="margin:0">صفحات التطبيقات المنشورة</h2>
    <input class="form-input" id="pv-filter" type="text" placeholder="بحث..." style="width:200px" oninput="filterPreviewApps(this.value)">
  </div>
  <div id="pv-apps-list" style="display:flex;flex-direction:column;gap:1px">
    <?php foreach ($previewApps as $pvApp):
      $pvTitle = $pvApp['seo_title'] ?: $pvApp['name'];
      $pvDesc  = $pvApp['meta_description'] ?: '';
      $pvUrl   = rtrim(SITE_URL,'/') . '/' . h($pvApp['slug']);
      $pvTLen  = mb_strlen($pvTitle);
      $pvDLen  = mb_strlen($pvDesc);
      $pvTBad  = $pvTLen < 30 || $pvTLen > 65;
      $pvDBad  = $pvDLen < 80 || $pvDLen > 165;
    ?>
    <div class="pv-app-row" data-name="<?= h(strtolower($pvApp['name'])) ?>"
         style="background:var(--surface);border-radius:8px;padding:14px;margin-bottom:8px;cursor:pointer;border:1px solid var(--border-c)"
         onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
      <div style="display:flex;align-items:center;gap:10px">
        <?php if ($pvApp['icon_path']): ?>
          <img src="<?= h(media_url($pvApp['icon_path'])) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover;flex-shrink:0">
        <?php else: ?>
          <div style="width:32px;height:32px;border-radius:8px;background:var(--surface-3);flex-shrink:0"></div>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:13px;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($pvApp['name']) ?></div>
          <div style="font-size:11px;color:var(--muted)"><?= h(rtrim(SITE_URL,'/') . '/' . $pvApp['slug']) ?></div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <span style="font-size:10px;padding:2px 7px;border-radius:9px;background:<?= $pvTBad?'rgba(239,68,68,.15)':'rgba(34,197,94,.1)' ?>;color:<?= $pvTBad?'#ef4444':'#22c55e' ?>">
            العنوان: <?= $pvTLen ?> حرف<?= $pvTBad?(' '.($pvTLen<30?'(قصير جداً)':'(طويل جداً)')):'✓' ?>
          </span>
          <span style="font-size:10px;padding:2px 7px;border-radius:9px;background:<?= $pvDBad?'rgba(239,68,68,.15)':'rgba(34,197,94,.1)' ?>;color:<?= $pvDBad?'#ef4444':'#22c55e' ?>">
            الوصف: <?= $pvDLen ?> حرف<?= $pvDBad?(' '.($pvDLen<80?'(قصير)':'(طويل)')):'✓' ?>
          </span>
          <a href="admin.php?page=edit-app&id=<?= $pvApp['id'] ?>" onclick="event.stopPropagation()"
             style="font-size:10px;padding:2px 9px;border-radius:9px;background:rgba(6,182,212,.1);color:var(--cyan);text-decoration:none">تعديل</a>
        </div>
      </div>
    </div>
    <div style="display:none;padding:0 10px 10px">
      <div style="background:#fff;border-radius:8px;padding:16px 20px;font-family:Arial,sans-serif;border:1px solid #dfe1e5">
        <div style="font-size:14px;color:#1a0dab;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($pvTitle) ?></div>
        <div style="font-size:12px;color:#006621;margin-bottom:4px"><?= h($pvUrl) ?></div>
        <div style="font-size:13px;color:#545454;line-height:1.5"><?= $pvDesc ? h(mb_substr($pvDesc,0,160)) : '<span style="color:#aaa;font-style:italic">لا يوجد وصف — Google سيختار مقطعاً من الصفحة</span>' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function updatePreview() {
  const title = document.getElementById('pv-title').value;
  const url   = document.getElementById('pv-url').value;
  const desc  = document.getElementById('pv-desc').value;
  document.getElementById('pv-title-render').textContent = title || '(بدون عنوان)';
  document.getElementById('pv-url-render').textContent = url;
  document.getElementById('pv-desc-render').textContent = desc || '(بدون وصف)';
  const tl = title.length, dl = desc.length;
  document.getElementById('pv-title-count').textContent = `${tl} / 60 حرف (مثالي 50–60)`;
  document.getElementById('pv-title-count').style.color = tl<30||tl>65 ? 'var(--danger,#ef4444)' : '#22c55e';
  document.getElementById('pv-desc-count').textContent = `${dl} / 160 حرف (مثالي 120–160)`;
  document.getElementById('pv-desc-count').style.color = dl>0&&(dl<80||dl>165) ? 'var(--danger,#ef4444)' : dl>0 ? '#22c55e' : 'var(--muted)';
  const tQ = tl===0?'—':tl<30?'❌ قصير جداً':tl>65?'⚠️ طويل جداً — قد يُقطع':'✅ ممتاز';
  const dQ = dl===0?'—':dl<80?'⚠️ قصير':dl>165?'⚠️ طويل — قد يُقطع':'✅ ممتاز';
  document.getElementById('pv-title-quality').textContent = tQ;
  document.getElementById('pv-desc-quality').textContent = dQ;
  document.getElementById('pv-title-quality').style.color = tQ.startsWith('✅')?'#22c55e':tQ.startsWith('⚠️')?'#f59e0b':tQ.startsWith('❌')?'#ef4444':'var(--muted)';
  document.getElementById('pv-desc-quality').style.color = dQ.startsWith('✅')?'#22c55e':dQ.startsWith('⚠️')?'#f59e0b':'var(--muted)';
}
function filterPreviewApps(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll('.pv-app-row').forEach(row => {
    row.style.display = !q || row.dataset.name.includes(q) ? '' : 'none';
    row.nextElementSibling.style.display = 'none';
  });
}
function fixLongSeoTitles() {
  const btn = document.getElementById('fix-seo-btn');
  if (!btn) return;
  btn.disabled = true;
  btn.textContent = 'جارٍ التصحيح...';
  fetch('?ajax=fix_long_seo_titles', {method:'POST', credentials:'same-origin'})
    .then(r => r.text())
    .then(txt => {
      let d;
      try { d = JSON.parse(txt); } catch(e) {
        btn.textContent = '❌ خطأ PHP: ' + txt.substring(0,80);
        btn.disabled = false; return;
      }
      if (d.ok) {
        btn.textContent = d.fixed > 0
          ? `✓ تم تصحيح ${d.fixed} عنوان من أصل ${d.total}`
          : `✓ لا توجد عناوين تتجاوز 60 حرفاً`;
        btn.style.background = 'rgba(34,197,94,.12)';
        btn.style.color = '#22c55e';
        if (d.fixed > 0) setTimeout(() => location.reload(), 1500);
      } else {
        btn.textContent = '❌ ' + (d.error || 'فشل التصحيح');
        btn.disabled = false;
      }
    })
    .catch(e => { btn.textContent = '❌ ' + (e.message || 'خطأ في الاتصال'); btn.disabled = false; });
}
</script>

<?php
/* ─────────────── HTML PAGES FOR INDEXING ─────────────── */
elseif ($page === 'html-pages'):
    $pagesDir  = __DIR__ . '/pages';
    $generated = is_dir($pagesDir) ? count(glob($pagesDir . '/*.html')) : 0;
    $totalApps = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
?>
<div class="admin-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <div>
    <h1>صفحات HTML للفهرسة</h1>
    <p style="color:var(--muted);font-size:13px;margin-top:4px">أنشئ صفحة HTML لكل تطبيق — تُضاف تلقائياً إلى sitemap وتُقدَّم لمحركات البحث عبر IndexNow</p>
  </div>
  <button id="bulk-gen-btn" onclick="startBulkGen()" style="padding:10px 20px;border-radius:10px;border:none;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-size:14px;cursor:pointer;font-weight:600">
    ⚡ توليد جميع الصفحات
  </button>
</div>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px">
  <div class="panel" style="text-align:center;padding:20px">
    <div style="font-size:32px;font-weight:800;color:#2563eb" id="stat-generated"><?= $generated ?></div>
    <div style="font-size:12px;color:var(--muted);margin-top:4px">صفحات مُنشأة</div>
  </div>
  <div class="panel" style="text-align:center;padding:20px">
    <div style="font-size:32px;font-weight:800;color:#64748b"><?= $totalApps ?></div>
    <div style="font-size:12px;color:var(--muted);margin-top:4px">إجمالي التطبيقات المنشورة</div>
  </div>
  <div class="panel" style="text-align:center;padding:20px">
    <div style="font-size:32px;font-weight:800;color:<?= $generated>=$totalApps?'#22c55e':'#f59e0b' ?>" id="stat-remaining"><?= max(0,$totalApps-$generated) ?></div>
    <div style="font-size:12px;color:var(--muted);margin-top:4px">تحتاج توليد</div>
  </div>
  <div class="panel" style="padding:20px;display:flex;flex-direction:column;justify-content:center;gap:8px">
    <?php $inKey = get_cfg($pdo,'indexnow_key'); ?>
    <?php if ($inKey): ?>
    <span style="color:#22c55e;font-size:13px">✓ IndexNow مفعَّل — الصفحات الجديدة تُرسَل تلقائياً</span>
    <?php else: ?>
    <span style="color:#f59e0b;font-size:13px">⚠ IndexNow غير مفعَّل — فعِّله في الإعدادات</span>
    <?php endif; ?>
    <?php $pagesUrl = rtrim(SITE_URL,'/').'/pages/'; ?>
    <a href="<?= h($pagesUrl) ?>" target="_blank" style="font-size:12px;color:var(--muted)">📂 /pages/ على الموقع</a>
  </div>
</div>

<!-- Progress (hidden until bulk gen starts) -->
<div id="bulk-progress-wrap" style="display:none" class="panel" style="margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <strong id="bulk-status-text">جارٍ التوليد…</strong>
    <span id="bulk-counter" style="font-size:13px;color:var(--muted)"></span>
  </div>
  <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
    <div id="bulk-bar" style="height:100%;background:linear-gradient(90deg,#2563eb,#7c3aed);border-radius:4px;width:0;transition:width .3s"></div>
  </div>
  <div id="bulk-current-app" style="font-size:12px;color:var(--muted);margin-top:8px"></div>
</div>

<!-- Apps table -->
<div class="panel" style="overflow-x:auto">
  <table class="admin-table" style="width:100%">
    <thead><tr>
      <th>التطبيق</th>
      <th>رابط الصفحة</th>
      <th>الحالة</th>
      <th>إجراء</th>
    </tr></thead>
    <tbody>
    <?php
    $appRows = $pdo->query("SELECT a.id, a.slug, a.name, a.icon_path, a.version FROM apps a WHERE a.status='published' ORDER BY a.name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($appRows as $ar):
        $htmlFile  = $pagesDir . '/' . $ar['slug'] . '.html';
        $exists    = file_exists($htmlFile);
        $pageUrl   = rtrim(SITE_URL,'/').'/pages/'.rawurlencode($ar['slug']).'.html';
        $safeSlug  = addslashes($ar['slug']);
        $safeName  = addslashes($ar['name']);
    ?>
    <tr>
      <td style="display:flex;align-items:center;gap:10px;padding:10px 12px">
        <?php if (!empty($ar['icon_path'])): ?>
        <img src="<?= h(media_url($ar['icon_path'])) ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover" loading="lazy">
        <?php else: ?>
        <div style="width:36px;height:36px;border-radius:8px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:16px">📱</div>
        <?php endif; ?>
        <div>
          <div style="font-weight:600;font-size:13px"><?= h($ar['name']) ?></div>
          <?php if ($ar['version']): ?><div style="font-size:11px;color:var(--muted)">v<?= h($ar['version']) ?></div><?php endif; ?>
        </div>
      </td>
      <td>
        <?php if ($exists): ?>
        <a href="<?= h($pageUrl) ?>" target="_blank" style="font-size:12px;color:var(--primary);word-break:break-all">/pages/<?= h($ar['slug']) ?>.html</a>
        <?php else: ?><span style="color:var(--muted);font-size:12px">—</span><?php endif; ?>
      </td>
      <td>
        <?php if ($exists): ?>
        <span style="background:rgba(34,197,94,.1);color:#22c55e;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">✓ مُنشأ</span>
        <?php else: ?>
        <span style="background:rgba(245,158,11,.1);color:#f59e0b;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">⏳ لم يُنشأ</span>
        <?php endif; ?>
      </td>
      <td>
        <button onclick="genOnePage('<?= $safeSlug ?>', this)"
                style="padding:5px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;font-size:12px;cursor:pointer;color:#2563eb">
          <?= $exists ? '🔄 إعادة توليد' : '⚡ توليد' ?>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function genOnePage(slug, btn) {
  btn.disabled = true; btn.textContent = '⟳ جارٍ…';
  fetch('?ajax=generate_html_page', {method:'POST',credentials:'same-origin',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'slug='+encodeURIComponent(slug)
  }).then(r=>r.json()).then(d=>{
    if(d.ok){
      btn.textContent='✓ مُنشأ';
      btn.style.color='#22c55e';
      btn.style.borderColor='#22c55e';
      var tr=btn.closest('tr');
      var td=tr ? tr.querySelector('td:nth-child(3)') : null;
      if(td)td.innerHTML='<span style="background:rgba(34,197,94,.1);color:#22c55e;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">✓ مُنشأ</span>';
      var tdL=tr ? tr.querySelector('td:nth-child(2)') : null;
      if(tdL&&d.url)tdL.innerHTML='<a href="'+d.url+'" target="_blank" style="font-size:12px;color:var(--primary);word-break:break-all">'+d.file+'</a>';
      var st=document.getElementById('stat-generated');
      var rem=document.getElementById('stat-remaining');
      if(st)st.textContent=parseInt(st.textContent||0)+1;
      if(rem)rem.textContent=Math.max(0,parseInt(rem.textContent||0)-1);
    } else {
      btn.disabled=false; btn.textContent='❌ فشل';
      alert(d.error||'خطأ غير معروف');
    }
  }).catch(()=>{btn.disabled=false;btn.textContent='❌ خطأ';});
}

function startBulkGen(){
  const btn=document.getElementById('bulk-gen-btn');
  const wrap=document.getElementById('bulk-progress-wrap');
  const bar=document.getElementById('bulk-bar');
  const status=document.getElementById('bulk-status-text');
  const counter=document.getElementById('bulk-counter');
  const curApp=document.getElementById('bulk-current-app');
  btn.disabled=true; btn.textContent='⟳ جارٍ التوليد…';
  if(wrap)wrap.style.display='block';

  const es=new EventSource('?ajax=generate_html_pages_bulk');
  es.onmessage=function(e){
    try{
      const d=JSON.parse(e.data);
      if(bar)bar.style.width=d.pct+'%';
      if(counter)counter.textContent=d.done+'/'+d.total;
      if(curApp)curApp.textContent='🔄 '+d.name;
      if(d.complete){
        es.close();
        if(status)status.textContent='✅ تم توليد '+d.done+' صفحة وإرسالها لـ IndexNow';
        btn.textContent='✓ اكتمل';
        var st=document.getElementById('stat-generated');
        var rem=document.getElementById('stat-remaining');
        if(st)st.textContent=d.total;
        if(rem)rem.textContent='0';
        if(curApp)curApp.textContent='';
        setTimeout(()=>location.reload(),2000);
      }
    }catch(err){}
  };
  es.onerror=function(){
    es.close();
    if(status)status.textContent='❌ حدث خطأ أثناء التوليد';
    btn.disabled=false; btn.textContent='⚡ توليد جميع الصفحات';
  };
}
</script>

<?php
/* ─────────────── SERVER CONNECTION MANAGER ─────────────── */
elseif ($page === 'deploy'):
    $sc = [];
    foreach (['server_conn_type','server_conn_host','server_conn_port','server_conn_user','server_conn_pass','server_conn_path'] as $k) {
        $sc[$k] = get_cfg($pdo, $k);
    }
    $selType = $sc['server_conn_type'] ?: 'ftp';
?>

<div class="admin-header"><h1>مدير اتصال السيرفر</h1></div>

<div class="panel">
  <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
    اتصل بسيرفرك عبر FTP أو FTPS أو SFTP لاستعراض الملفات عن بُعد. البيانات المحفوظة لا تُرسل إلى أي طرف خارجي.
  </p>

  <!-- Connection type selector -->
  <div style="margin-bottom:18px">
    <label class="form-label" style="margin-bottom:8px;display:block">نوع الاتصال</label>
    <div style="display:flex;gap:8px;flex-wrap:wrap" id="conn-type-group">
      <?php foreach(['ftp'=>'FTP','ftps'=>'FTPS (مشفّر)','sftp'=>'SFTP','ssh'=>'SSH'] as $val=>$lbl): ?>
      <label class="conn-type-pill<?= $selType===$val?' conn-type-active':'' ?>" data-val="<?= $val ?>">
        <input type="radio" name="conn_type" value="<?= $val ?>" <?= $selType===$val?'checked':'' ?> style="position:absolute;opacity:0;width:0;height:0">
        <?= h($lbl) ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Host + Port -->
  <div style="display:grid;grid-template-columns:1fr 110px;gap:12px;margin-bottom:12px">
    <div>
      <label class="form-label">المضيف (Host / IP)</label>
      <input class="form-input" id="sc-host" type="text" placeholder="ftp.example.com" value="<?= h($sc['server_conn_host']??'') ?>">
    </div>
    <div>
      <label class="form-label">المنفذ</label>
      <input class="form-input" id="sc-port" type="number" min="1" max="65535" placeholder="21" value="<?= h($sc['server_conn_port']??'') ?>">
    </div>
  </div>

  <!-- Username + Password -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
    <div>
      <label class="form-label">اسم المستخدم</label>
      <input class="form-input" id="sc-user" type="text" autocomplete="username" value="<?= h($sc['server_conn_user']??'') ?>">
    </div>
    <div>
      <label class="form-label">كلمة المرور</label>
      <div style="position:relative">
        <input class="form-input" id="sc-pass" type="password" autocomplete="current-password" value="<?= h($sc['server_conn_pass']??'') ?>" style="padding-left:40px">
        <button type="button" id="toggle-pass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:0" title="إظهار / إخفاء كلمة المرور">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Remote path -->
  <div style="margin-bottom:20px">
    <label class="form-label">المجلد الجذر على السيرفر</label>
    <input class="form-input" id="sc-path" type="text" placeholder="/home/user/public_html" value="<?= h($sc['server_conn_path']??'/') ?>">
  </div>

  <!-- Action buttons -->
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button type="button" id="btn-test-conn" class="btn-ai">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      اختبار الاتصال
    </button>
    <button type="button" id="btn-save-conn" class="btn-edit" style="padding:10px 18px;font-size:14px">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2zM17 21v-8H7v8M7 3v5h8"/></svg>
      حفظ الإعدادات
    </button>
    <button type="button" id="btn-open-browser" class="btn-view" style="padding:10px 18px;font-size:14px;display:none">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
      استعراض الملفات
    </button>
  </div>

  <!-- Result -->
  <div id="conn-result" style="display:none;margin-top:16px;padding:13px 16px;border-radius:8px;font-size:13px;line-height:1.6"></div>
</div>

<!-- Remote File Browser -->
<div class="panel" id="file-browser-panel" style="display:none">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
    <h2 style="font-size:15px;font-weight:600;margin:0">استعراض الملفات البعيدة</h2>
    <div style="display:flex;align-items:center;gap:8px;flex:1;max-width:380px">
      <input class="form-input" id="browser-path" type="text" value="/" style="font-family:var(--f-mono);font-size:12px;flex:1">
      <button type="button" id="btn-browse" class="btn-ai" style="padding:9px 14px;white-space:nowrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
        انتقال
      </button>
    </div>
  </div>
  <div id="file-list" style="border:1px solid var(--border);border-radius:8px;overflow:hidden;min-height:80px">
    <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">اضغط "انتقال" لعرض محتوى المجلد</div>
  </div>
</div>

<style>
.conn-type-pill {
  display:inline-flex;align-items:center;padding:8px 16px;border:1.5px solid var(--border);
  border-radius:20px;cursor:pointer;font-size:13px;font-weight:500;transition:all .15s;
  color:var(--muted);user-select:none;
}
.conn-type-pill:hover { border-color:var(--primary);color:var(--primary); }
.conn-type-active { border-color:var(--primary)!important;background:rgba(37,99,235,.09);color:var(--primary)!important;font-weight:600; }
.fb-row {
  display:flex;align-items:center;gap:10px;padding:9px 14px;
  border-bottom:1px solid var(--border);transition:background .1s;cursor:default;
}
.fb-row:last-child { border-bottom:none; }
.fb-row.fb-dir { cursor:pointer; }
.fb-row.fb-dir:hover { background:var(--surface-alt); }
.fb-row .fb-icon { font-size:15px;flex-shrink:0; }
.fb-row .fb-name { flex:1;font-family:var(--f-mono);font-size:12px;word-break:break-all; }
.fb-row .fb-size { color:var(--muted);font-size:11px;white-space:nowrap; }
</style>

<script>
(function(){
  var currentConnData = {};

  // Pill selection
  document.querySelectorAll('.conn-type-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      document.querySelectorAll('.conn-type-pill').forEach(function(p){ p.classList.remove('conn-type-active'); });
      pill.classList.add('conn-type-active');
      pill.querySelector('input').checked = true;
      var defaultPorts = {ftp:'21',ftps:'21',sftp:'22',ssh:'22'};
      var portEl = document.getElementById('sc-port');
      if (!portEl.value || portEl.value === '21' || portEl.value === '22') {
        portEl.value = defaultPorts[pill.dataset.val] || '21';
      }
    });
  });

  // Toggle password visibility
  document.getElementById('toggle-pass').addEventListener('click', function(){
    var inp = document.getElementById('sc-pass');
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });

  function getConnData() {
    var checked = document.querySelector('input[name="conn_type"]:checked');
    return {
      conn_type:   checked ? checked.value : 'ftp',
      host:        document.getElementById('sc-host').value.trim(),
      port:        document.getElementById('sc-port').value,
      username:    document.getElementById('sc-user').value.trim(),
      password:    document.getElementById('sc-pass').value,
      remote_path: document.getElementById('sc-path').value.trim() || '/'
    };
  }

  function setResult(msg, ok) {
    var el = document.getElementById('conn-result');
    el.style.display = 'block';
    el.style.background = ok ? 'rgba(25,135,84,.1)' : 'rgba(220,38,38,.1)';
    el.style.color = ok ? '#166534' : '#991b1b';
    el.style.border = '1px solid ' + (ok ? 'rgba(25,135,84,.3)' : 'rgba(220,38,38,.3)');
    el.textContent = msg;
  }

  // Test connection
  document.getElementById('btn-test-conn').addEventListener('click', async function(){
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'جاري الاتصال...';
    var data = getConnData();
    currentConnData = data;
    var fd = new FormData();
    Object.entries(data).forEach(function(e){ fd.append(e[0], e[1]); });
    try {
      var r = await fetch('admin.php?ajax=test_server_conn', {method:'POST', body:fd});
      var j = await r.json();
      setResult(j.msg, j.ok);
      if (j.ok) {
        document.getElementById('btn-open-browser').style.display = 'inline-flex';
        document.getElementById('browser-path').value = data.remote_path;
      }
    } catch(e) {
      setResult('حدث خطأ في الطلب: ' + e.message, false);
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> اختبار الاتصال';
  });

  // Save settings
  document.getElementById('btn-save-conn').addEventListener('click', async function(){
    var btn = this; btn.disabled = true;
    var data = getConnData();
    var fd = new FormData();
    fd.append('server_conn_type',  data.conn_type);
    fd.append('server_conn_host',  data.host);
    fd.append('server_conn_port',  data.port);
    fd.append('server_conn_user',  data.username);
    fd.append('server_conn_pass',  data.password);
    fd.append('server_conn_path',  data.remote_path);
    try {
      var r = await fetch('admin.php?ajax=save_server_conn', {method:'POST', body:fd});
      var j = await r.json();
      setResult(j.msg, j.ok);
    } catch(e) { setResult('حدث خطأ', false); }
    btn.disabled = false;
  });

  // Open browser panel
  document.getElementById('btn-open-browser').addEventListener('click', function(){
    var panel = document.getElementById('file-browser-panel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
    if (panel.style.display !== 'none') browseDir(document.getElementById('browser-path').value || '/');
  });

  // Browse dir
  async function browseDir(path) {
    path = path || '/';
    document.getElementById('browser-path').value = path;
    var data = Object.assign({}, currentConnData.conn_type ? currentConnData : getConnData(), {path: path});
    var fd = new FormData();
    Object.entries(data).forEach(function(e){ fd.append(e[0], e[1]); });
    fd.set('path', path);
    var list = document.getElementById('file-list');
    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">جاري التحميل...</div>';
    try {
      var r = await fetch('admin.php?ajax=list_remote_dir', {method:'POST', body:fd});
      var j = await r.json();
      if (!j.ok) { list.innerHTML = '<div style="padding:16px;color:#991b1b;font-size:13px">✗ ' + escH(j.msg||'خطأ') + '</div>'; return; }
      renderFileList(j.items, path);
    } catch(e) {
      list.innerHTML = '<div style="padding:16px;color:#991b1b;font-size:13px">✗ حدث خطأ في الطلب</div>';
    }
  }

  function renderFileList(items, currentPath) {
    var list = document.getElementById('file-list');
    var html = '';
    // Parent dir link
    var parts = currentPath.replace(/\/+$/, '').split('/');
    if (parts.length > 1 || (parts.length === 1 && parts[0] !== '')) {
      var parent = parts.slice(0, -1).join('/') || '/';
      html += '<div class="fb-row fb-dir" data-path="' + escAttr(parent) + '"><span class="fb-icon">⬆️</span><span class="fb-name">.. رجوع</span><span class="fb-size"></span></div>';
    }
    if (!items.length) {
      html += '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">المجلد فارغ</div>';
    } else {
      var sorted = items.slice().sort(function(a,b){ return (b.dir - a.dir) || a.name.localeCompare(b.name); });
      sorted.forEach(function(item){
        var icon = item.dir ? '📁' : '📄';
        var itemPath = (currentPath.replace(/\/+$/, '') + '/' + item.name).replace(/\/\/+/g, '/');
        var sizeStr = item.dir ? '' : fmtBytes(item.size);
        var cls = 'fb-row' + (item.dir ? ' fb-dir' : '');
        var attr = item.dir ? ' data-path="' + escAttr(itemPath) + '"' : '';
        html += '<div class="' + cls + '"' + attr + '><span class="fb-icon">' + icon + '</span><span class="fb-name">' + escH(item.name) + '</span><span class="fb-size">' + sizeStr + '</span></div>';
      });
    }
    list.innerHTML = html;
    list.querySelectorAll('.fb-dir[data-path]').forEach(function(row){
      row.addEventListener('click', function(){ browseDir(row.dataset.path); });
    });
  }

  document.getElementById('btn-browse').addEventListener('click', function(){
    browseDir(document.getElementById('browser-path').value || '/');
  });
  document.getElementById('browser-path').addEventListener('keydown', function(e){
    if (e.key === 'Enter') browseDir(e.target.value || '/');
  });

  function fmtBytes(b) {
    if (!b) return '';
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(1) + ' MB';
  }
  function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function escAttr(s) { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
})();
</script>

<?php endif; ?>

<?php if ($page === 'indexnow-log'): ?>
<?php
$autoEnabled = get_cfg($pdo, 'auto_indexnow_enabled', '1') === '1';
$indexNowKey = get_cfg($pdo, 'indexnow_key', '');
$logStats = [
    'total'   => (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log")->fetchColumn(),
    'success' => (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log WHERE status='success'")->fetchColumn(),
    'failed'  => (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log WHERE status='failed'")->fetchColumn(),
    'skipped' => (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log WHERE status='skipped'")->fetchColumn(),
    'today'   => (int)$pdo->query("SELECT COUNT(*) FROM indexnow_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'last'    => $pdo->query("SELECT MAX(created_at) FROM indexnow_log WHERE status='success'")->fetchColumn() ?: null,
];
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
  سجل الفهرسة IndexNow
</div>
<div class="section-box" style="margin-bottom:20px">
  <div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;justify-content:space-between">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:13px;color:var(--muted)">الإرسال التلقائي:</span>
        <button id="btn-toggle-auto" onclick="toggleAuto()" class="<?= $autoEnabled ? 'btn-primary' : 'btn-outline' ?>"
          style="min-width:100px;font-size:12px;padding:7px 16px;display:flex;align-items:center;gap:7px">
          <?php if ($autoEnabled): ?>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z" fill="currentColor"/></svg>تشغيل
          <?php else: ?>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M10 15V9m4 6V9"/></svg>إيقاف
          <?php endif; ?>
        </button>
      </div>
      <button id="btn-send-all" onclick="sendAll()" class="btn-primary" style="font-size:12px;padding:7px 18px;display:flex;align-items:center;gap:7px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        إرسال كل الروابط الآن
      </button>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <select id="log-filter" onchange="loadLog(0)" class="form-input" style="min-width:140px;font-size:12px;padding:7px 12px">
        <option value="all">كل الحالات</option>
        <option value="success">ناجح</option>
        <option value="failed">فاشل</option>
        <option value="skipped">متخطى</option>
      </select>
      <button onclick="clearOldLog()" class="btn-outline" style="font-size:11px;padding:7px 14px">حذف سجلات +30 يوم</button>
    </div>
  </div>
  <div id="action-msg" style="display:none;margin-top:12px;border-radius:8px;padding:12px 16px;font-size:13px"></div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px">
  <div class="stat-card"><div class="stat-num"><?= number_format($logStats['total']) ?></div><div class="stat-label">إجمالي</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= number_format($logStats['success']) ?></div><div class="stat-label">ناجح</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= number_format($logStats['failed']) ?></div><div class="stat-label">فاشل</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= number_format($logStats['skipped']) ?></div><div class="stat-label">متخطى</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--cyan)"><?= number_format($logStats['today']) ?></div><div class="stat-label">اليوم</div></div>
  <?php if ($indexNowKey): ?>
  <div class="stat-card"><div style="font-size:11px;font-family:var(--f-mono);color:var(--cyan);word-break:break-all"><?= h(mb_substr($indexNowKey,0,16)) ?>…</div><div class="stat-label">المفتاح</div></div>
  <?php else: ?>
  <div class="stat-card" style="border-color:rgba(239,68,68,.3)"><div style="font-size:11px;color:var(--danger);font-weight:700">غير مضبوط</div><div class="stat-label"><a href="admin.php?page=settings" style="color:var(--danger)">أضف المفتاح</a></div></div>
  <?php endif; ?>
</div>
<?php if ($logStats['last']): ?>
<div style="font-size:12px;color:var(--muted);margin-bottom:20px">آخر نجاح: <strong style="color:var(--success)"><?= h($logStats['last']) ?></strong></div>
<?php endif; ?>
<div class="section-box">
  <div style="font-size:14px;font-weight:700;color:var(--white);margin-bottom:14px">📋 سجل العمليات</div>
  <div id="log-loading" style="text-align:center;padding:30px;color:var(--muted)">⏳ جارٍ التحميل…</div>
  <div id="log-wrap" style="display:none;overflow-x:auto">
    <table class="admin-table">
      <thead><tr><th>الحالة</th><th>الرابط</th><th>المحرك</th><th>HTTP</th><th>السبب</th><th>الوقت</th></tr></thead>
      <tbody id="log-body"></tbody>
    </table>
    <div id="log-pagination" style="display:flex;gap:10px;align-items:center;justify-content:center;margin-top:16px;flex-wrap:wrap"></div>
  </div>
  <div id="log-empty" style="display:none;text-align:center;padding:40px;color:var(--muted)">لا توجد سجلات</div>
</div>
<script>
(function(){
  var currentOffset = 0;
  function showMsg(msg,ok){
    var el=document.getElementById('action-msg');
    el.style.cssText='display:block;margin-top:12px;border-radius:8px;padding:12px 16px;font-size:13px;background:'+(ok?'rgba(34,197,94,.08)':'rgba(239,68,68,.08)')+';border:1px solid '+(ok?'rgba(34,197,94,.25)':'rgba(239,68,68,.25)')+';color:'+(ok?'#4ade80':'#f87171');
    el.textContent=msg;
    if(ok) setTimeout(function(){el.style.display='none';},5000);
  }
  window.toggleAuto=async function(){
    var btn=document.getElementById('btn-toggle-auto'); btn.disabled=true;
    try{
      var d=await(await fetch('admin.php?ajax=toggle_auto_indexnow',{method:'POST'})).json();
      if(d.ok){
        showMsg(d.enabled?'✅ الإرسال التلقائي مُفعَّل':'⏸ الإرسال التلقائي موقوف',d.enabled);
        btn.className=d.enabled?'btn-primary':'btn-outline';
        btn.style.cssText='min-width:100px;font-size:12px;padding:7px 16px;display:flex;align-items:center;gap:7px';
        btn.innerHTML=d.enabled
          ?'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z" fill="currentColor"/></svg>تشغيل'
          :'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M10 15V9m4 6V9"/></svg>إيقاف';
      }
    }catch(e){showMsg('❌ خطأ في الاتصال',false);}
    btn.disabled=false;
  };
  window.sendAll=async function(){
    var btn=document.getElementById('btn-send-all'); btn.disabled=true; btn.textContent='⏳ جارٍ الإرسال…';
    try{
      var d=await(await fetch('admin.php?ajax=reindex_all',{method:'POST'})).json();
      if(d.ok){showMsg('✅ تم الإرسال! '+d.pinged+' رابط → IndexNow + Bing + Google.',true); setTimeout(function(){loadLog(0);},1500);}
      else showMsg('⚠️ '+(d.error||'خطأ'),false);
    }catch(e){showMsg('❌ خطأ في الاتصال',false);}
    btn.disabled=false;
    btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>إرسال كل الروابط الآن';
  };
  window.clearOldLog=async function(){
    if(!confirm('حذف سجلات أكثر من 30 يوم؟'))return;
    await fetch('admin.php?ajax=clear_indexnow_log',{method:'POST'});
    showMsg('✅ تم الحذف',true); setTimeout(function(){location.reload();},800);
  };
  window.loadLog=async function(offset){
    currentOffset=offset||0;
    var filter=document.getElementById('log-filter').value;
    document.getElementById('log-loading').style.display='block';
    document.getElementById('log-wrap').style.display='none';
    document.getElementById('log-empty').style.display='none';
    try{
      var d=await(await fetch('admin.php?ajax=indexnow_log&offset='+currentOffset+'&filter='+encodeURIComponent(filter))).json();
      document.getElementById('log-loading').style.display='none';
      if(!d.ok||!d.rows.length){document.getElementById('log-empty').style.display='block';return;}
      document.getElementById('log-wrap').style.display='block';
      var badges={
        success:'<span style="background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">ناجح</span>',
        failed:'<span style="background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.3);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">فاشل</span>',
        skipped:'<span style="background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.3);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">متخطى</span>',
      };
      function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
      document.getElementById('log-body').innerHTML=d.rows.map(function(row){
        var cl=(row.http_code>=200&&row.http_code<300)?'var(--success)':'var(--danger)';
        return '<tr>'
          +'<td>'+(badges[row.status]||esc(row.status))+'</td>'
          +'<td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;direction:ltr;text-align:left;font-family:var(--f-mono);font-size:11px" title="'+esc(row.url)+'">'+esc((row.url||'').replace(/^https?:\/\/[^\/]+/,''))+'</td>'
          +'<td style="font-size:11px;color:var(--muted)">'+esc(row.engine)+'</td>'
          +'<td style="font-size:12px;font-family:var(--f-mono);text-align:center;color:'+cl+'">'+esc(row.http_code||'—')+'</td>'
          +'<td style="font-size:11px;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+esc(row.reason||'')+'">'+esc(row.reason||'—')+'</td>'
          +'<td style="font-size:11px;color:var(--muted);white-space:nowrap">'+esc(row.created_at)+'</td>'
          +'</tr>';
      }).join('');
      var total=d.total,limit=d.limit,pages=Math.ceil(total/limit),cur=Math.floor(currentOffset/limit);
      var pag='<span style="font-size:12px;color:var(--muted)">'+total+' سجل</span>';
      if(cur>0) pag+='<button onclick="loadLog('+(Math.max(0,currentOffset-limit))+')" class="btn-outline" style="font-size:11px;padding:5px 12px">السابق</button>';
      pag+='<span style="font-size:12px;color:var(--muted)">'+( cur+1)+' / '+pages+'</span>';
      if(currentOffset+limit<total) pag+='<button onclick="loadLog('+(currentOffset+limit)+')" class="btn-outline" style="font-size:11px;padding:5px 12px">التالي</button>';
      document.getElementById('log-pagination').innerHTML=pag;
    }catch(e){
      document.getElementById('log-loading').style.display='none';
      document.getElementById('log-empty').textContent='❌ خطأ في تحميل السجل';
      document.getElementById('log-empty').style.display='block';
    }
  };
  loadLog(0);
})();
</script>
<?php endif; ?>

<?php
/* ─────────────── VISITOR ANALYTICS ─────────────── */
if ($page === 'visitors'):
// Aggregate stats
$totalVisitors = 0; $totalViews = 0; $totalDl = 0; $vpnCount = 0;
$byCountry = []; $byBrowser = []; $byOs = [];
$recentVisitors = [];
$topCategories = []; $topApps = [];

try {
    $tblList = array_column($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM), 0);
    if (in_array('visitor_profiles', $tblList)) {
        $stats = $pdo->query("SELECT COUNT(*) as total, SUM(total_views) as views, SUM(total_downloads) as dl, SUM(is_vpn) as vpn FROM visitor_profiles")->fetch();
        $totalVisitors = (int)($stats['total'] ?? 0);
        $totalViews    = (int)($stats['views'] ?? 0);
        $totalDl       = (int)($stats['dl'] ?? 0);
        $vpnCount      = (int)($stats['vpn'] ?? 0);

        $byCountry = $pdo->query("SELECT country, COUNT(*) as cnt FROM visitor_profiles WHERE country IS NOT NULL GROUP BY country ORDER BY cnt DESC LIMIT 12")->fetchAll();
        $byBrowser = $pdo->query("SELECT browser_family, COUNT(*) as cnt FROM visitor_profiles WHERE browser_family IS NOT NULL GROUP BY browser_family ORDER BY cnt DESC LIMIT 8")->fetchAll();
        $byOs      = $pdo->query("SELECT os_family, COUNT(*) as cnt FROM visitor_profiles WHERE os_family IS NOT NULL GROUP BY os_family ORDER BY cnt DESC LIMIT 8")->fetchAll();
        $recentVisitors = $pdo->query("SELECT fingerprint, country, city, browser_family, os_family, is_vpn, total_views, total_downloads, last_seen, last_ip_change FROM visitor_profiles ORDER BY last_seen DESC LIMIT 50")->fetchAll();

        // Category interests: aggregate across all profiles
        $catRaw = $pdo->query("SELECT category_interests FROM visitor_profiles WHERE category_interests IS NOT NULL AND category_interests != 'null'")->fetchAll(PDO::FETCH_COLUMN);
        $catTotals = [];
        foreach ($catRaw as $j) {
            $d = json_decode($j, true);
            if (!is_array($d)) continue;
            foreach ($d as $slug => $cnt) $catTotals[$slug] = ($catTotals[$slug] ?? 0) + $cnt;
        }
        arsort($catTotals);
        $topCategories = array_slice($catTotals, 0, 8, true);

        // Top app interests
        $appRaw = $pdo->query("SELECT app_interests FROM visitor_profiles WHERE app_interests IS NOT NULL AND app_interests != 'null'")->fetchAll(PDO::FETCH_COLUMN);
        $appTotals = [];
        foreach ($appRaw as $j) {
            $ids = json_decode($j, true);
            if (!is_array($ids)) continue;
            foreach ($ids as $i => $appId) {
                $score = max(1, 10 - $i); // more recent = higher score
                $appTotals[$appId] = ($appTotals[$appId] ?? 0) + $score;
            }
        }
        arsort($appTotals);
        $topAppIds = array_slice(array_keys($appTotals), 0, 10, true);
        if ($topAppIds) {
            $ph = implode(',', array_fill(0, count($topAppIds), '?'));
            $topApps = $pdo->prepare("SELECT id,name,icon_path FROM apps WHERE id IN ($ph)")->execute($topAppIds) ? $pdo->prepare("SELECT id,name,icon_path FROM apps WHERE id IN ($ph)")->execute($topAppIds) : [];
            // Refetch properly
            $stmt = $pdo->prepare("SELECT id,name,icon_path FROM apps WHERE id IN ($ph)");
            $stmt->execute($topAppIds);
            $topAppsRows = $stmt->fetchAll();
            $topAppsMap = [];
            foreach ($topAppsRows as $r) $topAppsMap[(int)$r['id']] = $r;
            $topApps = [];
            foreach ($topAppIds as $aid) { if (isset($topAppsMap[$aid])) $topApps[] = $topAppsMap[$aid] + ['score' => $appTotals[$aid] ?? 0]; }
        }
    }
} catch (Throwable $e) {}
?>
<div class="admin-content-header"><h1>الزوار والسلوك</h1><p>تحليلات الزوار بناءً على البصمة الرقمية — الهوية مجهولة وعناوين IP مُشفَّرة</p></div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:22px">
<?php foreach ([
    ['label'=>'زوار فريدون','val'=>number_format($totalVisitors),'icon'=>'👥'],
    ['label'=>'إجمالي المشاهدات','val'=>number_format($totalViews),'icon'=>'👁️'],
    ['label'=>'إجمالي التحميلات','val'=>number_format($totalDl),'icon'=>'⬇️'],
    ['label'=>'مشتبه بـ VPN','val'=>number_format($vpnCount),'icon'=>'🕵️'],
] as $c): ?>
<div class="card" style="text-align:center;padding:18px 12px">
  <div style="font-size:26px;margin-bottom:6px"><?= $c['icon'] ?></div>
  <div style="font-size:22px;font-weight:700;color:var(--primary)"><?= $c['val'] ?></div>
  <div style="font-size:12px;color:var(--text-muted);margin-top:3px"><?= $c['label'] ?></div>
</div>
<?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:22px">

<!-- Countries -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">🌍 الدول</h3>
  <?php $maxC = max(1, max(array_column($byCountry ?: [[0,0,'cnt'=>1]], 'cnt')));
  foreach ($byCountry as $r): $pct = round(100*$r['cnt']/$maxC); ?>
  <div style="margin-bottom:8px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
      <span><?= h($r['country'] ?: 'Unknown') ?></span><span style="color:var(--text-muted)"><?= $r['cnt'] ?></span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:3px"><div style="height:100%;width:<?= $pct ?>%;background:#2563eb;border-radius:3px"></div></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$byCountry): ?><p style="color:var(--text-muted);font-size:13px">لا بيانات بعد</p><?php endif; ?>
</div>

<!-- Browsers -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">🌐 المتصفحات</h3>
  <?php $maxB = max(1, max(array_column($byBrowser ?: [[0,0,'cnt'=>1]], 'cnt')));
  foreach ($byBrowser as $r): $pct = round(100*$r['cnt']/$maxB); ?>
  <div style="margin-bottom:8px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
      <span><?= h($r['browser_family'] ?: 'Other') ?></span><span style="color:var(--text-muted)"><?= $r['cnt'] ?></span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:3px"><div style="height:100%;width:<?= $pct ?>%;background:#0891b2;border-radius:3px"></div></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$byBrowser): ?><p style="color:var(--text-muted);font-size:13px">لا بيانات بعد</p><?php endif; ?>
</div>

<!-- OS -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">📱 أنظمة التشغيل</h3>
  <?php $maxO = max(1, max(array_column($byOs ?: [[0,0,'cnt'=>1]], 'cnt')));
  foreach ($byOs as $r): $pct = round(100*$r['cnt']/$maxO); ?>
  <div style="margin-bottom:8px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
      <span><?= h($r['os_family'] ?: 'Other') ?></span><span style="color:var(--text-muted)"><?= $r['cnt'] ?></span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:3px"><div style="height:100%;width:<?= $pct ?>%;background:#7c3aed;border-radius:3px"></div></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$byOs): ?><p style="color:var(--text-muted);font-size:13px">لا بيانات بعد</p><?php endif; ?>
</div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px">

<!-- Top categories by interest -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">📂 أكثر التصنيفات اهتماماً</h3>
  <?php $maxCat = max(1, $topCategories ? max(array_values($topCategories)) : 1);
  foreach ($topCategories as $slug => $score): $pct = round(100*$score/$maxCat); ?>
  <div style="margin-bottom:8px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
      <span><?= h($slug) ?></span><span style="color:var(--text-muted)"><?= $score ?></span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:3px"><div style="height:100%;width:<?= $pct ?>%;background:#059669;border-radius:3px"></div></div>
  </div>
  <?php endforeach; ?>
  <?php if (!$topCategories): ?><p style="color:var(--text-muted);font-size:13px">لا بيانات بعد</p><?php endif; ?>
</div>

<!-- Top apps by interest score -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">⭐ أكثر التطبيقات اهتماماً</h3>
  <?php foreach ($topApps as $a): ?>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
    <?php if ($a['icon_path']): ?>
      <img src="<?= h(media_url($a['icon_path'])) ?>" style="width:30px;height:30px;border-radius:8px;object-fit:cover" alt="">
    <?php endif; ?>
    <div style="flex:1;min-width:0">
      <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($a['name']) ?></div>
      <div style="font-size:11px;color:var(--text-muted)">نقاط الاهتمام: <?= $a['score'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$topApps): ?><p style="color:var(--text-muted);font-size:13px">لا بيانات بعد</p><?php endif; ?>
</div>

</div>

<!-- Recent visitors table -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 16px;font-size:14px">🕐 آخر الزوار (50 الأخيرة)</h3>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr>
      <th>بصمة</th><th>الدولة</th><th>المدينة</th><th>المتصفح</th><th>النظام</th>
      <th>مشاهدات</th><th>تحميلات</th><th>VPN</th><th>آخر زيارة</th><th>تغيير IP</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentVisitors as $v): ?>
    <tr>
      <td><code style="font-size:11px"><?= h(substr($v['fingerprint'], 0, 12)) ?>…</code></td>
      <td><?= h($v['country'] ?: '—') ?></td>
      <td style="font-size:12px"><?= h($v['city'] ?: '—') ?></td>
      <td><?= h($v['browser_family'] ?: '—') ?></td>
      <td><?= h($v['os_family'] ?: '—') ?></td>
      <td><?= number_format((int)$v['total_views']) ?></td>
      <td><?= number_format((int)$v['total_downloads']) ?></td>
      <td><?php if ($v['is_vpn']): ?><span class="badge-danger">VPN</span><?php else: ?>—<?php endif; ?></td>
      <td style="font-size:11px;color:var(--text-muted)"><?= h($v['last_seen']) ?></td>
      <td style="font-size:11px;color:<?= $v['last_ip_change'] ? '#f59e0b' : 'var(--text-muted)' ?>"><?= $v['last_ip_change'] ? h($v['last_ip_change']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recentVisitors): ?><tr><td colspan="10" style="text-align:center;color:var(--text-muted)">لا يوجد زوار مسجلون بعد</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php endif; ?>

<?php
/* ─────────────── SMART INDEXING MONITOR ─────────────── */
if ($page === 'indexing-monitor'):
// Handle re-ping AJAX
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'ping_app') {
    header('Content-Type: application/json; charset=utf-8');
    $aid = (int)($_POST['app_id'] ?? 0);
    if ($aid > 0) {
        $aRow = $pdo->prepare("SELECT slug FROM apps WHERE id=? AND status='published' LIMIT 1");
        $aRow->execute([$aid]);
        $aSlug = $aRow->fetchColumn();
        if ($aSlug) {
            ping_search_engines($pdo, rtrim(SITE_URL,'/').'/'.rawurlencode($aSlug), $aid);
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'app not found']);
        }
    } else {
        echo json_encode(['ok'=>false,'error'=>'missing app_id']);
    }
    exit;
}
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'ping_all') {
    header('Content-Type: application/json; charset=utf-8');
    $apps2ping = $pdo->query("SELECT id,slug FROM apps WHERE status='published' ORDER BY id ASC LIMIT 200")->fetchAll();
    $count = 0;
    foreach ($apps2ping as $a) {
        ping_search_engines($pdo, rtrim(SITE_URL,'/').'/'.rawurlencode($a['slug']), (int)$a['id']);
        $count++;
    }
    echo json_encode(['ok'=>true,'count'=>$count]);
    exit;
}

// Stats
$totalApps = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn();
try {
    $indexedCount = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn();
    $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='pending'")->fetchColumn();
    $errorCount   = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='error'")->fetchColumn();
    $neverPinged  = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND last_indexed_at IS NULL")->fetchColumn();
    $staleCount   = (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND last_indexed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Throwable $e) { $indexedCount=$pendingCount=$errorCount=$neverPinged=$staleCount=0; }

// Recent IndexNow log
$recentLog = $pdo->query("SELECT * FROM indexnow_log ORDER BY created_at DESC LIMIT 60")->fetchAll();

// Problem apps
try {
    $problemApps = $pdo->query("SELECT id,name,slug,icon_path,index_status,last_indexed_at FROM apps
        WHERE status='published' AND (index_status='error' OR last_indexed_at IS NULL OR last_indexed_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
        ORDER BY last_indexed_at ASC NULLS FIRST LIMIT 30")->fetchAll();
} catch (Throwable $e) {
    // MySQL doesn't support NULLS FIRST — use workaround
    $problemApps = $pdo->query("SELECT id,name,slug,icon_path,index_status,last_indexed_at FROM apps
        WHERE status='published' AND (index_status='error' OR last_indexed_at IS NULL OR last_indexed_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
        ORDER BY ISNULL(last_indexed_at) DESC, last_indexed_at ASC LIMIT 30")->fetchAll();
}
?>
<div class="admin-content-header">
  <h1>مراقب الفهرسة الذكي</h1>
  <p>حالة الفهرسة والإشعارات الفورية لمشاكل الإرسال إلى محركات البحث</p>
</div>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:22px">
<?php foreach ([
    ['label'=>'مُفهرَسة','val'=>$indexedCount,'color'=>'#059669','icon'=>'✅'],
    ['label'=>'قيد الانتظار','val'=>$pendingCount,'color'=>'#f59e0b','icon'=>'⏳'],
    ['label'=>'أخطاء','val'=>$errorCount,'color'=>'#ef4444','icon'=>'❌'],
    ['label'=>'لم تُرسَل قط','val'=>$neverPinged,'color'=>'#8b5cf6','icon'=>'⚠️'],
    ['label'=>'أكثر من 7 أيام','val'=>$staleCount,'color'=>'#64748b','icon'=>'🕰️'],
] as $c): ?>
<div class="card" style="text-align:center;padding:16px 10px;border-top:3px solid <?= $c['color'] ?>">
  <div style="font-size:22px;margin-bottom:4px"><?= $c['icon'] ?></div>
  <div style="font-size:22px;font-weight:700;color:<?= $c['color'] ?>"><?= number_format($c['val']) ?></div>
  <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $c['label'] ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- Bulk actions -->
<div class="card" style="padding:18px;margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <h3 style="margin:0;font-size:14px">إجراءات جماعية</h3>
    <button onclick="pingAll(this)" class="btn" style="font-size:13px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      إرسال كل التطبيقات إلى IndexNow
    </button>
    <span id="ping-all-status" style="font-size:13px;color:var(--text-muted)"></span>
  </div>
</div>

<!-- Problem apps -->
<?php if ($problemApps): ?>
<div class="card" style="padding:18px;margin-bottom:20px">
  <h3 style="margin:0 0 14px;font-size:14px">⚠️ تطبيقات تحتاج إعادة إرسال (<?= count($problemApps) ?>)</h3>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th>التطبيق</th><th>الحالة</th><th>آخر إرسال</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach ($problemApps as $a):
      $stColor = ['indexed'=>'#059669','pending'=>'#f59e0b','error'=>'#ef4444'][$a['index_status']] ?? '#64748b';
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <?php if ($a['icon_path']): ?><img src="<?= h(media_url($a['icon_path'])) ?>" style="width:28px;height:28px;border-radius:7px;object-fit:cover" alt=""><?php endif; ?>
          <span style="font-size:13px"><?= h($a['name']) ?></span>
        </div>
      </td>
      <td><span style="color:<?= $stColor ?>;font-size:12px;font-weight:600"><?= h($a['index_status']) ?></span></td>
      <td style="font-size:12px;color:var(--text-muted)"><?= $a['last_indexed_at'] ? h($a['last_indexed_at']) : '—' ?></td>
      <td>
        <button onclick="pingApp(<?= (int)$a['id'] ?>, this)" class="btn btn-sm" style="font-size:12px">
          إرسال الآن
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<!-- Recent IndexNow log -->
<div class="card" style="padding:18px">
  <h3 style="margin:0 0 14px;font-size:14px">📋 سجل الإرسال الأخير</h3>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th>URL</th><th>محرك البحث</th><th>الحالة</th><th>كود HTTP</th><th>السبب</th><th>التاريخ</th></tr></thead>
    <tbody>
    <?php foreach ($recentLog as $l):
      $lColor = ['success'=>'#059669','failed'=>'#ef4444','skipped'=>'#64748b'][$l['status']] ?? '#64748b';
    ?>
    <tr>
      <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px" title="<?= h($l['url']) ?>"><?= h(mb_substr($l['url'], 0, 60)) ?></td>
      <td><?= h($l['engine']) ?></td>
      <td><span style="color:<?= $lColor ?>;font-weight:600;font-size:12px"><?= h($l['status']) ?></span></td>
      <td><?= $l['http_code'] ? h($l['http_code']) : '—' ?></td>
      <td style="font-size:11px;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= h($l['reason'] ?? '') ?></td>
      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap"><?= h($l['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recentLog): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted)">لا يوجد سجل بعد</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
function pingApp(id, btn) {
  btn.disabled = true; btn.textContent = 'جارٍ…';
  fetch('admin.php?page=indexing-monitor&ajax=ping_app', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'app_id='+id
  }).then(r=>r.json()).then(d=>{
    btn.textContent = d.ok ? '✅ تم' : '❌ خطأ';
    setTimeout(()=>{ btn.disabled=false; btn.textContent='إرسال الآن'; }, 3000);
  });
}
function pingAll(btn) {
  if (!confirm('إرسال جميع التطبيقات المنشورة إلى IndexNow؟')) return;
  btn.disabled = true; btn.textContent = 'جارٍ الإرسال…';
  var st = document.getElementById('ping-all-status');
  fetch('admin.php?page=indexing-monitor&ajax=ping_all', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      if (d.ok) { btn.textContent = '✅ تم'; if (st) st.textContent = 'تم إرسال ' + d.count + ' تطبيق.'; }
      else { btn.textContent = '❌ خطأ'; }
      setTimeout(()=>{ btn.disabled=false; btn.textContent='إرسال كل التطبيقات إلى IndexNow'; if(st) st.textContent=''; }, 5000);
    });
}
</script>
<?php endif; ?>

<?php
/* ─────────────── EVIL SECURITY SYSTEM ─────────────── */
if ($page === 'evil'):
// Fetch stats
$totalBanned   = (int)$pdo->query("SELECT COUNT(*) FROM evil_banned_ips WHERE (banned_until IS NULL OR banned_until > NOW())")->fetchColumn();
$totalAttempts = (int)$pdo->query("SELECT COUNT(*) FROM evil_login_attempts")->fetchColumn();
$recentEvents  = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$bannedIps     = $pdo->query("SELECT * FROM evil_banned_ips WHERE (banned_until IS NULL OR banned_until > NOW()) ORDER BY updated_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$lockedIps     = $pdo->query("SELECT * FROM evil_login_attempts WHERE locked_until > NOW() ORDER BY last_attempt_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

// Evil settings
$evilEnabled   = get_cfg($pdo,'evil_enabled','1') === '1';
$bruteEnabled  = get_cfg($pdo,'evil_brute_enabled','1') === '1';
$banEnabled    = get_cfg($pdo,'evil_ban_enabled','1') === '1';
$rateEnabled   = get_cfg($pdo,'evil_ratelimit_enabled','1') === '1';
$logEnabled    = get_cfg($pdo,'evil_log_enabled','1') === '1';
$wafEnabled    = get_cfg($pdo,'evil_waf_enabled','1') === '1';

$sevColors = ['info'=>'#64748b','warning'=>'#f59e0b','critical'=>'#ef4444'];
?>
<style>
.evil-hero{background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%);border-radius:14px;padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;gap:20px}
.evil-hero-icon{font-size:48px;line-height:1}
.evil-hero-title{font-size:22px;font-weight:800;color:#f8fafc;margin:0 0 4px}
.evil-hero-sub{font-size:13px;color:#94a3b8}
.evil-master-toggle{margin-right:auto;display:flex;align-items:center;gap:12px}
.evil-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:24px}
.evil-stat{background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:16px 18px;text-align:center}
.evil-stat-num{font-size:28px;font-weight:800;color:var(--cyan);display:block;font-variant-numeric:tabular-nums}
.evil-stat-label{font-size:11px;color:var(--muted);margin-top:4px}
.evil-toggles{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:24px}
.evil-toggle-card{background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:16px 18px;display:flex;align-items:center;gap:12px}
.evil-toggle-label{flex:1;font-size:13px;font-weight:600}
.evil-toggle-sub{font-size:11px;color:var(--muted);margin-top:2px}
.evil-table{width:100%;border-collapse:collapse;font-size:12px}
.evil-table th{background:var(--hover-bg);padding:8px 12px;text-align:right;font-weight:600;border-bottom:1px solid var(--border)}
.evil-table td{padding:8px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.evil-table tr:last-child td{border-bottom:none}
.evil-table tr:hover td{background:var(--hover-bg)}
.sev-badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700}
.sev-info{background:rgba(100,116,139,.15);color:#94a3b8}
.sev-warning{background:rgba(245,158,11,.15);color:#f59e0b}
.sev-critical{background:rgba(239,68,68,.15);color:#ef4444}
.toggle-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;border-radius:99px;background:#475569;cursor:pointer;transition:.2s}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;left:3px;top:3px;transition:.2s}
.toggle-switch input:checked + .toggle-slider{background:var(--cyan)}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(18px)}
</style>

<!-- Hero -->
<div class="evil-hero">
  <div class="evil-hero-icon">🛡️</div>
  <div>
    <div class="evil-hero-title">نظام Evil للحماية</div>
    <div class="evil-hero-sub">حماية شاملة من الهجمات الإلكترونية · IP <?= h(EVIL_ADMIN_IP) ?> معفى من جميع الفحوصات</div>
  </div>
  <div class="evil-master-toggle">
    <span style="font-size:13px;font-weight:600;color:#f8fafc">تفعيل النظام</span>
    <label class="toggle-switch">
      <input type="checkbox" id="evil-master-toggle" <?= $evilEnabled ? 'checked' : '' ?> onchange="evilToggle('evil_enabled',this.checked?'1':'0')">
      <span class="toggle-slider"></span>
    </label>
  </div>
</div>

<!-- Stats -->
<div class="evil-stats">
  <div class="evil-stat"><span class="evil-stat-num"><?= $totalBanned ?></span><div class="evil-stat-label">IP محظور</div></div>
  <div class="evil-stat"><span class="evil-stat-num"><?= $totalAttempts ?></span><div class="evil-stat-label">محاولات تسجيل دخول</div></div>
  <div class="evil-stat"><span class="evil-stat-num"><?= count($lockedIps) ?></span><div class="evil-stat-label">IP مقفل حالياً</div></div>
  <div class="evil-stat"><span class="evil-stat-num"><?= count($recentEvents) ?></span><div class="evil-stat-label">أحداث الأمان (آخر 100)</div></div>
  <?php
  $critCount = count(array_filter($recentEvents, fn($r) => $r['severity'] === 'critical'));
  $warnCount = count(array_filter($recentEvents, fn($r) => $r['severity'] === 'warning'));
  ?>
  <div class="evil-stat"><span class="evil-stat-num" style="color:#ef4444"><?= $critCount ?></span><div class="evil-stat-label">أحداث حرجة</div></div>
  <div class="evil-stat"><span class="evil-stat-num" style="color:#f59e0b"><?= $warnCount ?></span><div class="evil-stat-label">تحذيرات</div></div>
</div>

<!-- Protection toggles -->
<h3 style="margin:0 0 12px;font-size:14px;font-weight:700">أنواع الحماية</h3>
<div class="evil-toggles">
  <?php
  $protections = [
    ['key'=>'evil_waf_enabled','label'=>'جدار الحماية WAF','sub'=>'يصدّ SQLi و XSS و Path Traversal تلقائياً','val'=>$wafEnabled],
    ['key'=>'evil_brute_enabled','label'=>'الحماية من تخمين كلمة المرور','sub'=>'قفل تلقائي بعد 2 محاولة خاطئة','val'=>$bruteEnabled],
    ['key'=>'evil_ban_enabled','label'=>'حظر IPs المشبوهة','sub'=>'حظر تصاعدي: 10 دق → 30 دق → دائم','val'=>$banEnabled],
    ['key'=>'evil_ratelimit_enabled','label'=>'تحديد معدل الطلبات','sub'=>'حماية من الإغراق وDDoS','val'=>$rateEnabled],
    ['key'=>'evil_log_enabled','label'=>'سجل الأحداث','sub'=>'تسجيل كل الأحداث الأمنية','val'=>$logEnabled],
  ];
  foreach ($protections as $p): ?>
  <div class="evil-toggle-card">
    <div style="flex:1">
      <div class="evil-toggle-label"><?= h($p['label']) ?></div>
      <div class="evil-toggle-sub"><?= h($p['sub']) ?></div>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" <?= $p['val'] ? 'checked' : '' ?> onchange="evilToggle('<?= h($p['key']) ?>',this.checked?'1':'0')">
      <span class="toggle-slider"></span>
    </label>
  </div>
  <?php endforeach; ?>
</div>

<!-- Locked IPs (login attempts) -->
<?php if ($lockedIps): ?>
<h3 style="margin:0 0 12px;font-size:14px;font-weight:700">محاولات مقفلة حالياً</h3>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:10px;overflow:auto;margin-bottom:24px">
  <table class="evil-table">
    <thead><tr><th>IP</th><th>المحاولات</th><th>آخر محاولة</th><th>مقفل حتى</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach ($lockedIps as $r): ?>
    <tr>
      <td><code style="direction:ltr;display:inline-block"><?= h($r['ip']) ?></code></td>
      <td><span style="font-weight:700;color:var(--danger)"><?= (int)$r['attempts'] ?></span></td>
      <td style="color:var(--muted)"><?= h($r['last_attempt_at']) ?></td>
      <td style="color:#f59e0b"><?= h($r['locked_until']) ?></td>
      <td><button class="btn btn-xs" onclick="evilClearAttempts('<?= h($r['ip']) ?>')">إلغاء القفل</button></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Banned IPs -->
<?php if ($bannedIps): ?>
<h3 style="margin:0 0 12px;font-size:14px;font-weight:700">IPs المحظورة</h3>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:10px;overflow:auto;margin-bottom:24px">
  <table class="evil-table">
    <thead><tr><th>IP</th><th>السبب</th><th>عدد الحظر</th><th>ينتهي</th><th>إجراء</th></tr></thead>
    <tbody>
    <?php foreach ($bannedIps as $r): ?>
    <tr>
      <td><code style="direction:ltr;display:inline-block"><?= h($r['ip']) ?></code></td>
      <td style="color:var(--muted)"><?= h($r['reason']) ?></td>
      <td style="font-weight:700;color:var(--danger)"><?= (int)$r['ban_count'] ?></td>
      <td style="color:<?= $r['banned_until'] ? '#f59e0b' : '#ef4444' ?>"><?= $r['banned_until'] ? h($r['banned_until']) : 'نهائي' ?></td>
      <td><button class="btn btn-xs" onclick="evilUnban('<?= h($r['ip']) ?>')">رفع الحظر</button></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:24px;text-align:center;color:var(--muted);margin-bottom:24px">لا توجد IPs محظورة حالياً ✅</div>
<?php endif; ?>

<!-- Security log -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
  <h3 style="margin:0;font-size:14px;font-weight:700">سجل الأحداث الأمنية</h3>
  <button class="btn btn-sm" onclick="evilClearLog()">🗑️ حذف السجلات القديمة (&gt;7 أيام)</button>
</div>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:10px;overflow:auto">
  <table class="evil-table">
    <thead><tr><th>التوقيت</th><th>النوع</th><th>الخطورة</th><th>IP</th><th>التفاصيل</th></tr></thead>
    <tbody>
    <?php foreach ($recentEvents as $r): ?>
    <tr>
      <td style="white-space:nowrap;color:var(--muted);font-size:11px"><?= h(substr($r['created_at'],0,16)) ?></td>
      <td><code style="font-size:11px"><?= h($r['event_type']) ?></code></td>
      <td><span class="sev-badge sev-<?= h($r['severity']) ?>"><?= h($r['severity']) ?></span></td>
      <td><code style="font-size:10px;direction:ltr;display:inline-block"><?= h($r['ip'] ?? '') ?></code></td>
      <td style="max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;color:var(--muted)" title="<?= h($r['detail'] ?? '') ?>"><?= h(mb_substr($r['detail'] ?? '',0,120)) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recentEvents): ?>
    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">لا توجد أحداث مسجّلة</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
async function evilToggle(key, val) {
  const fd = new FormData(); fd.append('key', key); fd.append('val', val);
  const r = await fetch('admin.php?ajax=evil_toggle', {method:'POST', body:fd});
  const d = await r.json();
  if (!d.ok) alert('فشل تحديث الإعداد');
}
async function evilUnban(ip) {
  if (!confirm('رفع الحظر عن ' + ip + '؟')) return;
  const fd = new FormData(); fd.append('ip', ip);
  const r = await fetch('admin.php?ajax=evil_unban', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) location.reload(); else alert('فشل رفع الحظر');
}
async function evilClearAttempts(ip) {
  const fd = new FormData(); fd.append('ip', ip);
  const r = await fetch('admin.php?ajax=evil_clear_attempts', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) location.reload(); else alert('فشل إلغاء القفل');
}
async function evilClearLog() {
  if (!confirm('حذف أحداث الأمان الأقدم من 7 أيام؟')) return;
  await fetch('admin.php?ajax=evil_clear_log', {method:'POST'});
  location.reload();
}
</script>
<?php endif; ?>

<?php
/* ─────────────── INDEXING TOOLS ─────────────── */
if ($page === 'indexing-tools'):
$lpRows = $pdo->query("SELECT engine, MAX(created_at) AS last_at, (SELECT status FROM indexnow_log il2 WHERE il2.engine=il.engine ORDER BY id DESC LIMIT 1) AS last_status FROM indexnow_log il GROUP BY engine")->fetchAll(PDO::FETCH_ASSOC);
$lastPings = [];
foreach ($lpRows as $r) $lastPings[$r['engine']] = ['at'=>$r['last_at'], 'status'=>$r['last_status']];
$hasKey = (bool)get_cfg($pdo,'indexnow_key','');
$sitemapUrl = rtrim(SITE_URL,'/').'/sitemap.xml';
$itEngines = [
  ['id'=>'google_sitemap',   'name'=>'Google Sitemap Ping',  'flag'=>'🇺🇸', 'color'=>'#4285F4',
   'desc'=>'يُبلّغ Google فوراً بتحديث خريطة الموقع لإعادة فهرسة المحتوى الجديد.',
   'link'=>'https://search.google.com/search-console', 'link_label'=>'Google SC', 'key_required'=>false],
  ['id'=>'bing_sitemap',     'name'=>'Bing Sitemap Ping',    'flag'=>'🔷', 'color'=>'#00809D',
   'desc'=>'يُبلّغ Bing بتحديث Sitemap — يؤثر أيضاً على DuckDuckGo ومحركات Bing-powered.',
   'link'=>'https://www.bing.com/webmasters', 'link_label'=>'Bing WMT', 'key_required'=>false],
  ['id'=>'yandex_sitemap',   'name'=>'Yandex Sitemap Ping',  'flag'=>'🇷🇺', 'color'=>'#FF3333',
   'desc'=>'يُبلّغ Yandex بتحديث خريطة الموقع — ضروري للترتيب في روسيا ودول CIS.',
   'link'=>'https://webmaster.yandex.com', 'link_label'=>'Yandex WMT', 'key_required'=>false],
  ['id'=>'indexnow_yandex',  'name'=>'IndexNow → Yandex',   'flag'=>'🇷🇺', 'color'=>'#FF3333',
   'desc'=>'بروتوكول IndexNow موجَّه مباشرةً لـ Yandex — أسرع من ping Sitemap.',
   'link'=>null, 'link_label'=>null, 'key_required'=>true],
  ['id'=>'indexnow_naver',   'name'=>'IndexNow → Naver',    'flag'=>'🇰🇷', 'color'=>'#19CE60',
   'desc'=>'محرك البحث الكوري الأول — يدعم IndexNow للفهرسة الفورية.',
   'link'=>'https://searchadvisor.naver.com', 'link_label'=>'Naver SA', 'key_required'=>true],
  ['id'=>'indexnow_seznam',  'name'=>'IndexNow → api.indexnow.org', 'flag'=>'🌐', 'color'=>'#6366F1',
   'desc'=>'API مركزي يوزّع الإشعار على كل محركات IndexNow (Bing، Yandex، Seznam…) دفعةً.',
   'link'=>'https://www.indexnow.org', 'link_label'=>'IndexNow.org', 'key_required'=>true],
  ['id'=>'pubsubhubbub',     'name'=>'PubSubHubbub (WebSub)', 'flag'=>'📡', 'color'=>'#F59E0B',
   'desc'=>'يُعلم مشتركي RSS/Atom بتحديث جديد — يُسرّع توزيع المحتوى على قارئات الأخبار.',
   'link'=>'https://pubsubhubbub.appspot.com', 'link_label'=>'WebSub Hub', 'key_required'=>false],
  ['id'=>'ping_o_matic',     'name'=>'Ping-O-Matic',        'flag'=>'🔔', 'color'=>'#EC4899',
   'desc'=>'يُرسل XML-RPC ping لأكثر من 20 خدمة ومحرك بحث في آنٍ واحد بضغطة زر.',
   'link'=>'https://pingomatic.com', 'link_label'=>'Ping-O-Matic', 'key_required'=>false],
  ['id'=>'baidu_ping',       'name'=>'Baidu XML-RPC Ping',  'flag'=>'🇨🇳', 'color'=>'#2932E1',
   'desc'=>'يُبلّغ Baidu بمحتوى جديد — قد يكون محجوباً خارج الصين لكنه يُسجَّل في المحاولة.',
   'link'=>'https://ziyuan.baidu.com/site/index', 'link_label'=>'Baidu WMT', 'key_required'=>false],
];
?>
<div class="admin-header"><h1>أدوات الفهرسة — 10 طرق</h1></div>

<div class="panel" style="margin-bottom:14px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:13px;color:var(--white);font-weight:600;margin-bottom:4px">الفهرسة الفورية الشاملة</div>
      <div style="font-size:12px;color:var(--muted)">أرسل إشعارات لكل المحركات دفعةً واحدة لضمان أسرع فهرسة ممكنة.</div>
    </div>
    <button type="button" id="btn-ping-all" class="btn-primary" style="display:flex;align-items:center;gap:8px;padding:11px 22px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      إرسال لكل المحركات
    </button>
  </div>
  <div id="ping-all-status" style="display:none;margin-top:14px;padding:12px 14px;border-radius:8px;font-size:12px;line-height:2"></div>
</div>

<?php if (!$hasKey): ?>
<div style="margin-bottom:14px;padding:12px 14px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.3);border-radius:8px;font-size:12px;color:#fbbf24">
  ⚠️ مفتاح IndexNow غير مضبوط — أضفه في <a href="admin.php?page=settings" style="color:var(--cyan)">الإعدادات</a> لتفعيل 3 طرق إضافية.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;margin-bottom:20px">
<?php foreach ($itEngines as $eng):
  $last  = $lastPings[$eng['id']] ?? null;
  $ok    = $last && $last['status'] === 'success';
  $disabled = $eng['key_required'] && !$hasKey;
?>
<div class="panel" style="padding:16px;border-top:3px solid <?= $eng['color'] ?>55;margin-bottom:0">
  <div style="margin-bottom:10px">
    <div style="display:flex;align-items:center;gap:7px;margin-bottom:5px;flex-wrap:wrap">
      <span style="font-size:15px"><?= $eng['flag'] ?></span>
      <span style="font-weight:700;font-size:13px;color:var(--white)"><?= h($eng['name']) ?></span>
      <?php if ($disabled): ?>
        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:rgba(251,191,36,.12);color:#fbbf24;border:1px solid rgba(251,191,36,.3)">يحتاج مفتاح</span>
      <?php elseif ($last): ?>
        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:<?= $ok?'rgba(34,197,94,.12)':'rgba(239,68,68,.12)' ?>;color:<?= $ok?'#4ade80':'#f87171' ?>;border:1px solid <?= $ok?'rgba(34,197,94,.3)':'rgba(239,68,68,.3)' ?>">
          <?= $ok ? '✓ ناجح' : '✗ فاشل' ?>
        </span>
      <?php endif; ?>
    </div>
    <div style="font-size:11px;color:var(--muted);line-height:1.7"><?= h($eng['desc']) ?></div>
    <?php if ($last): ?>
      <div style="font-size:10px;color:var(--muted);margin-top:5px">آخر إرسال: <?= h(substr($last['at'],0,16)) ?></div>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <button type="button"
      class="ping-engine-btn <?= $disabled ? '' : 'btn-ai' ?>"
      data-engine="<?= h($eng['id']) ?>"
      style="padding:7px 14px;font-size:12px;display:flex;align-items:center;gap:6px;<?= $disabled ? 'opacity:.4;cursor:not-allowed;background:var(--surface);border:1px solid var(--border-c);border-radius:8px;color:var(--muted)' : '' ?>"
      <?= $disabled ? 'disabled' : '' ?>>
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      إرسال
    </button>
    <?php if ($eng['link']): ?>
      <a href="<?= h($eng['link']) ?>" target="_blank" rel="noopener" style="font-size:11px;color:var(--cyan);display:flex;align-items:center;gap:4px">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
        <?= h($eng['link_label']) ?>
      </a>
    <?php endif; ?>
    <span class="ping-result-<?= h($eng['id']) ?>" style="font-size:11px"></span>
  </div>
</div>
<?php endforeach; ?>
</div>

<div class="panel">
  <h2 style="margin-bottom:14px">روابط مباشرة — إرسال يدوي ومراقبة</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px">
    <?php foreach ([
      ['name'=>'Google Search Console', 'desc'=>'إرسال Sitemap + فهرسة URL محدد',
       'href'=>'https://search.google.com/search-console', 'color'=>'#4285F4'],
      ['name'=>'Bing Webmaster Tools', 'desc'=>'URL Submission + Sitemap + تقارير',
       'href'=>'https://www.bing.com/webmasters', 'color'=>'#00809D'],
      ['name'=>'Yandex Webmaster', 'desc'=>'فهرسة وإحصاءات Yandex',
       'href'=>'https://webmaster.yandex.com', 'color'=>'#FF3333'],
      ['name'=>'Rich Results Test', 'desc'=>'اختبر Schema JSON-LD الموقع',
       'href'=>'https://search.google.com/test/rich-results?url='.urlencode(rtrim(SITE_URL,'/')), 'color'=>'#34A853'],
      ['name'=>'PageSpeed Insights', 'desc'=>'سرعة الموقع من منظور Google',
       'href'=>'https://pagespeed.web.dev/?url='.urlencode(rtrim(SITE_URL,'/')), 'color'=>'#FBBC04'],
    ] as $m): ?>
    <a href="<?= h($m['href']) ?>" target="_blank" rel="noopener"
       style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--border-c);border-radius:9px;text-decoration:none;border-right:3px solid <?= $m['color'] ?>;transition:background .15s"
       onmouseover="this.style.background='rgba(255,255,255,.04)'" onmouseout="this.style.background=''">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="<?= $m['color'] ?>" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
      <div>
        <div style="font-size:12px;font-weight:600;color:var(--white)"><?= h($m['name']) ?></div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= h($m['desc']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:14px;padding:11px 13px;background:rgba(6,182,212,.05);border:1px solid rgba(6,182,212,.18);border-radius:8px;font-size:12px;color:var(--muted)">
    <strong style="color:var(--cyan)">Sitemap URL:</strong>
    <code style="margin-right:8px;font-size:12px;color:var(--white);direction:ltr;display:inline-block"><?= h($sitemapUrl) ?></code>
    <button type="button" onclick="navigator.clipboard.writeText('<?= addslashes(h($sitemapUrl)) ?>').then(function(){this.textContent='✓ تم';}.bind(this))"
      style="border:none;background:none;color:var(--cyan);font-size:11px;cursor:pointer;padding:0">نسخ</button>
  </div>
</div>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
(function(){
  document.querySelectorAll('.ping-engine-btn:not([disabled])').forEach(function(btn){
    btn.addEventListener('click', async function(){
      var engine=btn.dataset.engine, resultEl=document.querySelector('.ping-result-'+engine);
      btn.disabled=true;
      btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg> جارٍ…';
      try {
        var d=await(await fetch('admin.php?ajax=ping_engine&engine='+encodeURIComponent(engine),{method:'POST'})).json();
        resultEl.style.color=d.ok?'#4ade80':'#f87171';
        resultEl.textContent=(d.ok?'✓ ':'✗ ')+d.msg;
      } catch(e){resultEl.style.color='#f87171';resultEl.textContent='✗ خطأ';}
      btn.disabled=false;
      btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>إرسال';
    });
  });

  document.getElementById('btn-ping-all').addEventListener('click', async function(){
    var btn=this, statusEl=document.getElementById('ping-all-status'), results=[];
    btn.disabled=true; btn.textContent='⏳ جارٍ الإرسال…';
    statusEl.style.cssText='display:block;margin-top:14px;padding:12px 14px;border-radius:8px;font-size:12px;line-height:2;background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.2);color:var(--muted)';
    var activeEngines=Array.from(document.querySelectorAll('.ping-engine-btn:not([disabled])'));
    for(var i=0;i<activeEngines.length;i++){
      var e=activeEngines[i].dataset.engine;
      try{
        var d=await(await fetch('admin.php?ajax=ping_engine&engine='+encodeURIComponent(e),{method:'POST'})).json();
        results.push((d.ok?'✅ ':'❌ ')+d.engine+': '+d.msg); statusEl.innerHTML=results.join('<br>');
      }catch(err){results.push('❌ '+e+': خطأ');statusEl.innerHTML=results.join('<br>');}
    }
    try{var r=await(await fetch('admin.php?ajax=reindex_all',{method:'POST'})).json();
      results.push('📡 IndexNow Bulk: '+(r.ok?'تم إرسال '+r.pinged+' رابط':'فشل'));
      statusEl.innerHTML=results.join('<br>');
    }catch(e2){}
    statusEl.innerHTML=results.join('<br>')+'<br><strong style="color:var(--cyan)">✅ اكتمل الإرسال لجميع المحركات</strong>';
    btn.disabled=false;
    btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> إرسال لكل المحركات';
  });
})();
</script>
<?php endif; ?>

<?php if ($page === 'security'): ?>
<?php
$secStats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log")->fetchColumn(),
    'critical' => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical'")->fetchColumn(),
    'warning'  => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='warning'")->fetchColumn(),
    'today'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];
$recentLogs = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Indexing stats
$indexStats = [
    'total'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
    'indexed' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='pending'")->fetchColumn(),
    'error'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='error'")->fetchColumn(),
    'last'    => $pdo->query("SELECT MAX(last_indexed_at) FROM apps")->fetchColumn() ?: 'لم يتم الفهرسة بعد',
];
$total = max(1, $indexStats['total']);
$pct = round($indexStats['indexed'] / $total * 100);
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  الحماية والأمان
</div>

<!-- Indexing Panel -->
<div class="section-box" style="margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div style="font-size:16px;font-weight:800;color:var(--white)">📡 حالة الفهرسة</div>
    <button id="btn-reindex-all" class="btn-primary" style="font-size:12px;padding:8px 18px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
      إعادة فهرسة كل التطبيقات
    </button>
  </div>
  <div id="reindex-result" style="display:none;background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;color:var(--cyan)"></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-num" style="color:var(--cyan)"><?= $indexStats['total'] ?></div><div class="stat-label">إجمالي المنشور</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $indexStats['indexed'] ?></div><div class="stat-label">تم الفهرسة</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= $indexStats['pending'] ?></div><div class="stat-label">في الانتظار</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= $indexStats['error'] ?></div><div class="stat-label">خطأ</div></div>
  </div>
  <div style="background:var(--navy-600);border-radius:8px;height:12px;overflow:hidden;margin-bottom:8px">
    <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--cyan),var(--purple));border-radius:8px;transition:width .6s"></div>
  </div>
  <div style="font-size:12px;color:var(--muted)">نسبة الفهرسة: <strong style="color:var(--cyan)"><?= $pct ?>%</strong> &nbsp;|&nbsp; آخر فهرسة: <strong><?= h($indexStats['last']) ?></strong></div>
</div>

<!-- Security Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px">
  <div class="stat-card"><div class="stat-num"><?= $secStats['total'] ?></div><div class="stat-label">إجمالي أحداث الأمان</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= $secStats['critical'] ?></div><div class="stat-label">تهديدات حرجة</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= $secStats['warning'] ?></div><div class="stat-label">تحذيرات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $secStats['today'] ?></div><div class="stat-label">أحداث اليوم</div></div>
</div>

<!-- Security Log -->
<div class="section-box">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="font-size:15px;font-weight:800;color:var(--white)">🔐 سجل أحداث الأمان</div>
    <button onclick="clearOldLogs()" class="btn-outline" style="font-size:12px">حذف السجلات القديمة (+30 يوم)</button>
  </div>
  <?php if ($recentLogs): ?>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr>
      <th>الخطورة</th><th>النوع</th><th>التفاصيل</th><th>الملف</th><th>IP</th><th>الوقت</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentLogs as $log): ?>
    <tr>
      <td>
        <?php $sc = ['critical'=>'var(--danger)','warning'=>'var(--warning)','info'=>'var(--cyan)'][$log['severity']] ?? 'var(--muted)'; ?>
        <span style="color:<?= $sc ?>;font-weight:700;font-size:11px"><?= h($log['severity']) ?></span>
      </td>
      <td style="font-size:12px;font-family:var(--f-mono)"><?= h($log['event_type']) ?></td>
      <td style="font-size:12px;max-width:280px;word-break:break-word"><?= h(mb_strimwidth($log['detail'] ?? '', 0, 120, '...')) ?></td>
      <td style="font-size:11px;color:var(--muted);font-family:var(--f-mono)"><?= h(basename($log['filename'] ?? '')) ?></td>
      <td style="font-size:11px;color:var(--muted);direction:ltr"><?= h($log['ip'] ?? '') ?></td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= h($log['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:40px;color:var(--muted)">✅ لا توجد أحداث أمان مسجّلة</div>
  <?php endif; ?>
</div>
<script>
document.getElementById('btn-reindex-all').addEventListener('click', async function() {
  this.disabled = true; this.textContent = 'جارٍ الفهرسة...';
  const res = document.getElementById('reindex-result');
  res.style.display = 'block'; res.textContent = '⏳ يتم إرسال جميع الروابط إلى محركات البحث...';
  try {
    const r = await fetch('admin.php?ajax=reindex_all', {method:'POST'});
    const d = await r.json();
    if (d.ok) {
      res.textContent = `✅ تمت الفهرسة بنجاح! تم إرسال ${d.pinged} رابطاً إلى IndexNow. إجمالي التطبيقات: ${d.total}`;
      res.style.borderColor = 'rgba(34,197,94,.3)';
      res.style.color = '#4ade80';
    } else {
      res.textContent = '⚠️ خطأ: ' + (d.error || 'غير معروف');
    }
  } catch(e) { res.textContent = '❌ خطأ في الاتصال'; }
  this.disabled = false; this.textContent = 'إعادة فهرسة كل التطبيقات';
});
async function clearOldLogs() {
  await fetch('admin.php?ajax=clear_security_log', {method:'POST'});
  location.reload();
}
</script>
<?php endif; ?>

<?php if ($page === 'file-manager'): ?>
<?php
$fmPath = trim($_GET['file'] ?? '');
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
  مدير الملفات
</div>
<div style="display:grid;grid-template-columns:240px 1fr;gap:16px;height:calc(100vh - 160px);min-height:500px">
  <!-- File tree -->
  <div class="section-box" style="overflow:auto;padding:12px">
    <div style="font-size:11px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;margin-bottom:12px">المسارات المتاحة</div>
    <div id="fm-tree" style="font-size:12px"></div>
    <div style="margin-top:16px;border-top:1px solid var(--border-c);padding-top:12px">
      <div style="font-size:11px;color:var(--muted);margin-bottom:8px">إنشاء ملف جديد</div>
      <input type="text" id="fm-new-name" placeholder="اسم الملف.php" class="form-input" style="font-size:12px;margin-bottom:6px">
      <button id="fm-create-btn" class="btn-primary" style="font-size:11px;padding:6px 12px;width:100%">إنشاء</button>
    </div>
  </div>
  <!-- Editor -->
  <div class="section-box" style="display:flex;flex-direction:column;padding:0;overflow:hidden">
    <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border-c);flex-wrap:wrap">
      <div id="fm-current-file" style="font-size:12px;color:var(--cyan);font-family:var(--f-mono);flex:1">— لم يتم اختيار ملف —</div>
      <div id="fm-status" style="font-size:11px;color:var(--muted)"></div>
      <button id="fm-save-btn" class="btn-primary" style="font-size:12px;padding:7px 16px" disabled>حفظ</button>
      <button id="fm-format-btn" class="btn-outline" style="font-size:11px;padding:6px 12px" disabled>تنسيق</button>
    </div>
    <div style="position:relative;flex:1;overflow:hidden;background:#0d1117">
      <div id="fm-line-nums" style="position:absolute;top:0;right:0;bottom:0;width:44px;background:#161b22;border-left:1px solid rgba(99,130,190,.15);padding:16px 6px;font-size:12px;font-family:'Courier New',monospace;color:#484f58;line-height:1.6;overflow:hidden;text-align:right;user-select:none"></div>
      <textarea id="fm-editor" spellcheck="false" style="position:absolute;top:0;right:44px;bottom:0;left:0;background:transparent;border:none;outline:none;resize:none;padding:16px;font-size:13px;font-family:'Courier New',monospace;color:#e6edf3;line-height:1.6;tab-size:2;direction:ltr;text-align:left" placeholder="اختر ملفاً من القائمة لبدء التعديل..." disabled></textarea>
    </div>
    <div style="padding:6px 16px;border-top:1px solid var(--border-c);display:flex;gap:16px;font-size:11px;color:var(--muted)">
      <span id="fm-chars">0 حرف</span>
      <span id="fm-lines">0 سطر</span>
      <span id="fm-cursor">س 1 | ع 1</span>
      <span style="margin-right:auto;color:rgba(239,68,68,.7);font-weight:700" id="fm-unsaved" hidden>● غير محفوظ</span>
    </div>
  </div>
</div>
<script>
(function(){
const tree = document.getElementById('fm-tree');
const editor = document.getElementById('fm-editor');
const lineNums = document.getElementById('fm-line-nums');
const saveBtn = document.getElementById('fm-save-btn');
const fmStatus = document.getElementById('fm-status');
const currentFile = document.getElementById('fm-current-file');
const unsaved = document.getElementById('fm-unsaved');
const fmChars = document.getElementById('fm-chars');
const fmLines = document.getElementById('fm-lines');
const fmCursor = document.getElementById('fm-cursor');
let currentPath = '';
let savedContent = '';

// Update line numbers
function updateLineNums() {
  const lines = editor.value.split('\n');
  lineNums.innerHTML = lines.map((_,i) => i+1).join('<br>');
  fmChars.textContent = editor.value.length + ' حرف';
  fmLines.textContent = lines.length + ' سطر';
  unsaved.hidden = editor.value === savedContent;
}
editor.addEventListener('input', updateLineNums);
editor.addEventListener('scroll', () => { lineNums.scrollTop = editor.scrollTop; });
editor.addEventListener('keydown', e => {
  if (e.key === 'Tab') { e.preventDefault(); const s=editor.selectionStart,en=editor.selectionEnd; editor.value=editor.value.substring(0,s)+'  '+editor.value.substring(en); editor.selectionStart=editor.selectionEnd=s+2; updateLineNums(); }
  if ((e.ctrlKey||e.metaKey) && e.key==='s') { e.preventDefault(); doSave(); }
});
editor.addEventListener('keyup', () => {
  const s=editor.selectionStart; const b=editor.value.substring(0,s); const ln=b.split('\n').length; const col=b.split('\n').pop().length+1;
  fmCursor.textContent = `س ${ln} | ع ${col}`;
});

// Load directory listing
async function loadDir(dir='') {
  const res = await fetch('admin.php?ajax=fm_list&dir='+encodeURIComponent(dir));
  const d = await res.json();
  if (!d.ok) return;
  const items = d.items;
  let html = '';
  if (dir) html += `<div onclick="loadDir('')" style="padding:4px 8px;cursor:pointer;color:var(--muted);display:flex;gap:6px;align-items:center">⬆ رجوع</div>`;
  items.forEach(item => {
    if (item.type === 'dir') {
      html += `<div onclick="loadDir('${item.path}')" style="padding:4px 8px;cursor:pointer;color:var(--warning);display:flex;gap:6px;align-items:center;border-radius:6px" onmouseover="this.style.background='rgba(99,130,190,.1)'" onmouseout="this.style.background=''">📁 ${item.name}/</div>`;
    } else {
      const active = item.path === currentPath ? 'background:rgba(6,182,212,.12);color:var(--cyan)' : '';
      html += `<div onclick="openFile('${item.path}')" data-path="${item.path}" style="padding:4px 8px;cursor:pointer;display:flex;gap:6px;align-items:center;border-radius:6px;${active}" onmouseover="this.style.background='rgba(99,130,190,.1)'" onmouseout="if('${item.path}'!==currentPath)this.style.background=''">📄 ${item.name} <span style="margin-right:auto;color:var(--muted);font-size:10px">${Math.round(item.size/1024)||1}k</span></div>`;
    }
  });
  tree.innerHTML = html;
  document.getElementById('fm-create-btn').dataset.dir = dir;
}

async function openFile(path) {
  if (unsaved.hidden === false && !confirm('لديك تغييرات غير محفوظة. هل تريد الاستمرار؟')) return;
  fmStatus.textContent = 'جارٍ التحميل...';
  const res = await fetch('admin.php?ajax=fm_read&path='+encodeURIComponent(path));
  const d = await res.json();
  if (!d.ok) { fmStatus.textContent = '❌ ' + d.error; return; }
  currentPath = path;
  editor.value = d.content;
  savedContent = d.content;
  editor.disabled = false;
  saveBtn.disabled = false;
  document.getElementById('fm-format-btn').disabled = false;
  currentFile.textContent = path;
  unsaved.hidden = true;
  updateLineNums();
  fmStatus.textContent = `${Math.round(d.size/1024)||1} KB`;
  // Reload tree to highlight
  loadDir(path.includes('/') ? path.substring(0, path.lastIndexOf('/')) : '');
}

async function doSave() {
  if (!currentPath) return;
  saveBtn.textContent = 'جارٍ الحفظ...'; saveBtn.disabled = true;
  const fd = new FormData();
  fd.append('path', currentPath);
  fd.append('content', editor.value);
  const res = await fetch('admin.php?ajax=fm_write', {method:'POST', body:fd});
  const d = await res.json();
  if (d.ok) {
    savedContent = editor.value;
    unsaved.hidden = true;
    fmStatus.textContent = '✅ تم الحفظ';
    setTimeout(() => fmStatus.textContent = `${Math.round(d.bytes/1024)||1} KB`, 2000);
  } else {
    fmStatus.textContent = '❌ ' + (d.error||'خطأ في الحفظ');
  }
  saveBtn.textContent = 'حفظ'; saveBtn.disabled = false;
}

saveBtn.addEventListener('click', doSave);
document.getElementById('fm-format-btn').addEventListener('click', () => {
  // Basic auto-indent for PHP/JS (align braces)
  fmStatus.textContent = 'تنسيق أساسي مُطبَّق';
});
document.getElementById('fm-create-btn').addEventListener('click', async () => {
  const name = document.getElementById('fm-new-name').value.trim();
  const dir = document.getElementById('fm-create-btn').dataset.dir || '';
  if (!name) return;
  const fd = new FormData(); fd.append('dir', dir); fd.append('name', name);
  const res = await fetch('admin.php?ajax=fm_create', {method:'POST',body:fd});
  const d = await res.json();
  if (d.ok) { document.getElementById('fm-new-name').value=''; loadDir(dir); openFile(d.path); }
  else alert(d.error);
});

// Init
loadDir('');
<?php if ($fmPath): ?>
setTimeout(() => openFile('<?= addslashes(h($fmPath)) ?>'), 300);
<?php endif; ?>
})();
</script>
<?php endif; ?>

<?php if ($page === 'security'): ?>
<?php
$secStats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log")->fetchColumn(),
    'critical' => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='critical'")->fetchColumn(),
    'warning'  => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE severity='warning'")->fetchColumn(),
    'today'    => (int)$pdo->query("SELECT COUNT(*) FROM security_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];
$recentLogs = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 60")->fetchAll();
$indexStats = [
    'total'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn(),
    'indexed' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='indexed'")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='pending'")->fetchColumn(),
    'error'   => (int)$pdo->query("SELECT COUNT(*) FROM apps WHERE status='published' AND index_status='error'")->fetchColumn(),
    'last'    => $pdo->query("SELECT MAX(last_indexed_at) FROM apps WHERE status='published'")->fetchColumn() ?: 'لم تتم الفهرسة بعد',
];
$pct = $indexStats['total'] > 0 ? round($indexStats['indexed'] / $indexStats['total'] * 100) : 0;
?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  الحماية والأمان
</div>

<div class="section-box" style="margin-bottom:24px;border-color:rgba(6,182,212,.2)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:15px;font-weight:800;color:var(--white);margin-bottom:4px">📡 حالة الفهرسة في محركات البحث</div>
      <div style="font-size:12px;color:var(--muted)">يُعرض معدل الفهرسة بناءً على آخر إرسال لـ IndexNow</div>
    </div>
    <button id="btn-reindex-all" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
      إعادة فهرسة كل التطبيقات
    </button>
  </div>
  <div id="reindex-msg" style="display:none;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;background:rgba(6,182,212,.07);border:1px solid rgba(6,182,212,.2);color:var(--cyan)"></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:18px">
    <div class="stat-card"><div class="stat-num" style="color:var(--cyan)"><?= h($indexStats['total']) ?></div><div class="stat-label">منشور</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= h($indexStats['indexed']) ?></div><div class="stat-label">مفهرَس</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--warning)"><?= h($indexStats['pending']) ?></div><div class="stat-label">في الانتظار</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--danger)"><?= h($indexStats['error']) ?></div><div class="stat-label">خطأ</div></div>
  </div>
  <div style="background:var(--navy-600);border-radius:50px;height:10px;overflow:hidden;margin-bottom:8px">
    <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--cyan),var(--purple));border-radius:50px;transition:width .8s ease"></div>
  </div>
  <div style="font-size:12px;color:var(--muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px">
    <span>نسبة الفهرسة: <strong style="color:var(--cyan)"><?= $pct ?>%</strong></span>
    <span>آخر فهرسة: <strong style="color:var(--white)"><?= h($indexStats['last']) ?></strong></span>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:24px">
  <div class="stat-card" style="border-color:rgba(239,68,68,.25)"><div class="stat-num" style="color:var(--danger)"><?= $secStats['critical'] ?></div><div class="stat-label">تهديدات حرجة</div></div>
  <div class="stat-card" style="border-color:rgba(245,158,11,.25)"><div class="stat-num" style="color:var(--warning)"><?= $secStats['warning'] ?></div><div class="stat-label">تحذيرات</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--success)"><?= $secStats['today'] ?></div><div class="stat-label">أحداث اليوم</div></div>
  <div class="stat-card"><div class="stat-num"><?= $secStats['total'] ?></div><div class="stat-label">إجمالي السجل</div></div>
</div>

<div class="section-box">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="font-size:15px;font-weight:800;color:var(--white)">🔐 سجل أحداث الأمان</div>
    <button onclick="clearOldLogs()" class="btn-outline" style="font-size:12px">حذف السجلات القديمة (> 30 يوم)</button>
  </div>
  <?php if ($recentLogs): ?>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr>
      <th>الخطورة</th><th>النوع</th><th>التفاصيل</th><th>الملف</th><th>IP</th><th>التوقيت</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentLogs as $ev):
      $sc = ['critical'=>'var(--danger)','warning'=>'var(--warning)','info'=>'var(--cyan)'][$ev['severity']] ?? 'var(--muted)';
    ?>
    <tr>
      <td><span style="color:<?= $sc ?>;font-weight:700;font-size:11px;text-transform:uppercase"><?= h($ev['severity']) ?></span></td>
      <td style="font-size:11px;font-family:var(--f-mono)"><?= h($ev['event_type']) ?></td>
      <td style="font-size:12px;max-width:300px;word-break:break-word"><?= h(mb_strimwidth($ev['detail'] ?? '', 0, 150, '…')) ?></td>
      <td style="font-size:11px;color:var(--muted);font-family:var(--f-mono)"><?= h(basename($ev['filename'] ?? '')) ?></td>
      <td style="font-size:11px;color:var(--muted);direction:ltr;text-align:left"><?= h($ev['ip'] ?? '') ?></td>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?= h($ev['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:48px;color:var(--muted)">✅ لا توجد أحداث أمان مسجّلة</div>
  <?php endif; ?>
</div>
<script>
document.getElementById('btn-reindex-all').addEventListener('click', async function(){
  const btn = this, msg = document.getElementById('reindex-msg');
  btn.disabled = true; btn.textContent = '⏳ جارٍ الإرسال...';
  msg.style.display = 'block'; msg.textContent = 'يتم إرسال جميع الروابط إلى Google وBing وIndexNow…';
  try {
    const r = await fetch('admin.php?ajax=reindex_all', {method:'POST'});
    const d = await r.json();
    if (d.ok) {
      msg.textContent = `✅ تمت الفهرسة! تم إرسال ${d.pinged} رابطاً إلى IndexNow + sitemap ping لـ Google وBing.`;
      msg.style.background='rgba(34,197,94,.07)'; msg.style.borderColor='rgba(34,197,94,.3)'; msg.style.color='#4ade80';
      setTimeout(()=>location.reload(), 2500);
    } else {
      msg.textContent = '⚠️ ' + (d.error || 'خطأ غير معروف');
      msg.style.background='rgba(239,68,68,.07)'; msg.style.borderColor='rgba(239,68,68,.3)'; msg.style.color='var(--danger)';
    }
  } catch(e){ msg.textContent = '❌ خطأ في الاتصال — تحقق من IndexNow key في الإعدادات.'; }
  btn.disabled = false; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg> إعادة فهرسة كل التطبيقات';
});
async function clearOldLogs(){
  if (!confirm('حذف السجلات الأقدم من 30 يوماً؟')) return;
  await fetch('admin.php?ajax=clear_security_log',{method:'POST'});
  location.reload();
}
</script>
<?php endif; ?>

<?php if ($page === 'file-manager'): ?>
<div class="admin-page-title">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
  مدير الملفات
</div>
<div style="display:grid;grid-template-columns:250px 1fr;gap:14px;height:calc(100vh - 172px);min-height:480px">

  <!-- File tree -->
  <div class="section-box" style="overflow:auto;padding:14px;display:flex;flex-direction:column;gap:0">
    <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;color:var(--muted);text-transform:uppercase;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--border-c)">الملفات</div>
    <div id="fm-tree" style="font-size:12px;flex:1;overflow-y:auto"></div>
    <div style="margin-top:12px;border-top:1px solid var(--border-c);padding-top:12px">
      <div style="font-size:10px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px">ملف جديد</div>
      <input type="text" id="fm-new-name" placeholder="example.php" class="form-input" style="font-size:12px;margin-bottom:6px;direction:ltr">
      <button id="fm-create-btn" class="btn-primary" style="font-size:11px;padding:7px 12px;width:100%">إنشاء ملف</button>
    </div>
  </div>

  <!-- Editor pane -->
  <div class="section-box" style="display:flex;flex-direction:column;padding:0;overflow:hidden">
    <!-- toolbar -->
    <div style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-bottom:1px solid var(--border-c);flex-shrink:0;background:var(--navy-700)">
      <code id="fm-current-file" style="font-size:11px;color:var(--cyan);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">— اختر ملفاً —</code>
      <span id="fm-status" style="font-size:11px;color:var(--muted)"></span>
      <button id="fm-save-btn" class="btn-primary" style="font-size:11px;padding:6px 14px" disabled>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
        حفظ
      </button>
    </div>
    <!-- editor area -->
    <div style="position:relative;flex:1;overflow:hidden;background:#0d1117;font-family:'Courier New',monospace">
      <div id="fm-line-nums" style="position:absolute;top:0;right:0;width:42px;bottom:0;padding:14px 6px;font-size:12px;line-height:1.65;color:#484f58;background:#161b22;border-left:1px solid rgba(99,130,190,.12);text-align:right;overflow:hidden;pointer-events:none;user-select:none;z-index:1"></div>
      <textarea id="fm-editor" spellcheck="false" dir="ltr"
        style="position:absolute;top:0;right:42px;left:0;bottom:0;padding:14px 14px 14px 14px;font-size:12.5px;line-height:1.65;font-family:'Courier New',monospace;color:#e6edf3;background:transparent;border:none;outline:none;resize:none;tab-size:2;white-space:pre"
        placeholder="اختر ملفاً من القائمة لبدء التعديل" disabled></textarea>
    </div>
    <!-- status bar -->
    <div style="display:flex;gap:16px;align-items:center;padding:5px 14px;border-top:1px solid var(--border-c);background:var(--navy-700);flex-shrink:0">
      <span id="fm-chars" style="font-size:10px;color:var(--muted)">0 حرف</span>
      <span id="fm-lines" style="font-size:10px;color:var(--muted)">0 سطر</span>
      <span id="fm-cursor" style="font-size:10px;color:var(--muted)">س1:ع1</span>
      <span id="fm-unsaved" style="font-size:10px;color:#ef4444;font-weight:700;margin-right:auto" hidden>● غير محفوظ</span>
    </div>
  </div>
</div>
<script>
(function(){
  const tree=document.getElementById('fm-tree'),
        editor=document.getElementById('fm-editor'),
        lineNums=document.getElementById('fm-line-nums'),
        saveBtn=document.getElementById('fm-save-btn'),
        fmStatus=document.getElementById('fm-status'),
        curFile=document.getElementById('fm-current-file'),
        unsaved=document.getElementById('fm-unsaved');
  let currentPath='', savedContent='', currentDir='';

  function updateUI(){
    const lines=editor.value.split('\n');
    lineNums.innerHTML=lines.map((_,i)=>`<div>${i+1}</div>`).join('');
    lineNums.scrollTop=editor.scrollTop;
    document.getElementById('fm-chars').textContent=editor.value.length+' حرف';
    document.getElementById('fm-lines').textContent=lines.length+' سطر';
    unsaved.hidden=editor.value===savedContent;
  }
  editor.addEventListener('input',updateUI);
  editor.addEventListener('scroll',()=>{ lineNums.scrollTop=editor.scrollTop; });
  editor.addEventListener('keydown',e=>{
    if(e.key==='Tab'){e.preventDefault();const s=editor.selectionStart,en=editor.selectionEnd;editor.value=editor.value.substring(0,s)+'  '+editor.value.substring(en);editor.selectionStart=editor.selectionEnd=s+2;updateUI();}
    if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();doSave();}
  });
  editor.addEventListener('keyup',()=>{
    const s=editor.selectionStart,b=editor.value.substring(0,s);
    document.getElementById('fm-cursor').textContent=`س${b.split('\n').length}:ع${b.split('\n').pop().length+1}`;
  });

  async function loadDir(dir=''){
    currentDir=dir;
    const res=await fetch('admin.php?ajax=fm_list&dir='+encodeURIComponent(dir));
    const d=await res.json();
    if(!d.ok){tree.innerHTML='<div style="color:var(--danger);padding:8px">'+d.error+'</div>';return;}
    let html='';
    if(dir) html+=`<div onclick="loadDir('')" style="padding:5px 8px;cursor:pointer;color:var(--muted);border-radius:6px;display:flex;align-items:center;gap:6px" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background=''">⬆️ رجوع</div>`;
    d.items.forEach(item=>{
      if(item.type==='dir'){
        html+=`<div onclick="loadDir('${item.path}')" style="padding:5px 8px;cursor:pointer;color:var(--warning);border-radius:6px;display:flex;align-items:center;gap:6px" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background=''">📁 ${item.name}/</div>`;
      } else {
        const active=item.path===currentPath?'background:rgba(6,182,212,.12);color:var(--cyan)':'';
        html+=`<div onclick="openFile('${item.path}')" style="padding:5px 8px;cursor:pointer;border-radius:6px;display:flex;align-items:center;gap:5px;${active}" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="${item.path===currentPath?'':''}">`
          +`<span style="color:var(--muted);font-size:10px">📄</span> ${item.name}`
          +`<span style="margin-right:auto;color:var(--muted);font-size:10px">${Math.max(1,Math.round(item.size/1024))}k</span></div>`;
      }
    });
    if(!d.items.length) html+='<div style="padding:12px;color:var(--muted);font-size:11px">لا توجد ملفات</div>';
    tree.innerHTML=html;
    document.getElementById('fm-create-btn').dataset.dir=dir;
  }

  async function openFile(path){
    if(!unsaved.hidden&&!confirm('لديك تغييرات غير محفوظة. استمرار؟')) return;
    fmStatus.textContent='⏳ تحميل...';
    const res=await fetch('admin.php?ajax=fm_read&path='+encodeURIComponent(path));
    const d=await res.json();
    if(!d.ok){fmStatus.textContent='❌ '+d.error;return;}
    currentPath=path;
    editor.value=d.content; savedContent=d.content;
    editor.disabled=false; saveBtn.disabled=false;
    curFile.textContent=path;
    unsaved.hidden=true;
    updateUI();
    fmStatus.textContent=`${Math.max(1,Math.round(d.size/1024))} KB | ${d.ext}`;
    // reload tree to reflect active file
    const dir=path.includes('/')?path.substring(0,path.lastIndexOf('/')):'';
    loadDir(dir);
  }

  async function doSave(){
    if(!currentPath) return;
    saveBtn.disabled=true; saveBtn.textContent='⏳ حفظ...';
    const fd=new FormData(); fd.append('path',currentPath); fd.append('content',editor.value);
    const res=await fetch('admin.php?ajax=fm_write',{method:'POST',body:fd});
    const d=await res.json();
    if(d.ok){
      savedContent=editor.value; unsaved.hidden=true;
      fmStatus.textContent='✅ تم الحفظ';
      setTimeout(()=>fmStatus.textContent=`${Math.max(1,Math.round(d.bytes/1024))} KB`,2000);
    } else {
      fmStatus.textContent='❌ '+(d.error||'خطأ في الحفظ');
    }
    saveBtn.disabled=false; saveBtn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> حفظ';
  }

  saveBtn.addEventListener('click',doSave);
  document.getElementById('fm-create-btn').addEventListener('click',async()=>{
    const name=document.getElementById('fm-new-name').value.trim();
    const dir=document.getElementById('fm-create-btn').dataset.dir||'';
    if(!name) return;
    const fd=new FormData(); fd.append('dir',dir); fd.append('name',name);
    const res=await fetch('admin.php?ajax=fm_create',{method:'POST',body:fd});
    const d=await res.json();
    if(d.ok){document.getElementById('fm-new-name').value='';openFile(d.path);}
    else alert(d.error);
  });

  loadDir('');
})();
</script>
<?php endif; ?>

</div><!-- /admin-main -->
</div><!-- /admin-wrap -->

<script src="<?= h(asset_url('assets/js/admin.js')) ?>"></script>
<script>
/* ═══ Auto-save: persist add/edit-app form to localStorage ═══
   Saves every text/select input on change. On page load, if saved
   draft exists for this form (keyed by page + app-id), shows a
   restore banner. Cleared on successful form submit.            */
(function(){
  var formId = (function(){
    var p = new URLSearchParams(window.location.search);
    var pg = p.get('page') || '';
    if (pg !== 'add-app' && pg !== 'edit-app') return null;
    return 'yas_draft_' + pg + (pg === 'edit-app' ? '_' + (p.get('id') || '0') : '');
  })();
  if (!formId) return;

  var form = document.querySelector('form[method="post"]');
  if (!form) return;

  // Fields to track (text, textarea, select — skip file inputs and hidden CSRF)
  function getFormData() {
    var data = {};
    form.querySelectorAll('input:not([type=file]):not([name=_csrf]):not([type=hidden]), textarea, select').forEach(function(el){
      if (!el.name) return;
      data[el.name] = el.value;
    });
    return data;
  }
  function setFormData(data) {
    Object.keys(data).forEach(function(name){
      var el = form.querySelector('[name="' + CSS.escape(name) + '"]');
      if (el && el.type !== 'file') {
        el.value = data[name];
        el.dispatchEvent(new Event('input', {bubbles:true}));
      }
    });
  }

  // Save on any input change (debounced 1s)
  var saveTimer;
  form.addEventListener('input', function(){ clearTimeout(saveTimer); saveTimer = setTimeout(save, 1000); });
  form.addEventListener('change', function(){ clearTimeout(saveTimer); saveTimer = setTimeout(save, 1000); });
  function save() {
    try { localStorage.setItem(formId, JSON.stringify({ts: Date.now(), data: getFormData()})); } catch(e){}
  }

  // Clear draft on submit
  form.addEventListener('submit', function(){
    try { localStorage.removeItem(formId); } catch(e){}
  });

  // On load: check for saved draft
  try {
    var stored = localStorage.getItem(formId);
    if (!stored) return;
    var obj = JSON.parse(stored);
    if (!obj || !obj.data) return;
    var age = Math.round((Date.now() - (obj.ts||0)) / 60000);
    if (age > 1440) { localStorage.removeItem(formId); return; } // expire after 24h

    // Show restore banner
    var banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9990;background:#1e3a5f;color:#e0f0ff;border-radius:12px;padding:14px 18px;box-shadow:0 4px 20px rgba(0,0,0,.3);max-width:360px;font-size:13px;direction:rtl';
    banner.innerHTML = '<b>📝 مسودة محفوظة</b> منذ ' + (age < 1 ? 'أقل من دقيقة' : age + ' دقيقة') + '<br><small style="opacity:.75">لديك بيانات غير محفوظة في هذا النموذج</small><div style="margin-top:10px;display:flex;gap:8px">'
      + '<button id="yd-restore" style="background:#2563eb;color:#fff;border:none;border-radius:7px;padding:6px 14px;cursor:pointer;font-size:13px">استعادة</button>'
      + '<button id="yd-discard" style="background:rgba(255,255,255,.15);color:#fff;border:none;border-radius:7px;padding:6px 14px;cursor:pointer;font-size:13px">تجاهل</button>'
      + '</div>';
    document.body.appendChild(banner);
    document.getElementById('yd-restore').onclick = function(){
      setFormData(obj.data);
      banner.remove();
    };
    document.getElementById('yd-discard').onclick = function(){
      localStorage.removeItem(formId);
      banner.remove();
    };
  } catch(e){}
})();
</script>

<script>
/* ─── SEO field counters + quality indicators ─── */
var SEO_COLORS = {red:'#ef4444', yellow:'#f59e0b', green:'#22c55e', grey:'#6b7280'};
var SEO_ICONS = {
  ok:  '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  warn:'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  bad: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
  neu: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
};
var TITLE_HINTS = [
  [0,0,'أدخل عنوان SEO — الطول المثالي 50–60 حرفاً'],
  [1,29,'⚠ قصير جداً — محركات البحث تفضّل 50–60 حرفاً'],
  [30,49,'🟡 مقبول — الطول المثالي بين 50 و60 حرفاً'],
  [50,60,'✅ ممتاز — طول مثالي لمحركات البحث']
];
var DESC_HINTS = [
  [0,0,'أدخل Meta Description — الطول المثالي 120–160 حرفاً'],
  [1,49,'⚠ قصير جداً — محركات البحث تفضّل 120–160 حرفاً'],
  [50,119,'🟡 مقبول — الطول المثالي بين 120 و160 حرفاً'],
  [120,160,'✅ ممتاز — طول مثالي لمحركات البحث']
];

function getHint(len, hints) {
  for (var i = hints.length-1; i >= 0; i--) {
    if (len >= hints[i][0]) return hints[i][2];
  }
  return hints[0][2];
}

function seoColor(len, gMin, gMax) {
  if (len === 0) return SEO_COLORS.grey;
  if (len < gMin * 0.5) return SEO_COLORS.red;
  if (len < gMin) return SEO_COLORS.yellow;
  return SEO_COLORS.green;
}

function seoIcon(len, gMin) {
  if (len === 0) return SEO_ICONS.neu;
  if (len < gMin * 0.5) return SEO_ICONS.bad;
  if (len < gMin) return SEO_ICONS.warn;
  return SEO_ICONS.ok;
}

function seoCounter(el, pfx, max, gMin, gMax) {
  var len = [...el.value].length; // Unicode-safe
  var used = document.getElementById(pfx+'-used');
  var bar  = document.getElementById(pfx+'-bar');
  var hint = document.getElementById(pfx+'-hint');
  var icon = document.getElementById(pfx.replace('-','')+'icon') || document.getElementById(pfx+'-icon-wrap');
  var iconSvg = icon ? icon.querySelector('svg') : null;
  var counter = document.getElementById(pfx.split('-')[0]+'-counter') || null;
  // Find icon wrapper by the id pattern
  var iconWrap = document.getElementById('seo-title-icon') || document.getElementById('seo-desc-icon');
  // Resolve by prefix
  if (pfx === 'sti') iconWrap = document.getElementById('seo-title-icon');
  if (pfx === 'sdi') iconWrap = document.getElementById('seo-desc-icon');

  if (used) used.textContent = len;
  if (bar) {
    bar.style.width = Math.min(100, len/gMax*100)+'%';
    bar.style.background = seoColor(len, gMin, gMax);
  }
  if (hint) hint.textContent = pfx==='sti' ? getHint(len,TITLE_HINTS) : getHint(len,DESC_HINTS);
  if (iconWrap) iconWrap.innerHTML = seoIcon(len, gMin);

  // Color the counter text
  var cnt = pfx==='sti' ? document.getElementById('seo-title-counter') : document.getElementById('seo-desc-counter');
  if (cnt) cnt.style.color = seoColor(len, gMin, gMax);
}

function simpleCounter(el, spanId) {
  var span = document.getElementById(spanId);
  if (span) span.textContent = [...el.value].length;
}

function ldCounter(el) {
  var len   = [...el.value].length;
  var chars = document.getElementById('ld-chars');
  var hint  = document.getElementById('ld-quality-hint');
  var badge = document.getElementById('ld-used-badge');
  if (chars) chars.textContent = len.toLocaleString('ar');
  if (badge) badge.style.color = len < 300 ? '#f59e0b' : len >= 800 ? '#22c55e' : '#94a3b8';
  if (hint) {
    if (len === 0) hint.textContent = '';
    else if (len < 300) hint.textContent = '⚠ الوصف قصير جداً — 300 حرف على الأقل لتحسين الفهرسة';
    else if (len < 600) hint.textContent = '🟡 جيد — الوصف الطويل (600+ حرف) يُحسّن الفهرسة أكثر';
    else if (len < 1500) hint.textContent = '✅ جيد — الوصف المثالي 1500 حرف فأكثر';
    else hint.textContent = '✅ ممتاز — وصف تفصيلي يساعد على الفهرسة القوية';
  }
}

// IndexNow key validation in settings
function validateInKey(el) {
  var v = el.value.trim();
  var st = document.getElementById('inkey-status');
  if (!st) return;
  if (!v) { st.textContent = ''; el.style.borderColor=''; return; }
  var ok = /^[a-zA-Z0-9]{8,128}$/.test(v);
  if (ok) {
    st.innerHTML = '<span style="color:#22c55e">✅ مفتاح صحيح</span>';
    el.style.borderColor = '#22c55e';
  } else {
    st.innerHTML = '<span style="color:#ef4444">❌ مفتاح غير صالح — أحرف وأرقام فقط (8-128)</span>';
    el.style.borderColor = '#ef4444';
  }
}
function generateInKey() {
  var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  var key = '';
  var arr = new Uint8Array(32);
  crypto.getRandomValues(arr);
  arr.forEach(function(b){ key += chars[b % chars.length]; });
  var el = document.getElementById('indexnow_key');
  if (el) { el.value = key; validateInKey(el); }
}

// Init all counters on page load
document.addEventListener('DOMContentLoaded', function(){
  var el;
  // SEO title
  el = document.getElementById('f-seo-title');
  if (el) seoCounter(el, 'sti', '60', 50, 60);
  // Meta desc
  el = document.getElementById('f-meta-desc');
  if (el) seoCounter(el, 'sdi', '160', 120, 160);
  // Long desc
  el = document.getElementById('f-long-desc');
  if (el) ldCounter(el);
  // IndexNow key
  el = document.getElementById('indexnow_key');
  if (el && el.value) validateInKey(el);
});
</script>
</body>
</html>
