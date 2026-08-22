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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= site_url('assets/css/style.css') ?>?v=6">
    <?php $gsv = trim(setting('google_site_verification')); if ($gsv): ?><meta name="google-site-verification" content="<?= e($gsv) ?>"><?php endif; ?>
    <?php $bsv = trim(setting('bing_site_verification')); if ($bsv): ?><meta name="msvalidate.01" content="<?= e($bsv) ?>"><?php endif; ?>
    <?php $pub = trim(setting('adsense_publisher_id')); if ($pub !== ''): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e($pub) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
    <?php if (!empty($o['jsonld'])): ?>
    <script type="application/ld+json"><?= str_replace('</script', '<\/script', json_encode($o['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></script>
    <?php endif; ?>
    <?php
}

function nav_links(): array {
    return [
        'Home' => site_url(''),
        'Articles' => site_url('articles.php'),
        'News' => site_url('articles.php?type=news'),
        'Comparisons' => site_url('articles.php?type=comparison'),
        'Tutorials' => site_url('articles.php?type=tutorial'),
        'Tools' => site_url('tools.php'),
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

function site_header(string $active = ''): void {
    $siteName = setting('site_name', 'Syria Home');
    cookie_consent_banner();
    ?>
    <header class="site-header">
      <div class="bar">
        <a href="<?= site_url('') ?>" class="logo"><span class="mark"><i class="fa-solid fa-layer-group"></i></span><?= e($siteName) ?></a>
        <nav class="main-nav" id="mainNav">
          <?php foreach (nav_links() as $label => $url): ?>
            <a href="<?= e($url) ?>" class="<?= $active === $label ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <form class="header-search" action="<?= site_url('search.php') ?>" method="get">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="q" placeholder="Search articles &amp; tools...">
        </form>
        <button class="hamburger" id="navToggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
      </div>
    </header>
    <div class="nav-backdrop" id="navBackdrop"></div>
    <?php
}

function site_footer(): void {
    $siteName = setting('site_name', 'Syria Home');
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
            <?php foreach (['social_twitter'=>'fa-x-twitter','social_facebook'=>'fa-facebook-f','social_linkedin'=>'fa-linkedin-in'] as $key=>$icon): $url = trim(setting($key)); if ($url === '') continue; ?>
              <a href="<?= e($url) ?>" target="_blank" rel="noopener"><i class="fa-brands <?= $icon ?>"></i></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div><h4>Explore</h4>
          <a href="<?= site_url('articles.php') ?>"><i class="fa-solid fa-newspaper fa-fw"></i> Articles</a>
          <a href="<?= site_url('articles.php?type=news') ?>"><i class="fa-solid fa-bolt fa-fw"></i> News</a>
          <a href="<?= site_url('tools.php') ?>"><i class="fa-solid fa-wrench fa-fw"></i> Web Tools</a>
          <a href="<?= site_url('products.php') ?>"><i class="fa-solid fa-store fa-fw"></i> Store</a>
        </div>
        <div><h4>Company</h4>
          <a href="<?= site_url('about.php') ?>"><i class="fa-solid fa-circle-info fa-fw"></i> About</a>
          <a href="<?= site_url('contact.php') ?>"><i class="fa-solid fa-envelope fa-fw"></i> Contact</a>
          <a href="<?= site_url('sitemap.php') ?>"><i class="fa-solid fa-sitemap fa-fw"></i> Sitemap</a>
        </div>
        <div><h4>Legal</h4>
          <a href="<?= site_url('privacy-policy.php') ?>"><i class="fa-solid fa-user-shield fa-fw"></i> Privacy Policy</a>
          <a href="<?= site_url('terms.php') ?>"><i class="fa-solid fa-gavel fa-fw"></i> Terms of Use</a>
          <a href="<?= site_url('editorial-policy.php') ?>"><i class="fa-solid fa-feather fa-fw"></i> Editorial Policy</a>
          <a href="<?= site_url('refund-policy.php') ?>"><i class="fa-solid fa-rotate-left fa-fw"></i> Refund Policy</a>
          <a href="<?= site_url('license.php') ?>"><i class="fa-solid fa-file-contract fa-fw"></i> License Terms</a>
          <a href="<?= site_url('cookie-policy.php') ?>"><i class="fa-solid fa-cookie-bite fa-fw"></i> Cookie Policy</a>
        </div>
      </div>
      <div class="container bottom">
        <span>© <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
        <span>Built with a self-hosted CMS · Powered by curiosity</span>
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
    ?>
    <a class="card" href="<?= site_url('article.php?slug=' . urlencode($a['slug'])) ?>">
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
    ?>
    <a class="card tool-card" href="<?= site_url('tool.php?slug=' . urlencode($t['slug'])) ?>">
      <div class="hero" style="<?= hero_style_css('g' . (((int)$t['id'] % 8) + 1)) ?>">
        <i class="fa-solid <?= e($t['icon_class']) ?>"></i>
      </div>
      <div class="body">
        <span class="cat">Free Tool</span>
        <h3><?= e($t['name']) ?></h3>
        <p><?= e($t['short_description']) ?></p>
        <span class="cta">Open tool <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <?php
}
