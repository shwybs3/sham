<?php
/**
 * 10 digital products for the storefront.
 *
 * A deliberate copywriting note: nothing here promises that Google will
 * approve a buyer's AdSense application. That decision is Google's, is
 * made manually, and depends on the buyer's own domain, traffic and
 * content — so promising it would be a claim we could not honor and
 * would generate refund demands we could not satisfy. What we do
 * promise is what we actually control: the build meets AdSense's
 * technical and policy prerequisites, plus free updates, setup support
 * and a refund window.
 */
function seed_products(PDO $pdo): void {
    $P = [];

    $P[] = [
        'name' => 'Syria Home — Complete Articles & Tools CMS',
        'type' => 'PHP Script', 'icon' => 'fa-newspaper', 'art' => 'p1',
        'price' => 89.00, 'compare' => 149.00, 'badge' => 'Best Seller', 'featured' => 1,
        'tagline' => 'The exact script powering this site — articles, 20 web tools, AI admin panel, Google API integrations.',
        'short' => 'A complete, self-hosted publishing platform: content CMS, 20 client-side web tools, scoped AI writing assistant, and OAuth integrations for AdSense, Search Console, Analytics and Google Ads.',
        'features' => "Full article CMS (news, tutorials, comparisons, reviews)\n20 working client-side web tools included\nScoped AI assistant (Gemini + OpenRouter fallback)\nGoogle AdSense / Search Console / Analytics / Ads integration\nGUI installer — no manual SQL import\nPer-page SEO: meta, Open Graph, JSON-LD schema\nDynamic sitemap, robots.txt and ads.txt\nAdSense-ready structure and required legal pages\nFully responsive, mobile-first design",
        'includes' => "Complete PHP source code\nInstallation wizard\nDocumentation (README)\n12 months of free updates\nEmail setup support\n14-day refund window",
        'meta_desc' => 'Complete self-hosted PHP CMS for articles and free web tools, with an AI admin panel and Google AdSense, Search Console, Analytics and Ads integrations.',
        'meta_kw' => 'php cms script, adsense ready script, article website script, web tools script',
    ];

    $P[] = [
        'name' => 'Web Tools Suite — 20 Client-Side Tools',
        'type' => 'PHP + JS Script', 'icon' => 'fa-toolbox', 'art' => 'p2',
        'price' => 49.00, 'compare' => 79.00, 'badge' => 'Popular', 'featured' => 1,
        'tagline' => 'Drop-in collection of 20 browser-based tools that need no server processing.',
        'short' => 'Image converters, generators, calculators and text utilities — all running client-side, so hosting costs stay near zero no matter how much traffic you get.',
        'features' => "20 fully working tools out of the box\n100% client-side — no server load, no uploads\nEach tool gets its own SEO-optimized page\nJSON-LD SoftwareApplication schema per tool\nSimple registry to add your own tools\nAdmin panel to edit copy and SEO per tool\nWorks as a standalone site or as a section",
        'includes' => "Full source code\nTool registry documentation\n12 months of free updates\nEmail setup support\n14-day refund window",
        'meta_desc' => 'A suite of 20 free client-side web tools you can host yourself — converters, generators and calculators, each with its own SEO page.',
        'meta_kw' => 'web tools script, online tools website script, free tools php script',
    ];

    $P[] = [
        'name' => 'AdSense-Ready Blog Starter Template',
        'type' => 'HTML/CSS Template', 'icon' => 'fa-file-code', 'art' => 'p3',
        'price' => 29.00, 'compare' => 49.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'A clean, fast blog theme built to meet AdSense policy and technical prerequisites from day one.',
        'short' => 'A responsive blog template with the structure AdSense reviewers look for: clear navigation, real legal pages, fast load times and properly placed ad zones.',
        'features' => "Fast, lightweight, no heavy frameworks\nPre-built Privacy Policy, Terms and Editorial Policy pages\nProperly sized, non-intrusive ad zones\nCore Web Vitals friendly\nFull schema markup on every page\nDark and light theme support\nMobile-first responsive layout",
        'includes' => "HTML/CSS/JS source files\nSetup guide\n6 months of free updates\nEmail support\n14-day refund window",
        'meta_desc' => 'A fast, responsive blog template built to meet Google AdSense technical and policy prerequisites, with legal pages and ad zones included.',
        'meta_kw' => 'adsense ready template, blog template, adsense blog theme',
    ];

    $P[] = [
        'name' => 'SEO Starter Kit — Schema & Meta Toolkit',
        'type' => 'Code Toolkit', 'icon' => 'fa-magnifying-glass-chart', 'art' => 'p4',
        'price' => 24.00, 'compare' => 39.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'Drop-in PHP helpers for meta tags, Open Graph, JSON-LD schema and sitemaps.',
        'short' => 'Stop hand-writing meta tags. A small, dependency-free PHP toolkit that generates complete, correct SEO markup for any page type.',
        'features' => "One function call generates full head markup\nArticle, NewsArticle, Product, FAQ and Breadcrumb schema\nOpen Graph and Twitter Card generation\nDynamic XML sitemap builder\nrobots.txt generator\nNo dependencies, works with any PHP project",
        'includes' => "PHP source files\nUsage documentation with examples\n6 months of free updates\n14-day refund window",
        'meta_desc' => 'A dependency-free PHP toolkit that generates meta tags, Open Graph, JSON-LD schema and dynamic sitemaps for any page type.',
        'meta_kw' => 'php seo toolkit, json-ld schema generator, sitemap generator php',
    ];

    $P[] = [
        'name' => 'AI Content Assistant Module',
        'type' => 'PHP Module', 'icon' => 'fa-robot', 'art' => 'p5',
        'price' => 39.00, 'compare' => 69.00, 'badge' => 'New', 'featured' => 1,
        'tagline' => 'Add a safely-scoped AI writing assistant to any PHP admin panel.',
        'short' => 'A drop-in module that connects Gemini (with OpenRouter fallback) to your admin panel — scoped to a whitelist of safe content actions, never raw file or SQL access.',
        'features' => "Gemini API integration with automatic OpenRouter fallback\nWhitelisted actions only — no file writes, no raw SQL\nAI-generated content always saved as a draft for review\nFull activity log of every AI action taken\nGraceful handling of malformed AI responses\nProvider-agnostic router you can extend",
        'includes' => "PHP module source\nIntegration guide\n12 months of free updates\nEmail support\n14-day refund window",
        'meta_desc' => 'A drop-in PHP module adding a safely-scoped Gemini AI content assistant to your admin panel, with OpenRouter fallback and full action logging.',
        'meta_kw' => 'gemini php integration, ai content module, ai admin assistant php',
    ];

    $P[] = [
        'name' => 'Google APIs Integration Pack',
        'type' => 'PHP Module', 'icon' => 'fa-plug-circle-bolt', 'art' => 'p6',
        'price' => 34.00, 'compare' => 59.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'OAuth2 + ready-made clients for AdSense, Search Console, Analytics and Google Ads.',
        'short' => 'Skip the OAuth boilerplate. A working authorization-code flow with automatic token refresh, plus thin clients for four Google APIs.',
        'features' => "Complete OAuth2 authorization-code flow\nAutomatic access-token refresh\nAdSense Management API client\nSearch Console API client\nAnalytics Data (GA4) API client\nGoogle Ads API client with developer-token handling\nClear, actionable error messages instead of raw API failures",
        'includes' => "PHP source files\nGoogle Cloud setup walkthrough\n12 months of free updates\nEmail support\n14-day refund window",
        'meta_desc' => 'PHP OAuth2 integration pack with ready-made clients for the AdSense Management, Search Console, Analytics Data and Google Ads APIs.',
        'meta_kw' => 'google api php integration, adsense api php, search console api php, oauth2 php',
    ];

    $P[] = [
        'name' => 'Admin Dashboard UI Kit',
        'type' => 'HTML/CSS Template', 'icon' => 'fa-gauge-high', 'art' => 'p7',
        'price' => 27.00, 'compare' => 45.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'A clean, dependency-free admin panel UI you can drop onto any backend.',
        'short' => 'Sidebar navigation, stat cards, data tables, tabbed settings, forms and flash messages — all in plain CSS with no framework to fight.',
        'features' => "Zero dependencies — plain HTML and CSS\nCollapsible sidebar with grouped navigation\nStat cards, data tables and status badges\nTabbed settings layout\nStyled forms, buttons and flash messages\nFully responsive down to mobile\nEasy to recolor with CSS custom properties",
        'includes' => "HTML/CSS source\nComponent reference\n6 months of free updates\n14-day refund window",
        'meta_desc' => 'A dependency-free admin dashboard UI kit in plain HTML and CSS — sidebar, stat cards, tables, tabbed settings and forms.',
        'meta_kw' => 'admin dashboard template, admin ui kit, php admin panel template',
    ];

    $P[] = [
        'name' => 'GUI Installer Wizard Module',
        'type' => 'PHP Module', 'icon' => 'fa-wand-magic-sparkles', 'art' => 'p8',
        'price' => 19.00, 'compare' => 34.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'Give your script a professional one-click installer instead of a config file to edit.',
        'short' => 'A multi-step install wizard: requirement checks, database connection test, admin account creation, auto-seeding, and a self-lock so it can never be re-run.',
        'features' => "Multi-step wizard with progress indicator\nServer requirement checks before install\nLive database connection test with clear errors\nWrites the config file for the user automatically\nSelf-healing schema creation — no SQL import\nAdmin account creation with password validation\nSelf-locking after install for security",
        'includes' => "PHP module source\nIntegration guide\n6 months of free updates\n14-day refund window",
        'meta_desc' => 'A PHP install wizard module with requirement checks, database testing, auto schema creation and self-locking security.',
        'meta_kw' => 'php installer script, install wizard php, script installer module',
    ];

    $P[] = [
        'name' => 'Landing Page Pack — 6 Conversion Templates',
        'type' => 'HTML/CSS Template', 'icon' => 'fa-rocket', 'art' => 'p9',
        'price' => 32.00, 'compare' => 55.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'Six ready-to-edit landing pages for products, apps, services and lead capture.',
        'short' => 'Fast, responsive landing pages with hero sections, feature grids, pricing tables, testimonial blocks and FAQ accordions — all in clean HTML and CSS.',
        'features' => "6 distinct landing page layouts\nHero, features, pricing, testimonials and FAQ sections\nSVG illustrations included, no stock photo licensing\nNo frameworks — loads fast on any host\nEasy recoloring via CSS custom properties\nFully responsive and accessible markup",
        'includes' => "HTML/CSS/SVG source files\nCustomization guide\n6 months of free updates\n14-day refund window",
        'meta_desc' => 'Six fast, responsive landing page templates with hero sections, pricing tables, testimonials and FAQ blocks in clean HTML and CSS.',
        'meta_kw' => 'landing page template, html landing pages, conversion template pack',
    ];

    $P[] = [
        'name' => 'SVG Icon & Illustration Pack',
        'type' => 'Design Asset', 'icon' => 'fa-shapes', 'art' => 'p10',
        'price' => 15.00, 'compare' => 29.00, 'badge' => '', 'featured' => 0,
        'tagline' => 'Original gradient SVG illustrations and icons — royalty-free, no attribution required.',
        'short' => 'Hand-built SVG artwork designed for tech sites: hero illustrations, decorative patterns and category icons, all fully editable and infinitely scalable.',
        'features' => "Original artwork — no stock licensing risk\nPure SVG: infinitely scalable, tiny file sizes\nGradient-based, modern visual style\nEditable colors via CSS custom properties\nHero illustrations, patterns and category icons\nRoyalty-free commercial license, no attribution required",
        'includes' => "All SVG source files\nColor customization guide\nCommercial license\n14-day refund window",
        'meta_desc' => 'Original royalty-free SVG illustrations, gradient patterns and icons built for modern tech websites — fully editable and scalable.',
        'meta_kw' => 'svg illustration pack, gradient svg icons, royalty free svg',
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO products
        (name, slug, tagline, product_type, icon_class, art_key, price, compare_at_price, currency, badge,
         short_description, full_description, features, includes_list, meta_title, meta_description, meta_keywords,
         status, featured, sort_order)
        VALUES (:name,:slug,:tagline,:product_type,:icon_class,:art_key,:price,:compare_at_price,'USD',:badge,
         :short_description,:full_description,:features,:includes_list,:meta_title,:meta_description,:meta_keywords,
         'published',:featured,:sort_order)");

    $order = 0;
    foreach ($P as $p) {
        $full = '<h2>What you get</h2><p>' . htmlspecialchars($p['short'], ENT_QUOTES, 'UTF-8') . '</p>'
              . '<h2>Support &amp; guarantee</h2>'
              . '<p>Every purchase includes email support for installation and setup, free updates for the period listed above, '
              . 'and a 14-day refund window if the product does not work as described.</p>'
              . '<p><strong>An honest note on AdSense:</strong> products described as "AdSense-ready" are built to meet Google\'s '
              . 'technical and policy prerequisites — original content structure, required legal pages, fast load times, clear '
              . 'navigation and correctly placed ad zones. Approval itself is Google\'s decision and depends on your own domain, '
              . 'traffic and published content, so no seller can honestly guarantee it, and we do not.</p>';

        $stmt->execute([
            'name' => $p['name'],
            'slug' => slugify($p['name']),
            'tagline' => $p['tagline'],
            'product_type' => $p['type'],
            'icon_class' => $p['icon'],
            'art_key' => $p['art'],
            'price' => $p['price'],
            'compare_at_price' => $p['compare'],
            'badge' => $p['badge'],
            'short_description' => $p['short'],
            'full_description' => $full,
            'features' => $p['features'],
            'includes_list' => $p['includes'],
            'meta_title' => $p['name'] . ' — Download',
            'meta_description' => $p['meta_desc'],
            'meta_keywords' => $p['meta_kw'],
            'featured' => $p['featured'],
            'sort_order' => $order++,
        ]);
    }
}
