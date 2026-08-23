<?php
$stats = [
    'articles' => (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn(),
    'drafts' => (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn(),
    'tools' => (int)$pdo->query("SELECT COUNT(*) FROM tools WHERE status='published'")->fetchColumn(),
    'views' => (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM articles")->fetchColumn(),
    'uses' => (int)$pdo->query("SELECT COALESCE(SUM(uses_count),0) FROM tools")->fetchColumn(),
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn(),
    'new_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn(),
];
$recent = $pdo->query("SELECT id, title, slug, status, views, published_at FROM articles ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentAi = $pdo->query("SELECT * FROM ai_activity_log ORDER BY created_at DESC LIMIT 6")->fetchAll();

$g = GoogleStats::summary(isset($_GET['refresh_stats']));
if (isset($_GET['refresh_stats'])) { header('Location: ?page=dashboard'); exit; }

try {
    $ratingAgg = $pdo->query("SELECT COUNT(*) c, AVG(rating) a FROM ratings")->fetch();
} catch (PDOException $e) { $ratingAgg = ['c' => 0, 'a' => 0]; }
?>

<?php if (GoogleStats::isAvailable()): ?>
<div class="card" style="padding:18px 22px">
  <div class="toolbar" style="margin-bottom:14px">
    <h3 style="margin:0"><i class="fa-brands fa-google" style="color:var(--brand1)"></i> Live Google data</h3>
    <span style="display:flex;align-items:center;gap:10px">
      <span class="hint"><?= $g['fetched_at'] ? 'Updated ' . time_ago(date('Y-m-d H:i:s', $g['fetched_at'])) : 'Never fetched' ?></span>
      <a class="btn gray sm" href="?page=dashboard&refresh_stats=1"><i class="fa-solid fa-arrows-rotate"></i> Refresh</a>
    </span>
  </div>

  <div class="grid-stats" style="margin-bottom:0">
    <?php if ($g['ga4']): ?>
      <div class="stat"><i class="fa-solid fa-circle" style="color:#16a34a;font-size:11px"></i><div class="n"><?= number_format($g['ga4']['live']) ?></div><div class="l">Users online now</div></div>
      <div class="stat"><i class="fa-solid fa-chart-line"></i><div class="n"><?= number_format($g['ga4']['page_views']) ?></div><div class="l">Page views · 28d</div></div>
    <?php endif; ?>
    <?php if ($g['gsc']): ?>
      <div class="stat"><i class="fa-solid fa-magnifying-glass"></i><div class="n"><?= number_format($g['gsc']['clicks']) ?></div><div class="l">Search clicks · 28d</div></div>
      <div class="stat"><i class="fa-solid fa-eye"></i><div class="n"><?= number_format($g['gsc']['impressions']) ?></div><div class="l">Impressions · 28d</div></div>
      <div class="stat"><i class="fa-solid fa-ranking-star"></i><div class="n"><?= $g['gsc']['position'] ? number_format($g['gsc']['position'], 1) : '—' ?></div><div class="l">Avg. position</div></div>
    <?php endif; ?>
    <?php if ($g['adsense']): ?>
      <div class="stat"><i class="fa-solid fa-sack-dollar"></i><div class="n"><?= e($g['adsense']['currency']) ?> <?= number_format($g['adsense']['earnings'], 2) ?></div><div class="l">AdSense today</div></div>
      <div class="stat"><i class="fa-solid fa-hand-pointer"></i><div class="n"><?= number_format($g['adsense']['clicks']) ?></div><div class="l">Ad clicks today</div></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($g['gsc']['queries']) || !empty($g['ga4']['pages'])): ?>
  <div class="row2" style="margin-top:18px">
    <?php if (!empty($g['gsc']['queries'])): ?>
    <div>
      <h4 style="margin:0 0 8px;font-size:13px">Top search queries · 28 days</h4>
      <table>
        <tr><th>Query</th><th>Clicks</th><th>Impr.</th><th>Pos.</th></tr>
        <?php foreach ($g['gsc']['queries'] as $q): ?>
        <tr><td><?= e($q['query']) ?></td><td><?= number_format($q['clicks']) ?></td><td><?= number_format($q['impressions']) ?></td><td><?= $q['position'] ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>
    <?php if (!empty($g['ga4']['pages'])): ?>
    <div>
      <h4 style="margin:0 0 8px;font-size:13px">Most-viewed pages · 28 days</h4>
      <table>
        <tr><th>Path</th><th>Views</th></tr>
        <?php foreach ($g['ga4']['pages'] as $p): ?>
        <tr><td><?= e($p['path']) ?></td><td><?= number_format($p['views']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (!$g['adsense'] && !$g['gsc'] && !$g['ga4']): ?>
    <p class="hint">Connected, but no product returned data yet. AdSense needs an approved account; Search Console needs the site URL set in Settings → API Keys; Analytics needs the GA4 property ID.</p>
  <?php endif; ?>
  <?php foreach ($g['errors'] as $err): ?>
    <p class="hint" style="color:var(--warn)"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($err) ?></p>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card" style="background:#f8fafc">
  <h3 style="margin-top:0"><i class="fa-brands fa-google"></i> Live Google data</h3>
  <p class="hint">Connect a Google account to show real AdSense earnings, Search Console rankings and Analytics traffic right here.</p>
  <a class="btn sm" href="?page=settings&tab=api"><i class="fa-solid fa-plug"></i> Connect Google</a>
</div>
<?php endif; ?>

<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-newspaper"></i><div class="n"><?= number_format($stats['articles']) ?></div><div class="l">Published articles</div></div>
  <div class="stat"><i class="fa-regular fa-file"></i><div class="n"><?= number_format($stats['drafts']) ?></div><div class="l">Drafts</div></div>
  <div class="stat"><i class="fa-solid fa-wrench"></i><div class="n"><?= number_format($stats['tools']) ?></div><div class="l">Live tools</div></div>
  <div class="stat"><i class="fa-regular fa-eye"></i><div class="n"><?= number_format($stats['views']) ?></div><div class="l">Article views</div></div>
  <div class="stat"><i class="fa-solid fa-bolt"></i><div class="n"><?= number_format($stats['uses']) ?></div><div class="l">Tool uses</div></div>
  <div class="stat"><i class="fa-solid fa-store"></i><div class="n"><?= number_format($stats['products']) ?></div><div class="l">Store products</div></div>
  <div class="stat"><i class="fa-solid fa-inbox"></i><div class="n"><?= number_format($stats['new_orders']) ?></div><div class="l">New orders</div></div>
  <div class="stat"><i class="fa-solid fa-star"></i><div class="n"><?= (int)$ratingAgg['c'] ? number_format((float)$ratingAgg['a'], 1) : '—' ?></div><div class="l"><?= number_format((int)$ratingAgg['c']) ?> visitor ratings</div></div>
</div>

<div class="row2">
  <div class="card">
    <h3 style="margin-top:0">Integration status</h3>
    <table>
      <tr><td>Google APIs (AdSense / Search Console / Analytics / Ads)</td><td><?= GoogleOAuth::isConnected() ? '<span class="badge ok">Connected</span>' : '<span class="badge off">Not connected</span>' ?></td></tr>
      <tr><td>Google Ads developer token</td><td><?= GoogleAdsClient::developerToken() !== '' ? '<span class="badge ok">Set</span>' : '<span class="badge warn">Missing</span>' ?></td></tr>
      <tr><td>Gemini AI assistant</td><td><?= GeminiClient::isConfigured() ? '<span class="badge ok">Ready</span>' : '<span class="badge off">No key</span>' ?></td></tr>
      <tr><td>OpenRouter (fallback AI)</td><td><?= OpenRouterClient::isConfigured() ? '<span class="badge ok">Ready</span>' : '<span class="badge off">No key</span>' ?></td></tr>
      <tr><td>AdSense publisher ID</td><td><?= trim(setting('adsense_publisher_id')) !== '' ? '<span class="badge ok">Set</span>' : '<span class="badge off">Not set</span>' ?></td></tr>
    </table>
    <a class="btn gray sm" style="margin-top:14px" href="?page=settings&tab=api">Configure in Settings →</a>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Recent AI activity</h3>
    <?php if (!$recentAi): ?>
      <p class="hint">Nothing yet. Use the AI Assistant to generate or edit content.</p>
    <?php else: foreach ($recentAi as $log): ?>
      <div style="padding:9px 0;border-bottom:1px solid var(--line);font-size:13px">
        <b><?= e($log['action']) ?></b> · <?= e($log['summary']) ?>
        <div class="hint"><?= time_ago($log['created_at']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar"><h3 style="margin:0">Recent articles</h3><a class="btn sm" href="?page=articles"><i class="fa-solid fa-plus"></i> New article</a></div>
  <table>
    <tr><th>Title</th><th>Status</th><th>Views</th><th>Published</th><th></th></tr>
    <?php foreach ($recent as $r): ?>
    <tr>
      <td><?= e($r['title']) ?></td>
      <td><?= $r['status'] === 'published' ? '<span class="badge ok">Published</span>' : '<span class="badge off">Draft</span>' ?></td>
      <td><?= number_format((int)$r['views']) ?></td>
      <td><?= date('M j, Y', strtotime($r['published_at'])) ?></td>
      <td><a class="btn gray sm" href="?page=articles&edit=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
