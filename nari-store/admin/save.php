<?php
define('NARI_ADMIN', true);
require_once __DIR__ . '/auth.php';
nari_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['csrf'] ?? '';
if (!nari_csrf_check($token)) {
    header('Location: index.php?error=csrf');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save_settings') {
    $settings = nari_read_json(SETTINGS_FILE);

    $settings['site_name']            = trim($_POST['site_name'] ?? ($settings['site_name'] ?? 'ناري ستور'));
    $settings['tagline']              = trim($_POST['tagline'] ?? ($settings['tagline'] ?? ''));
    $settings['whatsapp_number']      = preg_replace('/\D/', '', $_POST['whatsapp_number'] ?? ($settings['whatsapp_number'] ?? ''));
    $settings['whatsapp_display']     = trim($_POST['whatsapp_display'] ?? ($settings['whatsapp_display'] ?? ''));
    $settings['announcement_enabled'] = isset($_POST['announcement_enabled']);
    $settings['announcement_text']    = trim($_POST['announcement_text'] ?? ($settings['announcement_text'] ?? ''));
    $settings['maintenance_mode']     = isset($_POST['maintenance_mode']);
    $settings['maintenance_message']  = trim($_POST['maintenance_message'] ?? ($settings['maintenance_message'] ?? ''));
    $settings['instagram_url']        = trim($_POST['instagram_url'] ?? ($settings['instagram_url'] ?? ''));
    $settings['tiktok_url']           = trim($_POST['tiktok_url'] ?? ($settings['tiktok_url'] ?? ''));
    $settings['facebook_url']         = trim($_POST['facebook_url'] ?? ($settings['facebook_url'] ?? ''));
    $settings['ad_header_code']       = trim($_POST['ad_header_code'] ?? ($settings['ad_header_code'] ?? ''));

    if ($settings['whatsapp_number'] === '') {
        header('Location: index.php?error=phone');
        exit;
    }

    nari_write_json(SETTINGS_FILE, $settings);
    header('Location: index.php?saved=settings');
    exit;
}

if ($action === 'save_ai') {
    $settings = nari_read_json(SETTINGS_FILE);

    $settings['ai_chat_enabled']         = isset($_POST['ai_chat_enabled']);
    $settings['openrouter_api_key']      = trim($_POST['openrouter_api_key'] ?? ($settings['openrouter_api_key'] ?? ''));
    $settings['openrouter_model']        = trim($_POST['openrouter_model'] ?? ($settings['openrouter_model'] ?? ''));
    $settings['ai_system_prompt']        = trim($_POST['ai_system_prompt'] ?? ($settings['ai_system_prompt'] ?? ''));
    $turns = (int)($_POST['ai_max_turns_per_session'] ?? 12);
    $settings['ai_max_turns_per_session'] = $turns > 0 ? $turns : 12;

    nari_write_json(SETTINGS_FILE, $settings);
    header('Location: index.php?saved=ai');
    exit;
}

if ($action === 'save_services') {
    $services = nari_read_json(SERVICES_FILE);
    $posted = $_POST['services'] ?? [];

    foreach ($services as &$svc) {
        $id = $svc['id'] ?? '';
        $svc['active'] = isset($posted[$id]['active']);
        $svc['note'] = isset($posted[$id]['note']) ? trim((string)$posted[$id]['note']) : ($svc['note'] ?? '');
    }
    unset($svc);

    nari_write_json(SERVICES_FILE, $services);
    header('Location: index.php?saved=services');
    exit;
}

if ($action === 'save_products') {
    $products = nari_read_json(PRODUCTS_FILE);
    $posted = $_POST['products'] ?? [];

    foreach ($products as &$p) {
        $id = $p['id'] ?? '';
        if (!isset($posted[$id])) {
            continue;
        }
        $price = preg_replace('/\D/', '', $posted[$id]['price_syp'] ?? '');
        $p['price_syp'] = $price === '' ? 0 : (int)$price;
        $p['active'] = isset($posted[$id]['active']);
        $p['note'] = trim((string)($posted[$id]['note'] ?? ($p['note'] ?? '')));
    }
    unset($p);

    nari_write_json(PRODUCTS_FILE, $products);
    header('Location: products.php?saved=1');
    exit;
}

function nari_valid_slug(string $s): bool {
    return (bool) preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $s);
}

if ($action === 'save_page') {
    $pages = nari_read_json(PAGES_FILE);
    $originalSlug = trim((string)($_POST['original_slug'] ?? ''));
    $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
    $title = trim((string)($_POST['title'] ?? ''));

    if ($title === '' || !nari_valid_slug($slug)) {
        header('Location: content.php?type=page&edit=' . rawurlencode($originalSlug) . '&err=invalid');
        exit;
    }

    foreach ($pages as $p) {
        if (($p['slug'] ?? '') === $slug && $slug !== $originalSlug) {
            header('Location: content.php?type=page&edit=' . rawurlencode($originalSlug) . '&err=exists');
            exit;
        }
    }

    $entry = [
        'slug' => $slug,
        'title' => $title,
        'meta_description' => trim((string)($_POST['meta_description'] ?? '')),
        'keywords' => trim((string)($_POST['keywords'] ?? '')),
        'content_html' => (string)($_POST['content_html'] ?? ''),
        'published' => isset($_POST['published']),
        'updated_at' => date('Y-m-d'),
    ];

    $found = false;
    foreach ($pages as &$p) {
        if (($p['slug'] ?? '') === $originalSlug) {
            $p = $entry;
            $found = true;
            break;
        }
    }
    unset($p);
    if (!$found) {
        $pages[] = $entry;
    }

    nari_write_json(PAGES_FILE, $pages);
    header('Location: content.php?type=page&edit=' . rawurlencode($slug) . '&msg=saved');
    exit;
}

if ($action === 'delete_page') {
    $pages = nari_read_json(PAGES_FILE);
    $target = (string)($_POST['target_slug'] ?? '');
    $pages = array_values(array_filter($pages, fn($p) => ($p['slug'] ?? '') !== $target));
    nari_write_json(PAGES_FILE, $pages);
    header('Location: content.php?type=page&msg=deleted');
    exit;
}

if ($action === 'save_article') {
    $articles = nari_read_json(ARTICLES_FILE);
    $originalSlug = trim((string)($_POST['original_slug'] ?? ''));
    $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
    $title = trim((string)($_POST['title'] ?? ''));

    if ($title === '' || !nari_valid_slug($slug)) {
        header('Location: content.php?type=article&edit=' . rawurlencode($originalSlug) . '&err=invalid');
        exit;
    }

    foreach ($articles as $a) {
        if (($a['slug'] ?? '') === $slug && $slug !== $originalSlug) {
            header('Location: content.php?type=article&edit=' . rawurlencode($originalSlug) . '&err=exists');
            exit;
        }
    }

    $existingPublishedAt = '';
    foreach ($articles as $a) {
        if (($a['slug'] ?? '') === $originalSlug) {
            $existingPublishedAt = $a['published_at'] ?? '';
            break;
        }
    }

    $entry = [
        'slug' => $slug,
        'title' => $title,
        'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
        'meta_description' => trim((string)($_POST['meta_description'] ?? '')),
        'keywords' => trim((string)($_POST['keywords'] ?? '')),
        'category' => trim((string)($_POST['category'] ?? '')) ?: 'مقال',
        'content_html' => (string)($_POST['content_html'] ?? ''),
        'published' => isset($_POST['published']),
        'published_at' => $existingPublishedAt ?: date('Y-m-d'),
        'updated_at' => date('Y-m-d'),
    ];

    $found = false;
    foreach ($articles as &$a) {
        if (($a['slug'] ?? '') === $originalSlug) {
            $a = $entry;
            $found = true;
            break;
        }
    }
    unset($a);
    if (!$found) {
        $articles[] = $entry;
    }

    nari_write_json(ARTICLES_FILE, $articles);
    header('Location: content.php?type=article&edit=' . rawurlencode($slug) . '&msg=saved');
    exit;
}

if ($action === 'delete_article') {
    $articles = nari_read_json(ARTICLES_FILE);
    $target = (string)($_POST['target_slug'] ?? '');
    $articles = array_values(array_filter($articles, fn($a) => ($a['slug'] ?? '') !== $target));
    nari_write_json(ARTICLES_FILE, $articles);
    header('Location: content.php?type=article&msg=deleted');
    exit;
}

if ($action === 'save_site') {
    $settings = nari_read_json(SETTINGS_FILE);
    $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    if ($siteUrl !== '' && !preg_match('#^https?://#i', $siteUrl)) {
        $siteUrl = 'https://' . $siteUrl;
    }
    $settings['site_url'] = $siteUrl !== '' ? $siteUrl : ($settings['site_url'] ?? 'https://yassota.com');
    nari_write_json(SETTINGS_FILE, $settings);
    header('Location: site-settings.php?saved=site');
    exit;
}

if ($action === 'save_robots') {
    $content = (string)($_POST['robots_content'] ?? '');
    if (trim($content) === '') {
        header('Location: site-settings.php?error=empty_robots');
        exit;
    }
    file_put_contents(__DIR__ . '/../robots.txt', $content);
    header('Location: site-settings.php?saved=robots');
    exit;
}

if ($action === 'regenerate_sitemap') {
    $settings = nari_read_json(SETTINGS_FILE);
    $siteUrl = rtrim($settings['site_url'] ?? 'https://yassota.com', '/');
    $root = realpath(__DIR__ . '/..');

    $urls = [];
    $htmlFiles = glob($root . '/*.html');
    natsort($htmlFiles);
    foreach ($htmlFiles as $file) {
        $name = basename($file);
        if ($name === '404.html') continue;
        $isHome = $name === 'index.html';
        $urls[] = [
            'loc' => $siteUrl . '/' . ($isHome ? '' : $name),
            'changefreq' => $isHome ? 'weekly' : 'monthly',
            'priority' => $isHome ? '1.0' : '0.6',
        ];
    }

    $pages = nari_read_json(PAGES_FILE);
    foreach ($pages as $p) {
        if (empty($p['published']) || empty($p['slug'])) continue;
        $urls[] = ['loc' => $siteUrl . '/p/' . $p['slug'], 'changefreq' => 'monthly', 'priority' => '0.5'];
    }

    $articles = nari_read_json(ARTICLES_FILE);
    if (!empty($articles)) {
        $urls[] = ['loc' => $siteUrl . '/blog', 'changefreq' => 'weekly', 'priority' => '0.6'];
    }
    foreach ($articles as $a) {
        if (empty($a['published']) || empty($a['slug'])) continue;
        $urls[] = ['loc' => $siteUrl . '/article/' . $a['slug'], 'changefreq' => 'monthly', 'priority' => '0.6'];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
        $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
        $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>' . "\n";

    file_put_contents($root . '/sitemap.xml', $xml);
    header('Location: site-settings.php?saved=sitemap&count=' . count($urls));
    exit;
}

if ($action === 'change_password') {
    // ملاحظة: هذا الإجراء يعرض لك القيمة الجاهزة لوضعها يدوياً في config.php
    // لأن استضافات مجانية كثيرة تمنع الكتابة على ملفات .php من داخل السكربت نفسه لأسباب أمنية.
    $new = (string)($_POST['new_password'] ?? '');
    if (strlen($new) < 8) {
        header('Location: index.php?error=weak_password');
        exit;
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $_SESSION['nari_generated_hash'] = $hash;
    header('Location: index.php?saved=password_hash');
    exit;
}

header('Location: index.php');
exit;
