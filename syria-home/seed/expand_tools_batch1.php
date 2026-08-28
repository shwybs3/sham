<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch1(PDO $pdo): void {

expand_tool($pdo, 'png-to-webp-converter', <<<HTML
<p>Every image you publish on the web is a tradeoff between visual quality and load time, and for most sites the format the image is saved in matters more than almost any other single decision. WebP was built specifically to improve that tradeoff — it typically produces files 25–35% smaller than an equivalent-quality PNG or JPEG, which directly translates into a faster page, a better Core Web Vitals score, and less strain on visitors with slow or metered connections. This converter runs the entire process locally in your browser using the Canvas API, so your image is never uploaded to a server — nothing leaves your device at any point.</p>

<h2>Why file format still matters in 2026</h2>
<p>Search engines now factor page-loading performance directly into ranking, and a large uncompressed hero image is one of the most common reasons a page fails Google's Core Web Vitals thresholds. Switching a site's images from PNG or unoptimized JPEG to WebP is often the single fastest performance win available, because it requires no code changes, no redesign, and no new tooling — just re-exporting the same visual assets in a smarter container format.</p>
<p>PNG remains the right choice in one specific case: images that need true lossless transparency with sharp, hard edges, like a logo with fine detail. For photographs, product shots, blog headers, and most everyday web images, WebP (or its newer sibling AVIF) is almost always the better call.</p>

<h2>How this converter works</h2>
<ol>
<li>Choose an image file from your device — PNG, JPG, or an existing WebP you want to re-compress.</li>
<li>Pick your target format and, if converting to a lossy format, adjust the quality slider to balance file size against visual fidelity.</li>
<li>Preview the result instantly and compare the before/after file size.</li>
<li>Download the converted file — no watermark, no sign-up, no limit on how many times you use it.</li>
</ol>
<p>Because the conversion happens with the Canvas API directly in your browser tab, there's no upload step and no wait for a server round-trip — the whole process typically takes under a second for a normal-sized photo.</p>

<h2>Choosing the right quality setting</h2>
<p>For most web photography, a quality setting between 75 and 85 is the sweet spot — visually indistinguishable from the original at normal viewing sizes, while cutting file size dramatically. Go lower (50–65) for background images or thumbnails where absolute sharpness matters less. Reserve values above 90 for images that will be viewed at full size or zoomed in, like product photography on an e-commerce listing.</p>

<h2>Common use cases</h2>
<ul>
<li><strong>Blog and article headers</strong> — convert your hero images before publishing to shave meaningful load time off every page view.</li>
<li><strong>E-commerce product photos</strong> — smaller files mean faster-loading galleries, which measurably reduces cart abandonment on slow connections.</li>
<li><strong>Email newsletters</strong> — smaller embedded images load faster in inboxes and reduce the chance of an email getting clipped for being oversized.</li>
<li><strong>Portfolio and photography sites</strong> — serve near-identical visual quality at a fraction of the bandwidth cost.</li>
</ul>

<h2>A note on browser support</h2>
<p>WebP is supported by every major modern browser — Chrome, Firefox, Safari, and Edge all display it natively, and have for years. If you're supporting a genuinely legacy audience, most content management systems can serve a WebP image with a PNG/JPEG fallback automatically via the <code>&lt;picture&gt;</code> element, so you don't have to choose one format for every visitor.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is my image uploaded anywhere?</strong> No — conversion happens entirely in your browser using JavaScript's Canvas API. The image file never leaves your device.</p>
<p><strong>Will I lose quality converting to WebP?</strong> At quality settings above 75, the difference from the original is generally imperceptible to the human eye at normal viewing distance, while the file size drops substantially.</p>
<p><strong>Can I convert WebP back to PNG or JPEG?</strong> Yes — the tool works in both directions, useful if you need a PNG for a design program that doesn't support WebP.</p>
<p><strong>Is there a file size or usage limit?</strong> No — use it as many times as you like, on files as large as your browser can comfortably handle.</p>
HTML
);

expand_tool($pdo, 'image-compressor', <<<HTML
<p>Large, uncompressed photos are one of the single most common reasons a website feels slow — and it's an entirely fixable problem in seconds, without touching any code. This compressor re-encodes your image at a quality level you choose, optionally resizing it down from an oversized original first, and does the entire job locally in your browser so nothing you upload is ever sent to a remote server.</p>

<h2>Why unoptimized images slow everything down</h2>
<p>A photo straight off a modern phone camera is frequently 3–8 megabytes — dramatically larger than it needs to be for on-screen display, where even a full-width hero image rarely needs to exceed 1–2 megabytes to look crisp. That extra weight has to be downloaded by every single visitor, on every single page load, which adds up to real, measurable delay, especially on mobile connections. Compressing images before you publish them is one of the highest-leverage, lowest-effort improvements you can make to a site's actual perceived speed.</p>

<h2>How to use it</h2>
<ol>
<li>Select the image you want to compress.</li>
<li>Adjust the quality slider — watch the live preview and estimated file size update as you move it.</li>
<li>Optionally set a maximum width, which resizes oversized images down before compression, useful when the original resolution is far larger than anything the image will actually be displayed at.</li>
<li>Download the compressed result.</li>
</ol>

<h2>Finding the right quality setting</h2>
<p>There's rarely a reason to compress below quality 60 for anything meant to look good — visible artifacts (blurry edges, blocky patches in gradients) start appearing below that point on most images. A setting between 70 and 85 typically produces a file 60–80% smaller than the uncompressed original with no visible loss in normal browsing conditions. Photos with lots of fine detail (foliage, fabric texture, complex patterns) tolerate compression less gracefully than simple, smooth images and may need a slightly higher setting to avoid visible artifacts.</p>

<h2>Resizing versus compressing</h2>
<p>These solve different problems and are often best used together. Compression reduces file size at a given resolution by discarding redundant visual information. Resizing reduces the actual pixel dimensions — useful when an image is simply larger than it will ever be displayed at, which is extremely common with camera-straight-out photos being used as small thumbnails. Resizing a 4000px-wide photo down to 800px before compressing it typically produces a far smaller file, at equal visual quality, than compression alone ever could.</p>

<h2>Where this matters most</h2>
<ul>
<li><strong>Blog and content sites</strong> — every article's hero and inline images benefit directly from smaller file sizes.</li>
<li><strong>Online stores</strong> — product galleries with dozens of images compound the savings dramatically across a whole catalog.</li>
<li><strong>Portfolios</strong> — photographers and designers can compress a full gallery in minutes rather than manually re-exporting from design software.</li>
<li><strong>Social media prep</strong> — many platforms recompress uploads anyway, so pre-compressing gives you more control over the final visual result than letting the platform do it for you.</li>
</ul>

<h2>Frequently asked questions</h2>
<p><strong>Does compressing an image degrade it permanently?</strong> Only the exported copy is affected — always keep your original uncompressed file, and compress a copy for web use.</p>
<p><strong>What's a safe quality setting for most photos?</strong> 75–80 is a reliable starting point for photographic images; simpler graphics and illustrations can often go lower without visible loss.</p>
<p><strong>Is this tool private?</strong> Yes — everything happens locally in your browser tab. Nothing is uploaded to any server.</p>
<p><strong>Can I batch-compress multiple images at once?</strong> This tool processes one image at a time for full control over each file's settings; for very large batches, compress your most-viewed images first for the biggest impact.</p>
HTML
);

expand_tool($pdo, 'qr-code-generator', <<<HTML
<p>A QR code is one of the simplest bridges between the physical and digital world — point a phone camera at a printed square and land directly on a link, a Wi-Fi network, or a block of text, no typing required. This generator turns any URL or text into a scannable, downloadable QR code in seconds, entirely free, with no sign-up and no limit on how many you create.</p>

<h2>What QR codes are actually good for</h2>
<p>QR codes solve a specific, narrow problem well: getting someone from something physical (a poster, a receipt, a product package, a business card) to something digital (a website, a menu, a contact card) without them having to type a URL by hand — which, for anything longer than a few characters, most people simply won't do. Anywhere a visitor's phone is already out and pointed at something printed, a QR code removes friction that would otherwise cost you the click entirely.</p>

<h2>How to generate one</h2>
<ol>
<li>Enter the URL, text, or data you want the code to encode.</li>
<li>Preview the generated code instantly as you type.</li>
<li>Download it as a high-resolution PNG, ready to print or embed in a design.</li>
</ol>
<p>Because generation happens instantly and locally, there's no waiting, no watermark, and no account required — generate as many codes as you need.</p>

<h2>Practical use cases</h2>
<ul>
<li><strong>Restaurant and cafe menus</strong> — a table-tent QR code linking straight to a digital menu, avoiding printed menu reprints every time a price changes.</li>
<li><strong>Business cards</strong> — a code linking directly to a contact-save link or portfolio, more useful than a URL someone has to type in later.</li>
<li><strong>Event posters and flyers</strong> — a direct link to registration or ticket purchase, capturing interest at the exact moment someone is looking at the poster.</li>
<li><strong>Product packaging</strong> — linking to instructions, warranty registration, or supplementary content that wouldn't fit on the physical label.</li>
<li><strong>Wi-Fi sharing</strong> — encode network credentials so guests can join instantly by scanning, without you reading out a password character by character.</li>
</ul>

<h2>Design and print tips</h2>
<p>Leave a quiet margin of white space around the code — scanners rely on that border to recognize where the code starts and ends, and a code printed edge-to-edge with no margin often fails to scan reliably. Test any printed code with several different phone models before a large print run; scanning reliability can vary slightly between camera apps. For codes destined for small print (like a business card), keep the encoded content short — a full long URL produces a visually denser, harder-to-scan code than a short link would.</p>

<h2>Frequently asked questions</h2>
<p><strong>Do QR codes expire?</strong> No — a QR code simply encodes the data you gave it and works indefinitely, provided the underlying link it points to still resolves.</p>
<p><strong>Can I change where a QR code points after printing it?</strong> Only if you used a redirect/short-link service as the encoded URL and update that redirect — a code encoding a direct link can't be changed after it's printed.</p>
<p><strong>What size should I print a QR code at?</strong> As a rough guide, print at least 2×2 cm (roughly 1 inch) for close-range scanning, and scale up significantly for anything meant to be scanned from a few meters away, like a large poster.</p>
<p><strong>Is there a cost or limit to using this generator?</strong> No — it's free, unlimited, and requires no account.</p>
HTML
);

expand_tool($pdo, 'strong-password-generator', <<<HTML
<p>A weak or reused password remains one of the single most common causes of account compromise, and yet most people still choose passwords they can remember rather than ones that are actually hard to guess or crack. This generator produces cryptographically random passwords using your browser's <code>crypto.getRandomValues</code> API — the same class of secure randomness used in security-critical software — rather than a weaker, more predictable <code>Math.random()</code> source. Nothing is transmitted anywhere; every password is generated and displayed entirely on your own device.</p>

<h2>Why "random" matters more than "clever"</h2>
<p>A password that feels clever to a human — a word with numbers substituted for letters, a memorable phrase — is often far more predictable to an automated cracking tool than it feels to a person, because those substitution patterns are extremely well documented and built directly into cracking dictionaries. True randomness, generated by a cryptographic source rather than a human brain, doesn't have that weakness: there's no pattern to exploit, because there genuinely isn't one.</p>

<h2>How to use it</h2>
<ol>
<li>Choose your desired password length — longer is meaningfully stronger; 16+ characters is a reasonable modern baseline.</li>
<li>Select which character sets to include: uppercase, lowercase, numbers, and symbols.</li>
<li>Generate a password and copy it directly to your clipboard.</li>
<li>Generate as many as you like until you have one you're satisfied with, or generate a fresh one for every account you need to secure.</li>
</ol>

<h2>Length versus complexity</h2>
<p>Modern security guidance has shifted meaningfully toward favoring length over forced complexity rules. A long, fully random password is dramatically harder to crack by brute force than a shorter one stuffed with symbols, because every additional character multiplies the total number of possible combinations an attacker would have to try. If a site allows it, prefer length: 16–24 truly random characters is far stronger than an 8-character password with a symbol crammed in to satisfy a complexity rule.</p>

<h2>What to actually do with the generated password</h2>
<p>A strong, randomly generated password is only as useful as your ability to actually retrieve it later — which means it should go straight into a password manager, not into memory or a sticky note. Use a unique, freshly generated password for every account; reusing even a strong password across multiple sites means a single breached site compromises every other account using the same password. Combine a strong unique password with two-factor authentication wherever it's offered — the two together protect against different classes of attack.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is this generator actually secure, or just for demonstration?</strong> It uses your browser's built-in cryptographically secure random number generator — the same standard used by real security software — and nothing is ever transmitted or logged.</p>
<p><strong>What password length should I use?</strong> 16 characters is a solid general-purpose minimum; use the maximum length a given site allows wherever practical.</p>
<p><strong>Should I include symbols?</strong> Symbols help, but length matters more — if a site restricts symbols, a longer password without them still outperforms a short one with them.</p>
<p><strong>Where should I store the generated password?</strong> In a dedicated password manager — never in a plain text file, browser autofill alone, or written down somewhere easily found.</p>
HTML
);

expand_tool($pdo, 'json-formatter-validator', <<<HTML
<p>JSON is the backbone data format of the modern web — API responses, configuration files, and countless data exchanges all pass through it — but raw JSON, especially minified or generated by a script, is often a dense, unreadable single line that's nearly impossible to inspect by eye. This tool instantly formats, validates, and pretty-prints JSON, and — just as usefully — pinpoints exactly where a broken JSON document fails to parse, entirely inside your browser with nothing ever uploaded.</p>

<h2>Why properly formatted JSON matters</h2>
<p>Debugging an API integration or a configuration file is dramatically harder when the JSON involved is a single unreadable line. Proper indentation and line breaks turn a wall of text into a navigable, nested structure you can actually scan by eye — which matters enormously when you're trying to spot a missing comma, an unclosed bracket, or a value in the wrong place.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your JSON — minified, malformed, or already-formatted — into the input area.</li>
<li>The tool instantly validates the structure and either displays a cleanly indented, syntax-highlighted result or points to the exact location of a syntax error.</li>
<li>Copy the formatted output, or fix the flagged error and re-validate.</li>
</ol>

<h2>Common JSON errors this catches</h2>
<ul>
<li><strong>Trailing commas</strong> — valid in JavaScript object literals but invalid in strict JSON, a frequent source of confusing parse failures.</li>
<li><strong>Unquoted or single-quoted keys</strong> — JSON requires double quotes around every key; single quotes or bare keys are a common copy-paste mistake from JavaScript code.</li>
<li><strong>Mismatched brackets or braces</strong> — an unclosed <code>{</code> or <code>[</code> buried deep in a nested structure, which is often nearly impossible to spot by eye in unformatted text but immediately obvious once indented.</li>
<li><strong>Invalid escape sequences</strong> — a backslash used incorrectly inside a string value.</li>
</ul>

<h2>Who this tool is for</h2>
<p>Developers debugging an API response, anyone editing a configuration file by hand, students learning how JSON structures data, and non-technical users who've been handed a block of JSON and need to understand or clean it up all benefit from a fast, reliable formatter — no local tooling, code editor, or installation required, just a browser tab.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is my data uploaded anywhere when I use this?</strong> No — parsing and formatting happen entirely in your browser using JavaScript's built-in JSON engine. Nothing is sent to a server.</p>
<p><strong>Can this fix invalid JSON automatically?</strong> It identifies exactly where and why the JSON is invalid so you can fix it quickly, rather than guessing — it doesn't silently alter your data's meaning to force it to validate.</p>
<p><strong>Does it work with very large JSON documents?</strong> Yes, though extremely large files (many megabytes) may format more slowly depending on your device's processing power.</p>
<p><strong>What's the difference between JSON and a JavaScript object literal?</strong> JSON is a stricter subset — it requires double-quoted keys, disallows trailing commas, and doesn't support comments or functions, even though it looks almost identical to JavaScript object syntax.</p>
HTML
);

}
