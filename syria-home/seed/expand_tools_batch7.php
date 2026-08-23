<?php
/** Long-form expansion for tool full_description fields. See includes/content_expansion.php. */
function expand_tools_batch7(PDO $pdo): void {

expand_tool($pdo, 'uuid-id-generator', <<<HTML
<p>A UUID (Universally Unique Identifier) is a 128-bit value designed to be, for all practical purposes, guaranteed unique across every system, database, and application in existence — no coordination between systems required. This generator produces standards-compliant UUIDs instantly, along with other common ID formats, entirely in your browser.</p>

<h2>Why UUIDs are used instead of simple incrementing numbers</h2>
<p>A traditional auto-incrementing ID (1, 2, 3...) only guarantees uniqueness within a single table or system — combine data from two separate databases and those IDs collide immediately. A UUID's uniqueness holds across every system simultaneously, because the sheer size of the possible value space (over 5 undecillion possible values) makes an accidental collision astronomically unlikely, even generated independently by millions of different systems with no communication between them.</p>

<h2>How to use it</h2>
<ol>
<li>Generate a new UUID instantly with one click.</li>
<li>Choose the UUID version if you need a specific type (most common use cases want the standard random version 4).</li>
<li>Generate as many as you need in a batch, and copy them directly.</li>
</ol>

<h2>Common real-world uses</h2>
<ul>
<li><strong>Database primary keys</strong> — using a UUID instead of an auto-incrementing integer avoids collisions when merging data from multiple sources or systems.</li>
<li><strong>API request tracking</strong> — assigning a unique request ID to every API call, useful for tracing a specific request through logs across multiple services.</li>
<li><strong>Session and token identifiers</strong> — generating a unique, hard-to-guess identifier for a user session or temporary access token.</li>
<li><strong>File and resource naming</strong> — avoiding filename collisions when multiple uploads or generated files need guaranteed-unique names.</li>
</ul>

<h2>UUID versions and what they mean</h2>
<p>Version 4 is the most commonly used and generally the right default — it's generated from random or pseudo-random numbers and carries no embedded information beyond its own uniqueness. Version 1 embeds a timestamp and information derived from the generating device, which some systems use specifically because that embedded ordering is useful, though it can leak more information than a pure random UUID would.</p>

<h2>Frequently asked questions</h2>
<p><strong>Can two UUIDs actually collide?</strong> Theoretically possible but so astronomically unlikely for random (version 4) UUIDs that it's treated as effectively impossible in real-world system design.</p>
<p><strong>Is a UUID the same as a hash?</strong> No — a UUID is generated fresh with no relationship to any input data, while a hash is deterministically derived from specific input; generating a UUID twice never produces the same value, while hashing the same input twice always does.</p>
<p><strong>Is my generated UUID sent anywhere?</strong> No — generation happens entirely locally in your browser using a cryptographically secure random source.</p>
<p><strong>Which UUID version should I use for a new project?</strong> Version 4 (random) is the standard, safe default for the vast majority of use cases unless you have a specific reason to need embedded ordering information.</p>
HTML
);

expand_tool($pdo, 'google-serp-preview', <<<HTML
<p>How a page's title and description actually appear in a Google search result — with real character-limit truncation, real font rendering, and the real visual layout searchers see — is often surprisingly different from how it looks while editing it in a CMS text field. This tool renders an accurate live preview of exactly how your title tag and meta description will appear in Google search results, entirely in your browser.</p>

<h2>Why previewing matters more than most people expect</h2>
<p>Google truncates titles and descriptions that exceed a certain pixel width, not simply a character count, which means the effective cutoff point varies depending on which specific characters are used — wide characters like "W" and "M" eat up more space than narrow ones like "i" and "l." Writing a title tag without checking how it actually renders means real risk of it getting cut off mid-word with an ellipsis in a way that looks unfinished and unprofessional to anyone scanning search results.</p>

<h2>How to use it</h2>
<ol>
<li>Enter your intended page title, URL, and meta description.</li>
<li>See an accurate live preview rendered exactly as it would appear in an actual Google search result.</li>
<li>Adjust your copy until it fits cleanly without truncation, then use the finalized version on your live page.</li>
</ol>

<h2>Writing titles and descriptions that actually work</h2>
<ul>
<li><strong>Front-load the most important information</strong> — put your primary keyword and value proposition early in the title, since that's what's most likely to remain visible even if truncation does occur.</li>
<li><strong>Write a description that earns the click</strong> — Google sometimes overrides your meta description with its own extracted snippet if yours doesn't seem relevant to a specific search query, so writing genuinely useful, specific copy matters, not just fitting the length limit.</li>
<li><strong>Avoid keyword stuffing</strong> — a title crammed with repeated keywords reads as spammy to both searchers and Google's ranking systems, actively hurting rather than helping click-through rate.</li>
<li><strong>Match search intent</strong> — a title and description that clearly signal what the page actually delivers earns more clicks than a vague, clickbait-adjacent one, even at the same search ranking position.</li>
</ul>

<h2>How this fits into a broader SEO workflow</h2>
<p>A page can rank well and still underperform on actual traffic if its title and description don't compel a click once the page appears in results — click-through rate is itself a signal search engines pay attention to over time. Previewing and refining how a page will actually look in results, before publishing, is a fast, low-effort step that meaningfully affects real-world traffic beyond ranking position alone.</p>

<h2>Frequently asked questions</h2>
<p><strong>Why does my title get cut off even though it's under the recommended character count?</strong> Because Google truncates by rendered pixel width, not character count — titles using wider characters can truncate sooner than the same character count using narrower ones.</p>
<p><strong>Does Google always show the meta description I write?</strong> Not always — Google sometimes substitutes its own extracted snippet if it judges another portion of the page more relevant to a specific search query.</p>
<p><strong>Is my content uploaded anywhere?</strong> No — the preview renders entirely locally in your browser.</p>
<p><strong>Does a better SERP preview directly improve ranking?</strong> Not ranking position directly, but it improves click-through rate at whatever position you do rank, which is itself a meaningful factor in overall search performance.</p>
HTML
);

expand_tool($pdo, 'readability-analyzer', <<<HTML
<p>Writing that reads as effortless to a reader is very often the product of deliberate simplification — shorter sentences, more common words, clearer structure — rather than a first draft that simply came out that way. This analyzer scores any text's readability instantly, translating that score into an approximate reading-grade level, and highlights specific sentences that are dragging the overall score down, entirely in your browser.</p>

<h2>Why readability matters for real content, not just academic writing</h2>
<p>Content that's harder to read than necessary loses readers before they finish it, regardless of how good the underlying ideas are — a visitor skimming a blog post or product description on their phone, often distracted, simply won't push through a dense, complex paragraph the way a captive audience reading a printed textbook might. For most public-facing web content — articles, marketing copy, instructions — aiming for a readability level comparable to a well-written newspaper article reaches the broadest possible audience without dumbing anything down.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your text into the input.</li>
<li>See an instant readability score, translated into an approximate grade level.</li>
<li>Review specific sentences flagged as overly long or complex, and revise them directly.</li>
</ol>

<h2>What actually drives a readability score down</h2>
<ul>
<li><strong>Long sentences</strong> — sentences packed with multiple clauses are harder to parse than several shorter, clearer ones covering the same ground.</li>
<li><strong>Complex, multi-syllable words</strong> — where a simpler, equally accurate word exists, using it generally improves readability without losing precision.</li>
<li><strong>Passive voice overuse</strong> — passive constructions tend to read as more distant and harder to follow than direct, active phrasing.</li>
<li><strong>Dense paragraphs with no visual breaks</strong> — while not part of the numeric readability formula itself, long unbroken paragraphs make even well-written text feel harder to approach.</li>
</ul>

<h2>Readability isn't about writing down to your audience</h2>
<p>Clear, simple writing is not the same as simplistic or unsophisticated writing — some of the most effective, respected writing in any field is also the clearest, precisely because clarity respects a reader's time rather than making them work harder than necessary to extract meaning. Improving a readability score is almost always about tightening and clarifying existing ideas, not diluting them.</p>

<h2>Frequently asked questions</h2>
<p><strong>What readability score should I be aiming for?</strong> For general audience web content, a score comparable to a widely-read newspaper article is a reliable, broadly-applicable target; more specialized or technical audiences can tolerate a somewhat higher complexity level.</p>
<p><strong>Does a lower readability score mean better writing?</strong> Not automatically — some technical or specialized content genuinely requires more complex language; the goal is matching complexity to your actual audience, not minimizing it universally.</p>
<p><strong>Is my text uploaded anywhere?</strong> No — analysis happens entirely locally in your browser.</p>
<p><strong>Which readability formula does this use?</strong> It's based on established, widely-used readability formulas that weigh sentence length and word complexity, translated into an approximate reading-grade level for practical interpretation.</p>
HTML
);

expand_tool($pdo, 'keyword-density-analyzer', <<<HTML
<p>Keyword density — how frequently a specific word or phrase appears relative to a page's total word count — was once treated as a precise SEO target to hit, though modern search engines have moved well beyond simple frequency counting toward genuinely understanding topical relevance and context. This analyzer instantly breaks down word and phrase frequency across any text, helping you spot both under-optimization and the kind of unnatural repetition that reads as spammy.</p>

<h2>Why keyword density still matters, just differently than it used to</h2>
<p>Modern search engines evaluate topical relevance using far more sophisticated methods than simple keyword counting, which means chasing an exact target percentage is outdated advice. What still matters is making sure your target topic and its natural variations actually appear enough for both search engines and human readers to clearly understand what the content is about — while avoiding the opposite failure mode of repeating a phrase so often that it reads unnaturally and actively damages the reading experience.</p>

<h2>How to use it</h2>
<ol>
<li>Paste your content into the input area.</li>
<li>The tool analyzes word and phrase frequency automatically, showing which terms appear most often and at what percentage.</li>
<li>Review the results for both under-optimization (a target topic barely mentioned) and over-optimization (unnatural repetition).</li>
</ol>

<h2>Reading the results usefully</h2>
<ul>
<li><strong>Single words versus phrases</strong> — checking frequency for both individual keywords and multi-word phrases gives a fuller picture than single-word analysis alone.</li>
<li><strong>Natural variation matters</strong> — a page that uses only one exact phrase repeatedly, rather than natural synonyms and related terms, reads as unnatural to both readers and increasingly sophisticated search algorithms.</li>
<li><strong>Context over raw count</strong> — a term appearing fewer times but in clearly relevant, well-placed positions (headings, opening paragraph) generally matters more than raw frequency alone.</li>
</ul>

<h2>The real goal: relevance, not a target percentage</h2>
<p>Rather than chasing a specific keyword density percentage, use this analysis to sanity-check that your content clearly and naturally covers its intended topic — with enough genuine mentions and related terminology that both a human reader and a search engine immediately understand what the page is about, without the repetition ever feeling forced or robotic to a reader scanning the actual sentences.</p>

<h2>Frequently asked questions</h2>
<p><strong>Is there an ideal keyword density percentage to target?</strong> Modern SEO guidance has moved away from a fixed target percentage — focus on natural, relevant coverage of your topic instead of hitting a specific number.</p>
<p><strong>Can too much keyword repetition actually hurt rankings?</strong> Yes — search engines can flag unnaturally repetitive content as an attempt to manipulate rankings, which can hurt rather than help visibility.</p>
<p><strong>Is my content uploaded anywhere?</strong> No — analysis happens entirely locally in your browser.</p>
<p><strong>Should I analyze competitor content too?</strong> Comparing your keyword coverage against genuinely well-ranking competitor content can highlight topics or terms you may have under-covered.</p>
HTML
);

expand_tool($pdo, 'css-gradient-generator', <<<HTML
<p>A well-designed gradient can add real visual depth and interest to a background, button, or hero section — but hand-writing the exact CSS syntax for a smooth, well-balanced multi-color gradient, with the right angle and color stops, is fiddly to get right through trial and error alone. This generator lets you build a gradient visually and copies out clean, ready-to-use CSS instantly.</p>

<h2>Linear versus radial gradients</h2>
<p>A linear gradient transitions between colors along a straight line at an angle you control, the most common choice for backgrounds, buttons, and banners. A radial gradient transitions outward from a center point in a circular or elliptical pattern, useful for spotlight effects, subtle depth on cards, or a soft glow behind a focal element. Choosing the right type for the effect you're after is usually obvious once you see both rendered side by side rather than trying to picture the difference abstractly.</p>

<h2>How to use it</h2>
<ol>
<li>Choose a gradient type — linear or radial.</li>
<li>Add and adjust color stops, controlling each color's position within the gradient.</li>
<li>For linear gradients, set the angle; for radial, adjust the shape and center point.</li>
<li>Copy the generated CSS directly into your stylesheet.</li>
</ol>

<h2>Design principles for gradients that look intentional</h2>
<ul>
<li><strong>Fewer, well-chosen colors beat many</strong> — a gradient between two or three carefully chosen colors generally looks more polished than one crowded with too many competing stops.</li>
<li><strong>Adjacent colors on the color wheel blend more smoothly</strong> — colors that are close together produce a subtler, more elegant transition than colors from opposite ends of the spectrum, which can look jarring or muddy in the middle of the transition.</li>
<li><strong>Consider the angle deliberately</strong> — a diagonal gradient often reads as more dynamic and modern than a straight horizontal or vertical one, though the right choice depends entirely on the specific design context.</li>
<li><strong>Test against real content</strong> — a gradient that looks great as an empty background can interact poorly with text or icons placed on top of it; always preview with real content layered on.</li>
</ul>

<h2>Where gradients are used effectively</h2>
<p>Hero section backgrounds, call-to-action buttons that need to stand out visually, card and panel backgrounds for subtle depth, and brand-specific design elements that reinforce a visual identity across a site are all common, effective applications — used deliberately and sparingly, a gradient adds polish; overused across every element on a page, it quickly looks dated and busy.</p>

<h2>Frequently asked questions</h2>
<p><strong>Does the generated CSS work across all modern browsers?</strong> Yes — the standard <code>linear-gradient()</code> and <code>radial-gradient()</code> CSS functions are well supported across every current major browser.</p>
<p><strong>Can I use more than two colors in a single gradient?</strong> Yes — add as many color stops as you like, each positioned wherever you want along the gradient.</p>
<p><strong>Is my design data saved anywhere?</strong> No — the generator builds the CSS instantly and locally; nothing is stored or transmitted.</p>
<p><strong>Can I animate a CSS gradient?</strong> Gradients themselves aren't natively animatable in all browsers, though animating a gradient's background position or transitioning between two gradient backgrounds are both common workarounds.</p>
HTML
);

}
