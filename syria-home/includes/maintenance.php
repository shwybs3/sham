<?php
/**
 * Site-wide maintenance/"coming soon" gate. Off by default. Newly
 * provisioned subdomain sites (see SiteProvisioner) start with it ON,
 * so a fresh subsite shows a friendly placeholder — with a link back
 * to the main site — instead of half-finished content, until its
 * owner turns it off in Settings > General.
 */
function sh_check_maintenance(): void {
    if (!(int)setting('maintenance_mode', 0)) return;
    if (is_admin_logged_in()) return; // admins can always preview the live site

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $exempt = ['/admin', '/install', '/payment-webhook.php', '/payment-status.php', '/checkout.php',
        '/sitemap.php', '/robots.php', '/ads.php', '/assets/'];
    foreach ($exempt as $path) {
        if (str_contains($uri, $path)) return;
    }

    http_response_code(503);
    header('Retry-After: 3600');
    require ROOT_PATH . '/maintenance-page.php';
    exit;
}
