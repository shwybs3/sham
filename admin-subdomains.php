<?php
/* ════════════════════════════════════════════════════
   admin-subdomains.php — cPanel Subdomain Manager
   Uses the same cPanel settings already configured in
   admin.php → Settings (cpanel_url, cpanel_user,
   cpanel_api_token, server_doc_root).
   Access: yourdomain.com/admin-subdomains
   ════════════════════════════════════════════════════ */

session_start();
require_once __DIR__ . '/config.php';

/* ── Auth: require admin session ── */
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin');
    exit;
}

/* ── Read cPanel settings from DB (same as admin.php) ── */
$CP_URL   = rtrim(get_cfg($pdo, 'cpanel_url', '') ?: get_cfg($pdo, 'cpanel_api_url', ''), '/');
$CP_USER  = get_cfg($pdo, 'cpanel_user', '');
$CP_TOKEN = get_cfg($pdo, 'cpanel_api_token', '');
$DOC_BASE = rtrim(get_cfg($pdo, 'server_doc_root', '') ?: get_cfg($pdo, 'cpanel_docroot_base', ''), '/');

/* ── Helper: call cPanel UAPI via curl ── */
function cp_call(string $module, string $fn, array $post = []): array {
    global $CP_URL, $CP_USER, $CP_TOKEN;
    if (!$CP_URL || !$CP_TOKEN) return ['errors'=>['cPanel غير مُهيأ — راجع الإعدادات']];
    $url = "{$CP_URL}/execute/{$module}/{$fn}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($post),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ["Authorization: cpanel {$CP_USER}:{$CP_TOKEN}"],
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['errors' => [$err]];
    return json_decode((string)$res, true) ?: ['errors'=>['JSON parse error']];
}

/* ── List subdomains ── */
function list_subdomains(): array {
    $r = cp_call('SubDomain', 'listsubdomains');
    return $r['data'] ?? [];
}

/* ══════════════════════════════════════
   HANDLE POST
══════════════════════════════════════ */
$flash = ''; $flash_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Create a simple subdomain ── */
    if ($action === 'create') {
        $sub = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '-', $_POST['subdomain'] ?? '')));
        $dir = trim($_POST['dir'] ?? '') ?: "public_html/{$sub}.yassota.com";
        if (!$sub) { $flash = 'اسم فارغ'; $flash_type = 'error'; }
        else {
            $r = cp_call('SubDomain', 'addsubdomain', ['domain'=>$sub,'rootdomain'=>'yassota.com','dir'=>$dir]);
            if (!empty($r['errors'])) { $flash = implode(' | ', $r['errors']); $flash_type = 'error'; }
            else { $flash = "✅ تم إنشاء {$sub}.yassota.com — قد يستغرق DNS 10–30 دقيقة."; }
        }
    }

    /* ── Create AI tool subdomain + copy template ── */
    if ($action === 'tool_sub') {
        $slug = $_POST['slug'] ?? '';
        $tpl  = __DIR__ . '/subdomains/' . basename($slug);
        $sub  = strtolower(preg_replace('/[^a-z0-9\-]/', '-', $slug));
        $dir  = "public_html/{$sub}.yassota.com";
        $dest = $DOC_BASE ? $DOC_BASE . "/{$dir}" : '';

        /* Create the subdomain */
        $r = cp_call('SubDomain', 'addsubdomain', ['domain'=>$sub,'rootdomain'=>'yassota.com','dir'=>$dir]);
        $cpErr = !empty($r['errors']) && !preg_match('/exist|already/i', implode(' ', $r['errors']));

        /* Copy template if directory is reachable */
        if ($dest && is_dir($tpl)) {
            @mkdir($dest, 0755, true);
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tpl, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iter as $f) {
                $t = $dest . '/' . $iter->getSubPathName();
                if ($f->isDir()) @mkdir($t, 0755, true);
                else @copy((string)$f, $t);
            }
            $flash = "✅ {$sub}.yassota.com أُنشئ وتم نسخ القالب.";
        } elseif ($cpErr) {
            $flash = "خطأ cPanel: " . implode(' | ', $r['errors']);
            $flash_type = 'error';
        } else {
            $flash = "✅ {$sub}.yassota.com أُرسل لـ cPanel (القالب يحتاج نسخ يدوي: المسار غير متاح هنا).";
        }
    }

    /* ── Bulk create subdomains for top apps ── */
    if ($action === 'bulk_apps') {
        $limit = min(200, (int)($_POST['limit'] ?? 50));
        $apps  = $pdo->query("SELECT * FROM apps WHERE status='published' AND (subdomain_created IS NULL OR subdomain_created=0) ORDER BY views DESC LIMIT {$limit}")->fetchAll();
        $ok = $fail = 0;
        foreach ($apps as $app) {
            $r = cpanel_create_subdomain($pdo, $app);
            $r['ok'] ? $ok++ : $fail++;
        }
        $flash = "تم: {$ok} دومين ✅، فشل: {$fail} ❌";
        if ($fail) $flash_type = 'error';
    }
}

$subs = list_subdomains();

/* Get app counts */
$total_apps    = (int)($pdo->query("SELECT COUNT(*) FROM apps WHERE status='published'")->fetchColumn());
$subbed_apps   = (int)($pdo->query("SELECT COUNT(*) FROM apps WHERE subdomain_created=1")->fetchColumn() ?? 0);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>الدومينات الفرعية — يسوتا</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#f4f7fb;--surface:#fff;--border:rgba(15,23,42,.08);--text:#0f172a;--muted:#64748b;--cyan:#0ea5e9;--purple:#7c3aed;--indigo:#4f46e5;--success:#16a34a;--danger:#dc2626;--header-bg:#0c1e36;--r:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo',sans-serif;background:var(--bg);color:var(--text);direction:rtl;min-height:100vh;padding-bottom:60px}
.page-header{background:var(--header-bg);color:#e2e8f0;padding:0 28px;height:56px;display:flex;align-items:center;gap:20px;position:sticky;top:0;z-index:100}
.page-header h1{font-size:16px;font-weight:800;flex:1}
.page-header a{color:rgba(226,232,240,.55);font-size:12px;text-decoration:none;transition:color .15s}
.page-header a:hover{color:#0ea5e9}
.container{max-width:1100px;margin:0 auto;padding:24px 18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.card-title{font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.card-title::before{content:'';width:4px;height:18px;background:linear-gradient(180deg,var(--cyan),var(--purple));border-radius:4px;flex-shrink:0}
.row{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-bottom:8px}
label{font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:5px}
input,select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:13px;color:var(--text);background:var(--surface);outline:none;transition:border-color .15s,box-shadow .15s}
input:focus,select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(14,165,233,.1)}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-family:inherit;font-size:13px;font-weight:700;border:none;cursor:pointer;white-space:nowrap;transition:transform .15s,box-shadow .15s}
.btn-primary{background:linear-gradient(135deg,var(--cyan),var(--indigo));color:#fff;box-shadow:0 3px 10px rgba(14,165,233,.28)}
.btn-primary:hover{transform:translateY(-1px)}
.btn-success{background:linear-gradient(135deg,var(--success),#15803d);color:#fff}
.btn-orange{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff}
.btn-sm{padding:6px 13px;font-size:12px}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--muted)}
.btn-outline:hover{border-color:var(--cyan);color:var(--cyan)}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:600;border:1px solid}
.flash-success{background:rgba(22,163,74,.06);color:var(--success);border-color:rgba(22,163,74,.2)}
.flash-error{background:rgba(220,38,38,.06);color:var(--danger);border-color:rgba(220,38,38,.2)}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.stat{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px;text-align:center;border-top:3px solid}
.stat:nth-child(1){border-top-color:var(--cyan)}
.stat:nth-child(2){border-top-color:var(--purple)}
.stat:nth-child(3){border-top-color:var(--success)}
.stat:nth-child(4){border-top-color:#f97316}
.stat-num{font-size:24px;font-weight:900;color:var(--text)}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:3px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:right;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;border-bottom:2px solid var(--border)}
td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(14,165,233,.02)}
code{background:var(--bg);padding:2px 7px;border-radius:6px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--purple)}
.no-cred{background:rgba(249,115,22,.06);border:1px solid rgba(249,115,22,.2);border-radius:10px;padding:16px;color:#c2410c;font-size:14px;margin-bottom:18px}
.no-cred code{background:rgba(249,115,22,.08);color:#9a3412}
.tool-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.tool-item{display:flex;flex-direction:column;gap:8px;padding:14px;border:1px solid var(--border);border-radius:12px;background:var(--bg)}
.tool-item-title{font-weight:700;font-size:14px}
@media(max-width:700px){.row{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="page-header">
  <h1>🌐 إدارة الدومينات الفرعية</h1>
  <a href="/admin">← لوحة التحكم</a>
  <a href="/admin?tab=settings">⚙️ إعدادات cPanel</a>
</div>

<div class="container">

<?php if ($flash): ?>
<div class="flash flash-<?= $flash_type ?>"><?= h($flash) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats">
  <div class="stat"><div class="stat-num"><?= $total_apps ?></div><div class="stat-lbl">تطبيق منشور</div></div>
  <div class="stat"><div class="stat-num"><?= $subbed_apps ?></div><div class="stat-lbl">لديه دومين فرعي</div></div>
  <div class="stat"><div class="stat-num"><?= count($subs) ?></div><div class="stat-lbl">دومين فرعي نشط</div></div>
  <div class="stat"><div class="stat-num"><?= $total_apps - $subbed_apps ?></div><div class="stat-lbl">بدون دومين فرعي</div></div>
</div>

<?php if (!$CP_URL || !$CP_TOKEN): ?>
<div class="no-cred">
  <strong>⚠️ cPanel غير مهيأ</strong> — اذهب إلى
  <a href="/admin?tab=settings" style="color:#c2410c;font-weight:700">الإعدادات</a>
  وأضف:
  <ul style="margin:8px 0 0 16px;line-height:2">
    <li><code>cpanel_url</code> = <code>https://yassota.com:2083</code> (أو localhost:2083)</li>
    <li><code>cpanel_user</code> = اسم مستخدم cPanel الخاص بك</li>
    <li><code>cpanel_api_token</code> = <code>FS1B57RFLT2S0NA1HBNUAPKAVPGG6H4U</code></li>
    <li><code>server_doc_root</code> = <code>/home/yourusername</code></li>
  </ul>
</div>
<?php endif; ?>

<!-- AI Tools subdomains -->
<div class="card">
  <div class="card-title">أدوات الذكاء الاصطناعي — دومينات فرعية</div>
  <div class="tool-grid">
    <?php foreach ([
      ['yasmin', '🌸 ياسمين AI Chat',  'subdomains/yasmin'],
      ['szad',   '🤖 صزاد AI',          'subdomains/szad'],
    ] as [$slug, $label, $tpl]): ?>
    <div class="tool-item">
      <div class="tool-item-title"><?= $label ?></div>
      <code><?= $slug ?>.yassota.com</code>
      <form method="post" style="margin-top:4px">
        <input type="hidden" name="action" value="tool_sub">
        <input type="hidden" name="slug" value="<?= $slug ?>">
        <button type="submit" class="btn btn-primary btn-sm">إنشاء / تحديث</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Create custom subdomain -->
<div class="card">
  <div class="card-title">إنشاء دومين فرعي مخصص</div>
  <form method="post">
    <input type="hidden" name="action" value="create">
    <div class="row">
      <div>
        <label>الدومين الفرعي</label>
        <input type="text" name="subdomain" placeholder="مثال: blog" pattern="[a-z0-9\-]+" required>
      </div>
      <div>
        <label>مجلد الملفات (اختياري)</label>
        <input type="text" name="dir" placeholder="تلقائي: public_html/{slug}.yassota.com">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:22px">إنشاء</button>
    </div>
  </form>
</div>

<!-- Bulk create for apps -->
<div class="card">
  <div class="card-title">إنشاء دومينات التطبيقات بالجملة</div>
  <p style="font-size:13px;color:var(--muted);margin-bottom:14px">
    ينشئ دومينات فرعية لأكثر التطبيقات مشاهدة التي لا تملك دومين بعد.
    كل دومين يحتوي صفحة هبوط كاملة مع بيانات التطبيق وصفحات قانونية.
  </p>
  <form method="post" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <input type="hidden" name="action" value="bulk_apps">
    <div>
      <label>عدد التطبيقات</label>
      <select name="limit" style="width:140px">
        <option value="10">10 تطبيقات</option>
        <option value="25">25 تطبيق</option>
        <option value="50" selected>50 تطبيق</option>
        <option value="100">100 تطبيق</option>
        <option value="200">200 تطبيق</option>
      </select>
    </div>
    <button type="submit" class="btn btn-orange" style="margin-top:22px"
      onclick="return confirm('سيُنشئ هذا دومينات فرعية متعددة — قد يستغرق وقتاً. هل تريد المتابعة؟')">
      إنشاء الجملة
    </button>
  </form>
</div>

<!-- Existing subdomains -->
<div class="card">
  <div class="card-title">الدومينات الفرعية الحالية (<?= count($subs) ?>)</div>
  <?php if (!$subs): ?>
  <p style="color:var(--muted);font-size:13px">
    <?= ($CP_URL && $CP_TOKEN) ? 'لا توجد دومينات فرعية حتى الآن.' : 'أضف بيانات cPanel لعرض الدومينات.' ?>
  </p>
  <?php else: ?>
  <div style="overflow-x:auto;max-height:420px;overflow-y:auto">
    <table>
      <thead><tr><th>الدومين</th><th>المسار</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($subs as $sd): ?>
        <tr>
          <td><strong><?= h($sd['domain'] ?? '—') ?></strong></td>
          <td><code><?= h($sd['homedir'] ?? $sd['dir'] ?? '—') ?></code></td>
          <td><a href="https://<?= h($sd['domain'] ?? '') ?>" target="_blank" class="btn btn-outline btn-sm">زيارة ↗</a></td>
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
