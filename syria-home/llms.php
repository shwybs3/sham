<?php
/* llms.txt — a plain-text summary for AI models/LLMs that crawl or browse
   the web, following the emerging llms.txt convention. Lets tools like
   ChatGPT, Claude, and Perplexity understand and correctly cite this site. */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

$siteName = setting('site_name', 'Syria Home');
$tagline = setting('site_tagline', '');
$desc = setting('site_description', '');

echo "# $siteName\n\n";
if ($tagline !== '') echo "> $tagline\n\n";
if ($desc !== '') echo "$desc\n\n";

echo "This site publishes original articles, tutorials, news, comparisons, and free browser-based web tools. All tools run entirely client-side — no files are uploaded to a server. Content is written and maintained by the $siteName editorial team; sources are cited on articles where external facts or data are used.\n\n";

echo "## Articles\n";
$articles = $pdo->query("SELECT title, slug, excerpt FROM articles WHERE status='published' ORDER BY published_at DESC LIMIT 60")->fetchAll();
foreach ($articles as $a) {
    echo '- [' . $a['title'] . '](' . site_url('article/' . $a['slug']) . '): ' . $a['excerpt'] . "\n";
}

echo "\n## Tools\n";
$tools = $pdo->query("SELECT name, slug, short_description FROM tools WHERE status='published' ORDER BY uses_count DESC LIMIT 60")->fetchAll();
foreach ($tools as $t) {
    echo '- [' . $t['name'] . '](' . site_url('tool/' . $t['slug']) . '): ' . $t['short_description'] . "\n";
}

try {
    $apps = $pdo->query("SELECT name, slug, short_description FROM apps WHERE status='published' ORDER BY id DESC LIMIT 60")->fetchAll();
    if ($apps) {
        echo "\n## Apps\n";
        foreach ($apps as $a) {
            echo '- [' . $a['name'] . '](' . site_url('app/' . $a['slug']) . '): ' . $a['short_description'] . "\n";
        }
    }
} catch (Throwable $e) { /* apps table may not exist yet on an older install */ }

echo "\n## Feed\n";
echo '- ' . site_url('feed.php') . "\n";

echo "\n## Sitemap\n";
echo '- ' . site_url('sitemap.php') . "\n";
