<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/partials.php';

$tools = $pdo->query("SELECT * FROM tools WHERE status='published' ORDER BY uses_count DESC, id DESC")->fetchAll();
?><!doctype html><html lang="en"><head>
<?php seo_head([
    'title' => 'Free Online Tools | ' . setting('site_name'),
    'description' => 'A growing collection of free, fast, browser-based tools — converters, generators and calculators. No installs, no sign-up, nothing uploaded.',
    'canonical' => site_url('tools.php'),
]); ?>
</head><body>
<?php site_header('Tools'); ?>

<div class="page-hero container">
  <span class="eyebrow"><i class="fa-solid fa-wrench"></i> <?= count($tools) ?> free tools</span>
  <h1>Free web tools that just work.</h1>
  <p class="lead">Everything runs locally in your browser — nothing is ever uploaded to a server.</p>
</div>

<div class="container">
  <div class="grid grid-tools">
    <?php foreach ($tools as $t) tool_card($t); ?>
  </div>
</div>

<?php site_footer(); ?>
</body></html>
