<?php
/**
 * Canonical list of the 20 tools this site ships. `key` MUST match a
 * registered handler in assets/js/tools.js (SHTools.register). Used by
 * the admin tools form (dropdown of valid engines) and the seed script.
 */
const TOOL_REGISTRY = [
    'png_to_webp'        => ['name' => 'Image Format Converter (PNG/JPG/WebP)', 'icon' => 'fa-file-image'],
    'image_compressor'   => ['name' => 'Image Compressor', 'icon' => 'fa-compress'],
    'qr_code_generator'  => ['name' => 'QR Code Generator', 'icon' => 'fa-qrcode'],
    'password_generator' => ['name' => 'Strong Password Generator', 'icon' => 'fa-key'],
    'json_formatter'     => ['name' => 'JSON Formatter & Validator', 'icon' => 'fa-code'],
    'base64_tool'        => ['name' => 'Base64 Encoder / Decoder', 'icon' => 'fa-lock'],
    'word_counter'       => ['name' => 'Word & Character Counter', 'icon' => 'fa-align-left'],
    'case_converter'     => ['name' => 'Text Case Converter', 'icon' => 'fa-font'],
    'lorem_ipsum'        => ['name' => 'Lorem Ipsum Generator', 'icon' => 'fa-paragraph'],
    'markdown_to_html'   => ['name' => 'Markdown to HTML Converter', 'icon' => 'fa-file-code'],
    'csv_to_json'        => ['name' => 'CSV to JSON Converter', 'icon' => 'fa-table'],
    'hash_generator'     => ['name' => 'Hash Generator (MD5/SHA-1/SHA-256)', 'icon' => 'fa-fingerprint'],
    'url_encoder'        => ['name' => 'URL Encoder / Decoder', 'icon' => 'fa-link'],
    'color_converter'    => ['name' => 'Color Converter & Palette Generator', 'icon' => 'fa-palette'],
    'unit_converter'     => ['name' => 'Unit Converter', 'icon' => 'fa-ruler'],
    'bmi_calculator'     => ['name' => 'BMI Calculator', 'icon' => 'fa-weight-scale'],
    'age_calculator'     => ['name' => 'Age Calculator', 'icon' => 'fa-cake-candles'],
    'timestamp_converter'=> ['name' => 'Unix Timestamp Converter', 'icon' => 'fa-clock'],
    'css_minifier'       => ['name' => 'CSS Minifier', 'icon' => 'fa-file-invoice'],
    'text_to_speech'     => ['name' => 'Text to Speech', 'icon' => 'fa-volume-high'],

    /* ── "Pro" set (assets/js/tools-pro.js) ──
       Each one delivers the core function of a product that is normally
       sold by subscription or per-use. Every implementation is original
       and runs locally; `replaces` is shown on the public page so visitors
       know which paid workflow the free tool covers. */
    'bg_remover'         => ['name' => 'Background Remover', 'icon' => 'fa-scissors', 'replaces' => 'Paid per-image background removal services'],
    'image_resizer'      => ['name' => 'Bulk Image Resizer', 'icon' => 'fa-expand', 'replaces' => 'Paid bulk image resizing suites'],
    'watermark'          => ['name' => 'Watermark Adder', 'icon' => 'fa-stamp', 'replaces' => 'Watermarking apps with paid tiers'],
    'exif_viewer'        => ['name' => 'EXIF Viewer & Remover', 'icon' => 'fa-camera-retro', 'replaces' => 'Paid metadata scrubbing utilities'],
    'favicon_generator'  => ['name' => 'Favicon Generator', 'icon' => 'fa-icons', 'replaces' => 'Favicon services with paid downloads'],
    'regex_tester'       => ['name' => 'Regex Tester & Debugger', 'icon' => 'fa-asterisk', 'replaces' => 'Regex playgrounds with paid plans'],
    'diff_checker'       => ['name' => 'Text Diff Checker', 'icon' => 'fa-code-compare', 'replaces' => 'Subscription diff tools'],
    'jwt_decoder'        => ['name' => 'JWT Decoder', 'icon' => 'fa-user-lock', 'replaces' => 'Paid API debugging suites'],
    'sql_formatter'      => ['name' => 'SQL Formatter', 'icon' => 'fa-database', 'replaces' => 'Paid SQL IDE formatting'],
    'cron_builder'       => ['name' => 'Cron Expression Builder', 'icon' => 'fa-calendar-check', 'replaces' => 'Paid scheduling dashboards'],
    'uuid_generator'     => ['name' => 'UUID & ID Generator', 'icon' => 'fa-hashtag', 'replaces' => 'Paid developer toolbelts'],
    'serp_preview'       => ['name' => 'Google SERP Preview', 'icon' => 'fa-magnifying-glass-location', 'replaces' => 'SEO suites charging monthly for SERP preview'],
    'readability'        => ['name' => 'Readability Analyzer', 'icon' => 'fa-book-open-reader', 'replaces' => 'Paid writing-clarity editors'],
    'keyword_density'    => ['name' => 'Keyword Density Analyzer', 'icon' => 'fa-chart-pie', 'replaces' => 'SEO platforms gating content analysis'],
    'gradient_generator' => ['name' => 'CSS Gradient Generator', 'icon' => 'fa-fill-drip', 'replaces' => 'Design tools with paid export'],
    'palette_from_image' => ['name' => 'Colour Palette Extractor', 'icon' => 'fa-eye-dropper', 'replaces' => 'Palette apps with paid tiers'],
    'image_to_pdf'       => ['name' => 'Images to PDF Converter', 'icon' => 'fa-file-pdf', 'replaces' => 'PDF suites sold by subscription'],
    'speech_to_text'     => ['name' => 'Speech to Text', 'icon' => 'fa-microphone-lines', 'replaces' => 'Transcription services billed per minute'],
    'robots_generator'   => ['name' => 'Robots.txt Generator', 'icon' => 'fa-robot', 'replaces' => 'SEO plugins gating robots editing'],
    'meta_generator'     => ['name' => 'Meta Tag Generator', 'icon' => 'fa-tags', 'replaces' => 'Premium SEO plugin meta editors'],

    /* ── "Pro" set, part 2 (assets/js/tools-pro2.js) ── */
    'contrast_checker'        => ['name' => 'Contrast Checker (WCAG)', 'icon' => 'fa-circle-half-stroke', 'replaces' => 'Paid accessibility auditing tools'],
    'percentage_calculator'   => ['name' => 'Percentage Calculator', 'icon' => 'fa-percent', 'replaces' => 'Financial calculator apps with paid tiers'],
    'loan_calculator'         => ['name' => 'Loan & Mortgage Calculator', 'icon' => 'fa-hand-holding-dollar', 'replaces' => 'Paid mortgage/loan calculator tools'],
    'random_number_generator' => ['name' => 'Random Number & PIN Generator', 'icon' => 'fa-dice', 'replaces' => 'Paid randomization/lottery tools'],
    'timezone_converter'      => ['name' => 'Timezone Converter', 'icon' => 'fa-earth-americas', 'replaces' => 'Scheduling apps with paid timezone features'],
    'slug_generator'          => ['name' => 'URL Slug Generator', 'icon' => 'fa-link', 'replaces' => 'CMS plugins charging for slug/SEO utilities'],
    'html_entity_tool'        => ['name' => 'HTML Entity Encoder / Decoder', 'icon' => 'fa-code', 'replaces' => 'Paid developer utility suites'],
    'invoice_generator'       => ['name' => 'Invoice Generator', 'icon' => 'fa-file-invoice-dollar', 'replaces' => 'Subscription invoicing software'],
];
