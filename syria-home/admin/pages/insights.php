<?php
$connected = GoogleOAuth::isConnected();
?>
<?php if (!$connected): ?>
  <div class="card">
    <h3 style="margin-top:0">Not connected yet</h3>
    <p>Connect your Google account in <a href="?page=settings&tab=api">Settings → API Keys</a> to see AdSense earnings, Search Console queries, Analytics traffic and Google Ads campaigns here.</p>
  </div>
<?php else: ?>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-dollar-sign"></i> AdSense</h3>
    <?php $accounts = AdSenseClient::listAccounts();
    if (!empty($accounts['accounts'][0]['name'])):
        $earnings = AdSenseClient::earningsToday($accounts['accounts'][0]['name']); ?>
      <pre style="background:#f8f9ff;padding:14px;border-radius:10px;font-size:12px;overflow:auto"><?= e(json_encode($earnings, JSON_PRETTY_PRINT)) ?></pre>
    <?php elseif (!empty($accounts['error'])): ?>
      <p class="hint">Not available yet: <?= e(is_array($accounts['error']) ? ($accounts['error']['message'] ?? 'error') : $accounts['error']) ?> — this is normal until AdSense has approved the site and linked an account.</p>
    <?php else: ?>
      <p class="hint">No AdSense account found on this Google account yet.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-magnifying-glass"></i> Search Console — top queries (28 days)</h3>
    <?php $gsc = SearchConsoleClient::searchAnalytics(setting('gsc_site_url'));
    if (!empty($gsc['rows'])): ?>
      <table><tr><th>Query</th><th>Clicks</th><th>Impressions</th><th>CTR</th><th>Position</th></tr>
      <?php foreach ($gsc['rows'] as $r): ?>
        <tr><td><?= e($r['keys'][0] ?? '') ?></td><td><?= (int)($r['clicks'] ?? 0) ?></td><td><?= (int)($r['impressions'] ?? 0) ?></td><td><?= round(($r['ctr'] ?? 0) * 100, 1) ?>%</td><td><?= round($r['position'] ?? 0, 1) ?></td></tr>
      <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p class="hint">No data yet — this fills in once Search Console has indexed &amp; reported on the property at <code><?= e(setting('gsc_site_url')) ?></code>.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-solid fa-chart-line"></i> Analytics (GA4) — top pages (28 days)</h3>
    <?php if (trim(setting('ga4_property_id')) === ''): ?>
      <p class="hint">Add your GA4 Property ID in Settings → API Keys.</p>
    <?php else:
      $ga = AnalyticsDataClient::runReport(setting('ga4_property_id'));
      if (!empty($ga['rows'])): ?>
        <table><tr><th>Page</th><th>Views</th><th>Users</th><th>Avg. duration</th></tr>
        <?php foreach ($ga['rows'] as $r): ?>
          <tr><td><?= e($r['dimensionValues'][0]['value'] ?? '') ?></td><td><?= e($r['metricValues'][0]['value'] ?? '') ?></td><td><?= e($r['metricValues'][1]['value'] ?? '') ?></td><td><?= round((float)($r['metricValues'][2]['value'] ?? 0)) ?>s</td></tr>
        <?php endforeach; ?>
        </table>
      <?php else: ?><p class="hint">No data yet.</p><?php endif;
    endif; ?>
  </div>

  <div class="card">
    <h3 style="margin-top:0"><i class="fa-brands fa-google"></i> Google Ads — campaigns (30 days)</h3>
    <?php if (!GoogleAdsClient::isConfigured()): ?>
      <p class="hint">Add your Google Ads developer token + Customer ID in Settings → API Keys.</p>
    <?php else:
      $ads = GoogleAdsClient::campaignSummary();
      if (!empty($ads['results'])): ?>
        <table><tr><th>Campaign</th><th>Impressions</th><th>Clicks</th><th>Cost</th></tr>
        <?php foreach ($ads['results'] as $r): ?>
          <tr><td><?= e($r['campaign']['name'] ?? '') ?></td><td><?= (int)($r['metrics']['impressions'] ?? 0) ?></td><td><?= (int)($r['metrics']['clicks'] ?? 0) ?></td><td>$<?= number_format((int)($r['metrics']['costMicros'] ?? 0) / 1000000, 2) ?></td></tr>
        <?php endforeach; ?>
        </table>
      <?php else: ?><p class="hint">No active campaigns found, or: <?= e($ads['message'] ?? $ads['error'] ?? '') ?></p><?php endif;
    endif; ?>
  </div>

<?php endif; ?>
