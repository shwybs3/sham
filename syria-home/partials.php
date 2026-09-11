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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&family=Tajawal:wght@400;500;700;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= site_url('assets/css/style.css') ?>?v=7">
    <?php
    $themes = [
        'default' => ['brand1' => '#22d3ee', 'brand2' => '#a855f7'],
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
    <?php if (!empty($o['jsonld'])): ?>
    <script type="application/ld+json"><?= str_replace('</script', '<\/script', json_encode($o['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></script>
    <?php endif; ?>
    <?php
}

function nav_links(): array {
    return [
        'الرئيسية' => site_url(''),
        'الباقات' => site_url('products.php'),
        'إنستقرام' => site_url('products.php?platform=instagram'),
        'فيسبوك' => site_url('products.php?platform=facebook'),
        'يوتيوب' => site_url('products.php?platform=youtube'),
        'تيليجرام' => site_url('products.php?platform=telegram'),
        'الحماية الرقمية' => site_url('products.php?platform=security'),
        'المدونة' => site_url('articles.php'),
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
        <span class="art-icon"><i class="<?= e($p['icon_class'] ?: 'fa-solid fa-cube') ?>"></i></span>
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
        <span class="pcta">عرض الباقة <i class="fa-solid fa-arrow-left"></i></span>
      </div>
    </a>
    <?php
}

function cookie_consent_banner(): void {
    ?>
    <div id="cookieBanner" class="cookie-banner" role="dialog" aria-label="تنبيه ملفات تعريف الارتباط">
      <p>نستخدم ملفات تعريف الارتباط لتشغيل الموقع وتحليل الزيارات. <a href="<?= site_url('cookie-policy.php') ?>">اعرف أكثر</a></p>
      <div class="cookie-actions">
        <button type="button" onclick="shAcceptCookies()">حسناً</button>
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
    $siteName = setting('site_name', 'Yassota');
    cookie_consent_banner();
    ?>
    <header class="site-header">
      <div class="bar">
        <?php $logoUrl = trim(setting('logo_url')); ?>
        <a href="<?= site_url('') ?>" class="logo">
          <?php if ($logoUrl !== ''): ?>
            <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" style="max-height:38px;width:auto">
          <?php else: ?>
            <span class="mark"><i class="fa-solid fa-bolt"></i></span><?= e($siteName) ?>
          <?php endif; ?>
        </a>
        <nav class="main-nav" id="mainNav">
          <?php foreach (nav_links() as $label => $url): ?>
            <a href="<?= e($url) ?>" class="<?= $active === $label ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <form class="header-search" action="<?= site_url('search.php') ?>" method="get">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="q" placeholder="بحث عن باقة...">
        </form>
        <?php $tg = trim(setting('support_telegram')); ?>
        <a href="<?= $tg !== '' ? e('https://t.me/' . ltrim($tg, '@')) : site_url('contact.php') ?>" target="<?= $tg !== '' ? '_blank' : '_self' ?>" rel="noopener" class="header-cta"><i class="fa-solid fa-headset"></i> <span>تواصل معنا</span></a>
        <button class="hamburger" id="navToggle" aria-label="فتح القائمة"><i class="fa-solid fa-bars"></i></button>
      </div>
    </header>
    <div class="nav-backdrop" id="navBackdrop"></div>
    <?php
}

function site_footer(): void {
    $siteName = setting('site_name', 'Yassota');
    ?>
    <footer class="site-footer">
      <div class="container cols">
        <div>
          <div class="brand"><span class="mark" style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--brand1),var(--brand2));display:inline-flex;align-items:center;justify-content:center"><i class="fa-solid fa-bolt" style="font-size:14px"></i></span><?= e($siteName) ?></div>
          <p style="max-width:320px;color:#94a3b8;font-size:13px"><?= e(setting('site_tagline', 'باقات نمو رقمي وحماية سيبرانية بدفع فوري بالعملات الرقمية.')) ?></p>
          <?php $footerEmail = trim(setting('contact_email', 'contact@yassota.com')); if ($footerEmail !== ''): ?>
            <a href="mailto:<?= e($footerEmail) ?>" style="color:#94a3b8;font-size:13px;display:inline-flex;align-items:center;gap:6px;margin-bottom:10px"><i class="fa-solid fa-envelope"></i> <?= e($footerEmail) ?></a>
          <?php endif; ?>
          <div class="social-row">
            <?php foreach (['social_twitter'=>'fa-x-twitter','social_facebook'=>'fa-facebook-f','social_linkedin'=>'fa-linkedin-in','social_youtube'=>'fa-youtube','social_github'=>'fa-github','social_instagram'=>'fa-instagram'] as $key=>$icon): $url = trim(setting($key)); if ($url === '') continue; ?>
              <a href="<?= e($url) ?>" target="_blank" rel="noopener"><i class="fa-brands <?= $icon ?>"></i></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div><h4>الخدمات</h4>
          <a href="<?= site_url('products.php?platform=instagram') ?>"><i class="fa-brands fa-instagram fa-fw"></i> إنستقرام</a>
          <a href="<?= site_url('products.php?platform=facebook') ?>"><i class="fa-brands fa-facebook fa-fw"></i> فيسبوك</a>
          <a href="<?= site_url('products.php?platform=youtube') ?>"><i class="fa-brands fa-youtube fa-fw"></i> يوتيوب</a>
          <a href="<?= site_url('products.php?platform=telegram') ?>"><i class="fa-brands fa-telegram fa-fw"></i> تيليجرام</a>
          <a href="<?= site_url('products.php?platform=security') ?>"><i class="fa-solid fa-shield-halved fa-fw"></i> الحماية الرقمية</a>
        </div>
        <div><h4>الشركة</h4>
          <a href="<?= site_url('about.php') ?>"><i class="fa-solid fa-circle-info fa-fw"></i> من نحن</a>
          <a href="<?= site_url('contact.php') ?>"><i class="fa-solid fa-envelope fa-fw"></i> تواصل معنا</a>
          <a href="<?= site_url('articles.php') ?>"><i class="fa-solid fa-newspaper fa-fw"></i> المدونة</a>
          <a href="<?= site_url('tools.php') ?>"><i class="fa-solid fa-wrench fa-fw"></i> أدوات مجانية</a>
          <a href="<?= site_url('sitemap.php') ?>"><i class="fa-solid fa-sitemap fa-fw"></i> خريطة الموقع</a>
        </div>
        <div><h4>قانوني</h4>
          <a href="<?= site_url('privacy-policy.php') ?>"><i class="fa-solid fa-user-shield fa-fw"></i> سياسة الخصوصية</a>
          <a href="<?= site_url('terms.php') ?>"><i class="fa-solid fa-gavel fa-fw"></i> شروط الاستخدام</a>
          <a href="<?= site_url('refund-policy.php') ?>"><i class="fa-solid fa-rotate-left fa-fw"></i> سياسة الاسترجاع</a>
          <a href="<?= site_url('cookie-policy.php') ?>"><i class="fa-solid fa-cookie-bite fa-fw"></i> سياسة الكوكيز</a>
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
        <span>© <?= date('Y') ?> <?= e($siteName) ?> — جميع الحقوق محفوظة</span>
        <span>الدفع بالعملات الرقمية عبر NOWPayments</span>
      </div>
    </footer>
    <?php chatbot_widget(); ?>
    <script src="<?= site_url('assets/js/main.js') ?>?v=3"></script>
    <?php
}

/** Builds the OpenRouter system prompt from the live, published packages catalog. */
function storefront_chatbot_prompt(): string {
    global $pdo;
    $siteName = setting('site_name', 'Yassota');
    $lines = [];
    try {
        $rows = $pdo->query("SELECT name, tagline, price, currency, platform FROM products WHERE status='published' ORDER BY sort_order LIMIT 60")->fetchAll();
        foreach ($rows as $r) {
            $lines[] = '- ' . $r['name'] . ' (' . $r['platform'] . '): ' . money((float)$r['price'], $r['currency']) . ' — ' . $r['tagline'];
        }
    } catch (Throwable $e) {}
    $catalog = $lines ? implode("\n", $lines) : 'لا توجد باقات منشورة حالياً.';
    return "أنت مساعد مبيعات لموقع \"$siteName\" الذي يبيع باقات متابعين ومشاهدات وإعجابات لمنصات التواصل الاجتماعي "
        . "(إنستقرام، فيسبوك، يوتيوب، تيليجرام) بالإضافة إلى أدوات حماية رقمية (VPN، مدير كلمات مرور، وغيرها)، والدفع يتم "
        . "بالعملات الرقمية عبر NOWPayments. أجب بالعربية بإيجاز ووضوح، وساعد الزائر على اختيار الباقة المناسبة واذكر له "
        . "رابط صفحة \"الباقات\" لإتمام الطلب. لا تخترع أسعاراً أو باقات غير المذكورة أدناه. قائمة الباقات المتاحة حالياً:\n"
        . $catalog . "\nإن سُئلت عن دعم بشري وجّه المستخدم إلى صفحة \"تواصل معنا\".";
}

function chatbot_widget(): void {
    if (!OpenRouterClient::isConfigured()) return;
    $siteName = setting('site_name', 'Yassota');
    ?>
    <button id="chatToggle" onclick="shToggleChat()" aria-label="افتح المساعد الذكي"><i class="fa-solid fa-comment-dots"></i></button>
    <div id="chatPanel" role="dialog" aria-label="المساعد الذكي">
      <div class="chat-head">
        <span><i class="fa-solid fa-robot"></i> مساعد <?= e($siteName) ?></span>
        <span class="chat-close" onclick="shToggleChat()"><i class="fa-solid fa-xmark"></i></span>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="bubble bot">أهلاً 👋 أنا هنا لمساعدتك باختيار الباقة المناسبة. شو محتاج؟</div>
      </div>
      <div class="chat-input">
        <input id="chatInput" placeholder="اكتب سؤالك..." onkeydown="if(event.key==='Enter')shSendChat()">
        <button onclick="shSendChat()"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>
    <script>
    function shToggleChat(){ document.getElementById('chatPanel').classList.toggle('open'); }
    var shChatHistory = [];
    function shEscapeHtml(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function shSendChat(){
      var input = document.getElementById('chatInput');
      var text = input.value.trim();
      if(!text) return;
      var body = document.getElementById('chatBody');
      body.insertAdjacentHTML('beforeend', '<div class="bubble user">' + shEscapeHtml(text) + '</div>');
      shChatHistory.push({role:'user', content:text});
      input.value=''; body.scrollTop = body.scrollHeight;
      fetch('<?= site_url('chat.php') ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({message:text, history:shChatHistory})})
        .then(function(r){ return r.json(); })
        .then(function(j){
          body.insertAdjacentHTML('beforeend', '<div class="bubble bot">' + shEscapeHtml(j.reply) + '</div>');
          shChatHistory.push({role:'assistant', content:j.reply});
          body.scrollTop = body.scrollHeight;
        })
        .catch(function(){
          body.insertAdjacentHTML('beforeend', '<div class="bubble bot">تعذّر الاتصال، حاول مرة أخرى.</div>');
          body.scrollTop = body.scrollHeight;
        });
    }
    </script>
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
    $rating = rating_summary('tool', (int)$t['id']);
    ?>
    <a class="tcard" href="<?= site_url('tool.php?slug=' . urlencode($t['slug'])) ?>">
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
