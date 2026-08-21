<?php
/**
 * google-indexing-api.php — لوحة تحكم فهرسة جوجل + IndexNow
 *
 * Admin-only control panel for pushing URLs to Google's Indexing API and to
 * IndexNow (Bing / Yandex / Seznam). Replaces an earlier broken revision that
 * called an undefined gidx_identifier() and fataled on load.
 *
 * All heavy lifting lives in config.php:
 *   - google_indexing_request(PDO, array $urls)  → Google Indexing API
 *   - ping_search_engines(PDO, string $url)      → IndexNow + logging
 */

require_once __DIR__ . '/config.php';

if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8">'
       . '<title>غير مصرّح</title>'
       . '<body style="font-family:system-ui,sans-serif;background:#0b1120;color:#e8edf7;'
       . 'display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center">'
       . '<div><h1 style="font-size:20px;margin:0 0 10px">🔒 هذه الصفحة للمشرفين فقط</h1>'
       . '<p style="color:#8b9ab5;font-size:14px;margin:0 0 18px">سجّل دخولك من لوحة التحكم أولاً.</p>'
       . '<a href="/admin" style="color:#38bdf8">← لوحة التحكم</a></div></body></html>');
}

$msg = '';
$msgType = 'info';
$results = [];

/* ── Collect every indexable URL on the platform ───────────────── */
function gidx_collect_urls(PDO $pdo, string $scope): array {
    $urls = [];
    $base = rtrim(SITE_URL, '/');
    $host = parse_url($base, PHP_URL_HOST) ?: 'yassota.com';

    if ($scope === 'core' || $scope === 'all') {
        foreach (['', '/about', '/privacy-policy', '/terms', '/contact', '/faq',
                  '/dmca', '/cookie-policy', '/disclosure', '/blog', '/top',
                  '/updates', '/tools', '/products'] as $p) {
            $urls[] = $base . $p;
        }
    }

    if ($scope === 'apps' || $scope === 'all') {
        try {
            $rows = $pdo->query("SELECT slug FROM apps WHERE status='published' ORDER BY updated_at DESC")->fetchAll();
            foreach ($rows as $r) {
                if (empty($r['slug'])) continue;
                $urls[] = $base . '/' . $r['slug'];
                $urls[] = 'https://' . $r['slug'] . '.' . $host . '/';
            }
        } catch (\Throwable $e) {}
    }

    if ($scope === 'blog' || $scope === 'all') {
        try {
            $rows = $pdo->query("SELECT slug FROM blog_posts WHERE status='published' ORDER BY id DESC")->fetchAll();
            foreach ($rows as $r) {
                if (empty($r['slug'])) continue;
                $urls[] = $base . '/blog/' . $r['slug'];
                $urls[] = 'https://' . $r['slug'] . '.' . $host . '/';
            }
        } catch (\Throwable $e) {}
    }

    if ($scope === 'tools' || $scope === 'all') {
        try {
            $rows = $pdo->query("SELECT slug FROM web_tools WHERE status='published'")->fetchAll();
            foreach ($rows as $r) {
                if (!empty($r['slug'])) $urls[] = $base . '/tools/' . $r['slug'] . '/';
            }
        } catch (\Throwable $e) {}
    }

    return array_values(array_unique(array_filter($urls)));
}

/* ── Handle submissions ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_scope') {
        $scope = $_POST['scope'] ?? 'core';
        $urls  = gidx_collect_urls($pdo, $scope);
        $limit = max(1, min(200, (int)($_POST['limit'] ?? 100)));
        $urls  = array_slice($urls, 0, $limit);

        if (!$urls) {
            $msg = 'لم يُعثر على أي روابط في هذا النطاق.';
            $msgType = 'warn';
        } else {
            // Google Indexing API (batch)
            try { google_indexing_request($pdo, $urls); } catch (\Throwable $e) {}
            // IndexNow, one by one (it logs each into indexnow_log)
            $sent = 0;
            foreach ($urls as $u) {
                try { ping_search_engines($pdo, $u); $sent++; } catch (\Throwable $e) {}
            }
            $results = $urls;
            $msg = "تم إرسال {$sent} رابط إلى Google Indexing API و IndexNow.";
            $msgType = 'ok';
        }
    }

    if ($action === 'submit_manual') {
        $raw  = trim((string)($_POST['urls'] ?? ''));
        $urls = array_values(array_filter(array_map('trim', preg_split('/\R+/', $raw))));
        $urls = array_filter($urls, fn($u) => filter_var($u, FILTER_VALIDATE_URL));
        $urls = array_slice($urls, 0, 200);

        if (!$urls) {
            $msg = 'أدخل رابطاً واحداً صحيحاً على الأقل (كل رابط في سطر).';
            $msgType = 'warn';
        } else {
            try { google_indexing_request($pdo, $urls); } catch (\Throwable $e) {}
            foreach ($urls as $u) {
                try { ping_search_engines($pdo, $u); } catch (\Throwable $e) {}
            }
            $results = $urls;
            $msg = 'تم إرسال ' . count($urls) . ' رابط للفهرسة.';
            $msgType = 'ok';
        }
    }
}

/* ── Current configuration status ──────────────────────────────── */
$gJson      = get_cfg($pdo, 'google_indexing_json', '');
$indexNow   = get_cfg($pdo, 'indexnow_key', '');
$gReady     = false;
$gAccount   = '';
if ($gJson) {
    $decoded = json_decode(base64_decode($gJson, true), true);
    if (is_array($decoded) && ($decoded['type'] ?? '') === 'service_account') {
        $gReady   = true;
        $gAccount = (string)($decoded['client_email'] ?? '');
    }
}

/* ── Recent log ────────────────────────────────────────────────── */
$log = [];
try {
    $log = $pdo->query("SELECT url, engine, status, http_code, reason, created_at
                        FROM indexnow_log ORDER BY id DESC LIMIT 40")->fetchAll();
} catch (\Throwable $e) {}

$counts = ['apps' => 0, 'blog' => 0, 'tools' => 0];
foreach ([['apps','apps'],['blog','blog_posts'],['tools','web_tools']] as [$k, $tbl]) {
    try { $counts[$k] = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl` WHERE status='published'")->fetchColumn(); }
    catch (\Throwable $e) {}
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>فهرسة جوجل و IndexNow — لوحة التحكم</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<style>
  :root{
    --bg:#f7f9fc; --surface:#fff; --line:#e3e9f2;
    --text:#101a2c; --muted:#5f708c;
    --accent:#2563eb; --accent-soft:rgba(37,99,235,.08);
    --ok:#15803d; --ok-bg:rgba(22,163,74,.1);
    --warn:#b45309; --warn-bg:rgba(217,119,6,.1);
    --err:#b91c1c; --err-bg:rgba(220,38,38,.09);
  }
  @media (prefers-color-scheme:dark){
    :root:not([data-theme="light"]){
      --bg:#0b1120; --surface:#131c31; --line:#22304d;
      --text:#e8edf7; --muted:#8b9ab5; --accent:#38bdf8; --accent-soft:rgba(56,189,248,.1);
      --ok:#4ade80; --ok-bg:rgba(34,197,94,.1);
      --warn:#fbbf24; --warn-bg:rgba(251,191,36,.1);
      --err:#fca5a5; --err-bg:rgba(220,38,38,.12);
    }
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);direction:rtl;line-height:1.7;
       font-family:'Cairo','Tajawal',system-ui,-apple-system,'Segoe UI',Tahoma,sans-serif;
       padding:0 0 60px}
  .top{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 20px;
       display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
       position:sticky;top:0;z-index:10}
  .top h1{font-size:16px;font-weight:800;letter-spacing:-.2px}
  .top a{color:var(--accent);text-decoration:none;font-size:13px;font-weight:600}
  .wrap{max-width:920px;margin:0 auto;padding:26px 20px;display:flex;flex-direction:column;gap:20px}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:22px}
  .card h2{font-size:14px;font-weight:800;margin-bottom:4px}
  .card .sub{font-size:12.5px;color:var(--muted);margin-bottom:16px}
  .stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
  .stat{background:var(--accent-soft);border:1px solid var(--line);border-radius:10px;padding:14px 16px}
  .stat .n{font-size:24px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.2}
  .stat .l{font-size:11.5px;color:var(--muted);margin-top:2px}
  .pill{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;
        font-size:12px;font-weight:700}
  .pill.ok{background:var(--ok-bg);color:var(--ok)}
  .pill.warn{background:var(--warn-bg);color:var(--warn)}
  .banner{border-radius:10px;padding:12px 16px;font-size:13.5px;font-weight:600}
  .banner.ok{background:var(--ok-bg);color:var(--ok)}
  .banner.warn{background:var(--warn-bg);color:var(--warn)}
  .banner.info{background:var(--accent-soft);color:var(--accent)}
  label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px}
  select,input[type=number],textarea{width:100%;background:var(--bg);color:var(--text);
    border:1px solid var(--line);border-radius:9px;padding:10px 12px;font:inherit;font-size:13.5px}
  textarea{min-height:130px;resize:vertical;direction:ltr;font-family:ui-monospace,monospace;font-size:12.5px}
  .grid2{display:grid;grid-template-columns:2fr 1fr;gap:14px}
  @media(max-width:620px){.grid2{grid-template-columns:1fr}}
  button{background:var(--accent);color:#fff;border:none;border-radius:9px;padding:11px 26px;
    font:inherit;font-size:13.5px;font-weight:700;cursor:pointer;transition:opacity .15s}
  button:hover{opacity:.9}
  .actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .note{font-size:12px;color:var(--muted)}
  .scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
  table{width:100%;border-collapse:collapse;font-size:12.5px;min-width:560px}
  th,td{text-align:right;padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:top}
  th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-weight:700}
  td.u{direction:ltr;text-align:left;font-family:ui-monospace,monospace;font-size:11.5px;
       word-break:break-all;max-width:330px}
  .s-success{color:var(--ok);font-weight:700}
  .s-failed{color:var(--err);font-weight:700}
  .s-skipped{color:var(--warn);font-weight:700}
  ol{padding-inline-start:20px;font-size:13px;color:var(--muted)}
  ol li{margin-bottom:7px}
  code{background:var(--accent-soft);padding:2px 6px;border-radius:5px;font-size:12px;direction:ltr;
       display:inline-block}
  .urls-out{max-height:210px;overflow:auto;background:var(--bg);border:1px solid var(--line);
    border-radius:9px;padding:12px;direction:ltr;font-family:ui-monospace,monospace;font-size:11.5px;
    color:var(--muted);line-height:1.9}
</style>
</head>
<body>

<div class="top">
  <h1>🔎 فهرسة جوجل و IndexNow</h1>
  <a href="/admin">← لوحة التحكم</a>
</div>

<div class="wrap">

  <?php if ($msg): ?>
    <div class="banner <?= h($msgType) ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- Status -->
  <div class="card">
    <h2>حالة الإعداد</h2>
    <p class="sub">لا تُرسَل الروابط إلى جوجل إلا بعد ضبط حساب الخدمة.</p>
    <div class="stat-row">
      <div class="stat">
        <div class="n"><?= $gReady ? '✓' : '—' ?></div>
        <div class="l">Google Indexing API</div>
        <div style="margin-top:8px">
          <span class="pill <?= $gReady ? 'ok' : 'warn' ?>"><?= $gReady ? 'مضبوط' : 'غير مضبوط' ?></span>
        </div>
        <?php if ($gAccount): ?>
          <div class="l" style="margin-top:8px;direction:ltr;text-align:left;word-break:break-all"><?= h($gAccount) ?></div>
        <?php endif; ?>
      </div>
      <div class="stat">
        <div class="n"><?= $indexNow ? '✓' : '—' ?></div>
        <div class="l">IndexNow (Bing / Yandex)</div>
        <div style="margin-top:8px">
          <span class="pill <?= $indexNow ? 'ok' : 'warn' ?>"><?= $indexNow ? 'مضبوط' : 'غير مضبوط' ?></span>
        </div>
      </div>
      <div class="stat">
        <div class="n"><?= number_format($counts['apps'] + $counts['blog'] + $counts['tools']) ?></div>
        <div class="l">صفحة منشورة قابلة للفهرسة</div>
        <div class="l" style="margin-top:6px">
          <?= number_format($counts['apps']) ?> تطبيق ·
          <?= number_format($counts['blog']) ?> مقال ·
          <?= number_format($counts['tools']) ?> أداة
        </div>
      </div>
    </div>

    <?php if (!$gReady): ?>
    <div style="margin-top:18px">
      <div class="banner warn" style="margin-bottom:12px">لتفعيل الفهرسة الفورية من جوجل، أكمل الخطوات التالية:</div>
      <ol>
        <li>أنشئ مشروعاً في Google Cloud Console وفعّل <strong>Indexing API</strong>.</li>
        <li>أنشئ <strong>Service Account</strong> ونزّل ملف مفتاح JSON.</li>
        <li>في Google Search Console أضف بريد حساب الخدمة كـ <strong>مالك (Owner)</strong> للموقع.</li>
        <li>حوّل ملف JSON إلى Base64 ثم احفظه في: لوحة التحكم ← الإعدادات ← <code>google_indexing_json</code>.</li>
      </ol>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bulk submit -->
  <div class="card">
    <h2>إرسال جماعي</h2>
    <p class="sub">يرسل الروابط إلى Google Indexing API و IndexNow معاً — بما فيها الدومينات الفرعية.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="submit_scope">
      <div class="grid2">
        <div>
          <label for="scope">النطاق</label>
          <select name="scope" id="scope">
            <option value="core">الصفحات الأساسية والقانونية (14 رابط)</option>
            <option value="apps">التطبيقات + دوميناتها الفرعية</option>
            <option value="blog">المقالات + دوميناتها الفرعية</option>
            <option value="tools">أدوات الويب</option>
            <option value="all">كل شيء</option>
          </select>
        </div>
        <div>
          <label for="limit">حد أقصى</label>
          <input type="number" name="limit" id="limit" value="100" min="1" max="200">
        </div>
      </div>
      <div class="actions">
        <button type="submit">🚀 إرسال للفهرسة</button>
        <span class="note">حصة جوجل: 200 رابط يومياً لكل مشروع.</span>
      </div>
    </form>
  </div>

  <!-- Manual submit -->
  <div class="card">
    <h2>إرسال روابط محددة</h2>
    <p class="sub">رابط واحد في كل سطر.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="submit_manual">
      <textarea name="urls" placeholder="https://yassota.com/about&#10;https://yassota.com/blog/my-article&#10;https://capcut.yassota.com/"></textarea>
      <div class="actions"><button type="submit">📤 إرسال</button></div>
    </form>
  </div>

  <?php if ($results): ?>
  <div class="card">
    <h2>الروابط المُرسَلة (<?= count($results) ?>)</h2>
    <div class="urls-out"><?php foreach ($results as $u) echo h($u) . "\n"; ?></div>
  </div>
  <?php endif; ?>

  <!-- Log -->
  <div class="card">
    <h2>آخر عمليات الفهرسة</h2>
    <p class="sub">أحدث 40 عملية من سجل IndexNow.</p>
    <?php if (!$log): ?>
      <p class="note">لا توجد عمليات مسجّلة بعد.</p>
    <?php else: ?>
    <div class="scroll">
      <table>
        <thead><tr><th>الرابط</th><th>الحالة</th><th>الرمز</th><th>السبب</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($log as $r): ?>
          <tr>
            <td class="u"><?= h($r['url']) ?></td>
            <td class="s-<?= h($r['status']) ?>"><?= h($r['status']) ?></td>
            <td><?= $r['http_code'] !== null ? (int)$r['http_code'] : '—' ?></td>
            <td class="note"><?= h((string)($r['reason'] ?? '')) ?: '—' ?></td>
            <td class="note" style="white-space:nowrap"><?= h((string)$r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
