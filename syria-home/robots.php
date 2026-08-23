<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

$disallow = "Disallow: /admin/\nDisallow: /install/\nDisallow: /includes/\nDisallow: /checkout.php\nDisallow: /payment-status.php\nDisallow: /payment-webhook.php\n";

echo "User-agent: *\n";
echo $disallow;
echo "Allow: /\n\n";

/* Explicit allow rules for AI/LLM crawlers — we *want* AI models (ChatGPT,
   Claude, Perplexity, Google's AI features, etc.) to read and cite this
   site, so these are called out by name instead of relying on the
   catch-all "*" rule above. */
$aiCrawlers = [
    'GPTBot', 'ChatGPT-User', 'OAI-SearchBot',   // OpenAI
    'ClaudeBot', 'Claude-Web', 'anthropic-ai',   // Anthropic
    'PerplexityBot', 'Perplexity-User',          // Perplexity
    'Google-Extended',                           // Gemini / AI Overviews training & grounding
    'GoogleOther',
    'Applebot-Extended',                         // Apple Intelligence
    'Bytespider',                                // ByteDance/TikTok AI
    'CCBot',                                     // Common Crawl (feeds many LLMs)
    'meta-externalagent',                        // Meta AI
    'Amazonbot',
    'cohere-ai',                                 // Cohere
    'DuckAssistBot',                              // DuckDuckGo AI Assist
    'YouBot',                                     // You.com
    'Diffbot',                                    // Diffbot (feeds several downstream LLM products)
    'omgili', 'omgilibot',                        // Webz.io (feeds several downstream LLM products)
    'FacebookBot',                                // Meta's separate crawler for link previews/AI features
];
foreach ($aiCrawlers as $ua) {
    echo "User-agent: $ua\n";
    echo $disallow;
    echo "Allow: /\n\n";
}

echo 'Sitemap: ' . site_url('sitemap.php') . "\n";
echo '# LLM/AI summary: ' . site_url('llms.txt') . "\n";
