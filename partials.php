<?php
/* ═══════════════════════════════════════════════
   YASSOTA — partials.php
   Shared header/sidebar/app-card/footer markup so
   the homepage and the category/developer/top/updates
   listing pages render identical, consistent UI instead
   of each page hand-rolling its own copy.
   ═══════════════════════════════════════════════ */

function partial_icon(string $name, int $size = 0): string {
    // Default sizes per icon group
    $s = $size ?: 16;
    $icons = [
        /* ── Navigation & UI ── */
        'download'    => "<svg width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2'><path d='M12 3v12m0 0l-4-4m4 4l4-4'/><path d='M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2'/></svg>",
        'search'      => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.2'><circle cx='11' cy='11' r='8'/><path d='m21 21-4.35-4.35'/></svg>",
        'menu'        => "<svg width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M3 12h18M3 6h18M3 18h18'/></svg>",
        'home'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-4h4v4h4a1 1 0 001-1v-9'/></svg>",
        'info'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='12' cy='12' r='10'/><path d='M12 16v-4m0-4h.01'/></svg>",
        'arrow-r'     => "<svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><path d='M15 18l-6-6 6-6'/></svg>",
        'external'    => "<svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6'/><polyline points='15,3 21,3 21,9'/><line x1='10' y1='14' x2='21' y2='3'/></svg>",
        'chevron-d'   => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><path d='M6 9l6 6 6-6'/></svg>",
        'chevron-l'   => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><path d='M15 18l-6-6 6-6'/></svg>",
        'chevron-r'   => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><path d='M9 18l6-6-6-6'/></svg>",
        'close'       => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><path d='M18 6L6 18M6 6l12 12'/></svg>",

        /* ── Content types ── */
        'apps'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><rect x='2' y='2' width='9' height='9' rx='2'/><rect x='13' y='2' width='9' height='9' rx='2'/><rect x='2' y='13' width='9' height='9' rx='2'/><rect x='13' y='13' width='9' height='9' rx='2'/></svg>",
        'games'       => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M6 12h4m-2-2v4'/><circle cx='17' cy='10' r='1' fill='currentColor'/><circle cx='17' cy='14' r='1' fill='currentColor'/><path d='M2 8a2 2 0 012-2h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8z'/></svg>",
        'article'     => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8'><rect x='3' y='3' width='18' height='18' rx='2'/><path d='M7 8h10M7 12h10M7 16h6'/></svg>",
        'mail'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><rect x='2' y='4' width='20' height='16' rx='2'/><polyline points='22,4 12,13 2,4'/></svg>",
        'developer'   => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='12' cy='8' r='4'/><path d='M4 21v-1a7 7 0 0114 0v1'/></svg>",

        /* ── Metrics & status ── */
        'star'        => "<svg width='13' height='13' viewBox='0 0 24 24' fill='#fbbf24' stroke='#fbbf24' stroke-width='1'><path d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/></svg>",
        'star-lg'     => "<svg width='20' height='20' viewBox='0 0 24 24' fill='#fbbf24' stroke='#fbbf24' stroke-width='1'><path d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/></svg>",
        'trending'    => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M22 6l-9.5 9.5-5-5L2 18'/><path d='M16 6h6v6'/></svg>",
        'clock'       => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='12' cy='12' r='10'/><path d='M12 6v6l4 2'/></svg>",

        /* ── Emoji replacements (professional equivalents) ── */
        'smartphone'  => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><rect x='5' y='2' width='14' height='20' rx='2'/><path d='M12 18h.01'/></svg>",
        'folder'      => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z'/></svg>",
        'pen'         => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M12 20h9'/><path d='M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z'/></svg>",
        'check-circle'=> "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M22 11.08V12a10 10 0 11-5.93-9.14'/><polyline points='22,4 12,14.01 9,11.01'/></svg>",
        'lock'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><rect x='3' y='11' width='18' height='11' rx='2'/><path d='M7 11V7a5 5 0 0110 0v4'/></svg>",
        'calendar'    => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><rect x='3' y='4' width='18' height='18' rx='2'/><path d='M16 2v4M8 2v4M3 10h18'/></svg>",
        'globe'       => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='12' cy='12' r='10'/><path d='M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z'/></svg>",
        'refresh'     => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='23 4 23 10 17 10'/><path d='M20.49 15a9 9 0 11-2.12-9.36L23 10'/></svg>",
        'shield'      => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/></svg>",
        'award'       => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='12' cy='8' r='6'/><path d='M15.477 12.89L17 22l-5-3-5 3 1.523-9.11'/></svg>",
        'zap'         => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polygon points='13 2 3 14 12 14 11 22 21 10 12 10 13 2'/></svg>",
        'layers'      => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polygon points='12 2 2 7 12 12 22 7 12 2'/><polyline points='2 17 12 22 22 17'/><polyline points='2 12 12 17 22 12'/></svg>",
        'code'        => "<svg width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='16 18 22 12 16 6'/><polyline points='8 6 2 12 8 18'/></svg>",
    ];

    /* Emoji → icon automatic mapping */
    $emojiMap = [
        '📱'=>'smartphone','🗂️'=>'folder','✍️'=>'pen','⭐'=>'star-lg',
        '✅'=>'check-circle','🔒'=>'lock','🔐'=>'lock','📅'=>'calendar',
        '🌐'=>'globe','🔄'=>'refresh','🔥'=>'trending','🛡️'=>'shield',
        '🏆'=>'award','⚡'=>'zap','📂'=>'folder','🗃️'=>'folder',
        '💡'=>'zap','🎯'=>'award',
    ];
    if (isset($emojiMap[$name])) $name = $emojiMap[$name];

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
    <div style="position:relative;display:inline-block" class="nav-dropdown-wrap">
      <a href="<?= h(url('exchange')) ?>" class="<?= in_array($activeNav,['exchange','gold']) ? 'active' : '' ?>" style="display:flex;align-items:center;gap:3px">
        الأسعار <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
      <div class="nav-dropdown">
        <a href="<?= h(url('exchange')) ?>">أسعار الصرف</a>
        <a href="<?= h(url('gold')) ?>">أسعار الذهب</a>
      </div>
    </div>
    <div style="position:relative;display:inline-block" class="nav-dropdown-wrap">
      <a href="<?= h(url('calculators')) ?>" class="<?= in_array($activeNav,['calculators','solar']) ? 'active' : '' ?>" style="display:flex;align-items:center;gap:3px">
        حاسبات <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
      <div class="nav-dropdown">
        <a href="<?= h(url('calculators')) ?>">حاسبات البناء</a>
        <a href="<?= h(url('solar')) ?>">حاسبة الطاقة الشمسية</a>
      </div>
    </div>
    <a href="<?= h(url('tools')) ?>" class="<?= $activeNav === 'tools' ? 'active' : '' ?>">أدوات</a>
    <a href="<?= h(url('blog')) ?>" class="<?= $activeNav === 'blog' ? 'active' : '' ?>">المدونة</a>
  </nav>

  <!-- Mobile-only buttons — hidden on desktop, shown by CSS at ≤1024px -->
  <div class="header-mobile-btns">
    <button type="button" class="header-icon-btn mobile-search-toggle" id="mobile-search-toggle" aria-label="بحث"><?= partial_icon('search') ?></button>
    <button type="button" class="header-icon-btn" id="nav-toggle" aria-label="القائمة" aria-expanded="false">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</header>

<!-- Mobile nav drawer — right-side panel (RTL), hidden until #nav-toggle is pressed -->
<nav class="mobile-nav-drawer" id="mobile-nav-drawer" aria-hidden="true">
  <div class="mobile-nav-header">
    <span class="mobile-nav-logo">yas<span>sota</span></span>
    <button type="button" class="mobile-nav-close" id="mobile-nav-close" aria-label="إغلاق">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="mobile-nav-links">
    <a href="/" class="mobile-nav-link <?= $activeNav === 'home' ? 'active' : '' ?>"><?= partial_icon('home') ?> الرئيسية</a>
    <a href="/?cat=apps" class="mobile-nav-link <?= $activeNav === 'apps' ? 'active' : '' ?>"><?= partial_icon('apps') ?> تطبيقات</a>
    <a href="/?cat=games" class="mobile-nav-link <?= $activeNav === 'games' ? 'active' : '' ?>"><?= partial_icon('games') ?> ألعاب</a>
    <a href="<?= h(url('top?by=downloads')) ?>" class="mobile-nav-link"><?= partial_icon('trending') ?> الأكثر تحميلاً</a>
    <div style="font-size:10px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;padding:10px 16px 4px;opacity:.7">الأسعار</div>
    <a href="<?= h(url('exchange')) ?>" class="mobile-nav-link <?= $activeNav === 'exchange' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      أسعار الصرف
    </a>
    <a href="<?= h(url('gold')) ?>" class="mobile-nav-link <?= $activeNav === 'gold' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      أسعار الذهب
    </a>
    <div style="font-size:10px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;padding:10px 16px 4px;opacity:.7">حاسبات</div>
    <a href="<?= h(url('calculators')) ?>" class="mobile-nav-link <?= $activeNav === 'calculators' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/></svg>
      حاسبات البناء
    </a>
    <a href="<?= h(url('solar')) ?>" class="mobile-nav-link <?= $activeNav === 'solar' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
      الطاقة الشمسية
    </a>
    <div style="font-size:10px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;padding:10px 16px 4px;opacity:.7">أدوات الويب</div>
    <a href="<?= h(url('tools')) ?>" class="mobile-nav-link <?= $activeNav === 'tools' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      جميع الأدوات (20 أداة)
    </a>
    <div style="font-size:10px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase;padding:10px 16px 4px;opacity:.7">المحتوى</div>
    <a href="<?= h(url('updates')) ?>" class="mobile-nav-link"><?= partial_icon('clock') ?> آخر التحديثات</a>
    <a href="<?= h(url('blog')) ?>" class="mobile-nav-link <?= $activeNav === 'blog' ? 'active' : '' ?>"><?= partial_icon('article') ?> المدونة</a>
    <a href="<?= h(url('about')) ?>" class="mobile-nav-link"><?= partial_icon('info') ?> من نحن</a>
    <a href="<?= h(url('contact')) ?>" class="mobile-nav-link"><?= partial_icon('mail') ?> تواصل معنا</a>
  </div>
</nav>
<!-- Dark overlay behind the drawer -->
<div class="mobile-nav-overlay" id="mobile-nav-overlay" aria-hidden="true"></div>
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
  <a href="<?= h(app_url($app['slug'])) ?>" class="app-card-inner" data-hardnav="1">
    <?php if (!empty($app['icon_path'])): ?>
      <img src="<?= h(media_url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" class="app-card-icon" loading="lazy">
    <?php else: ?>
      <div class="app-card-icon-placeholder">
        <?= partial_icon('apps') ?>
      </div>
    <?php endif; ?>
    <div class="app-card-body">
      <div class="app-card-cat"><?= h($app['cat_name'] ?? 'تطبيق') ?></div>
      <div class="app-card-name"><?= h($app['name']) ?></div>
      <?php if (!empty($app['short_description'])): ?>
      <div class="app-card-desc"><?= h(mb_substr($app['short_description'], 0, 80)) ?><?= mb_strlen($app['short_description']) > 80 ? '…' : '' ?></div>
      <?php endif; ?>
      <div class="app-card-meta">
        <div class="app-card-rating">
          <?= partial_icon('star') ?>
          <span><?= h($app['rating']) ?></span>
        </div>
        <span class="app-card-size"><?= h($app['size_mb'] ? $app['size_mb'] . ' MB' : '') ?></span>
      </div>
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
            url('tools')            => 'أدوات الويب (20+)',
            url('exchange')         => 'أسعار الصرف',
            url('gold')             => 'أسعار الذهب',
            url('calculators')      => 'حاسبات البناء',
            url('solar')            => 'الطاقة الشمسية',
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
            url('disclosure')     => 'الإفصاح الإعلاني',
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

/* ── GDPR-compliant cookie + Consent Mode v2 banner.
   Shown once on first visit; stored in localStorage for 365 days.
   "Accept All"  → grants full AdSense/Analytics consent (gtag update).
   "Essential Only" → denies ad/analytics storage (Consent Mode compliant).
   Both buttons dismiss the banner — the user is never locked out. ── */
function render_cookie_banner(): void { ?>
<style>
.cookie-banner{
  position:fixed;bottom:0;right:0;left:0;z-index:9990;
  background:rgba(7,13,26,.97);
  border-top:1px solid rgba(6,182,212,.25);
  backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
  padding:18px 24px;
  display:flex;flex-wrap:wrap;align-items:center;gap:14px;
  font-family:'Cairo',sans-serif;direction:rtl;
  transform:translateY(100%);
  transition:transform .35s cubic-bezier(.4,0,.2,1);
}
.cookie-banner.cb-show{transform:translateY(0);}
.cb-text{flex:1;min-width:240px;font-size:13px;line-height:1.75;color:#94a3b8;}
.cb-text strong{color:#e2e8f0;display:block;margin-bottom:4px;font-size:14px;}
.cb-text a{color:#06b6d4;}
.cb-btns{display:flex;gap:10px;flex-shrink:0;flex-wrap:wrap;}
.cb-accept{
  padding:10px 22px;border-radius:50px;font-size:13px;font-weight:700;
  background:linear-gradient(135deg,#06b6d4,#3b82f6);
  color:#fff;border:none;cursor:pointer;font-family:'Cairo',sans-serif;
  transition:.2s;white-space:nowrap;
}
.cb-accept:hover{opacity:.88;}
.cb-reject{
  padding:10px 18px;border-radius:50px;font-size:13px;font-weight:600;
  background:transparent;border:1px solid rgba(255,255,255,.15);
  color:#94a3b8;cursor:pointer;font-family:'Cairo',sans-serif;
  transition:.2s;white-space:nowrap;
}
.cb-reject:hover{border-color:rgba(255,255,255,.3);color:#e2e8f0;}
</style>

<div id="cookie-banner" class="cookie-banner" role="dialog" aria-label="إشعار الخصوصية" hidden>
  <div class="cb-text">
    <strong>نستخدم ملفات تعريف الارتباط (Cookies)</strong>
    يستخدم yassota cookies ضرورية للتشغيل، وبموافقتك: إعلانات Google AdSense مخصصة وإحصاءات الزيارات.
    راجع <a href="<?= h(url('privacy-policy')) ?>">سياسة الخصوصية</a>
    و<a href="<?= h(url('cookie-policy')) ?>">سياسة الكوكيز</a>.
  </div>
  <div class="cb-btns">
    <button type="button" class="cb-accept" id="cb-accept-all">قبول الكل</button>
    <button type="button" class="cb-reject" id="cb-reject-all">الضروري فقط</button>
  </div>
</div>

<script>
(function(){
  var STORAGE_KEY = 'yassota_consent_v2';
  var stored = localStorage.getItem(STORAGE_KEY);
  var banner = document.getElementById('cookie-banner');
  if (!banner) return;

  function grantConsent(full){
    if (typeof gtag === 'function') {
      if (full) {
        gtag('consent','update',{
          ad_storage:'granted',
          ad_user_data:'granted',
          ad_personalization:'granted',
          analytics_storage:'granted'
        });
      } else {
        gtag('consent','update',{
          ad_storage:'denied',
          ad_user_data:'denied',
          ad_personalization:'denied',
          analytics_storage:'denied'
        });
      }
    }
  }

  if (stored) {
    grantConsent(stored === 'all');
    return;
  }

  // show after brief delay so it doesn't flash on first paint
  setTimeout(function(){
    banner.removeAttribute('hidden');
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){ banner.classList.add('cb-show'); });
    });
  }, 900);

  function dismiss(choice){
    banner.classList.remove('cb-show');
    setTimeout(function(){ banner.setAttribute('hidden',''); }, 380);
    localStorage.setItem(STORAGE_KEY, choice);
    var expiry = new Date();
    expiry.setFullYear(expiry.getFullYear() + 1);
    document.cookie = STORAGE_KEY + '=' + choice + '; expires=' + expiry.toUTCString() + '; path=/; SameSite=Lax';
    grantConsent(choice === 'all');
  }

  document.getElementById('cb-accept-all').addEventListener('click', function(){ dismiss('all'); });
  document.getElementById('cb-reject-all').addEventListener('click', function(){ dismiss('essential'); });
})();
</script>
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
