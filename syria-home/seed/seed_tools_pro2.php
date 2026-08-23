<?php
/**
 * Seeds a second "pro" tool batch — eight more tools whose core function is
 * normally sold as a paid product or subscription, reimplemented from
 * scratch and given away. Same pattern as seed_pro_tools.php: safe to
 * re-run, INSERT IGNORE on the unique slug.
 */
function seed_tools_pro2(PDO $pdo): void {
    $catId = function (string $slug) use ($pdo): ?int {
        $s = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $s->execute([$slug]);
        $r = $s->fetchColumn();
        return $r ? (int)$r : null;
    };
    $gen  = $catId('generators');
    $conv = $catId('converters');
    $calc = $catId('calculators');
    $dev  = $catId('developer-tools');

    /* [tool_key, name, category, icon, replaces, short, full, metaTitle, metaDesc, metaKeywords] */
    $tools = [
        ['contrast_checker', 'Contrast Checker (WCAG)', $dev ?: $gen, 'fa-circle-half-stroke',
            'Paid accessibility auditing tools',
            'Check any two colours against WCAG contrast requirements and see instantly whether they pass AA or AAA.',
            '<h2>Readable text is an accessibility requirement, not a nice-to-have</h2><p>WCAG defines minimum contrast ratios between text and its background so that people with low vision can actually read your site. Most paid accessibility auditors charge a monthly fee just to run this one check — this tool does it instantly, for free, with no limit on how many colour pairs you test.</p>'
            . '<h3>How the ratio is calculated</h3><p>The contrast ratio is derived from the relative luminance of both colours using the same formula the WCAG specification defines, expressed as a number between 1:1 (no contrast at all) and 21:1 (pure black on pure white). Nothing is approximated — the same math a professional audit tool uses runs directly in your browser.</p>'
            . '<h3>What AA and AAA actually mean</h3><p>AA is the baseline most legal accessibility standards require: 4.5:1 for normal text, 3:1 for large text (18pt or 14pt bold and up). AAA is the stricter, recommended-but-not-mandatory tier: 7:1 for normal text, 4.5:1 for large text. Government and enterprise sites are frequently required to hit AA at minimum.</p>'
            . '<h3>Fixing a failing pair</h3><p>When a pair fails, the fastest fix is usually darkening the darker colour or lightening the lighter one rather than changing hue — contrast is about luminance, not colour name. Two colours that look very different to the eye can still fail if their brightness is too close.</p>',
            'Free Contrast Checker — WCAG AA/AAA Compliance',
            'Check text and background colour contrast against WCAG AA and AAA requirements, free and instant. No sign-up, unlimited checks.',
            'contrast checker, wcag contrast ratio, accessibility checker, color contrast tool, aa aaa compliance'],

        ['percentage_calculator', 'Percentage Calculator', $calc ?: $gen, 'fa-percent',
            'Financial calculator apps with paid tiers',
            'Three common percentage calculations in one tool: X% of Y, what percent X is of Y, and percent change between two numbers.',
            '<h2>The three percentage questions people actually ask</h2><p>Most percentage confusion comes from mixing up which of three related questions you are actually trying to answer. This tool separates them clearly so you always get the right formula without having to remember it.</p>'
            . '<h3>"X% of Y" — finding a portion</h3><p>Used for discounts, tips and splitting a total: what is 20% of $150? This is the most common use and the one most people mean when they say "calculate a percentage."</p>'
            . '<h3>"X is what % of Y" — finding a proportion</h3><p>Used when you know two numbers and want the relationship between them: if you scored 42 out of 50, what percentage is that? This is the calculation behind grades, conversion rates and completion rates.</p>'
            . '<h3>"% change from X to Y" — measuring growth or decline</h3><p>Used for anything that moves over time: price changes, revenue growth, weight change. A positive result is an increase, a negative result is a decrease, and the formula is anchored to the original value, not the new one — a common source of manual calculation errors.</p>',
            'Free Percentage Calculator — % of, % Change, % Of Total',
            'Calculate X% of Y, what percent one number is of another, or percent increase/decrease between two numbers. Free, instant, no sign-up.',
            'percentage calculator, percent change calculator, percent of number, free calculator online'],

        ['loan_calculator', 'Loan & Mortgage Calculator', $calc ?: $gen, 'fa-hand-holding-dollar',
            'Paid mortgage/loan calculator tools',
            'Enter a loan amount, interest rate and term to see your monthly payment, total paid and total interest.',
            '<h2>Know your real monthly payment before you sign</h2><p>Loan and mortgage calculators are often locked behind a bank\'s marketing funnel or a paid financial-planning app. This one runs the standard amortization formula directly in your browser with no data sent anywhere.</p>'
            . '<h3>How the monthly payment is calculated</h3><p>The tool uses the standard fixed-rate amortization formula, which accounts for the loan principal, the monthly interest rate (your annual rate divided by 12), and the total number of monthly payments over the loan term. This is the same formula banks use to calculate a standard fixed-rate loan or mortgage payment.</p>'
            . '<h3>Why total interest often surprises people</h3><p>Over a long term, the total interest paid can approach or even exceed the original loan amount, especially at higher rates. Seeing that number up front — rather than only the monthly payment — is the single most useful thing this calculator does for a borrowing decision.</p>'
            . '<h3>What this does not include</h3><p>This covers principal and interest only. Real mortgage payments often include property tax, insurance and, for smaller down payments, mortgage insurance — factor those in separately when budgeting for an actual home purchase.</p>',
            'Free Loan & Mortgage Calculator — Monthly Payment',
            'Calculate your monthly loan or mortgage payment, total interest, and total amount paid. Free, instant, works for any loan amount, rate or term.',
            'loan calculator, mortgage calculator, monthly payment calculator, amortization calculator free'],

        ['random_number_generator', 'Random Number & PIN Generator', $gen, 'fa-dice',
            'Paid randomization/lottery tools',
            'Generate one or many random numbers in any range, with an option to guarantee no duplicates — for raffles, PINs, sampling or games.',
            '<h2>True randomness, not a predictable pattern</h2><p>This generator uses your browser\'s cryptographically secure random number source (the same API used for generating encryption keys) rather than a weaker pseudo-random function, so results are not predictable or biased toward any part of the range.</p>'
            . '<h3>Common uses</h3><p>Picking a raffle or giveaway winner from a list of ticket numbers, generating a PIN or verification code, drawing a random sample for testing or research, or just settling an argument about whose turn it is.</p>'
            . '<h3>The "no duplicates" option</h3><p>When generating several numbers at once — for a lottery draw or a unique sample — enabling this option removes each number from the pool once picked, so every result in the set is guaranteed unique. It automatically warns you if the range is too small to produce the count you asked for.</p>'
            . '<h3>Why this matters for fairness</h3><p>A poorly implemented random number generator can be subtly biased toward certain values, which matters if the result needs to be provably fair — a giveaway winner, for instance. This tool avoids that class of bug entirely by relying on the browser\'s vetted cryptographic API instead of a hand-rolled formula.</p>',
            'Free Random Number Generator — Unique Numbers & PINs',
            'Generate random numbers in any range, one or many at once, with an optional no-duplicates mode. Free, cryptographically random, instant.',
            'random number generator, random number picker, pin generator, lottery number generator free'],

        ['timezone_converter', 'Timezone Converter', $conv ?: $gen, 'fa-earth-americas',
            'Scheduling apps with paid timezone features',
            'Convert a date and time between any two timezones — useful for scheduling calls, meetings and launches across regions.',
            '<h2>Stop doing timezone math by hand</h2><p>Manually adding or subtracting hours between timezones is a common source of missed meetings, especially around daylight saving transitions when the offset temporarily shifts. This tool reads your browser\'s built-in, regularly updated timezone database, so it is always current with the latest rules — including daylight saving changes — for every region it lists.</p>'
            . '<h3>Why this is more reliable than a fixed offset chart</h3><p>A static "UTC+3" style chart breaks the moment daylight saving starts or ends in either the source or destination region, and different countries change their clocks on different dates. Because this tool asks your browser for the real rule set rather than using a hardcoded offset, that problem does not come up.</p>'
            . '<h3>Practical use</h3><p>Pick the date and time as it would appear in the origin timezone, choose the origin and destination zones, and the converted local time and date are shown immediately. Useful for scheduling a call with a remote team, planning a livestream launch, or simply knowing what time it is somewhere else right now.</p>',
            'Free Timezone Converter — Convert Time Between Cities',
            'Convert any date and time between timezones for free, accounting for daylight saving automatically. No sign-up, instant results.',
            'timezone converter, time zone calculator, convert time between timezones, meeting planner free'],

        ['slug_generator', 'URL Slug Generator', $conv ?: $gen, 'fa-link',
            'CMS plugins charging for slug/SEO utilities',
            'Turn any title into a clean, URL-safe slug — lowercase, hyphenated, and trimmed to a search-engine-friendly length.',
            '<h2>Clean URLs are still an SEO fundamental</h2><p>A URL like <code>/blog/2024/p?id=8821</code> tells a visitor and a search engine nothing about the page. A slug like <code>/blog/best-budget-laptops-2024</code> does — and short, descriptive URLs are consistently associated with better click-through rates in search results.</p>'
            . '<h3>What counts as a "clean" slug</h3><p>Lowercase only, words separated by a single hyphen (the convention nearly every search engine and CMS expects), no special characters, accents or punctuation, and no unnecessary stop-word bloat. This tool applies all of that automatically as you type.</p>'
            . '<h3>Why length matters</h3><p>Search results typically truncate very long URLs, and a slug stuffed with every possible keyword reads as spam rather than as a helpful label. Keeping a slug under roughly 60 characters, focused on the two or three words that actually matter, tends to perform better than a long one.</p>'
            . '<h3>Hyphens vs underscores</h3><p>Search engines treat a hyphen as a word separator but generally do not treat an underscore the same way, meaning "best-budget-laptops" is read as three separate words while "best_budget_laptops" may be read as one long token. Hyphens are the safer default for this reason.</p>',
            'Free URL Slug Generator — Clean SEO-Friendly URLs',
            'Convert any title into a clean, lowercase, hyphenated URL slug. Free, instant, adjustable max length — no sign-up required.',
            'slug generator, url slug maker, seo friendly url, slugify text online free'],

        ['html_entity_tool', 'HTML Entity Encoder / Decoder', $dev ?: $gen, 'fa-code',
            'Paid developer utility suites',
            'Encode special characters into HTML entities, or decode entities back into readable text — for safely embedding text in HTML.',
            '<h2>Why raw characters break HTML</h2><p>Characters like <code>&lt;</code>, <code>&gt;</code> and <code>&amp;</code> have special meaning in HTML, so pasting text containing them directly into a page can break its structure or, worse, open the door to cross-site scripting if the text comes from user input. Encoding those characters into their entity form makes them display correctly as plain text instead of being interpreted as markup.</p>'
            . '<h3>What encoding actually does</h3><p>Encoding converts characters such as <code>&lt;</code>, <code>&gt;</code>, <code>&amp;</code> and quotation marks into their safe entity equivalents, so a browser renders them literally instead of treating them as the start of a tag or attribute.</p>'
            . '<h3>When you need decoding instead</h3><p>The reverse operation matters when you are reading data that already contains encoded entities — scraped content, an RSS feed, or an API response — and need the plain, human-readable text back out for display or further processing.</p>'
            . '<h3>A developer utility, not a security guarantee</h3><p>Entity encoding is one layer of defence against injection issues, useful for safely displaying user-supplied text inside HTML, but it is not a substitute for proper input validation and output escaping in your actual application code.</p>',
            'Free HTML Entity Encoder & Decoder',
            'Encode text into safe HTML entities or decode entities back to plain text, free and instant. Runs entirely in your browser.',
            'html entity encoder, html entity decoder, escape html characters, html encode decode free'],

        ['invoice_generator', 'Invoice Generator', $gen, 'fa-file-invoice-dollar',
            'Subscription invoicing software',
            'Build a clean, professional invoice with line items and totals, then print it or save it as a PDF — no account, no monthly fee.',
            '<h2>An invoice you can send in two minutes</h2><p>Freelancers and small businesses are frequently pushed toward invoicing subscriptions for something that is, at its core, a formatted document with a table and a total. This tool builds that document directly in your browser and hands you a print-ready result with no account required.</p>'
            . '<h3>What is on the invoice</h3><p>Your business details, your client\'s billing details, an invoice number and date, an editable table of line items with quantity and price, and an automatically calculated subtotal, optional tax line, and total.</p>'
            . '<h3>Turning it into a PDF</h3><p>Once you preview the invoice, the "Print / Save as PDF" button opens your browser\'s print dialog with only the invoice itself visible — every other control on the page is hidden automatically. Choose "Save as PDF" as the destination in that dialog to get a clean PDF file instead of printing on paper.</p>'
            . '<h3>Nothing is stored anywhere</h3><p>All of your business and client details stay in your browser tab and are never sent to a server, which matters when the invoice contains a client\'s name, address and what you charged them.</p>',
            'Free Invoice Generator — Create & Print PDF Invoices',
            'Create a professional invoice with line items, tax and totals, then print or save as PDF. Free, no account, no monthly fee.',
            'invoice generator, free invoice maker, create invoice online, invoice pdf generator free'],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO tools
         (tool_key, name, slug, category_id, icon_class, `replaces`, short_description, full_description,
          meta_title, meta_description, meta_keywords, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?, 'published')"
    );
    foreach ($tools as $t) {
        [$key, $name, $cat, $icon, $replaces, $short, $full, $mTitle, $mDesc, $mKw] = $t;
        $stmt->execute([$key, $name, slugify($name), $cat, $icon, $replaces, $short, $full, $mTitle, $mDesc, $mKw]);
    }
}
