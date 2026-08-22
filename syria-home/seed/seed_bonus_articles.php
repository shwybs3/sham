<?php
/**
 * Two purpose-built articles requested separately from the main 21:
 *  1. A cross-promotional piece about Syria Home itself — used as the
 *     content on temporary "coming soon" pages for freshly provisioned
 *     subdomains (see includes/maintenance.php), and publishable as a
 *     normal article too.
 *  2. A marketing draft for selling the Syria Home script — left as a
 *     DRAFT with clearly marked placeholders for price/payment/images,
 *     since only the site owner can decide and enter those for real.
 *
 * Both are inserted (once — checked by slug) via the "Add starter
 * marketing articles" button on the admin Articles page, so an
 * already-installed site can get them without reinstalling.
 */
function seed_bonus_articles(PDO $pdo): int {
    $catMap = [];
    foreach ($pdo->query("SELECT id, slug FROM categories WHERE type='article'") as $r) $catMap[$r['slug']] = (int)$r['id'];

    $siteName = setting('site_name', 'Syria Home');
    $homeUrl = site_url('');
    $articlesUrl = site_url('articles.php');
    $toolsUrl = site_url('tools.php');
    $storeUrl = site_url('products.php');

    $A = [];

    $A[] = [
        'title' => 'Welcome to ' . $siteName,
        'cat' => 'ai-software', 'type' => 'article', 'icon' => 'fa-house-flag', 'gradient' => 'g1',
        'trending' => 1, 'status' => 'published',
        'tags' => 'about, ' . strtolower($siteName),
        'meta_kw' => strtolower($siteName) . ', tech news, free web tools, ' . strtolower($siteName) . ' articles',
        'excerpt' => 'A quick tour of what ' . $siteName . ' actually is: honest tech coverage plus free, no-signup browser tools — and where to start.',
        'body' => <<<HTML
<p>If you landed here from somewhere else on the web, here's the short version: {$siteName} is a straightforward tech publication. No account walls, no dark patterns, no fake urgency — just articles worth reading and tools that work the first time.</p>
<h2>What you'll find here</h2>
<ul>
<li><strong>News &amp; trend coverage</strong> — what's actually changing in AI, hardware, mobile and internet culture, explained without the hype.</li>
<li><strong>Honest comparisons</strong> — head-to-head breakdowns (AI assistants, EVs vs. hybrids, social platforms) written to help you decide, not to pad a word count.</li>
<li><strong>Practical tutorials</strong> — step-by-step guides for things people actually search for, like setting up passkeys or self-hosting your own cloud.</li>
<li><strong>Free web tools</strong> — image converters, generators, calculators and more, all running client-side in your browser. Nothing you process is ever uploaded to a server.</li>
</ul>
<h2>Why it's built this way</h2>
<p>Most "free tool" sites exist purely to serve ads around a tool that barely works. We built this the other way around: the tools had to be genuinely useful first, and the site had to be fast and honest enough that we'd want to use it ourselves.</p>
<h2>Where to start</h2>
<p>Browse the <a href="{$articlesUrl}">latest articles</a> for what's trending right now, or jump straight to the <a href="{$toolsUrl}">free tools collection</a> if you came here to get something done. Either way — <a href="{$homeUrl}">welcome</a>.</p>
HTML,
    ];

    $A[] = [
        'title' => 'Introducing the ' . $siteName . ' Script: Build a Site Like This One',
        'cat' => 'ai-software', 'type' => 'review', 'icon' => 'fa-rocket', 'gradient' => 'g5',
        'trending' => 0, 'status' => 'draft',
        'tags' => 'php script, self-hosted cms, ' . strtolower($siteName) . ' script',
        'meta_kw' => strtolower($siteName) . ' script, php cms script, self hosted blog script',
        'excerpt' => '[DRAFT — finish with real screenshots, final pricing and your NOWPayments checkout link before publishing] The exact script running this site is now available.',
        'body' => <<<HTML
<p><em>Editor's note — this article is a draft. Replace the bracketed placeholders below with real screenshots, your final price, and your live NOWPayments checkout link before publishing. It won't appear on the site until you set its status to Published.</em></p>

<p>Everything you're using right now — this article, the free tools page, the admin panel — runs on one self-hosted PHP script. We're making it available to anyone who wants to launch a similar site without building it from scratch.</p>

<h2>What's included</h2>
<ul>
<li>A complete content CMS: articles, news, tutorials, comparisons and reviews, each with full SEO out of the box.</li>
<li>20 working, client-side web tools — no server processing, no ongoing hosting cost per use.</li>
<li>A digital storefront with real crypto checkout built in.</li>
<li>An AI content assistant, scoped so it can draft and edit content but never touch your files, database, or credentials directly.</li>
<li>A one-click installer — no manual SQL, no command line required.</li>
<li>[INSERT 4–6 real screenshots here: homepage, admin dashboard, an article page, the tools page]</li>
</ul>

<h2>Why we built it this way</h2>
<p>[Write 2–3 sentences here about your motivation — what problem this solves for a buyer, and why they should trust it over a generic template.]</p>

<h2>Pricing</h2>
<p>[INSERT final price here, e.g. "$XX — one-time payment, lifetime license."] Payment is handled securely through NOWPayments (crypto checkout) — see the <a href="{$storeUrl}">Store</a> for the live listing and full feature breakdown.</p>

<h2>What you get</h2>
<ul>
<li>[Confirm: full source code / setup guide / support window / update policy]</li>
</ul>

<p><a href="#">[Link this to your Store product page once ready]</a></p>
HTML,
    ];

    $stmt = $pdo->prepare("INSERT INTO articles
        (title, slug, category_id, content_type, excerpt, body, hero_icon, hero_gradient, meta_title, meta_description, meta_keywords, tags, author, status, trending, reading_time, published_at)
        VALUES (:title,:slug,:category_id,:content_type,:excerpt,:body,:hero_icon,:hero_gradient,:meta_title,:meta_description,:meta_keywords,:tags,:author,:status,:trending,:reading_time,:published_at)");

    $inserted = 0;
    foreach ($A as $a) {
        $slug = slugify($a['title']);
        $exists = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE slug = ?");
        $exists->execute([$slug]);
        if ((int)$exists->fetchColumn() > 0) continue;

        $stmt->execute([
            'title' => $a['title'], 'slug' => $slug,
            'category_id' => $catMap[$a['cat']] ?? null,
            'content_type' => $a['type'], 'excerpt' => $a['excerpt'], 'body' => $a['body'],
            'hero_icon' => $a['icon'], 'hero_gradient' => $a['gradient'],
            'meta_title' => $a['title'], 'meta_description' => $a['excerpt'], 'meta_keywords' => $a['meta_kw'],
            'tags' => $a['tags'], 'author' => 'Editorial Team', 'status' => $a['status'],
            'trending' => $a['trending'], 'reading_time' => reading_time_from_html($a['body']),
            'published_at' => date('Y-m-d H:i:s'),
        ]);
        $inserted++;
    }
    return $inserted;
}
