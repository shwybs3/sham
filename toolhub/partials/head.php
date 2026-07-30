<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($page_title) ?></title>
<meta name="description" content="<?= h($page_desc) ?>">
<?php if ($page_keywords): ?><meta name="keywords" content="<?= h($page_keywords) ?>"><?php endif; ?>
<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">
<link rel="canonical" href="<?= h($page_canonical) ?>">
<meta name="theme-color" content="#0f172a">
<meta name="author" content="<?= h(SITE_NAME) ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:locale" content="ar_AR">
<meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
<meta property="og:title" content="<?= h($page_title) ?>">
<meta property="og:description" content="<?= h($page_desc) ?>">
<meta property="og:url" content="<?= h($page_canonical) ?>">
<meta property="og:image" content="<?= h(base_url('assets/img/og.png')) ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($page_title) ?>">
<meta name="twitter:description" content="<?= h($page_desc) ?>">
<meta name="twitter:image" content="<?= h(base_url('assets/img/og.png')) ?>">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

<!-- Styles -->
<link rel="stylesheet" href="/assets/css/style.css">

<!-- JSON-LD Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "<?= h($schema_type ?? 'WebSite') ?>",
  "name": "<?= h($page_title) ?>",
  "description": "<?= h($page_desc) ?>",
  "url": "<?= h($page_canonical) ?>",
  "inLanguage": "ar",
  "isPartOf": {
    "@type": "WebSite",
    "name": "<?= h(SITE_NAME) ?>",
    "url": "<?= h(SITE_URL) ?>"
  }
}
</script>
<?php if ($page === 'home'): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?= h(SITE_NAME) ?>",
  "url": "<?= h(SITE_URL) ?>",
  "description": "<?= h(SITE_TAGLINE) ?>",
  "inLanguage": "ar",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {"@type": "EntryPoint", "urlTemplate": "<?= h(SITE_URL) ?>/articles?q={search_term_string}"},
    "query-input": "required name=search_term_string"
  }
}
</script>
<?php endif; ?>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="manifest" href="/site.webmanifest">
</head>
