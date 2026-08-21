<?php
/**
 * استيراد 30 تطبيقاً شهيراً (محتوى معد مسبقاً بلهجة سورية + SEO كامل).
 *
 * المرحلة 1 (GET): يعرض قائمة التطبيقات مع خانات تحديد.
 * المرحلة 2 (POST): يستورد المحدد فقط، يتخطى الموجود منها.
 *
 * الاستخدام: سجّل دخولك للوحة التحكم في نفس المتصفح، ثم افتح:
 *   https://yoursite.com/install/bulk-import-apps.php
 *
 * ملاحظة: يمكنك أيضاً استخدام صفحة "استيراد 30 تطبيقاً جاهزاً" من داخل لوحة
 * التحكم مباشرةً (admin.php?page=import-preset) بدون الحاجة لهذا الملف.
 * احذف مجلد install/ و هذا الملف بعد الانتهاء.
 */
require_once __DIR__ . '/../config.php';
require_admin();

header('Content-Type: text/html; charset=UTF-8');
set_time_limit(0);

$SLUG_MAP = [
    'واتساب'           => ['whatsapp',           'social'],
    'تيليجرام'         => ['telegram',           'social'],
    'فيسبوك'           => ['facebook',           'social'],
    'ماسنجر'           => ['messenger',          'social'],
    'سناب شات'         => ['snapchat',           'social'],
    'ديسكورد'          => ['discord',            'social'],
    'تويتر X'          => ['twitter-x',          'social'],
    'ثريدز'            => ['threads',            'social'],
    'لينكدإن'          => ['linkedin',           'social'],
    'بينترست'          => ['pinterest',          'social'],
    'جوجل كروم'        => ['google-chrome',      'tools'],
    'يوسي براوزر'      => ['uc-browser',         'tools'],
    'يوتيوب'           => ['youtube',            'apps'],
    'نتفليكس'          => ['netflix',            'apps'],
    'سبوتيفاي'         => ['spotify',            'apps'],
    'شازام'            => ['shazam',             'apps'],
    'ساوند كلاود'      => ['soundcloud',         'apps'],
    'أوبرا ميني'       => ['opera-mini',         'tools'],
    'ترو كولر'         => ['truecaller',         'tools'],
    'جوجل ترانسليت'    => ['google-translate',   'tools'],
    'زوم'              => ['zoom',               'tools'],
    'مايكروسوفت تيمز'  => ['microsoft-teams',   'tools'],
    'دوولينجو'         => ['duolingo',           'productivity'],
    'WPS Office'       => ['wps-office',         'productivity'],
    'أدوبي أكروبات ريدر'=> ['adobe-acrobat-reader','productivity'],
    'كانفا'            => ['canva',              'design'],
    'بيكس آرت'         => ['picsart',            'design'],
    'إن شوت'           => ['inshot',             'design'],
    'بابجي موبايل'     => ['pubg-mobile',        'games'],
    'فري فاير'         => ['free-fire',          'games'],
    // ── دفعة الذكاء الاصطناعي 2026 (batch-ai2026.json) ──
    // بدون هذه المطابقات يولّد slugify() من الاسم العربي مسارات غير مفهومة
    // ("tshat-jy-by-ty" لـ ChatGPT، "klwd" لـ Claude) — أي مسارات لا يبحث عنها
    // أحد وتحصل على تقييم منخفض في فاحص المسارات.
    'تشات جي بي تي'      => ['chatgpt',            'productivity'],
    'جيميناي'          => ['gemini',             'productivity'],
    'كلود'              => ['claude-ai',          'productivity'],
    // نفس تهجئة الاسم الموجود في batch-x.json حتى يعمل تخطّي المكرر بالاسم
    'مايكروسوفت كوبيلوت' => ['microsoft-copilot',  'productivity'],
    'كوبوز'             => ['qobuz',              'music'],
    'بيربلكسيتي'      => ['perplexity-ai',       'productivity'],
    'ديب سيك'           => ['deepseek',           'productivity'],
    'جروك'              => ['grok-ai',            'productivity'],
    'كاراكتر AI'       => ['character-ai',        'productivity'],
];

$catLabels = [
    'social'=>'تواصل اجتماعي','tools'=>'أدوات','apps'=>'تطبيقات',
    'productivity'=>'إنتاجية','design'=>'تصميم','games'=>'ألعاب',
];

// ─── Load all apps from data files ───
$dataDir = __DIR__ . '/bulk-apps-data';
$files = glob($dataDir . '/batch-*.json');
sort($files);

$allApps = [];
foreach ($files as $file) {
    $batch = json_decode((string)file_get_contents($file), true);
    if (!$batch || empty($batch['apps'])) continue;
    foreach ($batch['apps'] as $app) {
        $name = trim($app['name'] ?? '');
        if ($name) $allApps[$name] = $app;
    }
}

// ─── Phase 2: Import ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected'])) {

    $selected = array_map('trim', (array)$_POST['selected']);
    $selected = array_filter($selected, fn($n) => isset($allApps[$n]));

    ignore_user_abort(false);

    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<title>استيراد التطبيقات</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;font-size:14px;line-height:1.9;margin:0}
.ok{color:#4ade80}.err{color:#f87171}.skip{color:#fbbf24}.head{color:#38bdf8;font-weight:bold}
.log{max-width:760px}
#bar-wrap{max-width:760px;height:8px;background:#1e293b;border-radius:6px;margin:14px 0;overflow:hidden}
#bar{height:100%;width:0%;background:linear-gradient(135deg,#2563eb,#7c3aed);transition:width .3s}
a{color:#38bdf8}
</style></head><body>
<div class="head" style="font-size:16px;margin-bottom:8px">🚀 بدء استيراد ' . count($selected) . ' تطبيق...</div>
<div id="bar-wrap"><div id="bar"></div></div>
<div class="log">';
    @ob_flush(); @flush();

    $catCache = [];
    $getCatId = function (string $slug) use ($pdo, &$catCache) {
        if (isset($catCache[$slug])) return $catCache[$slug];
        $s = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
        $s->execute([$slug]);
        return $catCache[$slug] = ($s->fetchColumn() ?: null);
    };

    $total = count($selected);
    $done = 0; $created = 0; $skipped = 0; $failed = 0;

    foreach ($selected as $name) {
        $data = $allApps[$name] ?? null;
        if (!$data) { $failed++; $done++; continue; }

        $ex = $pdo->prepare("SELECT COUNT(*) FROM apps WHERE name=?");
        $ex->execute([$name]);
        if ($ex->fetchColumn() > 0) {
            echo '<div class="skip">⏭ تخطّي "' . h($name) . '" — موجود مسبقاً</div>';
            $skipped++; $done++;
            $pct = round($done / $total * 100);
            echo '<script>document.getElementById("bar").style.width="' . $pct . '%"</script>';
            @ob_flush(); @flush();
            continue;
        }

        [$slugBase, $catSlug] = $SLUG_MAP[$name] ?? [slugify($name), 'apps'];
        $slug  = unique_slug($pdo, $slugBase);
        $catId = $getCatId($catSlug) ?: $getCatId('apps');

        echo '<div>⏳ ' . h($name) . ' — جاري البحث عن الأيقونة على Google Play...</div>';
        @ob_flush(); @flush();

        $playstoreUrl = playstore_search($name . ' ' . ($data['developer'] ?? ''));
        if (!$playstoreUrl) $playstoreUrl = playstore_search($name);
        $iconPath = null; $packageName = null;
        if ($playstoreUrl) {
            $meta = fetch_playstore_meta($playstoreUrl);
            if ($meta) {
                $packageName = $meta['package_name'] ?? null;
                if (!empty($meta['icon_url'])) $iconPath = import_remote_icon($meta['icon_url'], $slug);
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
                    json_encode($features, JSON_UNESCAPED_UNICODE),
                    json_encode($pros, JSON_UNESCAPED_UNICODE),
                    json_encode($cons, JSON_UNESCAPED_UNICODE),
                    json_encode($installSteps, JSON_UNESCAPED_UNICODE),
                    json_encode($faq, JSON_UNESCAPED_UNICODE),
                    trim($data['whats_new'] ?? ''),
                    $playstoreUrl, $packageName, 4.5,
                    trim($data['seo_title'] ?? ''),
                    trim($data['meta_description'] ?? ''),
                    trim($data['keywords'] ?? ''),
                ]);
            $created++;
            $editId = $pdo->lastInsertId();
            $icon = $iconPath ? '✓ أيقونة' : 'بدون أيقونة';
            echo '<div class="ok">✅ ' . h($name)
                . ' — تم الإنشاء (' . $icon . ')'
                . ' — <a href="../admin.php?page=edit-app&id=' . $editId . '" target="_blank">تعديل</a></div>';
        } catch (Throwable $e) {
            $failed++;
            echo '<div class="err">✗ فشل "' . h($name) . '": ' . h($e->getMessage()) . '</div>';
        }

        $done++;
        $pct = round($done / $total * 100);
        echo '<script>document.getElementById("bar").style.width="' . $pct . '%"</script>';
        @ob_flush(); @flush();
        usleep(300000);
    }

    echo '</div>'; // .log

    echo '<div class="head" style="margin-top:20px;max-width:760px">🏁 اكتمل الاستيراد'
        . ' — تم إنشاء ' . $created . ' تطبيق'
        . ($skipped ? " | تخطّي {$skipped}" : '')
        . ($failed  ? " | فشل {$failed}"   : '') . '</div>';

    echo '<div style="margin-top:12px;color:#fbbf24;max-width:760px">⚠️ كل التطبيقات مسودات — أضف روابط تحميل ثم انشرها.</div>';
    echo '<div style="margin-top:6px;color:#f87171;max-width:760px">⚠️ احذف الآن: install/bulk-import-apps.php و install/bulk-apps-data/</div>';
    echo '<div style="margin-top:14px"><a href="../admin.php?page=apps">→ إدارة التطبيقات</a>'
        . ' &nbsp; <a href="bulk-import-apps.php">← العودة للقائمة</a></div>';
    echo '</body></html>';
    exit;
}

// ─── Phase 1: Selection UI ───
$existingNames = [];
$stmt = $pdo->query("SELECT name FROM apps");
while ($row = $stmt->fetch()) $existingNames[] = $row['name'];

$catCounts = [];
foreach ($allApps as $name => $app) {
    $cs = $SLUG_MAP[$name][1] ?? 'apps';
    $cl = $catLabels[$cs] ?? $cs;
    $catCounts[$cl] = ($catCounts[$cl] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>استيراد التطبيقات الجاهزة — <?= count($allApps) ?> تطبيق</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;padding:24px}
.wrap{max-width:900px;margin:0 auto}
h1{font-size:22px;font-weight:800;color:#38bdf8;margin-bottom:6px}
.sub{color:#94a3b8;font-size:13px;margin-bottom:24px;line-height:1.8}
.controls{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:20px;background:#1e293b;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px}
.controls label{font-size:12px;color:#94a3b8;display:block;margin-bottom:4px}
.controls input,.controls select{background:#0f172a;border:1px solid rgba(255,255,255,.12);color:#e2e8f0;padding:8px 12px;border-radius:8px;font-size:13px;font-family:inherit}
.controls input[type=number]{width:100px}
.btn{padding:9px 18px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:background .15s}
.btn-primary{background:#2563eb;color:#fff}.btn-primary:hover{background:#1d4ed8}
.btn-outline{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.12)}.btn-outline:hover{background:rgba(255,255,255,.1)}
.btn-danger{background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.2)}.btn-danger:hover{background:rgba(248,113,113,.25)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:20px}
.card{background:#1e293b;border:1.5px solid rgba(255,255,255,.07);border-radius:12px;padding:14px 16px;cursor:pointer;transition:border-color .15s,box-shadow .15s;user-select:none;display:flex;gap:11px}
.card:hover{border-color:#2563eb;box-shadow:0 2px 12px rgba(37,99,235,.12)}
.card.sel{border-color:#2563eb;background:rgba(37,99,235,.08)}
.card.exist{opacity:.45;cursor:default}
.cb{width:18px;height:18px;border:2px solid rgba(255,255,255,.25);border-radius:4px;flex-shrink:0;margin-top:2px;position:relative;transition:.15s}
.card.sel .cb{background:#2563eb;border-color:#2563eb}
.card.sel .cb::after{content:'';position:absolute;left:3px;top:1px;width:8px;height:5px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg)}
.card-body{flex:1;min-width:0}
.card-name{font-weight:700;font-size:13px;color:#f1f5f9;margin-bottom:3px}
.card-dev{font-size:11px;color:#64748b;margin-bottom:5px}
.tag{display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px}
.tag-cat{background:rgba(37,99,235,.15);color:#60a5fa}
.tag-ex{background:rgba(74,222,128,.12);color:#4ade80;margin-right:5px}
.card-desc{font-size:11px;color:#64748b;margin-top:6px;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.bottom{background:#1e293b;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
#sel-info{font-size:13px;color:#94a3b8}
.notice{margin-top:20px;padding:14px 16px;border-radius:10px;font-size:13px;line-height:1.8}
.warn{background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.2);color:#fbbf24}
.info{background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.2);color:#60a5fa;margin-top:10px}
a{color:#38bdf8}
@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
<h1>📦 استيراد <?= count($allApps) ?> تطبيقاً جاهزاً</h1>
<p class="sub">
  محتوى عربي سوري كامل معدٌّ مسبقاً لكل تطبيق (وصف طويل + ميزات + FAQ + SEO).
  حدّد ما تريد استيراده — الموجود مسبقاً مُخطَّط عليه ومُستثنى.
  <br>أو استخدم <a href="../admin.php?page=import-preset">صفحة الاستيراد داخل لوحة التحكم</a> مباشرةً بدون هذا الملف.
</p>

<!-- Controls -->
<div class="controls">
  <div>
    <label>عدد الاستيراد (اختياري — فارغ = الكل المحدد)</label>
    <input type="number" id="ctrl-count" min="1" max="<?= count($allApps) ?>" placeholder="الكل">
  </div>
  <div>
    <label>تصفية حسب التصنيف</label>
    <select id="ctrl-cat">
      <option value="">كل التصنيفات (<?= count($allApps) ?>)</option>
      <?php foreach ($catCounts as $label => $n): ?>
      <option value="<?= h($label) ?>"><?= h($label) ?> (<?= $n ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="button" class="btn btn-outline" id="btn-all">تحديد الجديدة فقط</button>
  <button type="button" class="btn btn-danger" id="btn-none">إلغاء الكل</button>
</div>

<!-- App grid -->
<form method="post" id="frm">
<?php
$catOrder = ['تواصل اجتماعي','تطبيقات','أدوات','إنتاجية','تصميم','ألعاب'];
$bycat = [];
foreach ($allApps as $name => $app) {
    $cs  = $SLUG_MAP[$name][1] ?? 'apps';
    $cl  = $catLabels[$cs] ?? $cs;
    $bycat[$cl][] = ['name'=>$name,'app'=>$app,'catslug'=>$cs];
}
$order = array_merge($catOrder, array_diff(array_keys($bycat), $catOrder));
?>
<div class="grid" id="grid">
<?php foreach ($order as $catLabel):
    if (!isset($bycat[$catLabel])) continue;
    foreach ($bycat[$catLabel] as $item):
        $name  = $item['name'];
        $app   = $item['app'];
        $exist = in_array($name, $existingNames, true);
        $cl    = $catLabel;
?>
<div class="card<?= $exist?' exist':'' ?>" data-name="<?= h($name) ?>" data-cat="<?= h($cl) ?>">
  <div class="cb"></div>
  <div class="card-body">
    <div class="card-name"><?= h($name) ?></div>
    <div class="card-dev"><?= h($app['developer'] ?? '') ?></div>
    <div>
      <span class="tag tag-cat"><?= h($cl) ?></span>
      <?php if ($exist): ?><span class="tag tag-ex">✓ موجود</span><?php endif; ?>
    </div>
    <?php if (!$exist): ?>
    <div class="card-desc"><?= h($app['short_description'] ?? '') ?></div>
    <?php endif; ?>
    <?php if (!$exist): ?>
    <input type="checkbox" name="selected[]" value="<?= h($name) ?>" class="app-chk" style="display:none">
    <?php endif; ?>
  </div>
</div>
<?php endforeach; endforeach; ?>
</div>

<div class="bottom">
  <button type="submit" class="btn btn-primary" id="btn-submit">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    ابدأ الاستيراد
  </button>
  <span id="sel-info">لم يتم التحديد</span>
</div>
</form>

<div class="notice warn">⚠️ كل التطبيقات المستوردة تُحفظ كمسودات — راجعها وأضف روابط التحميل ثم انشرها.</div>
<div class="notice info">💡 تلميح: يمكنك الاستيراد من داخل لوحة التحكم من <a href="../admin.php?page=import-preset">صفحة الاستيراد الجاهز</a> بدون أن تحذف هذا الملف لاحقاً.</div>
</div>

<script>
const cards=[...document.querySelectorAll('.card:not(.exist)')];
function updateInfo(){
  const n=document.querySelectorAll('.app-chk:checked').length;
  const info=document.getElementById('sel-info');
  const btn=document.getElementById('btn-submit');
  info.textContent=n?n+' تطبيق محدد للاستيراد':'لم يتم تحديد أي تطبيق';
  btn.disabled=!n;
}
function syncCard(card){
  const chk=card.querySelector('.app-chk');
  if(chk&&chk.checked)card.classList.add('sel');
  else card.classList.remove('sel');
}
cards.forEach(c=>{
  c.addEventListener('click',()=>{
    const chk=c.querySelector('.app-chk');
    if(!chk)return;
    chk.checked=!chk.checked;
    syncCard(c);
    applyCountLimit();
    updateInfo();
  });
});
function applyCountLimit(){
  const n=parseInt(document.getElementById('ctrl-count').value)||0;
  if(!n)return;
  const checked=[...document.querySelectorAll('.app-chk:checked')];
  if(checked.length>n){
    checked.slice(n).forEach(chk=>{chk.checked=false;syncCard(chk.closest('.card'));});
  }
}
function getVisible(){
  const cat=document.getElementById('ctrl-cat').value;
  return cards.filter(c=>!cat||c.dataset.cat===cat);
}
document.getElementById('btn-all').addEventListener('click',()=>{
  const n=parseInt(document.getElementById('ctrl-count').value)||0;
  let added=0;
  getVisible().forEach(c=>{
    const chk=c.querySelector('.app-chk');
    if(!chk)return;
    if(n&&added>=n){chk.checked=false;syncCard(c);return;}
    chk.checked=true;syncCard(c);added++;
  });
  updateInfo();
});
document.getElementById('btn-none').addEventListener('click',()=>{
  cards.forEach(c=>{const chk=c.querySelector('.app-chk');if(chk)chk.checked=false;c.classList.remove('sel');});
  updateInfo();
});
document.getElementById('ctrl-cat').addEventListener('change',()=>{
  const cat=document.getElementById('ctrl-cat').value;
  cards.forEach(c=>{c.style.display=(!cat||c.dataset.cat===cat)?'':'none';});
});
document.getElementById('ctrl-count').addEventListener('input',()=>{applyCountLimit();updateInfo();});
document.getElementById('frm').addEventListener('submit',e=>{
  const n=document.querySelectorAll('.app-chk:checked').length;
  if(!n){e.preventDefault();alert('حدّد تطبيقاً واحداً على الأقل');}
});
updateInfo();
</script>
</body>
</html>
