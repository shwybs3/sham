<?php
/**
 * Web Tools Directory
 * Displays all published web tools with proper SEO
 */
require_once __DIR__ . '/static-cache-check.php';
$_scSlug = trim($_GET['slug'] ?? '');
static_cache_try_serve('tool', $_scSlug);

require_once __DIR__ . '/config.php';

$slug = $_scSlug;
if ($slug) static_cache_capture_start('tool', $slug);

// If slug provided, show single tool detail
if ($slug) {
    $tool = get_web_tool_by_slug($pdo, $slug);
    if (!$tool || $tool['status'] !== 'published') {
        http_response_code(404);
        die(h(__('tool_not_found')));
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
                'name' => __('home'),
                'item' => SITE_URL
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => __('tools'),
                'item' => SITE_URL . '/tools'
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $tool['name'],
                'item' => SITE_URL . '/tools/' . rawurlencode($slug) . '/'
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
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($tool['seo_title'] ?: $tool['name']) ?> — yassota</title>
  <meta name="description" content="<?= h($tool['meta_description'] ?: $tool['short_description']) ?>">
  <meta name="keywords" content="<?= h($tool['meta_tags']) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= h(SITE_URL . '/tools/' . rawurlencode($slug) . '/') ?>">
  <meta property="og:title" content="<?= h($tool['seo_title'] ?: $tool['name']) ?>">
  <meta property="og:description" content="<?= h($tool['meta_description'] ?: $tool['short_description']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= h(SITE_URL . '/tools/' . rawurlencode($slug) . '/') ?>">
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
    <a href="/tools" style="color: var(--muted); text-decoration: none; font-size: 14px;">← <?= h(__('tools')) ?></a>

    <h1 style="margin-top:16px;font-size:32px;line-height:1.3"><?= h($tool['name']) ?></h1>
    <p style="color: var(--muted); font-size: 14px; margin-top: 8px;">
      <?= h(__('date')) ?>: <?= h(date('d M Y', strtotime($tool['created_at']))) ?>
      | <?= h(__('views')) ?>: <?= number_format($tool['views']) ?>
    </p>

    <!-- Short description -->
    <?php if ($tool['short_description']): ?>
    <div class="tool-section" style="background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.08)); border-left: 4px solid var(--primary);">
      <p style="margin: 0; font-size: 16px; line-height: 1.6;">
        <?= h($tool['short_description']) ?>
      </p>
    </div>
    <?php endif; ?>

    <!-- Main content (long description) — AI-generated content is stored as
         HTML (see admin.php gen_tool_content_chunk), so it must render as
         markup here, not be escaped as literal text. -->
    <?php if ($tool['long_description']): ?>
    <div class="tool-section">
      <h3><?= h(__('overview')) ?></h3>
      <div class="tool-content">
        <?= str_contains($tool['long_description'], '<') ? $tool['long_description'] : nl2br(h($tool['long_description'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Features -->
    <?php if (!empty($features)): ?>
    <div class="tool-section">
      <h3><?= h(__('main_features')) ?></h3>
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
      <h3><?= h(__('pros_cons')) ?></h3>
      <div class="pros-cons">
        <?php if (!empty($pros)): ?>
        <div>
          <h4 style="margin-top: 0; color: #22c55e;"><?= h(__('pros')) ?></h4>
          <ul class="pro-list" style="margin: 0; padding-left: 20px; list-style: none;">
            <?php foreach ($pros as $p): ?>
            <li><?= h($p) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($cons)): ?>
        <div>
          <h4 style="margin-top: 0; color: #ef4444;"><?= h(__('cons')) ?></h4>
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
      <h3><?= h(__('how_to_use_tutorials')) ?></h3>
      <div class="tool-content">
        <?= nl2br(h($tool['tutorials'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- How it started -->
    <?php if (!empty($tool['how_it_started'])): ?>
    <div class="tool-section">
      <h3><?= h(__('how_it_started_label')) ?></h3>
      <div class="tool-content">
        <?= nl2br(h($tool['how_it_started'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (!empty($faq)): ?>
    <div class="tool-section">
      <h3><?= h(__('faq_heading')) ?></h3>
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
      <h3><?= h(__('latest_updates_heading')) ?></h3>
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
      <h3 style="margin-top:0"><?= h(__('apps_you_may_like')) ?></h3>
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
        <a href="/" style="font-size:12px;color:var(--cyan);text-decoration:none;font-weight:600"><?= h(__('view_all_apps')) ?> <?= partial_icon('arrow-r') ?></a>
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
<html lang="<?= defined('UI_LANG') ? UI_LANG : 'ar' ?>" dir="<?= defined('UI_DIR') ? UI_DIR : 'rtl' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(__('free_web_tools')) ?> — yassota</title>
  <meta name="description" content="<?= h(__('free_web_tools_desc')) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= h(SITE_URL . '/tools') ?>">
  <?php require_once __DIR__ . '/partials.php'; head_common(); ?>
  <style>
    .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; margin: 30px 0; }
    .tool-card { display: flex; flex-direction: column; background: var(--surface); border: 1px solid var(--border-c); border-radius: 14px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
    .tool-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .tool-card-body { padding: 18px 18px 14px; text-align: center; text-decoration: none; color: inherit; flex: 1; }
    .tool-card-icon { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; margin: 0 auto 10px; }
    .tool-card-icon-ph { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg,#667eea,#764ba2); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #fff; }
    .tool-card-title { font-size: 15px; font-weight: 700; margin-bottom: 6px; color: var(--fg); }
    .tool-card-desc { font-size: 12px; color: var(--muted); line-height: 1.6; margin-bottom: 8px; min-height: 2.6em; }
    .tool-card-meta { font-size: 11px; color: var(--muted); }
    .tool-card-browse { display: block; text-align: center; padding: 10px; font-size: 13px; font-weight: 700; color: #fff; background: linear-gradient(135deg,#667eea,#764ba2); text-decoration: none; }
    .tool-card-browse:hover { opacity: .9; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials.php'; render_site_header('', 'tools'); ?>

  <div class="container" style="max-width:1200px;padding:40px 20px">
    <h1 style="font-size:32px;margin:0 0 10px"><?= h(__('free_web_tools')) ?></h1>
    <p style="color: var(--muted); font-size: 16px; margin: 0 0 30px;">
      <?= h(__('free_web_tools_desc')) ?>
    </p>

    <?php if (empty($tools)): ?>
    <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
      <p style="font-size: 18px;"><?= h(__('no_tools_available')) ?></p>
    </div>
    <?php else: ?>
    <div class="tools-grid">
      <?php foreach ($tools as $t): ?>
      <div class="tool-card">
        <a href="<?= h(SITE_URL . '/tools/' . rawurlencode($t['slug']) . '/') ?>" class="tool-card-body">
          <?php if (!empty($t['icon_path'])): ?>
          <img src="<?= h(media_url($t['icon_path'])) ?>" alt="<?= h($t['name']) ?>" class="tool-card-icon" loading="lazy">
          <?php else: ?>
          <div class="tool-card-icon-ph">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
          </div>
          <?php endif; ?>
          <div class="tool-card-title"><?= h($t['name']) ?></div>
          <div class="tool-card-desc">
            <?= h(mb_substr($t['short_description'], 0, 80)) ?>
            <?php if (mb_strlen($t['short_description']) > 80): ?>…<?php endif; ?>
          </div>
          <div class="tool-card-meta">📊 <?= number_format($t['views']) ?> <?= h(__('views_label')) ?></div>
        </a>
        <a href="<?= h(SITE_URL . '/tools/' . rawurlencode($t['slug']) . '/') ?>" class="tool-card-browse"><?= h(__('browse')) ?></a>
      </div>
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
      <h2 style="font-size:24px;margin:0 0 20px;color:var(--fg)"><?= h(__('popular_apps_you_like')) ?></h2>
      <p style="color:var(--muted);margin:0 0 20px"><?= h(__('complete_experience')) ?></p>
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
           onmouseout="this.style.opacity='1'"><?= h(__('view_all_apps')) ?></a>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php require_once __DIR__ . '/partials.php'; render_site_footer(); ?>
</body>
</html><?php
}
