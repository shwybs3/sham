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
?>
<div class="grid-stats">
  <div class="stat"><i class="fa-solid fa-newspaper"></i><div class="n"><?= number_format($stats['articles']) ?></div><div class="l">Published articles</div></div>
  <div class="stat"><i class="fa-regular fa-file"></i><div class="n"><?= number_format($stats['drafts']) ?></div><div class="l">Drafts</div></div>
  <div class="stat"><i class="fa-solid fa-wrench"></i><div class="n"><?= number_format($stats['tools']) ?></div><div class="l">Live tools</div></div>
  <div class="stat"><i class="fa-regular fa-eye"></i><div class="n"><?= number_format($stats['views']) ?></div><div class="l">Article views</div></div>
  <div class="stat"><i class="fa-solid fa-bolt"></i><div class="n"><?= number_format($stats['uses']) ?></div><div class="l">Tool uses</div></div>
  <div class="stat"><i class="fa-solid fa-store"></i><div class="n"><?= number_format($stats['products']) ?></div><div class="l">Store products</div></div>
  <div class="stat"><i class="fa-solid fa-inbox"></i><div class="n"><?= number_format($stats['new_orders']) ?></div><div class="l">New orders</div></div>
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
