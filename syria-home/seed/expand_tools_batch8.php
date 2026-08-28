<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch8(PDO $pdo): void {

expand_tool($pdo, 'colour-palette-extractor', <<<HTML
<p>An image — a photograph, a piece of artwork, a screenshot of design inspiration — often already contains a beautiful, cohesive color palette; the challenge is extracting exact, usable color values from it rather than guessing at them by eye. This tool analyzes any image and pulls out its dominant colors automatically, giving you exact HEX and RGB values ready to use in a design, entirely in your browser.</p>

<h2>Why extracting colors from a real image beats guessing</h2>
<p>The human eye is surprisingly bad at accurately identifying an exact color value just by looking — what looks like a simple blue in a photo is very often a specific, slightly muted or warmed shade that's hard to reproduce accurately from memory or guesswork alone. Extracting the actual pixel values directly from an image guarantees color accuracy and consistency, which matters enormously when you're trying to match a brand palette or recreate the mood of a specific reference photo in a new design.</p>

<h2>How to use it</h2>
<ol>
<li>Upload an image — a photo, artwork, screenshot, or any visual reference.</li>
<li>The tool analyzes the image and extracts its most dominant colors automatically.</li>
<li>Copy each color's exact HEX or RGB value directly into your design tool or CSS.</li>
</ol>

<h2>Common use cases</h2>
<ul>
<li><strong>Building a brand palette from a logo or product photo</strong> — extracting exact colors to ensure consistent use across a website, marketing materials, and packaging.</li>
<li><strong>Matching a design to inspiration</strong> — pulling accurate colors from a mood-board image or reference photo rather than approximating them by eye.</li>
<li><strong>Web design from photography</strong> — building a site's color scheme around a hero photograph's actual dominant colors, so the palette and imagery feel cohesive rather than clashing.</li>
<li><strong>Print and packaging design</strong> — extracting precise color values needed for accurate reproduction across different printed materials.</li>
</ul>

<h2>How dominant color extraction works</h2>
<p>The tool analyzes every pixel in the uploaded image and groups similar colors together, identifying which color clusters occupy the largest proportion of the image — these become the "dominant" colors returned. This is meaningfully different from simply sampling a few individual pixels by eye, which can easily land on an unrepresentative outlier color rather than the image's actual overall visual character.</p>

<h2>Frequently asked questions</h2>
<p><strong>How many colors does the tool typically extract?</strong> Enough to give a genuinely useful palette — typically the handful of most visually dominant colors, which is usually all a real design palette needs.</p>
<p><strong>Is my image uploaded to a server for analysis?</strong> No — color extraction happens entirely locally in your browser using the Canvas API to read pixel data directly.</p>
<p><strong>Can I use extracted colors commercially?</strong> Yes — colors themselves aren't copyrightable; use any extracted palette freely, though be mindful of any separate copyright on the source image itself if it isn't yours.</p>
<p><strong>Why does the extracted palette not include a color I can clearly see in the image?</strong> The tool prioritizes the most visually dominant colors by pixel coverage; a color that's visually striking but covers a small area of the image may not surface as a top result.</p>
HTML
);

expand_tool($pdo, 'images-to-pdf-converter', <<<HTML
<p>Combining multiple images into a single, shareable PDF document — a scanned receipt, a set of reference photos, a batch of screenshots — is a task that traditionally required dedicated document software. This tool does it directly in your browser: select multiple images, arrange them in order, and download a single combined PDF, with nothing ever uploaded to a server.</p>

<h2>Why a PDF is often the right format for combining images</h2>
<p>A PDF preserves exact layout and print formatting reliably across every device and platform, unlike sharing a folder of loose image files, which can arrive out of order, inconsistently sized, or simply overwhelming to open one at a time. A single combined PDF is easier to share, easier to print correctly, and presents a more organized, professional result than a scattered set of individual image files attached to an email.</p>

<h2>How to use it</h2>
<ol>
<li>Select multiple images from your device.</li>
<li>Arrange them in the order you want them to appear in the final document.</li>
<li>Generate and download a single combined PDF, one image per page.</li>
</ol>

<h2>Common use cases</h2>
<ul>
<li><strong>Combining scanned or photographed documents</strong> — turning several photographed pages of a paper document into one organized, shareable PDF.</li>
<li><strong>Submitting receipts or expense documentation</strong> — combining multiple receipt photos into a single file for an expense report rather than attaching each one separately.</li>
<li><strong>Creating a simple portfolio or lookbook</strong> — assembling a set of images into a single, easy-to-browse document for sharing with a client or collaborator.</li>
<li><strong>Archiving photo sets</strong> — consolidating a related group of images into one file for long-term storage or organized record-keeping.</li>
</ul>

<h2>Tips for a cleaner result</h2>
<p>Arrange images in a logical order before generating the PDF, since reordering pages within a completed PDF afterward typically requires separate PDF-editing software this tool doesn't provide. If images vary significantly in size or orientation, be aware the resulting PDF pages will reflect those same inconsistencies — for the most polished result, consider resizing wildly mismatched images to a more consistent size first using a dedicated image resizer.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is there a limit to how many images I can combine?</strong> The practical limit depends on your device's available memory rather than the tool itself; most everyday document sets combine without issue.</p>
<p><strong>Are my images uploaded to a server?</strong> No — the entire process, from reading the images to assembling the final PDF, happens locally in your browser.</p>
<p><strong>Can I add text or annotations to the pages?</strong> This tool focuses specifically on combining images into a PDF; for adding text or annotations, a dedicated PDF editor would be the next step after generating the base document.</p>
<p><strong>Will image quality be reduced in the final PDF?</strong> The tool preserves image quality faithfully during conversion; extremely high-resolution images may result in a larger overall PDF file size as a natural tradeoff.</p>
HTML
);

expand_tool($pdo, 'speech-to-text', <<<HTML
<p>Typing out a long note, transcribing a recorded thought, or capturing spoken ideas while your hands are busy doing something else are all situations where speaking is simply faster and more natural than typing. This tool converts your spoken words into written text in real time, using your browser's built-in speech recognition, entirely on your own device.</p>

<h2>How browser-based speech recognition works</h2>
<p>Modern browsers include built-in speech recognition capability through the Web Speech API, which listens to audio from your microphone and converts it into text using on-device or browser-integrated recognition — no separate app installation and no dedicated hardware required beyond a working microphone. This tool provides a simple interface on top of that built-in capability, letting you dictate directly into a text field with live, real-time transcription.</p>

<h2>How to use it</h2>
<ol>
<li>Grant microphone access when prompted by your browser.</li>
<li>Press start and begin speaking naturally.</li>
<li>Watch your speech transcribed to text in real time, and copy the result when you're finished.</li>
</ol>

<h2>Getting more accurate transcription</h2>
<ul>
<li><strong>Speak at a natural, steady pace</strong> — neither rushing your words together nor speaking unnaturally slowly tends to produce the cleanest results.</li>
<li><strong>Minimize background noise</strong> — a quiet environment significantly improves recognition accuracy compared to a noisy room or an echo-heavy space.</li>
<li><strong>Speak punctuation explicitly if supported</strong> — saying "comma" or "period" aloud can insert actual punctuation, depending on your browser's specific recognition capabilities.</li>
<li><strong>Review and correct afterward</strong> — even accurate speech recognition occasionally mishears a word, particularly names or uncommon terms, so a quick proofread before using the final text is worthwhile.</li>
</ul>

<h2>Common use cases</h2>
<p>Drafting notes or emails hands-free, transcribing personal voice memos into searchable text, dictating a first draft faster than typing for people who think out loud more fluently than they type, and providing an accessible input method for anyone who finds typing difficult or uncomfortable are all situations where speech-to-text genuinely speeds up the process compared to typing everything by hand.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is my voice recording sent to a remote server?</strong> Processing relies on your browser's built-in speech recognition engine rather than a separate upload to this site's own servers.</p>
<p><strong>Why isn't the microphone working?</strong> Check that you've granted microphone permission to the page in your browser's settings — recognition can't function without that permission explicitly granted.</p>
<p><strong>Does this work in every language?</strong> Language support depends on your browser and device's built-in speech recognition capabilities, which typically cover a wide range of major languages.</p>
<p><strong>Can I edit the transcribed text afterward?</strong> Yes — the transcribed text appears in a normal editable text field, so you can correct any misheard words directly before copying it.</p>
HTML
);

expand_tool($pdo, 'robots-txt-generator', <<<HTML
<p>A robots.txt file tells search engine crawlers which parts of your site they're allowed to access and index — a small, plain-text file with an outsized effect on how (and whether) your site gets crawled correctly. This generator builds a properly formatted robots.txt file through a simple visual interface, without requiring you to memorize the exact directive syntax by hand.</p>

<h2>Why a correct robots.txt matters</h2>
<p>A robots.txt file with a mistake — a rule that's too broad, a syntax error, an accidentally disallowed important page — can actively prevent search engines from indexing content you want found, sometimes silently, with no obvious error message alerting you that anything is wrong. Getting this file right the first time avoids a slow, quiet loss of search visibility that can go unnoticed for weeks before anyone realizes traffic has been affected.</p>

<h2>How to use it</h2>
<ol>
<li>Specify which paths or sections of your site should be disallowed from crawling, if any.</li>
<li>Add any crawler-specific rules you need — allowing or blocking specific bots by name.</li>
<li>Include your sitemap location for crawlers to discover automatically.</li>
<li>Copy the generated file and upload it to your site's root directory as <code>robots.txt</code>.</li>
</ol>

<h2>Common robots.txt directives explained</h2>
<ul>
<li><strong>User-agent</strong> — specifies which crawler a rule applies to; an asterisk applies the rule to every crawler by default.</li>
<li><strong>Disallow</strong> — blocks a crawler from accessing a specific path, commonly used for admin areas, checkout flows, or duplicate content you don't want indexed.</li>
<li><strong>Allow</strong> — explicitly permits access to a path, useful for carving out an exception within a broader disallowed section.</li>
<li><strong>Sitemap</strong> — points crawlers directly to your sitemap file, helping them discover your site's full structure efficiently.</li>
</ul>

<h2>A common misconception worth clearing up</h2>
<p>Robots.txt is a set of instructions crawlers are expected to respect voluntarily — it is not a security mechanism, and it doesn't actually prevent access to a disallowed page, it only asks well-behaved crawlers not to index it. Anything genuinely sensitive should be protected with real authentication or access controls, not hidden behind a robots.txt disallow rule alone, since a disallowed path is still technically reachable by anyone with the direct URL.</p>

<h2>Frequently asked questions</h2>
<p><strong>Where does the robots.txt file need to be placed?</strong> Directly in your site's root directory, accessible at exactly <code>yoursite.com/robots.txt</code> — crawlers look for it at that specific location and nowhere else.</p>
<p><strong>Does disallowing a page in robots.txt guarantee it won't appear in search results?</strong> Not entirely — a disallowed page can sometimes still appear in search results (without its content indexed) if other sites link to it; for guaranteed exclusion, a noindex meta tag is the more reliable tool.</p>
<p><strong>Is my configuration data sent anywhere?</strong> No — the file is generated entirely locally in your browser.</p>
<p><strong>Can different rules apply to different search engines?</strong> Yes — specifying a particular crawler's user-agent name lets you apply different rules to different bots rather than one blanket rule for all of them.</p>
HTML
);

expand_tool($pdo, 'meta-tag-generator', <<<HTML
<p>Meta tags — the title, description, and social preview information embedded in a page's HTML head — directly shape how a page appears in search results and when shared on social media, yet they're easy to forget, get wrong, or leave at a generic default. This generator builds a complete, correctly formatted set of meta tags from a few simple inputs, ready to paste directly into your page's HTML.</p>

<h2>Why meta tags matter beyond just SEO</h2>
<p>A missing or poorly written title tag and meta description directly hurts both search ranking signals and, just as importantly, click-through rate from anyone who does see the page in results — a vague or missing description often gets replaced by an awkward, auto-extracted snippet of body text instead. Social preview tags (Open Graph and Twitter Card metadata) control how a shared link looks on platforms like Facebook, Twitter/X, and LinkedIn — without them, a shared link often displays with no image, no title, and a raw URL, which measurably reduces click-through when people share your content.</p>

<h2>How to use it</h2>
<ol>
<li>Enter your page's title, description, and canonical URL.</li>
<li>Add a preview image URL for social sharing.</li>
<li>The tool generates a complete, correctly formatted set of meta tags — including Open Graph and Twitter Card tags — ready to copy directly into your page's <code>&lt;head&gt;</code>.</li>
</ol>

<h2>The core meta tags every page should have</h2>
<ul>
<li><strong>Title tag</strong> — the single most important on-page SEO element, shown as the clickable headline in search results.</li>
<li><strong>Meta description</strong> — the summary text shown beneath the title in search results, directly influencing click-through rate.</li>
<li><strong>Canonical URL</strong> — tells search engines the authoritative version of a page, important for avoiding duplicate content issues.</li>
<li><strong>Open Graph tags</strong> — control how the page appears when shared on Facebook, LinkedIn, and other platforms that support the Open Graph protocol.</li>
<li><strong>Twitter Card tags</strong> — control the specific preview appearance when a link is shared on Twitter/X.</li>
</ul>

<h2>Writing meta tags that actually perform well</h2>
<p>Keep titles and descriptions within their effective display limits to avoid awkward truncation in search results, write genuinely specific, compelling copy rather than generic filler, and always include a properly sized preview image for social sharing tags — a missing or wrong-sized image is one of the most common reasons a shared link looks broken or unprofessional on social platforms.</p>

<h2>Frequently asked questions</h2>
<p><strong>Do I need both Open Graph and Twitter Card tags, or just one?</strong> Include both — most platforms support Open Graph, but Twitter/X specifically favors its own Twitter Card format, and having both ensures consistent previews everywhere a link might be shared.</p>
<p><strong>Where exactly do these tags go in my HTML?</strong> Inside the page's <code>&lt;head&gt;</code> section, alongside other document metadata.</p>
<p><strong>Is my page data sent anywhere?</strong> No — tags are generated entirely locally in your browser from the information you provide.</p>
<p><strong>How do I check that my meta tags are actually working correctly?</strong> Most major social platforms offer a free debugging or preview tool that shows exactly how a shared link will render, which is the most reliable way to confirm your tags display correctly.</p>
HTML
);

}
