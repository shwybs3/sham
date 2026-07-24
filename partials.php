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
        'mail'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    ];
    return $icons[$name] ?? '';
}

/* ── Visual star rating ── */
function render_stars(float $rating, int $count = 0): string {
    $full = (int)$rating;
    $half = ($rating - $full) >= 0.3 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = '<span class="star-row">';
    for ($i = 0; $i < $full;  $i++) $html .= '<span class="star-v full">★</span>';
    if ($half)                        $html .= '<span class="star-v half">★</span>';
    for ($i = 0; $i < $empty; $i++) $html .= '<span class="star-v empty">☆</span>';
    $html .= '</span><span class="star-score">' . number_format($rating, 1) . '</span>';
    if ($count > 0) $html .= '<span class="star-count">(' . number_format($count) . ')</span>';
    return $html;
}

/* ── Breadcrumb nav ── */
function render_breadcrumbs(array $crumbs): void { ?>
<nav class="breadcrumb" aria-label="مسار التنقل">
  <?php foreach ($crumbs as $i => $c): ?>
  <?php if ($i > 0): ?><svg class="bc-sep" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg><?php endif; ?>
  <?php if ($i < count($crumbs) - 1 && isset($c['url'])): ?>
    <a href="<?= h($c['url']) ?>"><?= h($c['label']) ?></a>
  <?php else: ?>
    <span class="bc-current"><?= h($c['label']) ?></span>
  <?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php }

function partial_wave(): string {
    return '<svg class="wave-divider" viewBox="0 0 1200 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,20 C150,40 350,0 600,20 C850,40 1050,0 1200,20" stroke="#2563eb" stroke-width="1.5" fill="none"/>
    </svg>';
}

/* ── Header (logo, search, top nav) ── */
function render_site_header(string $search = '', string $activeNav = 'home'): void { ?>
<header class="site-header">
  <a href="/" class="logo" style="display:flex;flex-direction:column;line-height:1;gap:2px">
    <span>yas<span style="color:var(--purple)">sota</span></span>
    <span style="font-size:9px;font-weight:500;letter-spacing:1.5px;color:var(--muted);text-transform:uppercase;font-family:var(--f-body)">دليل تطبيقات أندرويد</span>
  </a>

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
    <a href="<?= h(url('blog')) ?>" class="<?= $activeNav === 'blog' ? 'active' : '' ?>">المدونة</a>
    <a href="<?= h(url('about')) ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>">من نحن</a>
    <button type="button" class="mobile-search-toggle" id="mobile-search-toggle" aria-label="بحث"><?= partial_icon('search') ?></button>
    <button class="nav-toggle" id="nav-toggle" aria-label="القائمة" aria-expanded="false"><?= partial_icon('menu') ?></button>
  </nav>
</header>

<!-- Mobile nav drawer — opened by .nav-toggle -->
<nav class="mobile-nav-drawer" id="mobile-nav-drawer" aria-hidden="true">
  <a href="/" class="mobile-nav-link <?= $activeNav === 'home' ? 'active' : '' ?>"><?= partial_icon('home') ?> الرئيسية</a>
  <a href="/?cat=apps" class="mobile-nav-link <?= $activeNav === 'apps' ? 'active' : '' ?>"><?= partial_icon('apps') ?> تطبيقات</a>
  <a href="/?cat=games" class="mobile-nav-link <?= $activeNav === 'games' ? 'active' : '' ?>"><?= partial_icon('games') ?> ألعاب</a>
  <a href="<?= h(url('top?by=downloads')) ?>" class="mobile-nav-link"><?= partial_icon('trending') ?> الأكثر تحميلاً</a>
  <a href="<?= h(url('updates')) ?>" class="mobile-nav-link"><?= partial_icon('clock') ?> آخر التحديثات</a>
  <a href="<?= h(url('blog')) ?>" class="mobile-nav-link <?= $activeNav === 'blog' ? 'active' : '' ?>"><?= partial_icon('article') ?> المدونة</a>
  <a href="<?= h(url('about')) ?>" class="mobile-nav-link"><?= partial_icon('info') ?> من نحن</a>
  <a href="<?= h(url('contact')) ?>" class="mobile-nav-link"><?= partial_icon('mail') ?> تواصل معنا</a>
</nav>
<?php }

/* ── Sidebar: category list + a few discovery links reused on every listing page ── */
function render_category_tabs_bar(PDO $pdo, string $activeCat = ''): void {
    $cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
    ?><div class="cat-tabs-bar">
      <a href="/" class="cat-tab <?= !$activeCat ? 'active' : '' ?>"><?= partial_icon('home') ?> الكل</a>
      <?php foreach ($cats as $c): ?>
      <a href="/?cat=<?= h($c['slug']) ?>" class="cat-tab <?= $activeCat === $c['slug'] ? 'active' : '' ?>">
        <?= partial_icon($c['slug'] === 'games' ? 'games' : 'apps') ?> <?= h($c['name']) ?>
      </a>
      <?php endforeach; ?>
      <a href="<?= h(url('top?by=downloads')) ?>" class="cat-tab" style="color:#F97316">
        <?= partial_icon('trending') ?> الأكثر تحميلاً
      </a>
      <a href="<?= h(url('updates')) ?>" class="cat-tab">
        <?= partial_icon('clock') ?> آخر التحديثات
      </a>
    </div><?php
}

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
    <?php foreach (BLOG_TYPES as $t => $label): if ($t === 'code-page') continue; ?>
    <a href="<?= h(blog_type_url($t)) ?>" class="sidebar-link"><?= partial_icon('article') ?> <?= h($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= h(blog_type_url('code-page')) ?>" class="sidebar-link"><?= partial_icon('info') ?> صفحة المحتوى</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-title">yassota</div>
    <a href="<?= h(url('about')) ?>" class="sidebar-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
      من نحن
    </a>
    <a href="<?= h(url('faq')) ?>" class="sidebar-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
      الأسئلة الشائعة
    </a>
    <a href="<?= h(url('contact')) ?>" class="sidebar-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      اتصل بنا
    </a>
    <a href="<?= h(url('privacy-policy')) ?>" class="sidebar-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      سياسة الخصوصية
    </a>
  </div>
</aside>
<?php }

/* ── One app card, identical everywhere it's used ── */
function render_app_card(array $app): void { ?>
<div class="app-card reveal">
  <a href="<?= h(app_url($app['slug'])) ?>" data-hardnav="1">
    <?php if (!empty($app['icon_path'])): ?>
      <img src="<?= h(url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="app-card-icon" loading="lazy">
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
<footer class="site-footer" style="padding:0">
  <div style="max-width:100%;padding:36px 32px 24px;border-top:1px solid var(--border-c)">

    <!-- Brand + columns -->
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:32px;margin-bottom:28px">

      <!-- Brand -->
      <div>
        <div class="footer-logo" style="margin-bottom:10px">yas<span style="color:var(--purple)">sota</span></div>
        <p style="color:var(--muted);font-size:13px;line-height:1.75;max-width:300px;margin:0 0 12px">
          دليلك التحريري العربي المستقل لاكتشاف ومراجعة تطبيقات أندرويد — مراجعات دقيقة، معلومات موثوقة، ومحتوى محدّث باستمرار.
        </p>
        <p style="color:var(--muted);font-size:11px;line-height:1.65;padding-top:12px;border-top:1px solid var(--border-c);margin:0">
          yassota موقع تحريري مستقل لمراجعة التطبيقات ولا ينتمي لأي متجر أو شركة تطبيقات.
          بعض روابط التحميل توجّه إلى Google Play أو مصادر رسمية أخرى.
          يحتوي الموقع على إعلانات من Google AdSense.
        </p>
      </div>

      <!-- Col 1 -->
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:1.2px;color:var(--muted);text-transform:uppercase;margin-bottom:14px">اكتشف</div>
        <?php foreach ([
            url('') => 'الرئيسية',
            url('top?by=downloads') => 'الأكثر تحميلاً',
            url('top?by=views')     => 'الأكثر زيارة',
            url('updates')          => 'آخر التحديثات',
            url('blog')             => 'المدونة',
            url('rss')              => 'RSS',
        ] as $href => $label): ?>
        <a href="<?= h($href) ?>" style="display:block;color:var(--muted);font-size:13px;padding:4px 0;text-decoration:none;transition:color .15s"
           onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'"><?= h($label) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Col 2 -->
      <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:1.2px;color:var(--muted);text-transform:uppercase;margin-bottom:14px">yassota</div>
        <?php foreach ([
            url('about')          => 'من نحن',
            url('faq')            => 'الأسئلة الشائعة',
            url('contact')        => 'اتصل بنا',
            url('privacy-policy') => 'سياسة الخصوصية',
            url('terms')          => 'شروط الاستخدام',
            url('cookie-policy')  => 'سياسة الكوكيز',
            url('dmca')           => 'إشعار DMCA',
        ] as $href => $label): ?>
        <a href="<?= h($href) ?>" style="display:block;color:var(--muted);font-size:13px;padding:4px 0;text-decoration:none;transition:color .15s"
           onmouseover="this.style.color='var(--cyan)'" onmouseout="this.style.color='var(--muted)'"><?= h($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Copyright bar -->
    <div style="border-top:1px solid var(--border-c);padding-top:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
      <p style="font-size:11px;color:var(--muted);margin:0">
        &copy; 2024–<?= date('Y') ?> yassota — جميع الحقوق محفوظة | دليل تحريري مستقل لتطبيقات أندرويد
      </p>
      <p style="font-size:11px;color:var(--muted);margin:0">
        تحت إشراف تحريري — المحتوى يُحدَّث يومياً
      </p>
    </div>
  </div>
</footer>
<?php
render_cookie_banner();
}

/* ── Combined cookie + terms consent banner — shown once, remembered in localStorage.
   Covers AdSense EU-consent and the site's general T&C acceptance notice. ── */
function render_cookie_banner(string $policyUrl = ''): void {
    if (!$policyUrl) $policyUrl = url('cookie-policy');
    ?>
<div id="cookie-consent" class="cookie-consent cookie-consent-v2" hidden>
  <div class="cc-icon-wrap">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
  </div>
  <div class="cc-text">
    <strong>الخصوصية وشروط الاستخدام</strong>
    <p>باستخدام yassota فإنك توافق على <a href="<?= h(url('cookie-policy')) ?>">سياسة ملفات تعريف الارتباط</a>،
      <a href="<?= h(url('privacy-policy')) ?>">سياسة الخصوصية</a>،
      و<a href="<?= h(url('terms')) ?>">شروط الاستخدام</a>.
      نستخدم Cookies لتحسين التجربة وعرض إعلانات مناسبة.
    </p>
  </div>
  <button type="button" id="cookie-consent-accept" class="cc-accept-btn">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
    موافق وأواصل
  </button>
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
