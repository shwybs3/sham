<?php
/**
 * gidx-admin.php — Standalone admin panel for the Google Indexing API tool
 * Requires an active admin session from admin.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gidx-functions.php';

// Reuse admin session from admin.php
if (empty($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$msg  = '';
$msgType = 'success';

// ── Actions ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'grant_premium') {
        $ident = preg_replace('/[^a-f0-9]/', '', $_POST['identifier'] ?? '');
        $days  = max(1, (int)($_POST['days'] ?? 30));
        if ($ident) {
            gidx_grant_premium($pdo, $ident, $days);
            $msg = "تم منح Premium للمستخدم لمدة $days يوم";
        }
    } elseif ($action === 'revoke_premium') {
        $ident = preg_replace('/[^a-f0-9]/', '', $_POST['identifier'] ?? '');
        if ($ident) {
            $pdo->prepare("UPDATE gidx_quota SET premium_until = NULL WHERE identifier = ?")->execute([$ident]);
            $msg = "تم إلغاء Premium";
        }
    } elseif ($action === 'save_settings') {
        foreach (['nowpayments_api_key','nowpayments_ipn_secret','google_oauth_client_id','google_oauth_client_secret'] as $k) {
            set_cfg($pdo, $k, trim($_POST[$k] ?? ''));
        }
        $msg = "تم حفظ الإعدادات";
    } elseif ($action === 'delete_log') {
        $id = (int)($_POST['log_id'] ?? 0);
        if ($id) $pdo->prepare("DELETE FROM gidx_log WHERE id = ?")->execute([$id]);
        $msg = "تم حذف السجل";
    } elseif ($action === 'clear_logs') {
        $pdo->exec("TRUNCATE TABLE gidx_log");
        $msg = "تم مسح كل السجلات";
    }
}

// ── Data ─────────────────────────────────────────────────────────────────────
$stats = [
    'total_users'   => (int)$pdo->query("SELECT COUNT(*) FROM gidx_quota")->fetchColumn(),
    'premium_users' => (int)$pdo->query("SELECT COUNT(*) FROM gidx_quota WHERE premium_until > NOW()")->fetchColumn(),
    'total_payments'=> (int)$pdo->query("SELECT COUNT(*) FROM gidx_payments WHERE status IN ('finished','confirmed')")->fetchColumn(),
    'revenue_usd'   => (float)$pdo->query("SELECT COALESCE(SUM(price_usd),0) FROM gidx_payments WHERE status IN ('finished','confirmed')")->fetchColumn(),
    'total_logs'    => (int)$pdo->query("SELECT COUNT(*) FROM gidx_log")->fetchColumn(),
    'ok_logs'       => (int)$pdo->query("SELECT COUNT(*) FROM gidx_log WHERE result='ok'")->fetchColumn(),
];

function gidx_admin_url(string $p): string {
    return 'gidx-admin.php?page=' . urlencode($p);
}

$navItems = [
    'dashboard'   => ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard'],
    'subscribers' => ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'المشتركون'],
    'payments'    => ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'المدفوعات'],
    'logs'        => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'سجل الإرسال'],
    'settings'    => ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'الإعدادات'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة تحكم أداة الفهرسة</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f0f4f8;color:#1a202c;min-height:100vh;display:flex}
a{text-decoration:none;color:inherit}

/* Sidebar */
.sidebar{width:240px;min-height:100vh;background:#1a202c;color:#e2e8f0;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-brand{padding:20px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
.sidebar-brand h1{font-size:15px;font-weight:700;color:#fff;line-height:1.3}
.sidebar-brand p{font-size:11px;color:#718096;margin-top:2px}
.nav{padding:12px 0;flex:1}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:#a0aec0;cursor:pointer;transition:all .15s;border-radius:0}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff}
.nav-item.active{background:rgba(229,62,62,.15);color:#fc8181;border-right:3px solid #e53e3e}
.nav-item svg{width:18px;height:18px;flex-shrink:0}
.sidebar-footer{padding:12px 16px;border-top:1px solid rgba(255,255,255,.1);font-size:12px;color:#718096}
.sidebar-footer a{color:#fc8181}

/* Main */
.main{flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.topbar h2{font-size:18px;font-weight:700;color:#1a202c}
.topbar-right{display:flex;align-items:center;gap:12px;font-size:13px;color:#718096}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600}
.badge-green{background:#c6f6d5;color:#276749}
.badge-red{background:#fed7d7;color:#9b2c2c}
.badge-blue{background:#bee3f8;color:#2a69ac}
.badge-yellow{background:#fefcbf;color:#744210}

.content{padding:24px;flex:1}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;border:1px solid}
.alert-success{background:#f0fff4;border-color:#9ae6b4;color:#276749}
.alert-error{background:#fff5f5;border-color:#fc8181;color:#9b2c2c}

/* Cards */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:12px;padding:20px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.stat-card .stat-label{font-size:12px;color:#718096;margin-bottom:6px;font-weight:500}
.stat-card .stat-value{font-size:28px;font-weight:700;color:#1a202c}
.stat-card .stat-sub{font-size:11px;color:#a0aec0;margin-top:4px}
.stat-card.red .stat-value{color:#e53e3e}
.stat-card.green .stat-value{color:#38a169}
.stat-card.blue .stat-value{color:#3182ce}
.stat-card.yellow .stat-value{color:#d69e2e}

/* Panel */
.panel{background:#fff;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px;overflow:hidden}
.panel-header{padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.panel-header h3{font-size:14px;font-weight:700;color:#1a202c}
.panel-body{padding:20px}

/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:right;padding:10px 12px;background:#f7fafc;color:#718096;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #e2e8f0}
td{padding:10px 12px;border-bottom:1px solid #f0f4f8;color:#2d3748;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#f7fafc}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s}
.btn-primary{background:#e53e3e;color:#fff}
.btn-primary:hover{background:#c53030}
.btn-outline{background:#fff;color:#4a5568;border:1px solid #e2e8f0}
.btn-outline:hover{background:#f7fafc}
.btn-sm{padding:5px 10px;font-size:12px;border-radius:6px;cursor:pointer;border:none;font-weight:500}
.btn-green-sm{background:#c6f6d5;color:#276749}
.btn-green-sm:hover{background:#9ae6b4}
.btn-red-sm{background:#fed7d7;color:#9b2c2c}
.btn-red-sm:hover{background:#fc8181;color:#fff}

/* Form */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:#4a5568;margin-bottom:6px}
.form-input{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#2d3748;background:#fff;transition:border .15s}
.form-input:focus{outline:none;border-color:#e53e3e;box-shadow:0 0 0 3px rgba(229,62,62,.1)}
.form-hint{font-size:11px;color:#a0aec0;margin-top:4px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:640px){.form-grid{grid-template-columns:1fr}}

/* Mono */
.mono{font-family:'Courier New',monospace;font-size:11px;background:#f7fafc;padding:2px 6px;border-radius:4px;color:#4a5568}

/* Empty state */
.empty{text-align:center;padding:40px;color:#a0aec0}
.empty svg{width:48px;height:48px;margin:0 auto 12px;display:block;color:#cbd5e0}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <h1>أداة فهرسة Google</h1>
    <p>لوحة التحكم</p>
  </div>
  <nav class="nav">
    <?php foreach ($navItems as $key => $item): ?>
    <a href="<?= gidx_admin_url($key) ?>" class="nav-item <?= $page === $key ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="google-indexing-api.php" target="_blank">← الأداة</a>
    &nbsp;|&nbsp;
    <a href="admin.php">الإدارة الرئيسية</a>
  </div>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <h2>
      <?php
        $titles = ['dashboard'=>'الرئيسية','subscribers'=>'المشتركون','payments'=>'المدفوعات','logs'=>'سجل الإرسال','settings'=>'الإعدادات'];
        echo $titles[$page] ?? 'الرئيسية';
      ?>
    </h2>
    <div class="topbar-right">
      <span>مرحباً، Admin</span>
      <a href="admin.php?logout=1" style="color:#e53e3e;font-weight:600">خروج</a>
    </div>
  </div>

  <div class="content">
    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div>
    <?php endif; ?>

<?php /* ═══════════ DASHBOARD ═══════════ */ if ($page === 'dashboard'): ?>

  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="stat-label">إجمالي المستخدمين</div>
      <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
    </div>
    <div class="stat-card green">
      <div class="stat-label">مستخدمو Premium</div>
      <div class="stat-value"><?= number_format($stats['premium_users']) ?></div>
    </div>
    <div class="stat-card yellow">
      <div class="stat-label">مدفوعات مؤكدة</div>
      <div class="stat-value"><?= number_format($stats['total_payments']) ?></div>
    </div>
    <div class="stat-card red">
      <div class="stat-label">الإيرادات (USD)</div>
      <div class="stat-value">$<?= number_format($stats['revenue_usd'], 0) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">إجمالي الإرسالات</div>
      <div class="stat-value"><?= number_format($stats['total_logs']) ?></div>
      <div class="stat-sub"><?= number_format($stats['ok_logs']) ?> ناجح</div>
    </div>
  </div>

  <!-- Recent subscribers -->
  <?php
  $recent = $pdo->query("SELECT q.*, s.email, s.name FROM gidx_quota q LEFT JOIN subscribers s ON s.identifier=q.identifier ORDER BY q.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <div class="panel">
    <div class="panel-header">
      <h3>أحدث المستخدمين</h3>
      <a href="<?= gidx_admin_url('subscribers') ?>" class="btn btn-outline" style="font-size:12px;padding:6px 12px">عرض الكل</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المعرّف</th><th>البريد</th><th>الاستخدام</th><th>الحالة</th><th>تاريخ التسجيل</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r):
          $isPremium = !empty($r['premium_until']) && strtotime($r['premium_until']) > time(); ?>
          <tr>
            <td><span class="mono"><?= h(substr($r['identifier'],0,12)) ?>…</span></td>
            <td><?= h($r['email'] ?? '—') ?></td>
            <td><?= (int)$r['used_count'] ?> / <?= (int)$r['limit'] ?></td>
            <td><?php if ($isPremium): ?><span class="badge badge-green">Premium</span><?php else: ?><span class="badge badge-blue">مجاني</span><?php endif; ?></td>
            <td style="font-size:11px;color:#718096"><?= h($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5" class="empty">لا يوجد مستخدمون بعد</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent payments -->
  <?php $recentPay = $pdo->query("SELECT * FROM gidx_payments ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); ?>
  <div class="panel">
    <div class="panel-header">
      <h3>أحدث المدفوعات</h3>
      <a href="<?= gidx_admin_url('payments') ?>" class="btn btn-outline" style="font-size:12px;padding:6px 12px">عرض الكل</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>رقم الطلب</th><th>العملة</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($recentPay as $p):
          $color = match($p['status']) { 'finished','confirmed' => 'green', 'waiting','pending' => 'yellow', default => 'red' }; ?>
          <tr>
            <td><span class="mono"><?= h(substr($p['order_id'], 0, 20)) ?>…</span></td>
            <td><?= h(strtoupper($p['pay_currency'])) ?></td>
            <td>$<?= number_format((float)$p['price_usd'], 2) ?></td>
            <td><span class="badge badge-<?= $color ?>"><?= h($p['status']) ?></span></td>
            <td style="font-size:11px;color:#718096"><?= h($p['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recentPay): ?><tr><td colspan="5" class="empty">لا توجد مدفوعات بعد</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php /* ═══════════ SUBSCRIBERS ═══════════ */ elseif ($page === 'subscribers'):
  $search = trim($_GET['q'] ?? '');
  $searchSql = $search ? "AND (q.identifier LIKE ? OR s.email LIKE ? OR s.name LIKE ?)" : "";
  $stmt = $pdo->prepare("SELECT q.*, s.email, s.name FROM gidx_quota q LEFT JOIN subscribers s ON s.identifier=q.identifier WHERE 1 $searchSql ORDER BY q.created_at DESC LIMIT 200");
  if ($search) $stmt->execute(["%$search%", "%$search%", "%$search%"]);
  else $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-body" style="padding:12px 20px">
      <form method="get" style="display:flex;gap:10px;align-items:center">
        <input type="hidden" name="page" value="subscribers">
        <input class="form-input" type="text" name="q" value="<?= h($search) ?>" placeholder="بحث بالمعرّف أو البريد أو الاسم..." style="max-width:320px">
        <button type="submit" class="btn btn-primary">بحث</button>
        <?php if ($search): ?><a href="<?= gidx_admin_url('subscribers') ?>" class="btn btn-outline">مسح</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h3>المشتركون (<?= count($rows) ?>)</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المعرّف</th><th>البريد</th><th>الاسم</th><th>الاستخدام</th><th>Premium حتى</th><th>الحالة</th><th>إجراء</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
          $isPremium = !empty($r['premium_until']) && strtotime($r['premium_until']) > time(); ?>
          <tr>
            <td><span class="mono"><?= h(substr($r['identifier'],0,16)) ?>…</span></td>
            <td><?= h($r['email'] ?? '—') ?></td>
            <td><?= h($r['name'] ?? '—') ?></td>
            <td><?= (int)$r['used_count'] ?> / <?= (int)$r['limit'] ?></td>
            <td style="font-size:11px"><?= $isPremium ? h(substr($r['premium_until'],0,10)) : '—' ?></td>
            <td><?php if ($isPremium): ?><span class="badge badge-green">Premium</span><?php else: ?><span class="badge badge-blue">مجاني</span><?php endif; ?></td>
            <td>
              <form method="post" style="display:inline-flex;gap:6px;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="identifier" value="<?= h($r['identifier']) ?>">
                <?php if ($isPremium): ?>
                  <button name="action" value="revoke_premium" class="btn-sm btn-red-sm">إلغاء</button>
                <?php else: ?>
                  <input type="number" name="days" value="30" min="1" max="3650" style="width:55px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px">
                  <button name="action" value="grant_premium" class="btn-sm btn-green-sm">منح</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7"><div class="empty">لا يوجد مستخدمون</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php /* ═══════════ PAYMENTS ═══════════ */ elseif ($page === 'payments'):
  $filter = $_GET['status'] ?? '';
  $filterSql = $filter ? "WHERE status = ?" : "";
  $stmt = $pdo->prepare("SELECT * FROM gidx_payments $filterSql ORDER BY created_at DESC LIMIT 500");
  $filter ? $stmt->execute([$filter]) : $stmt->execute();
  $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $statuses = ['','pending','waiting','finished','confirmed','failed','expired'];
?>
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-body" style="padding:12px 20px">
      <form method="get" style="display:flex;gap:10px;align-items:center">
        <input type="hidden" name="page" value="payments">
        <select name="status" class="form-input" style="max-width:200px">
          <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= $s ?: 'كل الحالات' ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">فلترة</button>
        <?php if ($filter): ?><a href="<?= gidx_admin_url('payments') ?>" class="btn btn-outline">مسح</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h3>المدفوعات (<?= count($payments) ?>)</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>رقم الطلب</th><th>المعرّف</th><th>العملة</th><th>المبلغ المطلوب</th><th>المستلم</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p):
          $color = match($p['status']) { 'finished','confirmed' => 'green', 'waiting','pending' => 'yellow', default => 'red' }; ?>
          <tr>
            <td><span class="mono"><?= h(substr($p['order_id'],0,24)) ?>…</span></td>
            <td><span class="mono"><?= h(substr($p['identifier'],0,12)) ?>…</span></td>
            <td><?= h(strtoupper($p['pay_currency'])) ?></td>
            <td>$<?= number_format((float)$p['price_usd'],2) ?></td>
            <td><?= $p['actually_paid'] !== null ? h($p['actually_paid']) : '—' ?></td>
            <td><span class="badge badge-<?= $color ?>"><?= h($p['status']) ?></span></td>
            <td style="font-size:11px;color:#718096"><?= h($p['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="7"><div class="empty">لا توجد مدفوعات</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php /* ═══════════ LOGS ═══════════ */ elseif ($page === 'logs'):
  $filterResult = $_GET['result'] ?? '';
  $filterSql = $filterResult ? "WHERE result = ?" : "";
  $stmt = $pdo->prepare("SELECT * FROM gidx_log $filterSql ORDER BY created_at DESC LIMIT 500");
  $filterResult ? $stmt->execute([$filterResult]) : $stmt->execute();
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-body" style="padding:12px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <a href="<?= gidx_admin_url('logs') ?>&result=ok" class="btn btn-outline" style="font-size:12px">ناجح فقط</a>
      <a href="<?= gidx_admin_url('logs') ?>&result=fail" class="btn btn-outline" style="font-size:12px">فاشل فقط</a>
      <a href="<?= gidx_admin_url('logs') ?>" class="btn btn-outline" style="font-size:12px">الكل</a>
      <form method="post" style="margin-right:auto" onsubmit="return confirm('هل أنت متأكد من مسح كل السجلات؟')">
        <?= csrf_field() ?>
        <button name="action" value="clear_logs" class="btn btn-primary">مسح كل السجلات</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h3>سجل الإرسال (<?= count($logs) ?>)</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المعرّف</th><th>الرابط</th><th>النتيجة</th><th>الرسالة</th><th>التاريخ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><span class="mono"><?= h(substr($l['identifier'],0,12)) ?>…</span></td>
            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><a href="<?= h($l['url']) ?>" target="_blank" style="color:#3182ce;font-size:12px"><?= h($l['url']) ?></a></td>
            <td><?php if ($l['result']==='ok'): ?><span class="badge badge-green">نجاح</span><?php else: ?><span class="badge badge-red">فشل</span><?php endif; ?></td>
            <td style="font-size:12px;color:#718096;max-width:200px"><?= h($l['message'] ?? '') ?></td>
            <td style="font-size:11px;color:#718096"><?= h($l['created_at']) ?></td>
            <td>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="log_id" value="<?= (int)$l['id'] ?>">
                <button name="action" value="delete_log" class="btn-sm btn-red-sm">حذف</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6"><div class="empty">لا توجد سجلات</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php /* ═══════════ SETTINGS ═══════════ */ elseif ($page === 'settings'): ?>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">

    <div class="panel">
      <div class="panel-header"><h3>إعدادات NOWPayments</h3></div>
      <div class="panel-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">API Key</label>
            <input class="form-input" type="text" name="nowpayments_api_key" value="<?= h(get_cfg($pdo,'nowpayments_api_key')) ?>" placeholder="M9WQVSQ-...">
            <div class="form-hint">مفتاح API الخاص بحسابك على nowpayments.io</div>
          </div>
          <div class="form-group">
            <label class="form-label">IPN Secret</label>
            <input class="form-input" type="text" name="nowpayments_ipn_secret" value="<?= h(get_cfg($pdo,'nowpayments_ipn_secret')) ?>" placeholder="مفتاح HMAC للتحقق من IPN">
            <div class="form-hint">يُستخدم لتحقق HMAC-SHA512 من كل طلب IPN</div>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0;padding:12px;background:#fffbeb;border-radius:8px;border:1px solid #fbd38d">
          <p style="font-size:12px;color:#744210">
            رابط IPN (أضفه في إعدادات NOWPayments):<br>
            <strong><?= rtrim(SITE_URL,'/') ?>/gidx-payment-webhook.php</strong>
          </p>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><h3>إعدادات Google OAuth (اختياري)</h3></div>
      <div class="panel-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Client ID</label>
            <input class="form-input" type="text" name="google_oauth_client_id" value="<?= h(get_cfg($pdo,'google_oauth_client_id')) ?>" placeholder="xxxxx.apps.googleusercontent.com">
          </div>
          <div class="form-group">
            <label class="form-label">Client Secret</label>
            <input class="form-input" type="text" name="google_oauth_client_secret" value="<?= h(get_cfg($pdo,'google_oauth_client_secret')) ?>" placeholder="GOCSPX-...">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0;padding:12px;background:#ebf8ff;border-radius:8px;border:1px solid #90cdf4">
          <p style="font-size:12px;color:#2a69ac">
            Redirect URI (أضفه في Google Cloud Console):<br>
            <strong><?= rtrim(SITE_URL,'/') ?>/google-indexing-api/callback</strong>
          </p>
        </div>
      </div>
    </div>

    <div style="text-align:left">
      <button type="submit" class="btn btn-primary" style="padding:10px 28px">حفظ الإعدادات</button>
    </div>
  </form>

<?php endif; ?>
  </div><!-- /content -->
</div><!-- /main -->
</body>
</html>
