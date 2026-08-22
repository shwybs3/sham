<?php
/**
 * On-demand broken-link scan across every published article's body.
 * No cron dependency — a human clicks "Run scan," results are stored
 * so they persist until the next scan.
 */
function shExtractLinks(string $html): array {
    if (trim($html) === '') return [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
    libxml_clear_errors();
    $urls = [];
    foreach ($doc->getElementsByTagName('a') as $a) {
        $href = trim($a->getAttribute('href'));
        if ($href !== '' && !str_starts_with($href, '#') && !str_starts_with($href, 'mailto:') && !str_starts_with($href, 'tel:')) {
            $urls[] = $href;
        }
    }
    return $urls;
}

function shCheckUrl(string $url): int {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SyriaHomeLinkChecker/1.0)',
    ]);
    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 0 || $status === 405) {
        // Some servers reject HEAD — retry with GET.
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_RANGE => '0-2047',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SyriaHomeLinkChecker/1.0)',
        ]);
        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    return $status;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'scan' && csrf_check()) {
    @set_time_limit(120);
    $pdo->exec("TRUNCATE TABLE link_check_results");

    $articles = $pdo->query("SELECT id, title, body FROM articles WHERE status='published'")->fetchAll();
    $ins = $pdo->prepare("INSERT INTO link_check_results (article_id, article_title, url, is_internal, http_status, ok) VALUES (?,?,?,?,?,?)");
    $checked = 0;

    foreach ($articles as $art) {
        $urls = array_unique(shExtractLinks($art['body']));
        foreach ($urls as $url) {
            if (str_starts_with($url, '/')) $url = rtrim(SITE_URL, '/') . $url;
            if (!preg_match('~^https?://~i', $url)) continue; // skip anchors/relative junk we can't resolve safely

            $isInternal = str_starts_with($url, rtrim(SITE_URL, '/'));
            $status = shCheckUrl($url);
            $ok = $status >= 200 && $status < 400;
            $ins->execute([$art['id'], $art['title'], $url, $isInternal ? 1 : 0, $status, $ok ? 1 : 0]);
            $checked++;
            if ($checked >= 150) break 2; // keep one scan bounded on shared hosting
        }
    }
    header('Location: ?page=link-checker&scanned=1'); exit;
}

$results = $pdo->query("SELECT * FROM link_check_results ORDER BY ok ASC, checked_at DESC")->fetchAll();
$broken = array_filter($results, fn($r) => !$r['ok']);
$lastScan = $results ? $results[0]['checked_at'] : null;
?>
<?php if (isset($_GET['scanned'])): flash('ok', 'Scan complete.'); endif; ?>

<div class="card">
  <div class="toolbar">
    <div>
      <h3 style="margin:0">Broken Link Checker</h3>
      <p class="hint" style="margin:4px 0 0"><?= $lastScan ? 'Last scan: ' . time_ago($lastScan) : 'No scan run yet.' ?> — checks every link inside published article bodies (internal and external), up to 150 links per run.</p>
    </div>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="scan">
      <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Run scan now</button>
    </form>
  </div>

  <div class="grid-stats" style="margin-top:10px">
    <div class="stat"><i class="fa-solid fa-link"></i><div class="n"><?= count($results) ?></div><div class="l">Links checked</div></div>
    <div class="stat"><i class="fa-solid fa-triangle-exclamation"></i><div class="n"><?= count($broken) ?></div><div class="l">Broken</div></div>
  </div>

  <?php if ($results): ?>
  <table style="margin-top:16px">
    <tr><th>Status</th><th>URL</th><th>Found in</th><th>Type</th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
      <td><?= $r['ok'] ? '<span class="badge ok">' . (int)$r['http_status'] . '</span>' : '<span class="badge off">' . ((int)$r['http_status'] ?: 'No response') . '</span>' ?></td>
      <td style="max-width:420px;overflow:auto"><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener" style="font-size:12.5px"><?= e($r['url']) ?></a></td>
      <td><a href="?page=articles&edit=<?= (int)$r['article_id'] ?>"><?= e($r['article_title']) ?></a></td>
      <td><?= $r['is_internal'] ? 'Internal' : 'External' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
