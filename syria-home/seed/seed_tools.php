<?php
function seed_tools(PDO $pdo): void {
    $catId = function (PDO $pdo, string $slug): ?int {
        $s = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $s->execute([$slug]);
        $r = $s->fetchColumn();
        return $r ? (int)$r : null;
    };
    $img = $catId($pdo, 'image-tools');
    $txt = $catId($pdo, 'text-tools');
    $gen = $catId($pdo, 'generators');
    $conv = $catId($pdo, 'converters');
    $calc = $catId($pdo, 'calculators');

    $tools = [
        ['png_to_webp', 'PNG to WebP Converter', $img, 'fa-file-image',
            'Convert PNG, JPG, or WebP images in your browser — no upload, instant download, adjustable quality.',
            '<h2>Why convert to WebP?</h2><p>WebP images are typically 25–35% smaller than PNG or JPEG at the same visual quality, which means faster page loads and better Core Web Vitals scores. This tool converts entirely inside your browser using the Canvas API — your image is never sent to a server.</p><h2>How to use it</h2><p>Choose an image, pick your output format and quality, and download the result. Repeat as many times as you like, completely free.</p>',
            'Free PNG to WebP Converter Online', 'Convert PNG, JPG or WebP images online for free. Fast, private, in-browser conversion with adjustable quality — nothing is ever uploaded.', 'png to webp, image converter, webp converter, convert png online, free image converter'],
        ['image_compressor', 'Image Compressor', $img, 'fa-compress',
            'Shrink photo file size in seconds with an adjustable quality slider and optional resizing.',
            '<h2>Smaller images, faster sites</h2><p>Large, uncompressed photos are one of the most common reasons websites feel slow. This tool re-encodes your image as an optimized JPEG at a quality level you choose, and can resize oversized photos down to a sensible maximum width first.</p>',
            'Free Online Image Compressor', 'Compress and resize images online for free, right in your browser. Reduce file size without a noticeable quality loss.', 'image compressor, compress photo online, reduce image size, photo optimizer'],
        ['qr_code_generator', 'QR Code Generator', $gen, 'fa-qrcode',
            'Turn any link or text into a scannable QR code and download it as a PNG.',
            '<h2>Free QR codes, no sign-up</h2><p>Paste a URL, Wi-Fi credential string, or plain text, generate the code, and download it in seconds. Great for menus, business cards, posters and packaging.</p>',
            'Free QR Code Generator Online', 'Generate a free QR code from any text or URL and download it instantly as a high-resolution PNG.', 'qr code generator, free qr code, make qr code, qr code maker'],
        ['password_generator', 'Strong Password Generator', $gen, 'fa-key',
            'Generate cryptographically random passwords with full control over length and character sets.',
            '<h2>Built for real security</h2><p>This generator uses your browser\'s <code>crypto.getRandomValues</code> API — the same cryptographically secure randomness used in security-critical software — rather than a weaker <code>Math.random()</code>. Nothing is transmitted anywhere; every password is generated and shown locally.</p>',
            'Strong Random Password Generator', 'Generate a strong, cryptographically random password online for free. Customize length and character sets.', 'password generator, strong password, random password generator, secure password'],
        ['json_formatter', 'JSON Formatter & Validator', $txt, 'fa-code',
            'Beautify, minify and validate JSON instantly with clear error messages.',
            '<h2>Catch JSON errors instantly</h2><p>Paste any JSON to pretty-print it with 2-space indentation, minify it for production, or find out exactly what\'s wrong with invalid JSON — all client-side.</p>',
            'Free JSON Formatter & Validator', 'Format, validate and minify JSON online for free with instant error detection.', 'json formatter, json validator, json beautifier, json minifier, format json online'],
        ['base64_tool', 'Base64 Encoder / Decoder', $txt, 'fa-lock',
            'Encode text to Base64 or decode Base64 back to plain text, with full Unicode support.',
            '<h2>Where Base64 shows up</h2><p>Base64 encoding is everywhere — embedding images in CSS, encoding auth headers, email attachments. This tool handles Unicode correctly (accents, emoji, non-Latin scripts) unlike a plain <code>btoa()</code> call.</p>',
            'Free Base64 Encoder / Decoder', 'Encode or decode Base64 text online for free, with full Unicode support.', 'base64 encoder, base64 decoder, base64 converter, encode base64 online'],
        ['word_counter', 'Word & Character Counter', $txt, 'fa-align-left',
            'Live word, character, sentence and paragraph counts with an estimated reading time.',
            '<h2>Built for writers and editors</h2><p>Whether you\'re hitting a strict word count for an essay or checking a meta description\'s character limit, this counter updates live as you type — no button required.</p>',
            'Free Word & Character Counter', 'Count words, characters, sentences and paragraphs online for free, with live reading-time estimates.', 'word counter, character counter, text counter, reading time calculator'],
        ['case_converter', 'Text Case Converter', $txt, 'fa-font',
            'Switch between UPPERCASE, lowercase, Title Case, camelCase, snake_case and more in one click.',
            '<h2>Every case you need</h2><p>Useful for developers renaming variables, writers fixing accidental caps-lock, and anyone formatting headlines correctly.</p>',
            'Free Text Case Converter', 'Convert text between uppercase, lowercase, title case, camelCase, snake_case and kebab-case online for free.', 'case converter, text case converter, camel case converter, uppercase to lowercase'],
        ['lorem_ipsum', 'Lorem Ipsum Generator', $gen, 'fa-paragraph',
            'Generate placeholder paragraphs for mockups, wireframes and design layouts.',
            '<h2>Classic filler text, on demand</h2><p>Choose how many paragraphs you need and generate realistic-looking placeholder copy for design and development mockups.</p>',
            'Free Lorem Ipsum Generator', 'Generate Lorem Ipsum placeholder text online for free — pick how many paragraphs you need.', 'lorem ipsum generator, placeholder text, dummy text generator'],
        ['markdown_to_html', 'Markdown to HTML Converter', $conv, 'fa-file-code',
            'Convert Markdown to clean, ready-to-use HTML with a live preview.',
            '<h2>From Markdown to publishable HTML</h2><p>Supports headings, bold/italic, links, blockquotes, inline code and lists — paste your Markdown and copy the rendered HTML straight into your CMS.</p>',
            'Free Markdown to HTML Converter', 'Convert Markdown to HTML online for free with a live preview.', 'markdown to html, markdown converter, md to html'],
        ['csv_to_json', 'CSV to JSON Converter', $conv, 'fa-table',
            'Turn CSV data into clean, structured JSON using your first row as field names.',
            '<h2>Quick data wrangling</h2><p>Paste CSV data — from a spreadsheet export, for example — and get back a JSON array of objects, ready to drop into an API mock or script.</p>',
            'Free CSV to JSON Converter', 'Convert CSV to JSON online for free, right in your browser.', 'csv to json, csv converter, convert csv online'],
        ['hash_generator', 'Hash Generator (MD5/SHA)', $gen, 'fa-fingerprint',
            'Generate MD5, SHA-1, SHA-256 and SHA-512 hashes of any text instantly.',
            '<h2>Common use cases</h2><p>Verify file/text integrity, generate checksums for testing, or produce reference hashes for development — computed locally using your browser\'s built-in Web Crypto API (with a bundled MD5 implementation, since MD5 isn\'t in that API).</p>',
            'Free Hash Generator — MD5, SHA-1, SHA-256, SHA-512', 'Generate MD5, SHA-1, SHA-256 and SHA-512 hashes online for free.', 'hash generator, md5 generator, sha256 generator, checksum generator'],
        ['url_encoder', 'URL Encoder / Decoder', $conv, 'fa-link',
            'Percent-encode or decode URLs and query strings instantly.',
            '<h2>Get special characters right</h2><p>Spaces, ampersands and other reserved characters need encoding before they\'re safe inside a URL. This tool handles both directions.</p>',
            'Free URL Encoder / Decoder', 'Encode or decode URLs online for free.', 'url encoder, url decoder, percent encoding, encode url online'],
        ['color_converter', 'Color Converter & Palette Generator', $gen, 'fa-palette',
            'Convert between HEX, RGB and HSL, and generate a matching 5-color palette.',
            '<h2>For designers and developers</h2><p>Pick a color visually or type a HEX code, see it instantly in RGB and HSL, and generate a complementary/analogous palette with one click — click any swatch to copy its value.</p>',
            'Free Color Converter & Palette Generator', 'Convert HEX, RGB and HSL colors and generate matching palettes online for free.', 'color converter, hex to rgb, color palette generator, hex to hsl'],
        ['unit_converter', 'Unit Converter', $calc, 'fa-ruler',
            'Convert length, weight, volume and temperature units instantly.',
            '<h2>All the everyday conversions</h2><p>From meters to miles, kilograms to pounds, Celsius to Fahrenheit — pick a category and convert instantly as you type.</p>',
            'Free Unit Converter Online', 'Convert length, weight, volume and temperature units online for free.', 'unit converter, length converter, weight converter, temperature converter'],
        ['bmi_calculator', 'BMI Calculator', $calc, 'fa-weight-scale',
            'Calculate Body Mass Index from height and weight with an instant category result.',
            '<h2>A quick health snapshot</h2><p>BMI is a widely used (though imperfect) screening measure. Enter your height and weight to see your BMI and its standard category. This tool is informational only and not a substitute for medical advice.</p>',
            'Free BMI Calculator', 'Calculate your Body Mass Index (BMI) online for free and see your weight category instantly.', 'bmi calculator, body mass index calculator, calculate bmi'],
        ['age_calculator', 'Age Calculator', $calc, 'fa-cake-candles',
            'Calculate exact age in years, months and days, plus a countdown to your next birthday.',
            '<h2>More than just years</h2><p>Enter a date of birth to get your exact age breakdown, total days lived, and how many days remain until your next birthday.</p>',
            'Free Age Calculator Online', 'Calculate your exact age in years, months and days online for free.', 'age calculator, calculate age, date of birth calculator'],
        ['timestamp_converter', 'Unix Timestamp Converter', $conv, 'fa-clock',
            'Convert between Unix timestamps and human-readable dates instantly, in both directions.',
            '<h2>Essential for developers</h2><p>Paste a Unix timestamp to see the human date, or enter a date to get its timestamp — handy for debugging logs, APIs and databases.</p>',
            'Free Unix Timestamp Converter', 'Convert Unix timestamps to human-readable dates online for free, and back again.', 'unix timestamp converter, epoch converter, timestamp to date'],
        ['css_minifier', 'CSS Minifier', $conv, 'fa-file-invoice',
            'Strip comments and whitespace from CSS to shrink file size before shipping to production.',
            '<h2>Ship less CSS</h2><p>Paste your stylesheet and get back a minified version instantly, with a before/after size comparison — copy it or download it as a .css file.</p>',
            'Free CSS Minifier Online', 'Minify CSS online for free — remove comments and whitespace to shrink your stylesheet.', 'css minifier, minify css online, compress css'],
        ['text_to_speech', 'Text to Speech', $gen, 'fa-volume-high',
            'Have any text read aloud using your browser\'s built-in voices, with adjustable rate and pitch.',
            '<h2>Listen instead of reading</h2><p>Uses your browser\'s native speech synthesis — pick from the voices installed on your device, adjust the rate and pitch, and press play.</p>',
            'Free Text to Speech Online', 'Convert text to speech online for free using your browser\'s built-in voices.', 'text to speech, tts online, read aloud tool, free tts'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO tools (tool_key, name, slug, category_id, icon_class, short_description, full_description, meta_title, meta_description, meta_keywords, status)
        VALUES (?,?,?,?,?,?,?,?,?,?, 'published')");
    foreach ($tools as $t) {
        [$key, $name, $catId2, $icon, $short, $full, $mTitle, $mDesc, $mKw] = $t;
        $stmt->execute([$key, $name, slugify($name), $catId2, $icon, $short, $full, $mTitle, $mDesc, $mKw]);
    }
}
