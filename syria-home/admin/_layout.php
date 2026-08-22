<?php
/* Shared admin chrome: call admin_head() then admin_sidebar($active), print content, then admin_foot(). */

function admin_head(string $title): void {
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?> — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    :root{--brand1:#6366f1;--brand2:#22d3ee;--ink:#0f172a;--muted:#64748b;--line:#e7eaf3;--bg:#f4f6fb;--card:#fff;--ok:#16a34a;--err:#dc2626;--warn:#d97706}
    *{box-sizing:border-box}body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--ink)}
    .shell{display:flex;min-height:100vh}
    .sidebar{width:236px;background:#0f172a;color:#cbd5e1;flex-shrink:0;padding:20px 14px;position:sticky;top:0;height:100vh;overflow:auto}
    .sidebar .brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:17px;padding:6px 8px 20px}
    .sidebar .brand span{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--brand1),var(--brand2));display:flex;align-items:center;justify-content:center}
    .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:600;margin-bottom:2px}
    .sidebar a i{width:18px;text-align:center}
    .sidebar a:hover{background:#1e293b;color:#fff}
    .sidebar a.active{background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff}
    .sidebar .grp{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#475569;padding:16px 12px 6px;font-weight:800}
    .main{flex:1;min-width:0}
    .topbar{background:#fff;border-bottom:1px solid var(--line);padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
    .topbar h1{font-size:19px;margin:0}
    .topbar .who{font-size:13px;color:var(--muted)}
    .content{padding:26px 28px 60px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:22px;margin-bottom:20px}
    .grid-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:6px}
    .stat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px}
    .stat .n{font-size:26px;font-weight:800}
    .stat .l{font-size:12px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
    .stat i{float:right;font-size:20px;color:var(--brand1);opacity:.5}
    table{width:100%;border-collapse:collapse;font-size:13.5px}
    th,td{padding:11px 10px;text-align:left;border-bottom:1px solid var(--line)}
    th{font-size:11px;text-transform:uppercase;color:var(--muted);letter-spacing:.04em}
    .btn{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;border:0;padding:10px 16px;border-radius:9px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none}
    .btn.gray{background:#eef1f8;color:#334155}
    .btn.red{background:#fef2f2;color:var(--err)}
    .btn.sm{padding:7px 11px;font-size:12px}
    label{display:block;font-size:13px;font-weight:700;margin:14px 0 6px;color:#334155}
    input[type=text],input[type=password],input[type=number],input[type=url],input[type=email],textarea,select{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:9px;font-size:14px;font-family:inherit}
    textarea{min-height:110px;resize:vertical}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 9px;border-radius:99px}
    .badge.ok{background:#f0fdf4;color:var(--ok)}
    .badge.off{background:#f1f5f9;color:var(--muted)}
    .badge.warn{background:#fffbeb;color:var(--warn)}
    .flash{padding:12px 14px;border-radius:10px;font-size:13.5px;margin-bottom:16px}
    .flash.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
    .flash.err{background:#fef2f2;border:1px solid #fecaca;color:var(--err)}
    .tabs{display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap}
    .tabs a{padding:9px 15px;border-radius:9px;font-size:13px;font-weight:700;color:#334155;background:#fff;border:1px solid var(--line)}
    .tabs a.active{background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;border-color:transparent}
    .hint{font-size:12px;color:var(--muted);margin-top:4px}
    .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap}
    .toolbar input[type=search]{max-width:260px}

    .menu-toggle{display:none;background:none;border:0;font-size:20px;color:var(--ink);cursor:pointer;margin-right:8px;padding:4px 6px;flex-shrink:0}
    .sidebar-backdrop{display:none}
    @media (max-width: 960px){
      .shell{position:relative;overflow-x:hidden}
      .sidebar{position:fixed;top:0;left:0;bottom:0;height:100dvh;width:260px;max-width:82vw;z-index:210;
        transform:translateX(-100%);transition:transform .25s ease;box-shadow:0 0 40px rgba(0,0,0,.35)}
      .sidebar.open{transform:translateX(0)}
      .sidebar-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:200;
        opacity:0;pointer-events:none;transition:opacity .2s ease}
      .sidebar-backdrop.show{display:block;opacity:1;pointer-events:auto}
      .menu-toggle{display:inline-flex;align-items:center}
      .main{width:100%;min-width:0}
      .topbar{padding:14px 16px;flex-wrap:wrap;gap:8px}
      .topbar h1{font-size:17px}
      .content{padding:16px 14px 50px}
      .row2,.feature-grid{grid-template-columns:1fr !important}
      .grid-stats{grid-template-columns:repeat(2,1fr)}
      table{display:block;overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch}
      .toolbar{align-items:stretch}
      .tabs{overflow-x:auto;flex-wrap:nowrap;padding-bottom:4px}
      .tabs a{flex-shrink:0}
    }
    </style></head>
    <body>
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="shCloseSidebar()"></div>
    <script>
    function shOpenSidebar(){document.getElementById('adminSidebar').classList.add('open');document.getElementById('sidebarBackdrop').classList.add('show');document.body.style.overflow='hidden';}
    function shCloseSidebar(){document.getElementById('adminSidebar').classList.remove('open');document.getElementById('sidebarBackdrop').classList.remove('show');document.body.style.overflow='';}
    </script>
    <div class="shell">
    <?php
}

const ADMIN_NAV = [
    'dashboard' => ['Dashboard', 'fa-gauge-high'],
    'articles' => ['Articles', 'fa-newspaper'],
    'tools' => ['Web Tools', 'fa-wrench'],
    'categories' => ['Categories', 'fa-tags'],
    'products' => ['Store Products', 'fa-store'],
    'orders' => ['Orders & Payments', 'fa-inbox'],
    'link-checker' => ['Broken Link Checker', 'fa-link-slash'],
    'subsites' => ['Subdomain Sites', 'fa-sitemap'],
    'ai-assistant' => ['AI Assistant', 'fa-robot'],
    'insights' => ['Google Insights', 'fa-chart-line'],
    'settings' => ['Settings', 'fa-gear'],
];

function admin_sidebar(string $active): void {
    ?>
    <aside class="sidebar" id="adminSidebar" onclick="if(event.target.closest('a')) shCloseSidebar()">
      <div class="brand"><span><i class="fa-solid fa-layer-group"></i></span> <?= e(setting('site_name', 'Syria Home')) ?></div>
      <div class="grp">Content</div>
      <?php foreach (['dashboard','articles','tools','categories'] as $k): [$label,$icon] = ADMIN_NAV[$k]; ?>
        <a class="<?= $active === $k ? 'active' : '' ?>" href="?page=<?= $k ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></a>
      <?php endforeach; ?>
      <div class="grp">Store</div>
      <?php foreach (['products','orders'] as $k): [$label,$icon] = ADMIN_NAV[$k]; ?>
        <a class="<?= $active === $k ? 'active' : '' ?>" href="?page=<?= $k ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></a>
      <?php endforeach; ?>
      <div class="grp">Growth</div>
      <?php foreach (['link-checker','subsites'] as $k): [$label,$icon] = ADMIN_NAV[$k]; ?>
        <a class="<?= $active === $k ? 'active' : '' ?>" href="?page=<?= $k ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></a>
      <?php endforeach; ?>
      <div class="grp">Intelligence</div>
      <?php foreach (['ai-assistant','insights'] as $k): [$label,$icon] = ADMIN_NAV[$k]; ?>
        <a class="<?= $active === $k ? 'active' : '' ?>" href="?page=<?= $k ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></a>
      <?php endforeach; ?>
      <div class="grp">System</div>
      <a class="<?= $active === 'settings' ? 'active' : '' ?>" href="?page=settings"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="<?= site_url('') ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View site</a>
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>
    <div class="main">
    <?php
}

function admin_topbar(string $title): void {
    ?>
    <div class="topbar">
      <div style="display:flex;align-items:center">
        <button class="menu-toggle" onclick="shOpenSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
        <h1><?= e($title) ?></h1>
      </div>
      <div class="who"><i class="fa-regular fa-circle-user"></i> <?= e($_SESSION['admin_user'] ?? '') ?></div>
    </div>
    <div class="content">
    <?php
}

function admin_foot(): void {
    echo '</div></div></div></body></html>';
}

function flash(string $type, string $msg): void {
    echo '<div class="flash ' . e($type) . '">' . e($msg) . '</div>';
}
