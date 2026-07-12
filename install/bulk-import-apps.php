<?php
/**
 * استيراد 30 تطبيقاً شهيراً دفعة واحدة (محتوى معد مسبقاً بلهجة سورية + SEO كامل).
 * لا يلمس أي تطبيق موجود مسبقاً إطلاقاً — فقط يضيف تطبيقات جديدة كمسودات (draft).
 * يحاول تلقائياً جلب الأيقونة الحقيقية من Google Play لكل تطبيق (يحتاج اتصال
 * إنترنت صادر من السيرفر نفسه — لن يعمل من بيئة معزولة بدون إنترنت).
 *
 * الاستخدام: سجّل دخولك للوحة التحكم في نفس المتصفح، ثم افتح:
 *   https://yoursite.com/install/bulk-import-apps.php
 * احذف مجلد install/bulk-apps-data و ملف bulk-import-apps.php فوراً بعد الانتهاء.
 */
require_once __DIR__ . '/../config.php';
require_admin();

header('Content-Type: text/html; charset=UTF-8');
set_time_limit(0);
ignore_user_abort(false);

echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<title>استيراد التطبيقات</title>
<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;font-size:14px;line-height:1.9}
.ok{color:#4ade80}.err{color:#f87171}.skip{color:#fbbf24}.head{color:#38bdf8;font-weight:bold}
</style></head><body>';
echo '<div class="head">🚀 بدء استيراد التطبيقات...</div>';
@ob_flush(); @flush();

$dataDir = __DIR__ . '/bulk-apps-data';
$files = glob($dataDir . '/batch-*.json');
sort($files);

if (!$files) {
    die('<div class="err">لم يتم العثور على أي ملفات بيانات في ' . h($dataDir) . '</div></body></html>');
}

// name(Arabic) => [slug, category_slug] — fixed mapping so URLs stay clean English SEO slugs
// instead of the Arabic-derived fallback slugify() would otherwise produce.
$slugMap = [
    'واتساب' => ['whatsapp', 'social'], 'تيليجرام' => ['telegram', 'social'],
    'فيسبوك' => ['facebook', 'social'], 'ماسنجر' => ['messenger', 'social'],
    'سناب شات' => ['snapchat', 'social'], 'ديسكورد' => ['discord', 'social'],
    'تويتر X' => ['twitter-x', 'social'], 'ثريدز' => ['threads', 'social'],
    'لينكدإن' => ['linkedin', 'social'], 'بينترست' => ['pinterest', 'social'],
    'جوجل كروم' => ['google-chrome', 'tools'], 'يوسي براوزر' => ['uc-browser', 'tools'],
    'يوتيوب' => ['youtube', 'apps'], 'نتفليكس' => ['netflix', 'apps'],
    'سبوتيفاي' => ['spotify', 'apps'], 'شازام' => ['shazam', 'apps'],
    'ساوند كلاود' => ['soundcloud', 'apps'], 'أوبرا ميني' => ['opera-mini', 'tools'],
    'ترو كولر' => ['truecaller', 'tools'], 'جوجل ترانسليت' => ['google-translate', 'tools'],
    'زوم' => ['zoom', 'tools'], 'مايكروسوفت تيمز' => ['microsoft-teams', 'tools'],
    'دوولينجو' => ['duolingo', 'productivity'], 'WPS Office' => ['wps-office', 'productivity'],
    'أدوبي أكروبات ريدر' => ['adobe-acrobat-reader', 'productivity'], 'كانفا' => ['canva', 'design'],
    'بيكس آرت' => ['picsart', 'design'], 'إن شوت' => ['inshot', 'design'],
    'بابجي موبايل' => ['pubg-mobile', 'games'], 'فري فاير' => ['free-fire', 'games'],
];

$catCache = [];
$getCatId = function (string $slug) use ($pdo, &$catCache) {
    if (isset($catCache[$slug])) return $catCache[$slug];
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $stmt->execute([$slug]);
    return $catCache[$slug] = ($stmt->fetchColumn() ?: null);
};

$created = 0; $skipped = 0; $failed = 0;

foreach ($files as $file) {
    $batch = json_decode(file_get_contents($file), true);
    if (!$batch || empty($batch['apps'])) {
        echo '<div class="err">✗ ملف غير صالح: ' . h(basename($file)) . '</div>';
        @ob_flush(); @flush();
        continue;
    }

    foreach ($batch['apps'] as $data) {
        $name = trim($data['name'] ?? '');
        if (!$name) { $failed++; continue; }

        // Never touch an existing app — exact-name match skip.
        $exists = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE name=?");
        $exists->execute([$name]);
        if ($exists->fetchColumn() > 0) {
            echo '<div class="skip">⏭ تم تخطي "' . h($name) . '" — موجود مسبقاً</div>';
            @ob_flush(); @flush();
            $skipped++;
            continue;
        }

        [$slugBase, $catSlug] = $slugMap[$name] ?? [slugify($name), 'apps'];
        $slug  = unique_slug($pdo, $slugBase);
        $catId = $getCatId($catSlug) ?: $getCatId('apps');

        echo '<div>⏳ ' . h($name) . ' — جاري البحث عن الأيقونة على Google Play...</div>';
        @ob_flush(); @flush();

        // Best-effort real Play Store icon — a miss here is normal, not fatal.
        $playstoreUrl = playstore_search($name . ' ' . ($data['developer'] ?? ''));
        if (!$playstoreUrl) $playstoreUrl = playstore_search($name);
        $iconPath = null; $packageName = null;
        if ($playstoreUrl) {
            $meta = fetch_playstore_meta($playstoreUrl);
            if ($meta) {
                $packageName = $meta['package_name'] ?? null;
                if (!empty($meta['icon_url'])) {
                    $iconPath = import_remote_icon($meta['icon_url'], $slug);
                }
            }
        }

        $features     = array_values(array_filter($data['features'] ?? []));
        $pros         = array_values(array_filter($data['pros'] ?? []));
        $cons         = array_values(array_filter($data['cons'] ?? []));
        $installSteps = array_values(array_filter($data['install_steps'] ?? []));
        $faq          = array_is_list($data['faq'] ?? []) ? $data['faq'] : [];

        try {
            $pdo->prepare("INSERT INTO apps
                (name,slug,category_id,developer,license,icon_path,short_description,long_description,
                 features,pros,cons,install_steps,faq,whats_new,playstore_url,package_name,rating,
                 seo_title,meta_description,keywords,status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft')")
                ->execute([
                    $name, $slug, $catId, trim($data['developer'] ?? ''), 'Free', $iconPath,
                    trim($data['short_description'] ?? ''), trim($data['long_description'] ?? ''),
                    json_encode($features, JSON_UNESCAPED_UNICODE), json_encode($pros, JSON_UNESCAPED_UNICODE),
                    json_encode($cons, JSON_UNESCAPED_UNICODE), json_encode($installSteps, JSON_UNESCAPED_UNICODE),
                    json_encode($faq, JSON_UNESCAPED_UNICODE), trim($data['whats_new'] ?? ''),
                    $playstoreUrl, $packageName, 4.5,
                    trim($data['seo_title'] ?? ''), trim($data['meta_description'] ?? ''), trim($data['keywords'] ?? ''),
                ]);
            $created++;
            $iconNote = $iconPath ? 'أيقونة ✓' : 'بدون أيقونة (أضفها يدوياً أو استعمل "استيراد Play Store" من صفحة التعديل)';
            echo '<div class="ok">✅ ' . h($name) . ' — تم الإنشاء (' . $iconNote . ') — <a href="../admin.php?page=edit-app&id=' . $pdo->lastInsertId() . '" target="_blank" style="color:#38bdf8">فتح للتعديل</a></div>';
        } catch (Throwable $e) {
            $failed++;
            echo '<div class="err">✗ فشل إنشاء "' . h($name) . '": ' . h($e->getMessage()) . '</div>';
        }
        @ob_flush(); @flush();
        usleep(300000); // brief pause between apps — polite to the Play Store scrape
    }
}

echo '<div class="head" style="margin-top:20px">🏁 اكتمل الاستيراد — تم إنشاء ' . $created . ' تطبيق'
    . ($skipped ? "، تخطي {$skipped} (موجود مسبقاً)" : '')
    . ($failed ? "، فشل {$failed}" : '') . '</div>';
echo '<div style="margin-top:14px;color:#fbbf24">⚠️ كل التطبيقات المُنشأة هي مسودات (draft) — راجعها من "إدارة التطبيقات"، أضف رابط تحميل مباشر فعلي لكل واحد، ثم انشرها.</div>';
echo '<div style="margin-top:6px;color:#f87171">⚠️ احذف الآن: install/bulk-import-apps.php و install/bulk-apps-data/ بالكامل من السيرفر.</div>';
echo '<div style="margin-top:14px"><a href="../admin.php?page=apps" style="color:#38bdf8">→ الذهاب لقائمة التطبيقات</a></div>';
echo '</body></html>';
