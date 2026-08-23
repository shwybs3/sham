<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch3(PDO $pdo): void {

expand_tool($pdo, 'csv-to-json-converter', <<<HTML
<p>Spreadsheets and JSON serve very different audiences — CSV is the universal export format from Excel, Google Sheets, and virtually every database tool, while JSON is what most modern web applications and APIs actually expect as input. This converter bridges the two instantly, turning a CSV file's rows and columns into properly structured JSON objects, entirely in your browser with nothing uploaded to a server.</p>

<h2>Why this conversion comes up so often</h2>
<p>A common workflow looks like this: data lives in a spreadsheet because that's how it was collected or exported, but the destination — a web app, an API, a JavaScript project — needs it as JSON. Rather than manually retyping data into JSON's nested key-value format, which is slow and highly error-prone for anything beyond a handful of rows, this tool reads the CSV's header row as object keys and each subsequent row as a JSON object, automatically producing a clean array of records.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your CSV data, including the header row, into the input.</li>
<li>The tool automatically detects columns and converts each row into a JSON object using the header row as field names.</li>
<li>Copy the resulting JSON array, formatted and ready to use.</li>
</ol>

<h2>Common use cases</h2>
<ul>
<li><strong>Preparing data for a web app or API</strong> — converting a spreadsheet export into the JSON format a JavaScript application actually expects.</li>
<li><strong>Database seeding</strong> — turning tabular data into structured records for import into a NoSQL database or API-driven data store.</li>
<li><strong>Configuration migration</strong> — converting a spreadsheet-maintained list (like a product catalog or settings table) into a structured format a codebase can read directly.</li>
<li><strong>Quick data inspection</strong> — sometimes it's simply easier to spot a data quality issue once you see it in JSON's explicit key-value structure rather than in a dense grid of spreadsheet columns.</li>
</ul>

<h2>A note on data quality before converting</h2>
<p>The output JSON is only as clean as the input CSV — a header row with inconsistent naming, extra blank columns, or misaligned rows will carry those same problems straight into the JSON output. It's worth a quick visual scan of the CSV for obviously missing values or misaligned columns before converting, since fixing a formatting problem in the original spreadsheet is far easier than fixing it after the fact in JSON.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does this handle CSV files with commas inside quoted values?</strong> Yes — the parser correctly handles quoted fields that contain commas, a common source of errors in naive CSV parsing.</p>
<p><strong>What happens to empty cells in the CSV?</strong> They're converted to empty string values in the resulting JSON, preserving the original structure rather than silently dropping fields.</p>
<p><strong>Is my data uploaded anywhere?</strong> No — the entire conversion happens locally in your browser using JavaScript. Nothing leaves your device.</p>
<p><strong>Can I convert JSON back to CSV with this tool?</strong> This tool is focused specifically on CSV-to-JSON conversion, matching the most common direction developers need when integrating spreadsheet data into an application.</p>
HTML
);

expand_tool($pdo, 'hash-generator-md5-sha', <<<HTML
<p>A hash function takes any input — a password, a file, a block of text — and produces a fixed-length string of characters that acts as a unique fingerprint of that input. Change even a single character of the input and the resulting hash changes completely and unpredictably. This tool generates MD5, SHA-1, and SHA-256 hashes instantly from any text you provide, entirely in your browser, with nothing you type ever transmitted anywhere.</p>

<h2>What hashing is actually used for</h2>
<p>Hashing shows up constantly in software and security contexts, usually for one of a few specific purposes: verifying that a downloaded file wasn't corrupted or tampered with in transit, storing a representation of a password without storing the actual password itself, or generating a consistent, unique identifier for a piece of data. Unlike encryption, hashing is one-directional by design — you cannot reverse a hash back into its original input, which is exactly the point for most of its real uses.</p>

<h2>How to use it</h2>
<ol>
<li>Paste or type the text you want to hash.</li>
<li>Choose your algorithm — MD5, SHA-1, or SHA-256.</li>
<li>The hash generates instantly; copy it directly.</li>
</ol>

<h2>Choosing between MD5, SHA-1, and SHA-256</h2>
<p>MD5 and SHA-1 are both considered cryptographically broken for security-sensitive purposes — researchers have demonstrated practical ways to engineer two different inputs that produce the same hash, which undermines their use for anything where an attacker might benefit from that collision. They remain useful for non-security purposes, like a quick file-integrity check or generating a fast, deterministic identifier. SHA-256 is the modern standard for anything security-relevant, offering substantially stronger collision resistance and remaining the recommended choice wherever a hash needs to actually resist deliberate tampering.</p>

<h2>Common real-world uses</h2>
<ul>
<li><strong>File integrity verification</strong> — comparing a downloaded file's hash against a publisher's published hash to confirm nothing was corrupted or altered in transit.</li>
<li><strong>Generating consistent identifiers</strong> — using a hash of some input data as a deterministic, repeatable ID or cache key in software.</li>
<li><strong>Detecting duplicate content</strong> — comparing hashes of two pieces of text or files is a fast way to check whether they're identical, without comparing them character by character.</li>
<li><strong>Learning and demonstration</strong> — understanding how hash functions behave, including the avalanche effect where a tiny input change produces a completely different hash.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Can I reverse a hash back to the original text?</strong> No — hashing is a one-way function by design; there is no legitimate way to recover the original input purely from its hash.</p>
<p><strong>Which algorithm should I use for password storage?</strong> None of these directly — real password storage should use a purpose-built, slow hashing algorithm designed specifically for passwords (like bcrypt or Argon2), not a general-purpose hash like MD5 or SHA-256 used alone.</p>
<p><strong>Is my text sent anywhere when I generate a hash?</strong> No — hashing happens entirely locally in your browser using JavaScript's cryptographic functions.</p>
<p><strong>Why do MD5 and SHA-256 produce different-length outputs for the same input?</strong> Each algorithm produces a fixed output length regardless of algorithm — MD5 always produces 128 bits, SHA-256 always produces 256 bits — but the two algorithms compute entirely different values from the same input.</p>
HTML
);

expand_tool($pdo, 'url-encoder-decoder', <<<HTML
<p>URLs can only safely contain a limited set of characters — letters, digits, and a small handful of punctuation marks — which means spaces, special characters, and non-Latin text all need to be encoded into a URL-safe format before they can travel reliably through a link, query string, or API request. This tool encodes and decodes URLs instantly, entirely in your browser, with nothing you paste ever sent to a server.</p>

<h2>Why URL encoding exists</h2>
<p>A raw space in a URL, a special character like <code>&amp;</code> or <code>?</code> used somewhere other than its reserved purpose, or non-Latin characters can all break a link or cause a server to misinterpret where one part of the URL ends and another begins. URL encoding (also called percent-encoding) replaces problematic characters with a percent sign followed by their hexadecimal code — a space becomes <code>%20</code>, for instance — producing a string that's guaranteed to travel safely through any URL-handling system.</p>

<h2>How to use it</h2>
<ol>
<li>Paste text or an existing URL into the input.</li>
<li>Choose whether to encode (convert special characters to percent-encoded form) or decode (convert percent-encoded text back to readable form).</li>
<li>Copy the result instantly.</li>
</ol>

<h2>Common situations where this matters</h2>
<ul>
<li><strong>Building query strings</strong> — encoding a user-provided search term or parameter value before appending it to a URL, so special characters in that value don't break the URL's structure.</li>
<li><strong>Debugging a broken link</strong> — decoding a percent-encoded URL to read what it actually contains, useful when troubleshooting a redirect or tracking link that isn't working as expected.</li>
<li><strong>Sharing links with special characters</strong> — a search result URL or a link containing non-Latin text often needs encoding to paste reliably into an email or chat message without breaking.</li>
<li><strong>API development</strong> — many APIs require query parameters to be properly URL-encoded, and a malformed request due to unencoded special characters is a common source of confusing API errors.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>What's the difference between encoding a full URL versus a URL component?</strong> Encoding a full URL leaves structural characters like <code>:</code>, <code>/</code>, and <code>?</code> untouched since they define the URL's structure; encoding a single component (like a query parameter value) encodes those characters too, since they'd otherwise be misinterpreted as part of the URL's structure rather than as data.</p>
<p><strong>Why do I sometimes see a plus sign instead of %20 for spaces?</strong> Some encoding contexts (particularly form submissions) use <code>+</code> for spaces instead of <code>%20</code> — both are valid depending on context, and this is a common source of confusion when debugging.</p>
<p><strong>Is my data uploaded anywhere?</strong> No — encoding and decoding happen entirely locally in your browser.</p>
<p><strong>Can encoding break a URL that already works?</strong> Encoding characters that don't need encoding is generally harmless, but double-encoding an already-encoded URL (encoding it twice) can break it — check whether your input is already encoded before encoding it again.</p>
HTML
);

expand_tool($pdo, 'color-converter-palette-generator', <<<HTML
<p>Colors get represented differently depending on the tool or platform you're working in — a designer's HEX code, a CSS developer's RGB values, an image editor's HSL sliders — and translating between them by hand is slow and error-prone. This tool converts instantly between HEX, RGB, and HSL formats, and generates complementary and harmonious color palettes from any starting color, entirely in your browser.</p>

<h2>Why color formats differ in the first place</h2>
<p>HEX (like <code>#3B82F6</code>) is the most common format in web design and CSS, compact and easy to copy-paste. RGB expresses the same color as separate red, green, and blue intensity values, which is often more intuitive when adjusting a color programmatically. HSL — hue, saturation, lightness — is frequently the easiest format for humans to reason about directly, since adjusting lightness or saturation independently produces a predictable, intuitive result in a way that adjusting individual RGB channels doesn't.</p>

<h2>How to use it</h2>
<ol>
<li>Enter a color in any supported format — HEX, RGB, or HSL — or pick one visually.</li>
<li>See it instantly converted to the other two formats, ready to copy.</li>
<li>Generate a palette of complementary, analogous, or triadic colors based on your starting color.</li>
</ol>

<h2>Understanding palette generation</h2>
<ul>
<li><strong>Complementary colors</strong> — sit opposite each other on the color wheel, producing high contrast and visual energy when paired.</li>
<li><strong>Analogous colors</strong> — sit next to each other on the color wheel, producing a naturally harmonious, low-contrast palette.</li>
<li><strong>Triadic colors</strong> — three colors evenly spaced around the color wheel, producing a vibrant palette while maintaining visual balance.</li>
</ul>
<p>These relationships are grounded in traditional color theory and give a fast, reliable starting point for a palette that will look intentional rather than arbitrary, even without formal design training.</p>

<h2>Common use cases</h2>
<p>Web developers converting a designer's HEX palette into the RGB or HSL values a specific CSS function requires, designers exploring palette options starting from a single brand color, and anyone building a UI who needs a quick, harmonious set of accent colors all benefit from instant conversion and generation rather than manually calculating color relationships or guessing at complementary shades.</p>

<h2>Frequently asked questions</h2>
<p><strong>Which color format should I use in my CSS?</strong> All three work in modern CSS — HEX is the most compact and common; HSL is often easiest to adjust predictably (lighten or darken by changing one number).</p>
<p><strong>What makes a generated palette "accessible"?</strong> Accessibility depends specifically on contrast between text and background colors, not on the palette's harmony alone — always check contrast ratios separately for any text/background color pairing.</p>
<p><strong>Is my color data stored anywhere?</strong> No — all conversion and generation happens instantly and locally in your browser.</p>
<p><strong>Can I use a generated palette commercially?</strong> Yes — colors and color relationships aren't copyrightable; use any generated palette freely in personal or commercial work.</p>
HTML
);

expand_tool($pdo, 'unit-converter', <<<HTML
<p>Converting between measurement systems — metric and imperial, or between units within the same system — is one of those everyday calculations that's simple in principle but easy to get subtly wrong by hand, especially under time pressure or with an unfamiliar unit. This tool converts instantly across length, weight, volume, temperature, and more, entirely in your browser, with accurate results every time.</p>

<h2>Why unit conversion still trips people up</h2>
<p>Some conversions are simple multiplication (kilometers to meters), while others involve less intuitive factors (miles to kilometers, Fahrenheit to Celsius) that are genuinely hard to do reliably in your head, especially when precision matters. A single misplaced decimal or a mixed-up conversion factor can produce a result that's wrong by an order of magnitude — a mistake that's easy to make manually and easy to avoid with a dedicated, tested converter.</p>

<h2>Categories this tool covers</h2>
<ul>
<li><strong>Length</strong> — millimeters, centimeters, meters, kilometers, inches, feet, yards, and miles.</li>
<li><strong>Weight and mass</strong> — grams, kilograms, ounces, pounds, and stone.</li>
<li><strong>Volume</strong> — milliliters, liters, cups, pints, gallons, and fluid ounces.</li>
<li><strong>Temperature</strong> — Celsius, Fahrenheit, and Kelvin.</li>
<li><strong>Area, speed, and data storage</strong> — additional common categories for everyday and technical conversions alike.</li>
</ul>

<h2>How to use it</h2>
<ol>
<li>Select the category you're converting within (length, weight, temperature, and so on).</li>
<li>Enter your value and choose the source and target units.</li>
<li>The converted result updates instantly as you type.</li>
</ol>

<h2>Where accurate conversion actually matters</h2>
<p>Cooking a recipe written in a measurement system you don't normally use, following international shipping or packaging weight limits, converting a temperature setting on imported equipment, or working across metric and imperial units in a technical drawing or specification all depend on getting the conversion exactly right — a rounding error that seems small can compound into a real practical problem, whether that's a recipe that fails or a shipment that gets flagged for exceeding a weight limit.</p>

<h2>Frequently asked questions</h2>
<p><strong>How accurate are the conversions?</strong> The tool uses precise standard conversion factors rather than rounded approximations, giving results accurate to several decimal places.</p>
<p><strong>Why do some countries still use imperial units?</strong> Historical and cultural reasons — the United States remains a notable holdout from the metric system used by most of the rest of the world, which is exactly why conversion tools like this stay useful for anyone working across both systems.</p>
<p><strong>Is there a limit to how many conversions I can do?</strong> No — use it as many times as you need, for free, with no account required.</p>
<p><strong>Does it handle negative temperatures correctly?</strong> Yes — temperature conversion correctly handles negative values across Celsius, Fahrenheit, and Kelvin.</p>
HTML
);

}
