<?php
/**
 * deploy.php — تحديث الموقع مباشرة من GitHub
 *
 * WHAT IT DOES
 * ------------
 * Downloads a branch snapshot of this repository straight from GitHub and
 * writes it over the site files, so changes pushed to the repo land on the
 * live server without FTP, SSH, or a git checkout on the host. The repository
 * is public, so no GitHub credentials are involved at any point.
 *
 * SAFETY
 * ------
 *  - Gated by DEPLOY_TOKEN (set it in config.local.php). Refuses to run while
 *    the token is missing or left at the placeholder, so an unconfigured
 *    upload is inert rather than an open door onto the filesystem.
 *  - The source is pinned to one owner/repo constant below. No user input ever
 *    reaches the download URL, so this cannot be pointed at another codebase.
 *  - PROTECTED_PATHS holds the paths a deploy must never clobber: local credentials,
 *    user uploads, caches, logs. They are skipped even when present in the
 *    archive.
 *  - "معاينة" (dry run) lists every add/overwrite without touching a file.
 *  - Each run writes a backup of the files it replaces into .deploy-backup/.
 *
 * SETUP (once)
 * ------------
 *   1. In config.local.php add:
 *        define('DEPLOY_TOKEN', '<long-random-string>');
 *   2. Open: https://yassota.com/deploy.php?token=<that-string>
 */

declare(strict_types=1);
@set_time_limit(300);
@ini_set('memory_limit', '256M');

const REPO_OWNER   = 'shwybs3';
const REPO_NAME    = 'sham';
const DEFAULT_REF  = 'claude/app-store-platform-rebuild-l83ig3';

/** Never overwritten, even if the archive contains them. */
const PROTECTED_PATHS = [
    'config.local.php',
    '.cpanel.php',
    'deploy.php',          // don't yank the script out from under itself
    'uploads/',
    'static-cache/',
    'db-error.log',
    '.env',
];

$root = __DIR__;

/* ── Auth ──────────────────────────────────────────────────────── */
$configured = false;
$localCfg = $root . '/config.local.php';
if (is_file($localCfg)) { require_once $localCfg; }
if (defined('DEPLOY_TOKEN') && is_string(DEPLOY_TOKEN) && strlen(DEPLOY_TOKEN) >= 16
    && DEPLOY_TOKEN !== 'CHANGE_ME') {
    $configured = true;
}

$given = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$authed = $configured && $given !== '' && hash_equals(DEPLOY_TOKEN, $given);

function page(string $title, string $html, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">',
         '<title>', htmlspecialchars($title), '</title>',
         '<meta name="viewport" content="width=device-width,initial-scale=1">',
         '<meta name="robots" content="noindex,nofollow">',
         '<style>',
         ':root{--bg:#0b1120;--surface:#141d33;--line:#243352;--ink:#e9eefb;--muted:#8a9ab8;',
         '--accent:#38bdf8;--ok:#4ade80;--warn:#fbbf24;--err:#fca5a5}',
         '*{box-sizing:border-box;margin:0;padding:0}',
         'body{background:var(--bg);color:var(--ink);direction:rtl;line-height:1.75;',
         "font-family:'Cairo',system-ui,-apple-system,'Segoe UI',Tahoma,sans-serif;padding:0 0 50px}",
         '.top{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 20px}',
         '.top h1{font-size:16px;font-weight:800;max-width:820px;margin:0 auto}',
         '.wrap{max-width:820px;margin:0 auto;padding:24px 20px;display:flex;flex-direction:column;gap:18px}',
         '.card{background:var(--surface);border:1px solid var(--line);border-radius:13px;padding:22px}',
         '.card h2{font-size:14px;font-weight:800;margin-bottom:5px}',
         '.sub{font-size:12.5px;color:var(--muted);margin-bottom:15px}',
         'label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px}',
         'input,select{width:100%;background:var(--bg);color:var(--ink);border:1px solid var(--line);',
         'border-radius:9px;padding:10px 12px;font:inherit;font-size:13.5px}',
         'button{background:var(--accent);color:#08131f;border:none;border-radius:9px;padding:11px 24px;',
         'font:inherit;font-size:13.5px;font-weight:800;cursor:pointer}',
         'button.ghost{background:transparent;color:var(--accent);border:1px solid var(--line)}',
         'button:hover{opacity:.9}',
         '.row{display:flex;gap:10px;flex-wrap:wrap;margin-top:15px}',
         '.banner{border-radius:9px;padding:12px 16px;font-size:13.5px;font-weight:600}',
         '.banner.ok{background:rgba(34,197,94,.1);color:var(--ok)}',
         '.banner.warn{background:rgba(251,191,36,.1);color:var(--warn)}',
         '.banner.err{background:rgba(220,38,38,.12);color:var(--err)}',
         '.log{background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:13px;',
         'max-height:420px;overflow:auto;direction:ltr;text-align:left;',
         'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;line-height:1.85;color:var(--muted)}',
         '.log .a{color:var(--ok)}.log .u{color:var(--accent)}.log .s{color:var(--muted);opacity:.65}',
         'code{background:rgba(56,189,248,.1);padding:2px 7px;border-radius:5px;font-size:12px;direction:ltr;display:inline-block}',
         'ol{padding-inline-start:20px;font-size:13px;color:var(--muted)}ol li{margin-bottom:8px}',
         '.stat{display:flex;gap:22px;flex-wrap:wrap;margin-bottom:14px}',
         '.stat div{font-size:12px;color:var(--muted)}',
         '.stat b{display:block;font-size:21px;color:var(--ink);font-variant-numeric:tabular-nums}',
         '</style></head><body>',
         '<div class="top"><h1>🚀 نشر التحديثات من GitHub</h1></div>',
         '<div class="wrap">', $html, '</div></body></html>';
    exit;
}

if (!$configured) {
    page('إعداد مطلوب',
        '<div class="banner warn">لم يُضبط رمز النشر بعد — الأداة معطّلة.</div>'
      . '<div class="card"><h2>خطوة واحدة للتفعيل</h2>'
      . '<p class="sub">أضف السطر التالي إلى ملف <code>config.local.php</code> على الخادم، '
      . 'مع استبدال القيمة بسلسلة عشوائية طويلة (32 حرفاً فأكثر):</p>'
      . '<div class="log"><span class="u">define(\'DEPLOY_TOKEN\', \''
      . htmlspecialchars(bin2hex(random_bytes(20))) . '\');</span></div>'
      . '<p class="sub" style="margin-top:14px">ثم افتح: '
      . '<code>/deploy.php?token=الرمز</code></p></div>', 403);
}

if (!$authed) {
    page('رمز غير صحيح',
        '<div class="banner err">🔒 رمز النشر غير صحيح أو مفقود.</div>'
      . '<div class="card"><form method="get">'
      . '<label for="t">رمز النشر</label>'
      . '<input type="password" name="token" id="t" autofocus autocomplete="off">'
      . '<div class="row"><button type="submit">دخول</button></div>'
      . '</form></div>', 403);
}

/* ── Helpers ───────────────────────────────────────────────────── */
function is_protected(string $rel): bool {
    foreach (PROTECTED_PATHS as $p) {
        if (str_ends_with($p, '/')) { if (str_starts_with($rel, $p)) return true; }
        elseif ($rel === $p) return true;
    }
    return false;
}

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($dir);
}

/* ── Run ───────────────────────────────────────────────────────── */
$ref    = preg_replace('#[^A-Za-z0-9._/\-]#', '', (string)($_POST['ref'] ?? DEFAULT_REF)) ?: DEFAULT_REF;
$dryRun = ($_POST['mode'] ?? '') !== 'apply';
$doRun  = ($_SERVER['REQUEST_METHOD'] === 'POST');

if (!$doRun) {
    page('نشر التحديثات',
        '<div class="card"><h2>سحب أحدث نسخة من GitHub</h2>'
      . '<p class="sub">تُنزَّل نسخة الفرع المحدّد وتُكتب فوق ملفات الموقع. '
      . 'الملفات المحميّة (<code>config.local.php</code>، <code>uploads/</code>، الكاش والسجلات) لا تُمس أبداً.</p>'
      . '<form method="post">'
      . '<input type="hidden" name="token" value="' . htmlspecialchars($given, ENT_QUOTES) . '">'
      . '<label for="ref">الفرع</label>'
      . '<input type="text" name="ref" id="ref" value="' . htmlspecialchars(DEFAULT_REF, ENT_QUOTES) . '">'
      . '<div class="row">'
      . '<button type="submit" name="mode" value="dry" class="ghost">👁 معاينة بدون تطبيق</button>'
      . '<button type="submit" name="mode" value="apply">⬇ تنزيل وتطبيق</button>'
      . '</div></form></div>'
      . '<div class="card"><h2>ما الذي يحدث عند التطبيق</h2><ol>'
      . '<li>تنزيل أرشيف الفرع من GitHub (المستودع عام — بدون مفاتيح).</li>'
      . '<li>فكّ الأرشيف في مجلد مؤقّت.</li>'
      . '<li>نسخ احتياطي للملفات التي ستُستبدل إلى <code>.deploy-backup/</code>.</li>'
      . '<li>كتابة الملفات الجديدة مع تخطّي كل مسار محميّ.</li>'
      . '<li>حذف المجلد المؤقّت.</li>'
      . '</ol></div>');
}

$log = [];
$url = 'https://codeload.github.com/' . REPO_OWNER . '/' . REPO_NAME . '/zip/refs/heads/' . $ref;
$tmp = $root . '/.deploy-tmp';
$zipPath = $root . '/.deploy-' . bin2hex(random_bytes(4)) . '.zip';

rrmdir($tmp);

// 1. Download
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_USERAGENT      => 'yassota-deploy',
]);
$zipData = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($zipData === false || $httpCode !== 200) {
    page('فشل التنزيل',
        '<div class="banner err">تعذّر تنزيل الفرع <code>' . htmlspecialchars($ref) . '</code> '
      . '(رمز ' . $httpCode . ($curlErr ? ' — ' . htmlspecialchars($curlErr) : '') . ').</div>'
      . '<div class="card"><p class="sub">تأكّد من صحة اسم الفرع، ومن أن الخادم يسمح بالاتصال الخارجي عبر cURL.</p>'
      . '<div class="row"><a href="?token=' . htmlspecialchars($given, ENT_QUOTES) . '"><button class="ghost">← رجوع</button></a></div></div>', 502);
}

file_put_contents($zipPath, $zipData);
unset($zipData);

// 2. Extract
if (!class_exists('ZipArchive')) {
    @unlink($zipPath);
    page('إضافة ناقصة', '<div class="banner err">إضافة ZipArchive غير مفعّلة على الخادم — فعّلها من cPanel ← Select PHP Version ← Extensions ← zip.</div>', 500);
}
$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    @unlink($zipPath);
    page('أرشيف تالف', '<div class="banner err">تعذّر فتح الأرشيف المُنزَّل.</div>', 500);
}
@mkdir($tmp, 0755, true);
$zip->extractTo($tmp);
$zip->close();
@unlink($zipPath);

// GitHub wraps everything in <repo>-<ref-with-slashes-as-dashes>/
$dirs = glob($tmp . '/*', GLOB_ONLYDIR);
$src  = $dirs[0] ?? '';
if (!$src || !is_dir($src)) {
    rrmdir($tmp);
    page('أرشيف غير متوقّع', '<div class="banner err">لم يُعثر على مجلد المصدر داخل الأرشيف.</div>', 500);
}

// 3. Walk and copy
$backupDir = $root . '/.deploy-backup/' . date('Ymd-His');
$added = $updated = $skipped = $unchanged = 0;

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $item) {
    $rel = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($src))), '/');
    if ($rel === '') continue;
    if (str_starts_with($rel, '.git/') || $rel === '.git') continue;

    if (is_protected($rel)) { $skipped++; $log[] = ['s', $rel . '  (محميّ — تُخطّي)']; continue; }

    $dest = $root . '/' . $rel;

    if ($item->isDir()) {
        if (!is_dir($dest) && !$dryRun) @mkdir($dest, 0755, true);
        continue;
    }

    $newData = @file_get_contents($item->getPathname());
    if ($newData === false) continue;

    if (is_file($dest)) {
        if (md5_file($dest) === md5($newData)) { $unchanged++; continue; }
        if (!$dryRun) {
            $bDest = $backupDir . '/' . $rel;
            @mkdir(dirname($bDest), 0755, true);
            @copy($dest, $bDest);
            @file_put_contents($dest, $newData);
        }
        $updated++;
        $log[] = ['u', $rel];
    } else {
        if (!$dryRun) {
            @mkdir(dirname($dest), 0755, true);
            @file_put_contents($dest, $newData);
        }
        $added++;
        $log[] = ['a', $rel];
    }
}

rrmdir($tmp);

/* ── Report ────────────────────────────────────────────────────── */
$logHtml = '';
foreach (array_slice($log, 0, 400) as [$kind, $line]) {
    $mark = $kind === 'a' ? '+ ' : ($kind === 'u' ? '~ ' : '· ');
    $logHtml .= '<span class="' . $kind . '">' . $mark . htmlspecialchars($line) . '</span><br>';
}
if (count($log) > 400) $logHtml .= '<span class="s">… و' . (count($log) - 400) . ' سطراً آخر</span>';
if ($logHtml === '') $logHtml = '<span class="s">لا تغييرات — الخادم محدّث بالفعل.</span>';

$head = $dryRun
    ? '<div class="banner warn">👁 معاينة فقط — لم يُعدَّل أي ملف على الخادم.</div>'
    : '<div class="banner ok">✅ تم النشر بنجاح. النسخة الاحتياطية في <code>.deploy-backup/</code></div>';

page($dryRun ? 'معاينة النشر' : 'اكتمل النشر',
    $head
  . '<div class="card">'
  . '<div class="stat">'
  . '<div><b>' . $added . '</b>ملف جديد</div>'
  . '<div><b>' . $updated . '</b>ملف محدَّث</div>'
  . '<div><b>' . $unchanged . '</b>دون تغيير</div>'
  . '<div><b>' . $skipped . '</b>محميّ (تُخطّي)</div>'
  . '</div>'
  . '<div class="log">' . $logHtml . '</div>'
  . '<form method="post" class="row">'
  . '<input type="hidden" name="token" value="' . htmlspecialchars($given, ENT_QUOTES) . '">'
  . '<input type="hidden" name="ref" value="' . htmlspecialchars($ref, ENT_QUOTES) . '">'
  . ($dryRun ? '<button type="submit" name="mode" value="apply">⬇ تطبيق هذه التغييرات فعلياً</button>' : '')
  . '<a href="?token=' . htmlspecialchars($given, ENT_QUOTES) . '"><button type="button" class="ghost">← البداية</button></a>'
  . '</form></div>');
