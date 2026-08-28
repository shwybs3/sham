<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch2(PDO $pdo): void {

expand_tool($pdo, 'base64-encoder-decoder', <<<HTML
<p>Base64 is a way of representing binary or arbitrary data as plain, safe-to-transmit text, and it shows up constantly in web development — embedding small images directly in CSS or HTML, encoding credentials in an authentication header, or passing binary data through a system that only understands text. This tool encodes and decodes Base64 instantly, entirely in your browser, with the conversion happening locally and nothing ever transmitted anywhere.</p>

<h2>What Base64 actually is</h2>
<p>Base64 takes raw binary data and re-represents it using only 64 printable ASCII characters (letters, digits, and a couple of symbols), which makes it safe to include inside contexts — like a URL, an email, or a JSON string — that weren't designed to carry arbitrary binary bytes safely. It is not encryption and provides no security on its own; it's purely a format conversion, and anyone can decode it back to the original data instantly, which is an important distinction for anyone tempted to use it to "hide" sensitive information.</p>

<h2>How to use it</h2>
<ol>
<li>Paste text or a data string into the input.</li>
<li>Choose whether to encode (plain text to Base64) or decode (Base64 back to plain text).</li>
<li>Copy the result directly to your clipboard.</li>
</ol>

<h2>Common real-world uses</h2>
<ul>
<li><strong>Embedding small images in CSS or HTML</strong> — a Base64-encoded data URI lets a small icon or background image live directly inside your stylesheet, avoiding an extra network request.</li>
<li><strong>Debugging API tokens and headers</strong> — many authentication schemes (like Basic Auth) encode credentials as Base64, and decoding a header value is often the fastest way to confirm what's actually being sent.</li>
<li><strong>Reading JWT payloads</strong> — the middle segment of a JSON Web Token is Base64-encoded JSON, and decoding it by hand is a quick way to inspect claims without a dedicated JWT tool.</li>
<li><strong>Passing binary-ish data through text-only systems</strong> — some legacy APIs or email systems only reliably handle plain text, and Base64 lets binary content travel safely through them.</li>
</ul>

<h2>A common misconception worth clearing up</h2>
<p>Because Base64 output looks like a jumble of random characters, it's sometimes mistaken for a form of security or obfuscation. It is neither — decoding it requires no key or password, just the same algorithm run in reverse, which any browser, programming language, or online tool (including this one) can do instantly. Never rely on Base64 encoding alone to protect sensitive information; use it purely as a format conversion, and use actual encryption for anything that needs to stay confidential.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is Base64 the same as encryption?</strong> No — it's a reversible format conversion with no key or password involved. Anyone can decode Base64 text back to its original form.</p>
<p><strong>Why does encoded text look longer than the original?</strong> Base64 encoding increases data size by roughly 33%, since it's re-representing binary data using a more limited character set.</p>
<p><strong>Is my data sent anywhere when I use this tool?</strong> No — encoding and decoding both happen entirely in your browser using JavaScript's built-in functions.</p>
<p><strong>Can Base64 handle any type of data?</strong> Yes — it works on any binary or text input, which is exactly why it's used so widely across different systems and formats.</p>
HTML
);

expand_tool($pdo, 'word-character-counter', <<<HTML
<p>Nearly every piece of writing you publish online lives under some kind of length constraint — a tweet's character cap, a meta description that gets truncated past a certain point, an essay with a strict word count, a form field with a hard limit. This tool counts words, characters, sentences, and estimated reading time in real time as you type or paste text, entirely in your browser, with nothing you write ever leaving your device.</p>

<h2>Why counting by hand doesn't work</h2>
<p>Manually estimating word or character count is unreliable enough that it's simply not worth attempting for anything where the limit actually matters — a caption that's one character over Twitter's limit gets silently rejected, and a meta description a few characters too long gets awkwardly truncated with an ellipsis in search results, undercutting content that was otherwise carefully written. A live, accurate counter removes the guesswork entirely.</p>

<h2>What it measures</h2>
<ul>
<li><strong>Character count</strong> — both with and without spaces, useful for platforms with strict character limits like meta descriptions and social captions.</li>
<li><strong>Word count</strong> — for essays, articles, and assignments with a required word count.</li>
<li><strong>Sentence and paragraph count</strong> — useful for gauging structure and pacing at a glance.</li>
<li><strong>Estimated reading time</strong> — calculated from average adult reading speed, useful for setting reader expectations on longer content.</li>
</ul>

<h2>Platform-specific limits worth knowing</h2>
<p>Different platforms enforce very different limits, and writing without checking against the actual target platform is a common source of last-minute rewrites. A Google meta description is generally truncated somewhere around 155–160 characters in search results. A tweet is capped at 280 characters. A typical essay assignment specifies a word count rather than a character count, and instructors frequently enforce it strictly. Checking your draft against the actual limit of the platform you're publishing to — rather than guessing — avoids the frustration of a last-minute rewrite under time pressure.</p>

<h2>Who uses a tool like this</h2>
<p>Students working against an assignment's word count, content marketers writing meta descriptions and social captions, authors tracking progress toward a manuscript's target length, and anyone drafting a form submission with a strict character cap all rely on an instant, accurate counter rather than manually estimating and frequently guessing wrong.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does this count spaces as characters?</strong> The tool shows both counts — with and without spaces — since different platforms measure length differently.</p>
<p><strong>How is reading time calculated?</strong> Based on an average adult reading speed, typically around 200–250 words per minute; actual reading time varies by content complexity and individual reader speed.</p>
<p><strong>Is my text saved or uploaded anywhere?</strong> No — everything is calculated locally in your browser as you type. Nothing is transmitted or stored.</p>
<p><strong>Does it count words correctly with punctuation?</strong> Yes — the counting logic handles standard punctuation and spacing correctly, matching how most word processors and platforms count.</p>
HTML
);

expand_tool($pdo, 'text-case-converter', <<<HTML
<p>Fixing text that's stuck in the wrong case — a caption autocorrect capitalized wrong, a heading pasted in from an all-caps source, a list of tags that need consistent formatting — is one of those small, tedious tasks that eats disproportionate time if done manually. This converter instantly switches text between uppercase, lowercase, title case, sentence case, and a few programming-specific formats, entirely in your browser with nothing ever uploaded.</p>

<h2>The case formats this tool supports</h2>
<ul>
<li><strong>UPPERCASE</strong> — every letter capitalized, useful for headers, emphasis, or matching a specific brand style.</li>
<li><strong>lowercase</strong> — every letter in lower case, often used for tags, slugs, or a deliberately casual tone.</li>
<li><strong>Title Case</strong> — the first letter of each major word capitalized, the standard format for headlines and titles.</li>
<li><strong>Sentence case</strong> — only the first letter of each sentence capitalized, the standard format for normal prose.</li>
<li><strong>camelCase and PascalCase</strong> — programming-specific formats used for variable and function names, where spaces are removed and word boundaries are marked by capitalization instead.</li>
<li><strong>snake_case and kebab-case</strong> — words joined with underscores or hyphens, common for file names, URL slugs, and variable naming conventions in code.</li>
</ul>

<h2>How to use it</h2>
<ol>
<li>Paste or type your text into the input area.</li>
<li>Choose the case format you need from the available options.</li>
<li>Copy the converted result instantly.</li>
</ol>

<h2>Practical scenarios where this saves real time</h2>
<p>A heading pasted from a source that used all-caps styling needs converting to proper title case before it looks right in your own content. A developer needs a human-readable label converted into camelCase for a variable name, or a title converted into kebab-case for a URL slug. A writer needs a sentence that autocorrect capitalized incorrectly reset to proper sentence case. In every one of these cases, retyping by hand is slower and more error-prone than a one-click conversion.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does Title Case handle small words like "a" and "the" correctly?</strong> Standard title case conventions vary slightly by style guide (some capitalize every word, others leave short connector words lowercase) — the tool follows common headline-style conventions.</p>
<p><strong>What's the difference between camelCase and PascalCase?</strong> camelCase starts with a lowercase letter (<code>myVariableName</code>), while PascalCase capitalizes the first letter too (<code>MyVariableName</code>) — both remove spaces and capitalize subsequent word boundaries.</p>
<p><strong>When would I need snake_case or kebab-case?</strong> snake_case is common in Python variable naming and database column names; kebab-case is the standard for URL slugs and CSS class names.</p>
<p><strong>Is my text stored or sent anywhere?</strong> No — all conversion happens instantly and locally in your browser.</p>
HTML
);

expand_tool($pdo, 'lorem-ipsum-generator', <<<HTML
<p>When you're designing a layout, mocking up a page, or testing how a template handles varying content lengths, you need placeholder text that looks and behaves like real content — without the distraction of actual meaningful words pulling a reviewer's attention toward the copy instead of the design. Lorem Ipsum, the standard placeholder Latin-derived text used across publishing and design for centuries, does exactly that. This generator produces however much you need, formatted as paragraphs, sentences, or a word list, instantly and for free.</p>

<h2>Why designers still use Lorem Ipsum</h2>
<p>Real, meaningful text is a distraction when you're evaluating a layout — a reviewer's eye is drawn to interesting words and their actual meaning rather than to the spacing, typography, and visual hierarchy you're actually trying to assess. Lorem Ipsum's Latin-derived, deliberately non-meaningful words let a design be judged purely on its visual merits: does the text wrap well, does the line length feel right, does the layout hold up with a full paragraph versus a short one.</p>

<h2>How to use it</h2>
<ol>
<li>Choose how much text you need — a set number of words, sentences, or paragraphs.</li>
<li>Generate instantly and copy the result directly into your design tool, CMS, or code.</li>
<li>Regenerate as many times as needed for different length variations.</li>
</ol>

<h2>Where placeholder text is genuinely useful</h2>
<ul>
<li><strong>Website and app mockups</strong> — filling in body copy, headlines, and captions before real content is written, so a design review can focus on layout rather than copy.</li>
<li><strong>Print and publishing layouts</strong> — testing how a magazine spread, brochure, or book layout handles a full page of running text.</li>
<li><strong>Testing responsive designs</strong> — generating varying lengths of text to see how a layout handles a short one-line heading versus a long, wrapping one.</li>
<li><strong>Font and typography testing</strong> — evaluating how a typeface looks in bulk running text rather than a single sample sentence.</li>
</ul>

<h2>When to use real content instead</h2>
<p>Placeholder text is a design-phase tool, not a launch-ready one — a page shipped live with Lorem Ipsum still in it looks unfinished and unprofessional to actual visitors, and search engines index literal Latin-derived filler text just as they'd index any other content, which does nothing useful for a page's actual SEO. Always swap it out for real, final copy before anything goes live.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is Lorem Ipsum actual Latin?</strong> It's derived from a genuine classical Latin text, scrambled and altered over centuries of use until it no longer forms coherent, translatable sentences — its value is purely as visually realistic filler.</p>
<p><strong>How much placeholder text should I generate for a typical layout?</strong> Match it roughly to your expected real content length — a few sentences for a short caption, several paragraphs for a full article body mockup.</p>
<p><strong>Can I use this for print projects, not just web design?</strong> Yes — it's equally useful for testing any layout, digital or print, that needs to be evaluated with realistic-looking running text.</p>
<p><strong>Is it free to use without limits?</strong> Yes — generate as much as you need, as often as you need it, with no account required.</p>
HTML
);

expand_tool($pdo, 'markdown-to-html-converter', <<<HTML
<p>Markdown has become the default way developers, writers, and documentation authors draft content — it's fast to write, readable even in its raw form, and avoids the overhead of a full rich-text editor. But eventually that Markdown needs to become actual HTML to display properly on a website, in an email, or inside a CMS that doesn't natively understand Markdown syntax. This tool converts Markdown to clean, valid HTML instantly, entirely in your browser, with nothing you write ever uploaded to a server.</p>

<h2>Why Markdown became the standard for drafting content</h2>
<p>Markdown lets you write formatted text — headings, bold and italic emphasis, lists, links, code blocks — using plain, readable punctuation-based syntax instead of clicking through toolbar buttons or writing raw HTML tags by hand. A line starting with <code>##</code> becomes a heading; text wrapped in <code>**asterisks**</code> becomes bold. It's fast enough to write without breaking your writing flow, and readable enough in its raw form that you can review it without rendering it first.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your Markdown-formatted text into the input area.</li>
<li>The tool instantly converts it to clean, semantic HTML.</li>
<li>Copy the HTML output directly into your website's code, CMS, or email template.</li>
</ol>

<h2>What gets converted</h2>
<ul>
<li><strong>Headings</strong> — <code>#</code> through <code>######</code> become <code>&lt;h1&gt;</code> through <code>&lt;h6&gt;</code> tags.</li>
<li><strong>Emphasis</strong> — bold and italic markers become <code>&lt;strong&gt;</code> and <code>&lt;em&gt;</code> tags.</li>
<li><strong>Lists</strong> — both bulleted and numbered lists convert to proper <code>&lt;ul&gt;</code>/<code>&lt;ol&gt;</code> markup.</li>
<li><strong>Links and images</strong> — Markdown's compact link and image syntax becomes standard <code>&lt;a&gt;</code> and <code>&lt;img&gt;</code> tags.</li>
<li><strong>Code blocks and inline code</strong> — properly wrapped in <code>&lt;pre&gt;</code>/<code>&lt;code&gt;</code> tags, preserving formatting.</li>
</ul>

<h2>Common use cases</h2>
<p>Developers writing documentation in Markdown who need to publish it to a platform that only accepts HTML, bloggers who draft in Markdown for speed but publish to a CMS with a rich-text HTML editor, and anyone converting README files or technical notes into a web-ready format all benefit from an instant, reliable converter rather than manually rewriting formatted text as HTML tags by hand.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does this support GitHub-flavored Markdown extensions like tables?</strong> Common extended syntax — including tables and strikethrough — is supported alongside standard Markdown formatting.</p>
<p><strong>Will the output HTML need additional styling?</strong> The converter produces clean, semantic HTML without inline styles — you'll apply your own CSS to match your site's design, exactly as you would with any hand-written HTML.</p>
<p><strong>Is my content uploaded anywhere?</strong> No — conversion happens entirely in your browser using JavaScript. Nothing is sent to a server.</p>
<p><strong>Can I convert HTML back to Markdown with this tool?</strong> This tool is built specifically for Markdown-to-HTML conversion in one direction, matching the most common real-world publishing workflow.</p>
HTML
);

}
