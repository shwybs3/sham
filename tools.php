<?php
/**
 * Web Tools Directory
 * Displays all published web tools with proper SEO
 */
require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');

// If slug provided, show single tool detail
if ($slug) {
    $tool = get_web_tool_by_slug($pdo, $slug);
    if (!$tool || $tool['status'] !== 'published') {
        http_response_code(404);
        die('أداة غير موجودة');
    }

    // Increment views
    try { $pdo->prepare("UPDATE web_tools SET views=views+1 WHERE id=?")->execute([$tool['id']]); } catch (\Throwable $e) {}

    // Parse JSON fields
    $features = json_decode($tool['features'] ?? '[]', true) ?: [];
    $pros     = json_decode($tool['pros'] ?? '[]', true) ?: [];
    $cons     = json_decode($tool['cons'] ?? '[]', true) ?: [];
    $faq      = json_decode($tool['faq'] ?? '[]', true) ?: [];

    // Build breadcrumb schema
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'الرئيسية',
                'item' => SITE_URL
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'الأدوات',
                'item' => SITE_URL . '/tools'
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $tool['name'],
                'item' => SITE_URL . '/tools?slug=' . rawurlencode($slug)
            ]
        ]
    ];

    // Build FAQ schema if present
    $faqSchema = null;
    if (!empty($faq)) {
        $faqItems = [];
        foreach ($faq as $q) {
            if (!empty($q['q']) && !empty($q['a'])) {
                $faqItems[] = [
                    '@type' => 'Question',
                    'name' => $q['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $q['a']
                    ]
                ];
            }
        }
        if (!empty($faqItems)) {
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems
            ];
        }
    }

    ?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($tool['seo_title'] ?: $tool['name']) ?> — yassota</title>
  <meta name="description" content="<?= h($tool['meta_description'] ?: $tool['short_description']) ?>">
  <meta name="keywords" content="<?= h($tool['meta_tags']) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= h(SITE_URL . '/tools?slug=' . rawurlencode($slug)) ?>">
  <meta property="og:title" content="<?= h($tool['seo_title'] ?: $tool['name']) ?>">
  <meta property="og:description" content="<?= h($tool['meta_description'] ?: $tool['short_description']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= h(SITE_URL . '/tools?slug=' . rawurlencode($slug)) ?>">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?= h($tool['seo_title'] ?: $tool['name']) ?>">
  <meta name="twitter:description" content="<?= h($tool['meta_description'] ?: $tool['short_description']) ?>">
  <?php require_once __DIR__ . '/partials.php'; head_common(); ?>
  <style>
    .tool-content { line-height: 1.8; }
    .tool-section { margin: 24px 0; padding: 16px; background: var(--bg); border-radius: 8px; }
    .tool-section h3 { margin: 0 0 12px; font-size: 18px; color: var(--primary); }
    .tool-features { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin: 12px 0; }
    .feature-item { padding: 10px; background: linear-gradient(135deg, rgba(37,99,235,.05), rgba(99,102,241,.05)); border-radius: 6px; }
    .pros-cons { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 12px 0; }
    .pro-list, .con-list { padding: 12px; border-radius: 6px; }
    .pro-list { background: rgba(34,197,94,.08); }
    .con-list { background: rgba(239,68,68,.08); }
    .pro-list li, .con-list li { margin: 6px 0; padding-left: 20px; }
    .pro-list li:before { content: '✓ '; color: #22c55e; }
    .con-list li:before { content: '✗ '; color: #ef4444; }
    .faq-item { margin: 12px 0; padding: 12px; background: var(--surface); border-left: 3px solid var(--primary); }
    .faq-q { font-weight: 700; margin-bottom: 8px; }
    .faq-a { color: var(--muted); font-size: 14px; }
  </style>
  <script type="application/ld+json">
  <?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>
  <?php if ($faqSchema): ?>
  <script type="application/ld+json">
  <?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>
  <?php endif; ?>
</head>
<body>
  <?php require_once __DIR__ . '/partials.php'; render_site_header('', 'tools'); ?>

  <div class="container" style="max-width:900px;padding:40px 20px">
    <a href="/tools" style="color: var(--muted); text-decoration: none; font-size: 14px;">← الأدوات</a>

    <h1 style="margin-top:16px;font-size:32px;line-height:1.3"><?= h($tool['name']) ?></h1>
    <p style="color: var(--muted); font-size: 14px; margin-top: 8px;">
      تاريخ الإنشاء: <?= h(date('d M Y', strtotime($tool['created_at']))) ?>
      | المشاهدات: <?= number_format($tool['views']) ?>
    </p>

    <!-- Short description -->
    <?php if ($tool['short_description']): ?>
    <div class="tool-section" style="background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.08)); border-left: 4px solid var(--primary);">
      <p style="margin: 0; font-size: 16px; line-height: 1.6;">
        <?= h($tool['short_description']) ?>
      </p>
    </div>
    <?php endif; ?>

    <!-- Main content (long description) -->
    <?php if ($tool['long_description']): ?>
    <div class="tool-section">
      <h3>نظرة عامة</h3>
      <div class="tool-content">
        <?= nl2br(h($tool['long_description'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Features -->
    <?php if (!empty($features)): ?>
    <div class="tool-section">
      <h3>المميزات الرئيسية</h3>
      <div class="tool-features">
        <?php foreach ($features as $f): ?>
        <div class="feature-item">✨ <?= h($f) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pros and Cons -->
    <?php if (!empty($pros) || !empty($cons)): ?>
    <div class="tool-section">
      <h3>الإيجابيات والسلبيات</h3>
      <div class="pros-cons">
        <?php if (!empty($pros)): ?>
        <div>
          <h4 style="margin-top: 0; color: #22c55e;">الإيجابيات</h4>
          <ul class="pro-list" style="margin: 0; padding-left: 20px; list-style: none;">
            <?php foreach ($pros as $p): ?>
            <li><?= h($p) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($cons)): ?>
        <div>
          <h4 style="margin-top: 0; color: #ef4444;">السلبيات</h4>
          <ul class="con-list" style="margin: 0; padding-left: 20px; list-style: none;">
            <?php foreach ($cons as $c): ?>
            <li><?= h($c) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tutorials/Usage -->
    <?php if ($tool['tutorials']): ?>
    <div class="tool-section">
      <h3>كيفية الاستخدام والشروحات</h3>
      <div class="tool-content">
        <?= nl2br(h($tool['tutorials'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- How it started -->
    <?php if (!empty($tool['how_it_started'])): ?>
    <div class="tool-section">
      <h3>كيف بدأت هذه الأداة</h3>
      <div class="tool-content">
        <?= nl2br(h($tool['how_it_started'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (!empty($faq)): ?>
    <div class="tool-section">
      <h3>أسئلة شائعة</h3>
      <?php foreach ($faq as $item): ?>
        <?php if (!empty($item['q']) && !empty($item['a'])): ?>
        <div class="faq-item">
          <div class="faq-q"><?= h($item['q']) ?></div>
          <div class="faq-a"><?= nl2br(h($item['a'])) ?></div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- What's New -->
    <?php if ($tool['whats_new']): ?>
    <div class="tool-section">
      <h3>آخر التحديثات</h3>
      <div class="tool-content">
        <?= nl2br(h($tool['whats_new'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Related Apps -->
    <?php
      $relatedApps = [];
      try {
        $relatedApps = $pdo->query(
          "SELECT id, slug, name, icon_path FROM apps WHERE status='published' ORDER BY RAND() LIMIT 4"
        )->fetchAll();
      } catch (\Throwable $e) { $relatedApps = []; }
    ?>
    <?php if (!empty($relatedApps)): ?>
    <div class="tool-section" style="background:linear-gradient(135deg, rgba(34,197,94,.08), rgba(59,130,246,.08));border-left:4px solid #22c55e">
      <h3 style="margin-top:0">تطبيقات قد تهمك</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-top:12px">
        <?php foreach ($relatedApps as $app): ?>
        <a href="<?= h(app_url($app['slug'])) ?>"
           style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px;background:var(--surface);border:1px solid var(--border-c);border-radius:8px;text-decoration:none;transition:.2s"
           onmouseover="this.style.borderColor='rgba(34,197,94,.4)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='var(--border-c)';this.style.transform=''">
          <?php if (!empty($app['icon_path'])): ?>
          <img src="<?= h(media_url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" style="width:40px;height:40px;border-radius:6px">
          <?php else: ?>
          <div style="width:40px;height:40px;border-radius:6px;background:var(--border-c);display:flex;align-items:center;justify-content:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
          </div>
          <?php endif; ?>
          <div style="font-size:12px;font-weight:600;color:var(--fg);text-align:center;line-height:1.3"><?= h(mb_substr($app['name'], 0, 25)) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <div style="text-align:center;margin-top:12px">
        <a href="/" style="font-size:12px;color:var(--cyan);text-decoration:none;font-weight:600">عرض جميع التطبيقات <?= partial_icon('arrow-r') ?></a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <?php require_once __DIR__ . '/partials.php'; render_site_footer(); ?>
</body>
</html><?php
} else {
    // Show tools directory listing
    $tools = list_web_tools($pdo, 'published', 1000);
    ?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>أدوات الويب — yassota</title>
  <meta name="description" content="أدوات ويب مجانية لمحترفي التطوير والتسويق الرقمي">
  <meta name="keywords" content="أدوات ويب, أدوات تطوير, أدوات تسويق رقمي">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= h(SITE_URL . '/tools') ?>">
  <?php require_once __DIR__ . '/partials.php'; head_common(); ?>
  <style>
    .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 30px 0; }
    .tool-card { padding: 20px; background: var(--surface); border: 1px solid var(--border-c); border-radius: 12px; transition: transform .2s, box-shadow .2s; cursor: pointer; }
    .tool-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .tool-card-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--fg); }
    .tool-card-desc { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 12px; }
    .tool-card-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border-c); font-size: 12px; color: var(--muted); }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials.php'; render_site_header('', 'tools'); ?>

  <div class="container" style="max-width:1200px;padding:40px 20px">
    <h1 style="font-size:32px;margin:0 0 10px">أدوات الويب المجانية</h1>
    <p style="color: var(--muted); font-size: 16px; margin: 0 0 30px;">
      مجموعة شاملة من أدوات الويب المجانية لتسهيل عملك
    </p>

    <?php if (empty($tools)): ?>
    <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
      <p style="font-size: 18px;">لا توجد أدوات متاحة حالياً</p>
    </div>
    <?php else: ?>
    <div class="tools-grid">
      <?php foreach ($tools as $t): ?>
      <a href="<?= h(SITE_URL . '/tools?slug=' . rawurlencode($t['slug'])) ?>" style="text-decoration: none; color: inherit;">
        <div class="tool-card">
          <div class="tool-card-title"><?= h($t['name']) ?></div>
          <div class="tool-card-desc">
            <?= h(mb_substr($t['short_description'], 0, 100)) ?>
            <?php if (mb_strlen($t['short_description']) > 100): ?>…<?php endif; ?>
          </div>
          <div class="tool-card-meta">
            <span>📊 <?= number_format($t['views']) ?> مشاهدة</span>
            <span>📅 <?= h(date('M Y', strtotime($t['created_at']))) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Related Apps Section -->
    <?php if (!empty($tools)):
      $relatedApps = [];
      try {
        $relatedApps = $pdo->query(
          "SELECT id, slug, name, icon_path FROM apps WHERE status='published' ORDER BY downloads DESC LIMIT 6"
        )->fetchAll();
      } catch (\Throwable $e) { $relatedApps = []; }
    ?>
    <?php if (!empty($relatedApps)): ?>
    <div style="margin-top:60px;padding:30px;background:linear-gradient(135deg, rgba(34,197,94,.08), rgba(59,130,246,.08));border:1px solid rgba(34,197,94,.2);border-radius:12px">
      <h2 style="font-size:24px;margin:0 0 20px;color:var(--fg)">تطبيقات مشهورة قد تهمك</h2>
      <p style="color:var(--muted);margin:0 0 20px">استكمل تجربتك باستخدام أفضل التطبيقات الأكثر تحميلاً</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
        <?php foreach ($relatedApps as $app): ?>
        <a href="<?= h(app_url($app['slug'])) ?>"
           style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:16px;background:var(--surface);border:1px solid var(--border-c);border-radius:10px;text-decoration:none;transition:.2s"
           onmouseover="this.style.borderColor='rgba(34,197,94,.4)';this.style.transform='translateY(-4px)';this.style.boxShadow='0 4px 12px rgba(34,197,94,.1)'"
           onmouseout="this.style.borderColor='var(--border-c)';this.style.transform='';this.style.boxShadow=''">
          <?php if (!empty($app['icon_path'])): ?>
          <img src="<?= h(media_url($app['icon_path'])) ?>" alt="<?= h($app['name']) ?>" style="width:48px;height:48px;border-radius:8px">
          <?php else: ?>
          <div style="width:48px;height:48px;border-radius:8px;background:var(--border-c);display:flex;align-items:center;justify-content:center">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
          </div>
          <?php endif; ?>
          <div style="font-size:13px;font-weight:600;color:var(--fg);text-align:center;line-height:1.3"><?= h(mb_substr($app['name'], 0, 30)) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <div style="text-align:center;margin-top:20px">
        <a href="/" style="display:inline-block;padding:10px 20px;background:var(--primary);color:white;text-decoration:none;border-radius:6px;font-weight:600;transition:.2s"
           onmouseover="this.style.opacity='.85'"
           onmouseout="this.style.opacity='1'">عرض جميع التطبيقات</a>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php require_once __DIR__ . '/partials.php'; render_site_footer(); ?>
</body>
</html><?php
}
