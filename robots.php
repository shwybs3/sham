<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
?>
# ── yassota robots.txt ──────────────────────────────────────────────────────
# Generated dynamically so the Sitemap URL always matches SITE_URL.
# Last updated: <?= date('Y-m-d') ?>


# ── Global rules ─────────────────────────────────────────────────────────────
User-agent: *
Allow: /

# Admin & installer — no crawler access
Disallow: /admin.php
Disallow: /install/
Disallow: /config.php

# Uploaded cache fragments — internal only
Disallow: /uploads/.cache/

# Internal AJAX / tracking endpoints — no standalone crawl value
# (These are XHR endpoints called by JS, not content pages)
Disallow: /search-suggest.php
Disallow: /comment-form.php
Disallow: /track-view.php

# Search result pages with query params — noindex in HTML, but save crawl budget
Disallow: /?q=
Disallow: /*?q=

# Pagination duplicates — content pages are fine, just limit paginated index
Disallow: /?page=

# Direct PHP file access — site uses clean URLs via .htaccess rewrites.
# Note: /download.php is intentionally NOT blocked so Googlebot can follow
# download clean URLs (/app-slug/download) which rewrite to it.
Disallow: /index.php
Disallow: /app.php

# ── AdsBot — Google requires a separate rule for ad crawlers ─────────────────
User-agent: AdsBot-Google
Allow: /
Disallow: /admin.php
Disallow: /install/

# ── Sitemap ───────────────────────────────────────────────────────────────────
Sitemap: <?= SITE_URL ?>/sitemap.xml
