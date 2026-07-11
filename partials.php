<?php
/* ═══════════════════════════════════════════════
   YASSOTA — partials.php
   Shared header/sidebar/app-card/footer markup so
   the homepage and the category/developer/top/updates
   listing pages render identical, consistent UI instead
   of each page hand-rolling its own copy.
   ═══════════════════════════════════════════════ */

function partial_icon(string $name): string {
    $icons = [
        'download' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>',
        'search'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
        'menu'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>',
        'star'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24" stroke="#fbbf24" stroke-width="1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'apps'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="9" height="9" rx="2"/><rect x="13" y="2" width="9" height="9" rx="2"/><rect x="2" y="13" width="9" height="9" rx="2"/><rect x="13" y="13" width="9" height="9" rx="2"/></svg>',
        'games'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 12h4m-2-2v4"/><circle cx="17" cy="10" r="1" fill="currentColor"/><circle cx="17" cy="14" r="1" fill="currentColor"/><path d="M2 8a2 2 0 012-2h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8z"/></svg>',
        'info'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>',
        'home'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9"/></svg>',
        'developer'=> '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0114 0v1"/></svg>',
        'trending' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-9.5 9.5-5-5L2 18"/><path d="M16 6h6v6"/></svg>',
        'clock'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
        'arrow-r'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>',
        'external' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function partial_wave(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#00f5ff" stroke-width="1.5" fill="none"/>
    </svg>';
}

/* ── Header (logo, search, top nav) ── */
function render_site_header(string $search = '', string $activeNav = 'home'): void { ?>
<header class="site-header">
  <a href="index.php" class="logo">yass<span>ota</span></a>

  <form action="index.php" method="get" class="header-search">
    <input type="text" name="q" placeholder="ابحث عن تطبيق أو لعبة..." value="<?= h($search) ?>">
    <button type="submit"><?= partial_icon('search') ?></button>
  </form>

  <nav class="header-nav">
    <a href="index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">الرئيسية</a>
    <a href="index.php?cat=apps" class="<?= $activeNav === 'apps' ? 'active' : '' ?>">تطبيقات</a>
    <a href="index.php?cat=games" class="<?= $activeNav === 'games' ? 'active' : '' ?>">ألعاب</a>
    <button class="nav-toggle" aria-label="القائمة"><?= partial_icon('menu') ?></button>
  </nav>
</header>
<?php }

/* ── Sidebar: category list + a few discovery links reused on every listing page ── */
function render_site_sidebar(PDO $pdo, string $activeCatSlug = ''): void {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
    ?>
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-title">الأقسام</div>
    <a href="index.php" class="sidebar-link <?= !$activeCatSlug ? 'active' : '' ?>">
      <?= partial_icon('home') ?> الكل
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="category.php?slug=<?= h($cat['slug']) ?>" class="sidebar-link <?= $activeCatSlug === $cat['slug'] ? 'active' : '' ?>">
      <?= partial_icon($cat['slug'] === 'games' ? 'games' : 'apps') ?>
      <?= h($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-title">اكتشف</div>
    <a href="top.php?by=downloads" class="sidebar-link"><?= partial_icon('trending') ?> الأكثر تحميلاً</a>
    <a href="top.php?by=views" class="sidebar-link"><?= partial_icon('trending') ?> الأكثر زيارة</a>
    <a href="updates.php" class="sidebar-link"><?= partial_icon('clock') ?> آخر التحديثات</a>
  </div>
</aside>
<?php }

/* ── One app card, identical everywhere it's used ── */
function render_app_card(array $app): void { ?>
<div class="app-card reveal">
  <a href="app.php?slug=<?= h($app['slug']) ?>" data-hardnav="1">
    <?php if (!empty($app['icon_path'])): ?>
      <img src="<?= h($app['icon_path']) ?>" alt="<?= h($app['name']) ?>" class="app-card-icon" loading="lazy">
    <?php else: ?>
      <div class="app-card-icon-placeholder">
        <?= partial_icon('apps') ?>
      </div>
    <?php endif; ?>
    <div class="app-card-cat"><?= h($app['cat_name'] ?? 'تطبيق') ?></div>
    <div class="app-card-name"><?= h($app['name']) ?></div>
    <div class="app-card-meta">
      <div class="app-card-rating">
        <?= partial_icon('star') ?>
        <span><?= h($app['rating']) ?></span>
      </div>
      <span class="app-card-size"><?= h($app['size_mb'] ? $app['size_mb'] . ' MB' : '') ?></span>
    </div>
  </a>
  <a href="download.php?id=<?= (int)$app['id'] ?>" class="btn-dl-card" data-hardnav="1">
    <?= partial_icon('download') ?> تحميل
  </a>
</div>
<?php }

/* ── A grid of app cards, with an empty-state message ── */
function render_app_grid(array $apps, string $emptyMessage = 'لا توجد نتائج'): void {
    if (!$apps) {
        echo '<div style="text-align:center;padding:60px 20px;color:var(--muted)"><p style="font-size:16px">' . h($emptyMessage) . '</p></div>';
        return;
    }
    ?>
  <div class="apps-grid">
    <?php foreach ($apps as $app): render_app_card($app); endforeach; ?>
  </div>
<?php }

function render_site_footer(): void { ?>
<footer class="site-footer">
  <div class="footer-logo">yass<span style="color:var(--purple)">ota</span></div>
  <p>&copy; <?= date('Y') ?> yassota — جميع الحقوق محفوظة</p>
</footer>
<?php }

/* ── Pagination, identical shape on every listing page ── */
function render_pagination(int $page, int $totalPages): void {
    if ($totalPages <= 1) return;
    ?>
  <div class="pagination reveal">
    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php }
