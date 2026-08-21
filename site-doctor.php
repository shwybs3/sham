<?php
/**
 * site-doctor.php — أداة تشخيص مستقلة لحساب الاستضافة
 *
 * Standalone, zero-dependency, READ-ONLY diagnostic page. It deliberately does
 * not require config.php or touch the database, so it still works when every
 * other page on the account is returning 500 — which is exactly when it is
 * needed. It never writes, deletes, moves, or executes anything: the whole
 * point is to tell you what is broken, not to hand a web endpoint the power to
 * break more.
 *
 * الاستخدام:
 *   1. غيّر DOCTOR_PASSWORD تحت لكلمة سر خاصة فيك.
 *   2. ارفع الملف إلى public_html (أو أي مجلد دومين).
 *   3. افتحه بالمتصفح: https://yassota.com/site-doctor.php
 *   4. احذفه بعد ما تخلص التشخيص.
 */

// ── غيّر هذا السطر قبل الرفع ──────────────────────────────────────
const DOCTOR_PASSWORD = 'CHANGE_ME_NOW';

// ── لا تعدّل تحت هذا السطر ────────────────────────────────────────

// The tool reports on the environment; it must not be broken by the very
// misconfiguration it is diagnosing, so errors are shown rather than fatal.
@ini_set('display_errors', '0');
error_reporting(E_ALL);
@set_time_limit(120);

session_start();

function d_h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ── Refuse to run with the shipped default (or a weak) password ───
   The sentinel is assembled at runtime rather than written as one literal:
   editing the password by find-replacing the placeholder would otherwise
   rewrite this comparison too and silently disable the guard. */
$d_default = 'CHANGE' . '_ME_' . 'NOW';
if (hash_equals($d_default, DOCTOR_PASSWORD) || strlen(DOCTOR_PASSWORD) < 8) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8">'
       . '<body style="font-family:system-ui;padding:40px;background:#0f172a;color:#e2e8f0">'
       . '<h1 style="color:#f87171">⚠️ كلمة السر غير صالحة</h1>'
       . '<p>افتح <code>site-doctor.php</code> وغيّر قيمة <code>DOCTOR_PASSWORD</code> بأول الملف '
       . 'لكلمة سر خاصة فيك <strong>(8 أحرف على الأقل)</strong>، ثم أعد رفع الملف.</p>'
       . '</body></html>';
    exit;
}

/* ── Auth ─────────────────────────────────────────────────────────── */
if (isset($_GET['logout'])) { session_destroy(); header('Location: ?'); exit; }

if (empty($_SESSION['doctor_ok'])) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Brief sleep on failure blunts trivial guessing without a rate table.
        if (hash_equals(DOCTOR_PASSWORD, (string)($_POST['pw'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['doctor_ok'] = true;
            header('Location: ?');
            exit;
        }
        sleep(2);
        $err = 'كلمة السر غير صحيحة.';
    }
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex,nofollow">'
       . '<title>تشخيص السيرفر</title>'
       . '<body style="font-family:system-ui;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">'
       . '<form method="post" style="background:#1e293b;padding:32px;border-radius:14px;width:100%;max-width:340px">'
       . '<h1 style="margin:0 0 18px;font-size:19px">🩺 تشخيص السيرفر</h1>'
       . ($err ? '<p style="color:#f87171;font-size:13px">' . d_h($err) . '</p>' : '')
       . '<input type="password" name="pw" autofocus required placeholder="كلمة السر" '
       . 'style="width:100%;padding:12px;border-radius:9px;border:1px solid #475569;background:#0f172a;color:#e2e8f0;font-size:16px;box-sizing:border-box">'
       . '<button style="width:100%;margin-top:12px;padding:12px;border:0;border-radius:9px;background:#3b82f6;color:#fff;font-weight:700;font-size:15px;cursor:pointer">دخول</button>'
       . '</form></body></html>';
    exit;
}

/* ── Discovery ────────────────────────────────────────────────────── */

$here = __DIR__;
// Walk up to the cPanel home dir (the one holding public_html), at most a few
// levels so this never wanders off into / on an unusual layout.
$home = $here;
for ($i = 0; $i < 4; $i++) {
    if (is_dir($home . '/public_html')) break;
    $parent = dirname($home);
    if ($parent === $home) break;
    $home = $parent;
}

/** Domain-looking folders in the home dir, plus public_html. */
function d_domain_dirs(string $home): array {
    $out = [];
    if (is_dir($home . '/public_html')) {
        $out['public_html (الدومين الرئيسي)'] = $home . '/public_html';
    }
    foreach ((array)@scandir($home) as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '.') continue;
        $path = $home . '/' . $entry;
        if (!is_dir($path)) continue;
        // A folder named like a domain — the cPanel convention for addon
        // domains and subdomains with their own document root.
        if (preg_match('/^[a-z0-9._-]+\.[a-z]{2,}$/i', $entry)) {
            $out[$entry] = $path;
        }
    }
    ksort($out);
    return $out;
}

/** Newest readable error log under a directory, with its last lines. */
function d_error_tail(string $dir, int $lines = 25): array {
    foreach (['error_log', 'php_errorlog', '.error_log'] as $name) {
        $f = $dir . '/' . $name;
        if (is_file($f) && is_readable($f)) {
            $all = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($all) {
                return ['file' => $f, 'mtime' => @filemtime($f), 'lines' => array_slice($all, -$lines)];
            }
        }
    }
    return [];
}

function d_perm(string $path): string {
    $p = @fileperms($path);
    return $p === false ? '—' : substr(sprintf('%o', $p), -3);
}

/** Is this permission dangerous for PHP under suPHP/suexec? */
function d_perm_bad(string $perms, bool $isDir): bool {
    if ($perms === '—' || strlen($perms) !== 3) return false;
    // Group- or world-writable PHP files/dirs are refused by suPHP with a 500.
    $group = (int)$perms[1];
    $world = (int)$perms[2];
    return ($group & 2) === 2 || ($world & 2) === 2;
}

$domains = d_domain_dirs($home);

/* ── Live HTTP probe (only when explicitly requested) ─────────────── */
$probe = [];
if (isset($_GET['probe']) && function_exists('curl_init')) {
    foreach ($domains as $label => $path) {
        $host = ($label === 'public_html (الدومين الرئيسي)') ? null : $label;
        if (!$host) continue;
        $ch = curl_init('https://' . $host . '/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY         => false,
            CURLOPT_USERAGENT      => 'yassota-site-doctor/1.0',
        ]);
        $body = curl_exec($ch);
        $probe[$host] = [
            'code'  => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'time'  => round((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2),
            'error' => curl_error($ch) ?: null,
            'bytes' => is_string($body) ? strlen($body) : 0,
        ];
        curl_close($ch);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>🩺 تشخيص السيرفر — yassota</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;line-height:1.7;padding:16px}
.wrap{max-width:1000px;margin:0 auto}
h1{font-size:21px;margin:0 0 4px}
.sub{color:#94a3b8;font-size:13px;margin:0 0 20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:16px;margin-bottom:14px}
.card h2{font-size:15px;margin:0 0 12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:8px 6px;text-align:start;border-bottom:1px solid #334155;vertical-align:top}
th{color:#94a3b8;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px}
.ok{color:#4ade80}.bad{color:#f87171}.warn{color:#fbbf24}.dim{color:#64748b}
code{background:#0f172a;padding:2px 6px;border-radius:4px;font-size:12px;direction:ltr;display:inline-block}
pre{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px;overflow-x:auto;font-size:11.5px;direction:ltr;text-align:left;white-space:pre-wrap;word-break:break-word;margin:0;max-height:320px;overflow-y:auto}
.btn{display:inline-block;background:#3b82f6;color:#fff;padding:9px 16px;border-radius:9px;text-decoration:none;font-size:13px;font-weight:700;border:0;cursor:pointer}
.btn.gray{background:#475569}
.tag{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.tag.ok{background:rgba(74,222,128,.15);color:#4ade80}
.tag.bad{background:rgba(248,113,113,.15);color:#f87171}
.tag.warn{background:rgba(251,191,36,.15);color:#fbbf24}
.scroll{overflow-x:auto}
.hint{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);border-radius:9px;padding:12px;font-size:13px;margin-top:12px}

/* On a phone the domain table's status columns fall off the right edge, and
   this page exists to be read on a phone during an outage. Below 720px each
   row becomes a labelled card so nothing needs horizontal scrolling. */
@media (max-width:720px){
  table.domains, table.domains tbody, table.domains tr, table.domains td{display:block;width:100%}
  table.domains thead{display:none}
  table.domains tr{
    border:1px solid #334155;border-radius:10px;padding:10px 12px;margin-bottom:10px;background:#0f172a;
  }
  table.domains td{border:0;padding:4px 0;display:flex;gap:10px;align-items:baseline;justify-content:space-between}
  table.domains td::before{
    content:attr(data-label);color:#94a3b8;font-size:11.5px;font-weight:600;flex-shrink:0;
  }
  table.domains td.dom-name{display:block;padding-bottom:8px;margin-bottom:6px;border-bottom:1px solid #334155}
  table.domains td.dom-name::before{content:none}
}
</style>
</head>
<body>
<div class="wrap">

<h1>🩺 تشخيص السيرفر</h1>
<p class="sub">
  أداة قراءة فقط — لا تعدّل ولا تحذف أي ملف.
  <strong style="color:#fbbf24">احذف هذا الملف بعد ما تخلص.</strong>
  · <a href="?logout=1" style="color:#60a5fa">خروج</a>
</p>

<!-- ── البيئة ── -->
<div class="card">
  <h2>⚙️ بيئة PHP</h2>
  <div class="scroll"><table>
    <tr><th>إصدار PHP</th><td><code><?= d_h(PHP_VERSION) ?></code>
      <?php if (version_compare(PHP_VERSION, '8.0', '<')): ?>
        <span class="tag bad">قديم — الموقع يحتاج 8.0+</span>
      <?php else: ?><span class="tag ok">مناسب</span><?php endif; ?>
    </td></tr>
    <tr><th>مجلد الحساب</th><td><code><?= d_h($home) ?></code></td></tr>
    <tr><th>هذا الملف</th><td><code><?= d_h(__FILE__) ?></code></td></tr>
    <tr><th>السيرفر</th><td><code><?= d_h($_SERVER['SERVER_SOFTWARE'] ?? '—') ?></code></td></tr>
    <tr><th>memory_limit</th><td><code><?= d_h(ini_get('memory_limit')) ?></code></td></tr>
    <tr><th>الإضافات المطلوبة</th><td>
      <?php foreach (['pdo_mysql','curl','mbstring','gd','json','openssl'] as $ext): ?>
        <span class="tag <?= extension_loaded($ext) ? 'ok' : 'bad' ?>"><?= d_h($ext) ?></span>
      <?php endforeach; ?>
    </td></tr>
    <tr><th>مساحة القرص الحرة</th><td>
      <?php $free = @disk_free_space($home); ?>
      <code><?= $free ? number_format($free / 1073741824, 2) . ' GB' : '—' ?></code>
      <?php if ($free && $free < 209715200): ?><span class="tag bad">شبه ممتلئ — سبب شائع للـ500</span><?php endif; ?>
    </td></tr>
  </table></div>
</div>

<!-- ── الدومينات ── -->
<div class="card">
  <h2>🌐 الدومينات ومجلداتها (<?= count($domains) ?>)
    <a class="btn" href="?probe=1" style="margin-inline-start:auto">🔄 افحص الدومينات مباشرة</a>
  </h2>

  <?php if (!$domains): ?>
    <p class="bad">ما لقيت أي مجلد دومين تحت <code><?= d_h($home) ?></code>.</p>
  <?php else: ?>
  <div class="scroll"><table class="domains">
    <thead><tr>
      <th>الدومين / المجلد</th>
      <th>index.php</th>
      <th>config.php</th>
      <th>.htaccess</th>
      <th>صلاحيات المجلد</th>
      <?php if ($probe): ?><th>الحالة المباشرة</th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($domains as $label => $path):
      $idx      = $path . '/index.php';
      $cfg      = $path . '/config.php';
      $ht       = $path . '/.htaccess';
      $hasIdx   = is_file($idx);
      $hasCfg   = is_file($cfg);
      $hasHt    = is_file($ht);
      $dirPerm  = d_perm($path);
      $idxPerm  = $hasIdx ? d_perm($idx) : '';
      $p        = $probe[$label] ?? null;
    ?>
      <tr>
        <td class="dom-name">
          <strong><?= d_h($label) ?></strong><br>
          <code style="font-size:11px" class="dim"><?= d_h($path) ?></code>
        </td>
        <td data-label="index.php">
          <span>
          <?php if ($hasIdx): ?>
            <span class="ok">✔</span>
            <code style="font-size:11px"><?= d_h($idxPerm) ?></code>
            <?php if (d_perm_bad($idxPerm, false)): ?> <span class="tag bad">صلاحيات خطرة</span><?php endif; ?>
          <?php else: ?>
            <span class="bad">✘ مفقود</span>
          <?php endif; ?>
          </span>
        </td>
        <td data-label="config.php"><span><?= $hasCfg ? '<span class="ok">✔</span>' : '<span class="dim">—</span>' ?></span></td>
        <td data-label=".htaccess"><span><?= $hasHt  ? '<span class="ok">✔</span>' : '<span class="dim">—</span>' ?></span></td>
        <td data-label="صلاحيات المجلد">
          <span>
            <code><?= d_h($dirPerm) ?></code>
            <?php if (d_perm_bad($dirPerm, true)): ?> <span class="tag bad">777 يسبب 500</span><?php endif; ?>
          </span>
        </td>
        <?php if ($probe): ?>
        <td data-label="الحالة المباشرة">
          <span>
          <?php if (!$p): ?><span class="dim">—</span>
          <?php elseif ($p['error']): ?><span class="bad">✘</span> <span style="font-size:11px"><?= d_h($p['error']) ?></span>
          <?php else:
            $c = $p['code'];
            $cls = $c >= 200 && $c < 300 ? 'ok' : ($c >= 500 ? 'bad' : 'warn'); ?>
            <span class="tag <?= $cls ?>">HTTP <?= (int)$c ?></span>
            <span class="dim" style="font-size:11px"><?= d_h($p['time']) ?>s</span>
          <?php endif; ?>
          </span>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <div class="hint">
    <strong>كيف تقرأ الجدول:</strong><br>
    • <strong>index.php مفقود</strong> ← هذا سبب الـ<code>404</code> مباشرة.<br>
    • <strong>صلاحيات 777 / خطرة</strong> ← هذا سبب الـ<code>500</code> على أغلب استضافات cPanel (suPHP يرفض تشغيل ملف يقدر الكل يكتب عليه). الصح: ملفات <code>644</code>، مجلدات <code>755</code>.<br>
    • <strong>config.php موجود</strong> بمجلد دومين فرعي ← يعني الدومين نسخة مستقلة. إذا مفقود وفيه <code>tools/chat</code> ← الملفات بمكان غلط.
  </div>
  <?php endif; ?>
</div>

<!-- ── سجل الأخطاء ── -->
<div class="card">
  <h2>🐞 آخر أخطاء PHP</h2>
  <p class="sub" style="margin:0 0 12px">هون بيطلع السبب الحقيقي لأي 500 — اقرأ آخر سطر.</p>
  <?php
  $found = false;
  foreach ($domains as $label => $path):
      $log = d_error_tail($path);
      if (!$log) continue;
      $found = true; ?>
      <div style="margin-bottom:16px">
        <div style="font-size:12.5px;margin-bottom:6px">
          <strong><?= d_h($label) ?></strong>
          <span class="dim">— <?= d_h(date('Y-m-d H:i', (int)$log['mtime'])) ?></span>
        </div>
        <pre><?= d_h(implode("\n", $log['lines'])) ?></pre>
      </div>
  <?php endforeach;

  $rootLog = d_error_tail($home);
  if ($rootLog): $found = true; ?>
    <div style="margin-bottom:16px">
      <div style="font-size:12.5px;margin-bottom:6px"><strong>مجلد الحساب</strong></div>
      <pre><?= d_h(implode("\n", $rootLog['lines'])) ?></pre>
    </div>
  <?php endif; ?>

  <?php if (!$found): ?>
    <p class="dim">ما لقيت ملف <code>error_log</code> مقروء. فعّل تسجيل الأخطاء من cPanel، أو افتح
    <strong>cPanel ← Metrics ← Errors</strong>.</p>
  <?php endif; ?>
</div>

<p class="sub" style="text-align:center;margin-top:24px">
  ⚠️ <strong>احذف <code>site-doctor.php</code> من السيرفر بعد ما تخلص التشخيص.</strong>
</p>

</div>
</body>
</html>
