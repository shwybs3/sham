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
  <?php require_once __DIR__ . '/partials.php'; navbar(); ?>

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
  </div>

  <?php require_once __DIR__ . '/partials.php'; footer(); ?>
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
  <?php require_once __DIR__ . '/partials.php'; navbar(); ?>

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
  </div>

  <?php require_once __DIR__ . '/partials.php'; footer(); ?>
</body>
</html><?php
}
