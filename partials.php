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
        'article'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function partial_wave(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#2563eb" stroke-width="1.5" fill="none"/>
    </svg>';
}

/* ── Header (logo, search, top nav) ── */
function render_site_header(string $search = '', string $activeNav = 'home'): void { ?>
<header class="site-header">
  <a href="/" class="logo">yass<span>ota</span></a>

  <div class="header-search-wrap">
    <form action="/" method="get" class="header-search" id="header-search-form" autocomplete="off">
      <input type="text" name="q" id="search-input" placeholder="ابحث عن تطبيق أو لعبة..." value="<?= h($search) ?>">
      <button type="submit"><?= partial_icon('search') ?></button>
    </form>
    <div id="search-suggestions" class="search-suggestions" hidden></div>
  </div>

  <nav class="header-nav">
    <a href="/" class="<?= $activeNav === 'home' ? 'active' : '' ?>">الرئيسية</a>
    <a href="/?cat=apps" class="<?= $activeNav === 'apps' ? 'active' : '' ?>">تطبيقات</a>
    <a href="/?cat=games" class="<?= $activeNav === 'games' ? 'active' : '' ?>">ألعاب</a>
    <button type="button" class="mobile-search-toggle" id="mobile-search-toggle" aria-label="بحث"><?= partial_icon('search') ?></button>
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
    <a href="/" class="sidebar-link <?= !$activeCatSlug ? 'active' : '' ?>">
      <?= partial_icon('home') ?> الكل
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= h(url('category/' . $cat['slug'])) ?>" class="sidebar-link <?= $activeCatSlug === $cat['slug'] ? 'active' : '' ?>">
      <?= partial_icon($cat['slug'] === 'games' ? 'games' : 'apps') ?>
      <?= h($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-title">اكتشف</div>
    <a href="<?= h(url('top?by=downloads')) ?>" class="sidebar-link"><?= partial_icon('trending') ?> الأكثر تحميلاً</a>
    <a href="<?= h(url('top?by=views')) ?>" class="sidebar-link"><?= partial_icon('trending') ?> الأكثر زيارة</a>
    <a href="<?= h(url('updates')) ?>" class="sidebar-link"><?= partial_icon('clock') ?> آخر التحديثات</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-title">المدونة</div>
    <?php foreach (BLOG_TYPES as $t => $label): ?>
    <a href="<?= h(blog_type_url($t)) ?>" class="sidebar-link"><?= partial_icon('article') ?> <?= h($label) ?></a>
    <?php endforeach; ?>
  </div>
</aside>
<?php }

/* ── One app card, identical everywhere it's used ── */
function render_app_card(array $app): void { ?>
<div class="app-card reveal">
  <a href="<?= h(app_url($app['slug'])) ?>" data-hardnav="1">
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
  <a href="<?= h(download_url($app['slug'])) ?>" class="btn-dl-card" data-hardnav="1">
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
  <nav style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center;margin:14px 0;font-size:12px">
    <a href="<?= h(url('about')) ?>" style="color:var(--muted)">من نحن</a>
    <a href="<?= h(url('contact')) ?>" style="color:var(--muted)">اتصل بنا</a>
    <a href="<?= h(url('privacy-policy')) ?>" style="color:var(--muted)">سياسة الخصوصية</a>
    <a href="<?= h(url('terms')) ?>" style="color:var(--muted)">شروط الاستخدام</a>
    <a href="<?= h(url('cookie-policy')) ?>" style="color:var(--muted)">سياسة الكوكيز</a>
    <a href="<?= h(url('dmca')) ?>" style="color:var(--muted)">DMCA</a>
    <a href="/top?by=downloads" style="color:var(--muted)">الأكثر تحميلاً</a>
    <a href="/updates" style="color:var(--muted)">آخر التحديثات</a>
    <a href="/blog" style="color:var(--muted)">المدونة</a>
    <a href="/rss" style="color:var(--muted)">RSS</a>
  </nav>
  <p>&copy; <?= date('Y') ?> yassota — جميع الحقوق محفوظة</p>
</footer>
<?php
render_cookie_banner();
}

/* ── Cookie consent banner — dismissible, remembered via localStorage.
   Supports AdSense's EU user-consent requirement and is generally expected
   on any site running third-party ad/analytics cookies. ── */
function render_cookie_banner(string $policyUrl = ''): void {
    if (!$policyUrl) $policyUrl = url('cookie-policy');
    ?>
<div id="cookie-consent" class="cookie-consent" hidden>
  <p>نستخدم ملفات تعريف الارتباط (Cookies) لتحسين تجربتك وعرض إعلانات ذات صلة. بمتابعة تصفح الموقع فإنك توافق على
    <a href="<?= h($policyUrl) ?>">سياسة ملفات تعريف الارتباط</a>.</p>
  <button type="button" id="cookie-consent-accept" class="btn-primary" style="padding:9px 22px;font-size:13px;flex-shrink:0">موافق</button>
</div>
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
