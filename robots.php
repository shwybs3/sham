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

# AJAX / internal API endpoints — no standalone crawl value
Disallow: /search-suggest.php
Disallow: /comment-form.php
Disallow: /track-view.php

# Search result pages with query params — noindex in HTML, but save crawl budget
Disallow: /?q=
Disallow: /*?q=

# Pagination duplicates — keep paginated index pages out; app detail pages are fine
# (Googlebot can still discover them via the sitemap and internal links)
Disallow: /?page=

# Legacy .php direct access — site uses clean URLs, .php are redirected
Disallow: /index.php
Disallow: /app.php
Disallow: /download.php

# ── AdsBot — Google requires a separate rule for ad crawlers ─────────────────
User-agent: AdsBot-Google
Allow: /
Disallow: /admin.php
Disallow: /install/

# ── Sitemap ───────────────────────────────────────────────────────────────────
Sitemap: <?= SITE_URL ?>/sitemap.xml
