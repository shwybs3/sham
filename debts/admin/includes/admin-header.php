<?php
/**
 * admin/includes/admin-header.php
 */
$shopName = $settings['shop_name'] ?? 'دفتر الدكان';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'لوحة الإدارة') ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="../assets/css/style.css?v=<?= cssVersion() ?>">
<style>
  body{display:flex; min-height:100vh;}
  .admin-sidebar{
    width:230px; flex-shrink:0; background:var(--panel); border-inline-end:1px solid var(--line);
    padding:20px 0; position:sticky; top:0; height:100vh; overflow-y:auto;
  }
  .admin-sidebar .brand{padding:0 20px 18px; border-bottom:1px solid var(--line); margin-bottom:10px;}
  .admin-nav{display:flex; flex-direction:column; gap:2px; padding:0 10px;}
  .admin-nav a{
    display:flex; align-items:center; gap:11px; padding:11px 12px; border-radius:10px;
    color:var(--ink-dim); font-size:14px; font-weight:600; transition:background .15s, color .15s;
  }
  .admin-nav a:hover{color:var(--ink); background:var(--panel-2);}
  .admin-nav a.active{color:var(--brand); background:rgba(34,197,142,.1);}
  .admin-nav .sep{height:1px; background:var(--line); margin:10px 12px;}
  .admin-main{flex:1; min-width:0;}
  .admin-topbar{
    padding:18px 30px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; background:rgba(11,14,19,.9); backdrop-filter:blur(8px); z-index:10;
  }
  .admin-topbar h1{font-family:var(--disp); font-size:19px; margin:0; display:flex; align-items:center; gap:10px;}
  .admin-content{padding:26px 30px;}
  .panel{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); padding:22px 24px; margin-bottom:22px;}
  .panel h2{font-family:var(--disp); font-size:16px; margin:0 0 18px; display:flex; align-items:center; gap:8px;}
  .table-wrap{overflow-x:auto;}
  table{width:100%; border-collapse:collapse; font-size:14px;}
  th{text-align:right; padding:10px 12px; border-bottom:2px solid var(--line); color:var(--ink-dim); font-size:12px; white-space:nowrap;}
  td{padding:12px; border-bottom:1px solid var(--line); vertical-align:middle;}
  tr:last-child td{border-bottom:none;}
  .badge{display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:700;}
  .badge-debt{background:rgba(239,106,95,.12); color:var(--debt);}
  .badge-ok{background:rgba(34,197,142,.12); color:var(--brand);}
  .row-actions{display:flex; gap:6px;}
  .icon-btn{
    width:32px; height:32px; border-radius:8px; border:1px solid var(--line); background:var(--panel-2);
    color:var(--ink-dim); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:.15s;
  }
  .icon-btn:hover{color:var(--ink); border-color:var(--ink-faint);}
  .icon-btn.danger:hover{color:var(--debt); border-color:var(--debt);}
  .notice{padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:13.5px; display:flex; align-items:center; gap:8px;}
  .notice.ok{background:rgba(34,197,142,.1); border:1px solid rgba(34,197,142,.3); color:var(--brand);}
  .notice.err{background:rgba(239,106,95,.1); border:1px solid rgba(239,106,95,.3); color:var(--debt);}

  /* ── تجاوب مع شاشات الجوال ── */
  @media (max-width: 860px){
    body{flex-direction:column;}
    .admin-sidebar{
      width:100%; height:auto; position:sticky; top:0; z-index:50;
      border-inline-end:none; border-bottom:1px solid var(--line);
      padding:10px 0;
    }
    .admin-sidebar .brand{padding:0 14px 10px; margin-bottom:8px;}
    .admin-nav{
      flex-direction:row; flex-wrap:nowrap; overflow-x:auto; padding:0 10px 4px;
      -webkit-overflow-scrolling:touch; scrollbar-width:none;
    }
    .admin-nav::-webkit-scrollbar{display:none;}
    .admin-nav a{white-space:nowrap; flex-shrink:0; padding:9px 12px; font-size:13px;}
    .admin-nav .sep{width:1px; height:22px; margin:0 4px; align-self:center; flex-shrink:0;}
    .admin-topbar{padding:14px 16px;}
    .admin-content{padding:16px;}
    .panel{padding:16px;}
    .stat-strip{grid-template-columns:1fr 1fr;}
    .row-actions{flex-wrap:wrap;}
  }
  @media (max-width: 420px){
    .stat-strip{grid-template-columns:1fr;}
  }
</style>
</head>
<body>
  <div class="admin-sidebar">
    <a href="index.php" class="brand" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--ink);">
      <div class="mark" style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--brand),#17966b);display:flex;align-items:center;justify-content:center;color:#04150e;"><?= icon('store', 18) ?></div>
      <div style="font-family:var(--disp); font-weight:800; font-size:15px;">لوحة الإدارة</div>
    </a>
    <nav class="admin-nav">
      <a href="quick-entry.php" class="<?= ($activeNav ?? '') === 'quick' ? 'active' : '' ?>"><?= icon('plus-circle', 18) ?> تسجيل سريع</a>
      <a href="index.php" class="<?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>"><?= icon('dashboard', 18) ?> نظرة عامة</a>
      <a href="customers.php" class="<?= ($activeNav ?? '') === 'customers' ? 'active' : '' ?>"><?= icon('users', 18) ?> الزبائن</a>
      <div class="sep"></div>
      <a href="prices.php" class="<?= ($activeNav ?? '') === 'prices' ? 'active' : '' ?>"><?= icon('tag', 18) ?> قائمة الأسعار</a>
      <a href="market-prices.php" class="<?= ($activeNav ?? '') === 'market' ? 'active' : '' ?>"><?= icon('trending-up', 18) ?> أسعار السوق</a>
      <a href="articles.php" class="<?= ($activeNav ?? '') === 'articles' ? 'active' : '' ?>"><?= icon('receipt', 18) ?> المدونة</a>
      <a href="settings.php" class="<?= ($activeNav ?? '') === 'settings' ? 'active' : '' ?>"><?= icon('settings', 18) ?> الإعدادات</a>
      <div class="sep"></div>
      <a href="migrate.php" class="<?= ($activeNav ?? '') === 'migrate' ? 'active' : '' ?>"><?= icon('database', 18) ?> ترقية DB</a>
      <a href="../index.php" target="_blank"><?= icon('external', 18) ?> عرض الموقع</a>
      <a href="logout.php"><?= icon('logout', 18) ?> تسجيل الخروج</a>
    </nav>
  </div>
  <div class="admin-main">
    <div class="admin-topbar">
      <h1><?= h($pageTitle ?? 'لوحة الإدارة') ?></h1>
    </div>
    <div class="admin-content">
