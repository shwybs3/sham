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
];
