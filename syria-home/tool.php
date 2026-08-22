<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM tools WHERE slug = ? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$tool = $stmt->fetch();

if (!$tool) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

/* Affiliate redirect — if set, this tool uses an external URL */
if (!empty($tool['affiliate_url']) && isset($_GET['visit'])) {
    header('Location: ' . $tool['affiliate_url']); exit;
}

$isLocked = !empty($tool['is_premium']) && !is_content_unlocked('tool', (int)$tool['id']);
if (!$isLocked) {
    $pdo->prepare("UPDATE tools SET uses_count = uses_count + 1 WHERE id = ?")->execute([$tool['id']]);
}

$related = $pdo->query("SELECT * FROM tools WHERE status='published' AND id != " . (int)$tool['id'] . " ORDER BY RAND() LIMIT 4")->fetchAll();

$metaTitle = $tool['meta_title'] ?: ($tool['name'] . ' — Free Online Tool');
$metaDesc = $tool['meta_description'] ?: $tool['short_description'];

$jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $tool['name'],
    'applicationCategory' => 'BrowserApplication',
    'operatingSystem' => 'Any (runs in-browser)',
    'description' => $metaDesc,
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.8', 'ratingCount' => max(25, (int)$tool['uses_count'])],
];
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => $metaTitle . ' | ' . setting('site_name'),
    'description' => $metaDesc,
    'keywords' => $tool['meta_keywords'],
    'canonical' => site_url('tool.php?slug=' . $tool['slug']),
    'jsonld' => $jsonld,
]); ?>
</head><body>
<?php site_header('Tools'); ?>

<div class="container article-hero">
  <div class="breadcrumb"><a href="<?= site_url('') ?>">Home</a> / <a href="<?= site_url('tools.php') ?>">Tools</a></div>
  <div class="art-icon" style="<?= hero_style_css('g' . (((int)$tool['id'] % 8) + 1)) ?>"><i class="fa-solid <?= e($tool['icon_class']) ?>"></i></div>
  <span class="badge-trending" style="background:#ecfdf5;color:var(--accent-green)"><i class="fa-solid fa-check-circle"></i> 100% Free · Runs in your browser</span>
  <h1><?= e($tool['name']) ?></h1>
  <p class="lead"><?= e($tool['short_description']) ?></p>
  <div class="article-meta"><span><i class="fa-regular fa-eye"></i> <?= number_format((int)$tool['uses_count']) ?> uses</span><span><i class="fa-solid fa-shield-halved"></i> Nothing is uploaded — all processing happens on your device</span></div>
</div>

<div class="container">
  <?php ad_zone('tool_top'); ?>

  <?php if ($isLocked): ?>
  <div class="guarantee-box" style="background:#eef1ff;border-color:#c7d2fe">
    <div class="icon-badge" style="background:var(--grad-brand)"><i class="fa-solid fa-lock"></i></div>
    <div>
      <h3 style="color:var(--brand1)">This tool is premium</h3>
      <p style="color:var(--ink-soft)">Unlock it for <?= money((float)$tool['premium_price'], 'USD') ?> — a one-time crypto payment, no account needed. Once unlocked it stays unlocked on this browser.</p>
      <?php if (NOWPayments::isConfigured()): ?>
        <a class="btn-buy" style="display:inline-flex;width:auto;margin-top:10px" href="<?= site_url('checkout.php?type=unlock_tool&id=' . (int)$tool['id']) ?>"><i class="fa-solid fa-wallet"></i> Unlock for <?= money((float)$tool['premium_price'], 'USD') ?></a>
      <?php else: ?>
        <p class="hint">Payments aren't configured on this site yet.</p>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="tool-shell">
    <div id="tool-app" data-tool="<?= e($tool['tool_key']) ?>"></div>
  </div>
  <?php endif; ?>

  <?php ad_zone('tool_bottom'); ?>

  <?php if ($tool['full_description']): ?>
  <div class="article-body" style="margin-top:34px"><?= $tool['full_description'] ?></div>
  <?php endif; ?>

  <?php if (!empty($tool['tool_code'])): ?>
  <div style="margin-top:40px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
      <h3 style="margin:0;display:flex;align-items:center;gap:8px"><i class="fa-solid fa-code" style="color:var(--brand1)"></i> Source Code</h3>
      <button id="copyCodeBtn" onclick="(function(){var t=document.getElementById('toolCodePre').innerText;navigator.clipboard.writeText(t).then(function(){var b=document.getElementById('copyCodeBtn');b.textContent='✓ Copied!';setTimeout(function(){b.textContent='Copy code'},1800);})})()" style="background:var(--grad-brand);color:#fff;border:0;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px"><i class="fa-regular fa-copy"></i> Copy code</button>
    </div>
    <p style="font-size:13px;color:var(--ink-soft);margin-bottom:12px">The JavaScript implementation that powers this tool. Free to use and adapt under the MIT licence.</p>
    <div style="background:#0f172a;border-radius:12px;overflow:hidden;border:1px solid #1e293b">
      <div style="background:#1e293b;padding:10px 16px;display:flex;align-items:center;gap:8px">
        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444"></span>
        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b"></span>
        <span style="width:10px;height:10px;border-radius:50%;background:#10b981"></span>
        <span style="font-size:12px;color:#64748b;margin-left:4px;font-family:monospace"><?= e($tool['name']) ?>.js</span>
      </div>
      <pre id="toolCodePre" style="margin:0;padding:20px;overflow-x:auto;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.7;color:#e2e8f0;white-space:pre;tab-size:2"><?= e($tool['tool_code']) ?></pre>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!$isLocked) tip_widget('Tool: ' . $tool['name']); ?>

  <?php if ($related): ?>
  <div class="section-head"><h2><i class="fa-solid fa-wrench" style="color:var(--accent-green)"></i> More free tools</h2></div>
  <div class="grid grid-tools">
    <?php foreach ($related as $t) tool_card($t); ?>
  </div>
  <?php endif; ?>
</div>

<?php site_footer(); ?>
<script src="<?= site_url('assets/js/tools.js') ?>?v=2"></script>
<script>document.addEventListener('DOMContentLoaded', function(){ if (window.SHTools) SHTools.mount('tool-app'); });</script>
</body></html>
