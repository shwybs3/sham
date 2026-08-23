<?php
/* Shared layout pieces used by every public-facing page. */

function seo_head(array $o): void {
    $title = $o['title'] ?? setting('site_name', 'Syria Home');
    $desc = $o['description'] ?? setting('site_description', '');
    $keywords = $o['keywords'] ?? '';
    $canonical = $o['canonical'] ?? site_url($_SERVER['REQUEST_URI'] ?? '');
    $image = $o['image'] ?? site_url('assets/img/og-default.svg');
    $type = $o['type'] ?? 'website';
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <?php if ($keywords): ?><meta name="keywords" content="<?= e($keywords) ?>"><?php endif; ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta property="og:type" content="<?= e($type) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($image) ?>">
    <meta property="og:site_name" content="<?= e(setting('site_name', 'Syria Home')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($desc) ?>">
    <link rel="icon" href="<?= site_url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500<?= ($o['lang'] ?? 'en') === 'ar' ? '&family=Cairo:wght@400;500;600;700;800' : '' ?>&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= site_url('assets/css/style.css') ?>?v=7">
    <?php if (!empty($o['hreflang'])): foreach ($o['hreflang'] as $hl => $href): ?>
    <link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($href) ?>">
    <?php endforeach; endif; ?>
    <link rel="alternate" type="application/rss+xml" title="<?= e(setting('site_name', 'Syria Home')) ?> — <?= e(t('Latest articles', 'أحدث المقالات', $o['lang'] ?? 'en')) ?>" href="<?= site_url('feed.php' . (($o['lang'] ?? 'en') === 'ar' ? '?lang=ar' : '')) ?>">
    <?php
    $themes = [
        'default' => ['brand1' => '#6366f1', 'brand2' => '#22d3ee'],
        'dark'    => ['brand1' => '#a78bfa', 'brand2' => '#34d399'],
        'ocean'   => ['brand1' => '#0ea5e9', 'brand2' => '#38bdf8'],
        'sunset'  => ['brand1' => '#f97316', 'brand2' => '#fbbf24'],
    ];
    $th = $themes[setting('site_theme', 'default')] ?? $themes['default'];
    ?>
    <style>:root{--brand1:<?= $th['brand1'] ?>;--brand2:<?= $th['brand2'] ?>}</style>
    <?php $gsv = trim(setting('google_site_verification')); if ($gsv): ?><meta name="google-site-verification" content="<?= e($gsv) ?>"><?php endif; ?>
    <?php $bsv = trim(setting('bing_site_verification')); if ($bsv): ?><meta name="msvalidate.01" content="<?= e($bsv) ?>"><?php endif; ?>
    <?php $pub = trim(setting('adsense_publisher_id')); if ($pub !== ''): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($pub) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
    <?php if (!empty($o['jsonld'])):
        // Accept either one schema object, or a list of several — a page can carry
        // an Article schema and a BreadcrumbList schema at the same time, for example.
        $blocks = array_is_list($o['jsonld']) ? $o['jsonld'] : [$o['jsonld']];
        foreach ($blocks as $block): ?>
    <script type="application/ld+json"><?= str_replace('</script', '<\/script', json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></script>
    <?php endforeach; endif; ?>
    <?php
}

function nav_links(string $lang = 'en'): array {
    if ($lang === 'ar') {
        return [
            'الرئيسية' => site_url('ar/'),
            'المقالات' => site_url('articles.php?lang=ar'),
            'الأخبار' => site_url('articles.php?lang=ar&type=news'),
            'المقارنات' => site_url('articles.php?lang=ar&type=comparison'),
            'الدروس' => site_url('articles.php?lang=ar&type=tutorial'),
            'الأدوات' => site_url('tools.php?lang=ar'),
            'التطبيقات' => site_url('apps.php'),
            'المتجر' => site_url('products.php'),
        ];
    }
    return [
        'Home' => site_url(''),
        'Articles' => site_url('articles.php'),
        'News' => site_url('articles.php?type=news'),
        'Comparisons' => site_url('articles.php?type=comparison'),
        'Tutorials' => site_url('articles.php?type=tutorial'),
        'Tools' => site_url('tools.php'),
        'Apps' => site_url('apps.php'),
        'Store' => site_url('products.php'),
    ];
}

function money(float $amount, string $currency = 'USD'): string {
    $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $sym = $symbols[$currency] ?? ($currency . ' ');
    return $sym . number_format($amount, 2);
}

function product_card(array $p): void {
    $off = null;
    if (!empty($p['compare_at_price']) && (float)$p['compare_at_price'] > (float)$p['price']) {
        $off = (int)round(100 - ((float)$p['price'] / (float)$p['compare_at_price'] * 100));
    }
    ?>
    <a class="product-card" href="<?= site_url('product.php?slug=' . urlencode($p['slug'])) ?>">
      <div class="art-wrap">
        <?= svg_product_art($p['art_key']) ?>
        <?php if (!empty($p['badge'])): ?><span class="badge-corner"><?= e($p['badge']) ?></span><?php endif; ?>
        <span class="art-icon"><i class="fa-solid <?= e($p['icon_class']) ?>"></i></span>
      </div>
      <div class="pbody">
        <span class="ptype"><i class="fa-solid fa-tag"></i> <?= e($p['product_type']) ?></span>
        <h3><?= e($p['name']) ?></h3>
        <p><?= e($p['tagline']) ?></p>
        <?php $rating = rating_summary('product', (int)$p['id']); ?>
        <?php if ($rating['count'] >= 3): ?>
          <span class="tcard-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star<?= $i > round($rating['avg']) ? ' off' : '' ?>"></i><?php endfor; ?>
            <b><?= number_format($rating['avg'], 1) ?></b>
          </span>
        <?php endif; ?>
        <div class="price-row">
          <span class="price-now"><?= money((float)$p['price'], $p['currency']) ?></span>
          <?php if ($off): ?>
            <span class="price-was"><?= money((float)$p['compare_at_price'], $p['currency']) ?></span>
            <span class="price-off">−<?= $off ?>%</span>
          <?php endif; ?>
        </div>
        <span class="pcta">View details <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <?php
}

function app_card(array $a): void {
    ?>
    <a class="product-card" href="<?= site_url('app.php?slug=' . urlencode($a['slug'])) ?>">
      <div class="art-wrap" style="display:flex;align-items:center;justify-content:center;background:#f4f5fb;aspect-ratio:1.4/1">
        <?php if ($a['icon_path']): ?>
          <img src="<?= site_url($a['icon_path']) ?>" alt="<?= e($a['name']) ?>" style="width:72px;height:72px;border-radius:16px;box-shadow:var(--shadow)">
        <?php else: ?>
          <span class="art-icon"><i class="fa-brands fa-google-play"></i></span>
        <?php endif; ?>
      </div>
      <div class="pbody">
        <?php if ($a['category']): ?><span class="ptype"><i class="fa-solid fa-tag"></i> <?= e($a['category']) ?></span><?php endif; ?>
        <h3><?= e($a['name']) ?></h3>
        <p><?= e($a['short_description']) ?></p>
        <span class="pcta"><i class="fa-brands fa-google-play"></i> <?= e($a['developer'] ?: 'Google Play') ?></span>
      </div>
    </a>
    <?php
}

function cookie_consent_banner(): void {
    ?>
    <div id="cookieBanner" class="cookie-banner" role="dialog" aria-label="Cookie notice">
      <p>We use cookies for core site functionality, analytics, and (once approved) ads. <a href="<?= site_url('cookie-policy.php') ?>">Learn more</a></p>
      <div class="cookie-actions">
        <button type="button" onclick="shAcceptCookies()">Got it</button>
      </div>
    </div>
    <script>
    (function () {
      var shown = false;
      try { shown = !!localStorage.getItem('sh_cookie_consent'); } catch (e) {}
      if (!shown) {
        document.addEventListener('DOMContentLoaded', function () {
          var b = document.getElementById('cookieBanner');
          if (b) b.classList.add('show');
        });
      }
    })();
    function shAcceptCookies() {
      try { localStorage.setItem('sh_cookie_consent', '1'); } catch (e) {}
      var b = document.getElementById('cookieBanner');
      if (b) b.classList.remove('show');
    }
    </script>
    <?php
}

/** $switchUrl, when given, is the URL of this exact page's counterpart in
 *  the other language (from a translation_of lookup) — shown as the
 *  language-switcher link. Without it, the switcher falls back to each
 *  language's homepage. */
function site_header(string $active = '', string $lang = 'en', ?string $switchUrl = null): void {
    $siteName = setting('site_name', 'Syria Home');
    cookie_consent_banner();
    $otherLang = $lang === 'ar' ? 'en' : 'ar';
    $switchHref = $switchUrl ?? site_url($otherLang === 'ar' ? 'ar/' : '');
    $switchLabel = $otherLang === 'ar' ? 'العربية' : 'English';
    ?>
    <header class="site-header">
      <div class="bar">
        <?php $logoUrl = trim(setting('logo_url')); ?>
        <a href="<?= site_url($lang === 'ar' ? 'ar/' : '') ?>" class="logo">
          <?php if ($logoUrl !== ''): ?>
            <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" style="max-height:38px;width:auto">
          <?php else: ?>
            <span class="mark"><i class="fa-solid fa-layer-group"></i></span><?= e($siteName) ?>
          <?php endif; ?>
        </a>
        <nav class="main-nav" id="mainNav">
          <?php foreach (nav_links($lang) as $label => $url): ?>
            <a href="<?= e($url) ?>" class="<?= $active === $label ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <form class="header-search" action="<?= site_url('search.php') ?>" method="get">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="q" placeholder="<?= e(t('Search articles & tools...', 'ابحث في المقالات والأدوات...', $lang)) ?>">
        </form>
        <a href="<?= e($switchHref) ?>" class="lang-switch"><i class="fa-solid fa-language"></i> <span><?= e($switchLabel) ?></span></a>
        <a href="<?= site_url($lang === 'ar' ? 'tools.php?lang=ar' : 'tools.php') ?>" class="header-cta"><i class="fa-solid fa-bolt"></i> <span><?= e(t('Free Tools', 'أدوات مجانية', $lang)) ?></span></a>
        <button class="hamburger" id="navToggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
      </div>
    </header>
    <div class="nav-backdrop" id="navBackdrop"></div>
    <?php
}

/** Cross-domain "network" band — shown just above the footer on every
 *  site in the family, linking out to the others (skips the current host). */
function network_grid(): void {
    $raw = setting('network_sites', '');
    $sites = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($sites) || !$sites) return;
    $selfHost = strtolower(preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST'] ?? ''));
    $cards = [];
    foreach ($sites as $s) {
        $url = trim($s['url'] ?? '');
        if ($url === '') continue;
        $host = strtolower(preg_replace('~^www\.~', '', (string)parse_url($url, PHP_URL_HOST)));
        if ($selfHost !== '' && $host === $selfHost) continue;
        $cards[] = $s;
    }
    if (!$cards) return;
    ?>
    <div class="network-band">
      <div class="container">
        <h4 class="network-title"><i class="fa-solid fa-diagram-project"></i> Explore the rest of the network</h4>
        <div class="network-grid">
          <?php foreach ($cards as $s): $url = trim($s['url'] ?? ''); if ($url === '') continue; ?>
            <a class="network-card" href="<?= e($url) ?>" style="background:<?= e(trim($s['color'] ?? '') ?: '#1e293b') ?>" target="_blank" rel="noopener">
              <span class="network-card-name"><?= e($s['name'] ?? $url) ?></span>
              <?php if (!empty($s['tagline'])): ?><span class="network-card-tagline"><?= e($s['tagline']) ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
}

function site_footer(string $lang = 'en'): void {
    $siteName = setting('site_name', 'Syria Home');
    network_grid();
    ?>
    <footer class="site-footer">
      <div class="container cols">
        <div>
          <div class="brand"><span class="mark" style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--brand1),var(--brand2));display:inline-flex;align-items:center;justify-content:center"><i class="fa-solid fa-layer-group" style="font-size:14px"></i></span><?= e($siteName) ?></div>
          <p style="max-width:320px;color:#94a3b8;font-size:13px"><?= e(setting('site_tagline')) ?></p>
          <?php $footerEmail = trim(setting('contact_email', 'contact@yassota.com')); if ($footerEmail !== ''): ?>
            <a href="mailto:<?= e($footerEmail) ?>" style="color:#94a3b8;font-size:13px;display:inline-flex;align-items:center;gap:6px;margin-bottom:10px"><i class="fa-solid fa-envelope"></i> <?= e($footerEmail) ?></a>
          <?php endif; ?>
          <div class="social-row">
            <?php foreach (['social_twitter'=>'fa-x-twitter','social_facebook'=>'fa-facebook-f','social_linkedin'=>'fa-linkedin-in','social_youtube'=>'fa-youtube','social_github'=>'fa-github','social_instagram'=>'fa-instagram'] as $key=>$icon): $url = trim(setting($key)); if ($url === '') continue; ?>
              <a href="<?= e($url) ?>" target="_blank" rel="noopener"><i class="fa-brands <?= $icon ?>"></i></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div><h4><?= e(t('Explore', 'استكشف', $lang)) ?></h4>
          <a href="<?= site_url($lang === 'ar' ? 'articles.php?lang=ar' : 'articles.php') ?>"><i class="fa-solid fa-newspaper fa-fw"></i> <?= e(t('Articles', 'المقالات', $lang)) ?></a>
          <a href="<?= site_url($lang === 'ar' ? 'articles.php?lang=ar&type=news' : 'articles.php?type=news') ?>"><i class="fa-solid fa-bolt fa-fw"></i> <?= e(t('News', 'الأخبار', $lang)) ?></a>
          <a href="<?= site_url($lang === 'ar' ? 'tools.php?lang=ar' : 'tools.php') ?>"><i class="fa-solid fa-wrench fa-fw"></i> <?= e(t('Web Tools', 'أدوات الويب', $lang)) ?></a>
          <a href="<?= site_url('apps.php') ?>"><i class="fa-brands fa-google-play fa-fw"></i> <?= e(t('Apps', 'التطبيقات', $lang)) ?></a>
          <a href="<?= site_url('products.php') ?>"><i class="fa-solid fa-store fa-fw"></i> <?= e(t('Store', 'المتجر', $lang)) ?></a>
          <a href="<?= site_url('feed.php' . ($lang === 'ar' ? '?lang=ar' : '')) ?>"><i class="fa-solid fa-rss fa-fw"></i> <?= e(t('RSS Feed', 'خلاصة RSS', $lang)) ?></a>
        </div>
        <div><h4><?= e(t('Company', 'الشركة', $lang)) ?></h4>
          <a href="<?= site_url('about.php') ?>"><i class="fa-solid fa-circle-info fa-fw"></i> <?= e(t('About', 'من نحن', $lang)) ?></a>
          <a href="<?= site_url('contact.php') ?>"><i class="fa-solid fa-envelope fa-fw"></i> <?= e(t('Contact', 'اتصل بنا', $lang)) ?></a>
          <a href="<?= site_url('sitemap.php') ?>"><i class="fa-solid fa-sitemap fa-fw"></i> <?= e(t('Sitemap', 'خريطة الموقع', $lang)) ?></a>
        </div>
        <div><h4><?= e(t('Legal', 'قانوني', $lang)) ?></h4>
          <a href="<?= site_url('privacy-policy.php') ?>"><i class="fa-solid fa-user-shield fa-fw"></i> <?= e(t('Privacy Policy', 'سياسة الخصوصية', $lang)) ?></a>
          <a href="<?= site_url('terms.php') ?>"><i class="fa-solid fa-gavel fa-fw"></i> <?= e(t('Terms of Use', 'شروط الاستخدام', $lang)) ?></a>
          <a href="<?= site_url('editorial-policy.php') ?>"><i class="fa-solid fa-feather fa-fw"></i> <?= e(t('Editorial Policy', 'السياسة التحريرية', $lang)) ?></a>
          <a href="<?= site_url('refund-policy.php') ?>"><i class="fa-solid fa-rotate-left fa-fw"></i> <?= e(t('Refund Policy', 'سياسة الاسترجاع', $lang)) ?></a>
          <a href="<?= site_url('license.php') ?>"><i class="fa-solid fa-file-contract fa-fw"></i> <?= e(t('License Terms', 'شروط الترخيص', $lang)) ?></a>
          <a href="<?= site_url('cookie-policy.php') ?>"><i class="fa-solid fa-cookie-bite fa-fw"></i> <?= e(t('Cookie Policy', 'سياسة ملفات تعريف الارتباط', $lang)) ?></a>
          <?php
          try {
              global $pdo;
              $footerPages = $pdo->query("SELECT title, slug FROM pages WHERE show_in_footer=1 AND status='published' ORDER BY title ASC")->fetchAll();
              foreach ($footerPages as $fp):
          ?>
            <a href="<?= e(site_url('p/' . $fp['slug'])) ?>"><i class="fa-solid fa-file-lines fa-fw"></i> <?= e($fp['title']) ?></a>
          <?php endforeach; } catch (Throwable $e) {} ?>
        </div>
      </div>
      <div class="container bottom">
        <span>© <?= date('Y') ?> <?= e($siteName) ?>. <?= e(t('All rights reserved.', 'جميع الحقوق محفوظة.', $lang)) ?></span>
        <span><?= e(t('Built with a self-hosted CMS · Powered by curiosity', 'مبني بنظام إدارة محتوى ذاتي الاستضافة · بدافع الفضول', $lang)) ?></span>
      </div>
    </footer>
    <script src="<?= site_url('assets/js/main.js') ?>?v=3"></script>
    <?php
}

function ad_zone(string $slotKey = 'default'): void {
    $pub = trim(setting('adsense_publisher_id'));
    $slot = trim(setting('adsense_slot_' . $slotKey));
    if ($pub === '' || $slot === '') { echo '<div class="ad-zone empty"></div>'; return; }
    ?>
    <div class="ad-zone">
      <ins class="adsbygoogle" style="display:block" data-ad-client="<?= e($pub) ?>" data-ad-slot="<?= e($slot) ?>" data-ad-format="auto" data-full-width-responsive="true"></ins>
      <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
    <?php
}

function article_card(array $a): void {
    $cat = $a['category_name'] ?? ucfirst($a['content_type']);
    $href = ($a['lang'] ?? 'en') === 'ar' ? site_url('ar/article/' . $a['slug']) : site_url('article.php?slug=' . urlencode($a['slug']));
    ?>
    <a class="card" href="<?= e($href) ?>">
      <div class="hero" style="<?= hero_style_css($a['hero_gradient']) ?>">
        <span class="badge"><?= e(ucfirst($a['content_type'])) ?></span>
        <?php if (!empty($a['trending'])): ?><span class="trend"><i class="fa-solid fa-fire"></i> Trending</span><?php endif; ?>
        <i class="fa-solid <?= e($a['hero_icon']) ?>"></i>
      </div>
      <div class="body">
        <span class="cat"><?= e($cat) ?></span>
        <h3><?= e($a['title']) ?></h3>
        <p><?= e($a['excerpt']) ?></p>
        <div class="meta">
          <span><i class="fa-regular fa-clock"></i><?= (int)$a['reading_time'] ?> min read</span>
          <span><i class="fa-regular fa-eye"></i><?= number_format((int)$a['views']) ?></span>
        </div>
        <span class="cta">Read article <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <?php
}

/** Parses "Title | https://url" lines (as entered in the admin article
 *  form) into a clean list of {title,url}. Lines missing a URL or with an
 *  unsafe scheme are skipped rather than guessed at. */
function parse_sources(string $raw): array {
    $out = [];
    foreach (preg_split('~\r\n|\r|\n~', $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode('|', $line, 2);
        $url = trim($parts[1] ?? $parts[0]);
        $title = count($parts) > 1 ? trim($parts[0]) : $url;
        if (!preg_match('~^https?://~i', $url)) continue;
        $out[] = ['title' => $title !== '' ? $title : $url, 'url' => $url];
    }
    return $out;
}

/** Real, editorial-entered citations only — never fabricated — shown as a
 *  genuine "Sources" section for reader trust, E-E-A-T, and AdSense review. */
function sources_block(?string $raw): void {
    $sources = parse_sources((string)$raw);
    if (!$sources) return;
    ?>
    <div class="sources-block container" style="padding:0;margin-top:26px">
      <h4 style="font-size:14px;color:var(--ink-soft);margin:0 0 10px"><i class="fa-solid fa-book"></i> Sources</h4>
      <ol style="margin:0;padding-left:20px;font-size:13.5px;color:var(--ink-soft);line-height:1.9">
        <?php foreach ($sources as $s): ?>
          <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener nofollow"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php
}

function tip_widget(string $label): void {
    if (!NOWPayments::isConfigured()) return;
    $presets = array_filter(array_map('trim', explode(',', setting('tip_presets', '3,5,10'))));
    ?>
    <div class="tool-shell" style="margin:30px 0;padding:20px 22px">
      <h3 style="margin:0 0 4px;font-size:15px"><i class="fa-solid fa-mug-hot" style="color:var(--brand1)"></i> Enjoyed this? Support the site</h3>
      <p style="font-size:13px;color:var(--muted);margin:0 0 14px">A small crypto tip helps keep this free and ad-light.</p>
      <div class="row">
        <?php foreach ($presets as $amt): if (!is_numeric($amt)) continue; ?>
          <a class="btn-ghost" href="<?= site_url('checkout.php?type=tip&amount=' . urlencode($amt) . '&label=' . urlencode($label)) ?>">$<?= e($amt) ?></a>
        <?php endforeach; ?>
        <a class="btn-ghost" href="<?= site_url('checkout.php?type=tip&label=' . urlencode($label)) ?>">Custom amount</a>
      </div>
    </div>
    <?php
}

function tool_card(array $t): void {
    $rating = rating_summary('tool', (int)$t['id']);
    $href = ($t['lang'] ?? 'en') === 'ar' ? site_url('ar/tool/' . $t['slug']) : site_url('tool.php?slug=' . urlencode($t['slug']));
    ?>
    <a class="tcard" href="<?= e($href) ?>">
      <div class="tcard-top">
        <span class="tcard-icon" style="<?= hero_style_css('g' . (((int)$t['id'] % 8) + 1)) ?>"><i class="fa-solid <?= e($t['icon_class']) ?>"></i></span>
        <div class="tcard-heading">
          <h3><?= e($t['name']) ?></h3>
          <?php if ($rating['count'] >= 3): ?>
            <span class="tcard-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star<?= $i > round($rating['avg']) ? ' off' : '' ?>"></i><?php endfor; ?>
              <b><?= number_format($rating['avg'], 1) ?></b>
            </span>
          <?php else: ?>
            <span class="tcard-rating muted">Not yet rated</span>
          <?php endif; ?>
        </div>
      </div>
      <p class="tcard-desc"><?= e($t['short_description']) ?></p>
      <div class="tcard-foot">
        <span class="tcard-badges">
          <span class="pill pill-free">FREE</span>
          <?php if ((int)($t['uses_count'] ?? 0) > 0): ?><span class="pill pill-views"><i class="fa-regular fa-eye"></i> <?= number_format((int)$t['uses_count']) ?></span><?php endif; ?>
        </span>
        <span class="tcard-cta">Visit Tool <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <?php
}
