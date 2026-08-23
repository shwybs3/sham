<?php
/**
 * Seeds the "pro" tool set — twenty tools whose core function is normally
 * sold as a paid product or subscription, reimplemented from scratch and
 * given away. Each row records what paid workflow it covers in `replaces`,
 * which the public tool page shows as a badge.
 *
 * Safe to re-run: INSERT IGNORE on the unique slug means existing rows and
 * any edits made in the admin are left untouched.
 */
function seed_pro_tools(PDO $pdo): void {
    $catId = function (string $slug) use ($pdo): ?int {
        $s = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $s->execute([$slug]);
        $r = $s->fetchColumn();
        return $r ? (int)$r : null;
    };
    $img  = $catId('image-tools');
    $txt  = $catId('text-tools');
    $gen  = $catId('generators');
    $conv = $catId('converters');

    /* [tool_key, name, category, icon, replaces, short, full, metaTitle, metaDesc, metaKeywords] */
    $tools = [
        ['bg_remover', 'Background Remover', $img, 'fa-scissors',
            'Paid per-image background removal services',
            'Erase the background from a photo and download a transparent PNG — free, unlimited, and processed entirely on your device.',
            '<h2>Transparent backgrounds without the per-image bill</h2><p>Most background removers charge per image or lock the full-resolution download behind a subscription. This one does the job in your browser and never asks for a card.</p>'
            . '<h3>How it works</h3><p>The tool samples the colour at the edges of your image and flood-fills inward, clearing every pixel within the tolerance you set. That approach is extremely effective on the images people most often need cut out: product shots on white, logos on flat colour, headshots against a studio backdrop, screenshots and scanned documents.</p>'
            . '<h3>Getting a clean edge</h3><p>Start with the default tolerance of 32. If parts of the background survive, raise it. If your subject starts disappearing, lower it. The edge softness slider feathers the cut so the result does not look like it was stamped out with scissors.</p>'
            . '<h3>What it will not do</h3><p>Being honest about the limits: this is not a machine-learning matting model, so it will not separate wind-blown hair from a cluttered street scene. For busy or gradient backgrounds you will get a better result by shooting against a plain backdrop first. For the flat-background case — which is the overwhelming majority of e-commerce and design work — the output is genuinely comparable to the paid services.</p>'
            . '<h3>Your files stay yours</h3><p>Nothing is uploaded. The image is decoded into a canvas in your own browser, processed there, and handed back to you. There is no server that could retain a copy.</p>',
            'Free Background Remover — Transparent PNG, No Upload',
            'Remove image backgrounds free and download a transparent PNG. Runs entirely in your browser — no uploads, no watermarks, no sign-up, no per-image fee.',
            'background remover, remove background from image, transparent png, free background eraser, cut out image'],

        ['image_resizer', 'Bulk Image Resizer', $img, 'fa-expand',
            'Paid bulk image resizing suites',
            'Resize and re-encode as many images as you like at once, with presets for every common social and web size.',
            '<h2>Resize a whole folder in one pass</h2><p>Select multiple files and every one is resized, re-encoded and ready to download. There is no cap on how many images you process and no watermark on the output.</p>'
            . '<h3>Presets that match where the image is going</h3><p>Full HD, social share cards at 1200×630, square posts, stories, product thumbnails and avatars are all one click away. Pick a preset or type exact dimensions, and keep the aspect ratio locked so nothing is stretched.</p>'
            . '<h3>Choose the format that actually helps</h3><p>WebP will usually give you the smallest file at a quality indistinguishable from the original, which is why it is the default. JPEG remains the safest choice for older email clients, and PNG is there for anything needing transparency. The size saving is shown per file, so you can see exactly what each setting bought you.</p>'
            . '<h3>Why file size is worth the effort</h3><p>Oversized images are the single most common cause of slow pages. Serving a 4000-pixel photo into a 400-pixel slot forces every visitor to download roughly a hundred times more data than they can see, and it is measured directly in your Core Web Vitals.</p>',
            'Free Bulk Image Resizer — Batch Resize Online',
            'Resize multiple images at once for free with presets for social, web and product sizes. Converts to WebP, JPEG or PNG in your browser — nothing is uploaded.',
            'bulk image resizer, batch resize images, resize photos online, image resizer free, webp converter'],

        ['watermark', 'Watermark Adder', $img, 'fa-stamp',
            'Watermarking apps with paid tiers',
            'Stamp your name across a photo before you share it — corner, centre or tiled, with full control over size and opacity.',
            '<h2>Mark your work before it travels</h2><p>Once an image is on the open web it will be reposted, and a visible watermark is the cheapest way to keep your name attached to it. This tool adds one in a few seconds without the export limits that free tiers of watermarking apps impose.</p>'
            . '<h3>Placement matters</h3><p>A corner mark is unobtrusive and easy to crop out. A tiled watermark running diagonally across the whole frame is very hard to remove without visibly damaging the image, which is why photographers use it for proofs and previews. Both are one dropdown away here.</p>'
            . '<h3>Finding the right weight</h3><p>Around 40–50% opacity is usually the sweet spot: clearly legible when you look for it, quiet enough not to fight the photograph. Pick a colour that contrasts with the area it sits on — white over dark images, dark over light ones.</p>'
            . '<h3>Private by construction</h3><p>The image never leaves your device, which matters when you are watermarking client work or unpublished material.</p>',
            'Free Watermark Adder — Add Text Watermarks to Photos',
            'Add a text watermark to any image free. Corner, centre or tiled placement with adjustable size, colour and opacity. Processed in your browser, no upload.',
            'add watermark to photo, watermark maker, free watermark tool, protect photos online, image watermark'],

        ['exif_viewer', 'EXIF Viewer & Remover', $img, 'fa-camera-retro',
            'Paid metadata scrubbing utilities',
            'See the hidden data your photos carry — including GPS coordinates — and download a stripped copy.',
            '<h2>Your photos know where you were</h2><p>Every photo taken on a phone carries an EXIF block, and on most default settings that block includes the exact latitude and longitude where the shutter fired. It also typically records the camera model, the lens, the date and time, and sometimes the camera body serial number.</p>'
            . '<h3>Why that matters</h3><p>People routinely post photos taken at home without realising the file names their street. Large social platforms strip metadata on upload, but files sent by email, messaging apps in document mode, cloud links and direct downloads usually keep it intact.</p>'
            . '<h3>Read it, then remove it</h3><p>Drop a JPEG in and the tool parses the EXIF block directly and lists what it finds, flagging GPS data prominently. One button then re-encodes the image without any metadata segment, giving you a clean copy to share.</p>'
            . '<h3>Done locally, as it must be</h3><p>A tool that asks you to upload a photo in order to check whether it leaks your location has rather missed the point. Parsing here happens in your browser and the file is never transmitted.</p>',
            'Free EXIF Viewer & Remover — Check Photo GPS Data',
            'View hidden EXIF metadata in your photos, including GPS location, and download a copy with all metadata stripped. Runs locally in your browser.',
            'exif viewer, remove exif data, photo metadata remover, check photo gps, strip image metadata'],

        ['favicon_generator', 'Favicon Generator', $gen, 'fa-icons',
            'Favicon services with paid downloads',
            'Turn one logo into every favicon size a modern site needs, with the HTML to paste into your head.',
            '<h2>Ten sizes from one upload</h2><p>Browsers, phones and app launchers all want a different icon size. This generates the full set — 16 through 512 pixels — plus the link tags to wire them up.</p>'
            . '<h3>Which sizes actually get used</h3><p>16×16 shows in browser tabs and bookmarks. 32×32 is used by most desktop browsers on high-density displays. 180×180 is what iOS uses for a home-screen shortcut. 192 and 512 are what Android and installable web apps expect in a manifest.</p>'
            . '<h3>Design notes</h3><p>Whatever reads clearly at 16 pixels is your real constraint. Detailed logos turn to mush at that size, so most brands use a simplified mark rather than the full wordmark. Add a background colour if your logo is a transparent PNG that would vanish against a dark tab bar, and use the corner radius slider if you want a rounded app-style icon.</p>',
            'Free Favicon Generator — All Sizes + HTML',
            'Generate every favicon size from one image free, with the HTML link tags ready to paste. Includes 16px to 512px, Apple touch icon and manifest sizes.',
            'favicon generator, create favicon, favicon from image, apple touch icon, site icon generator'],

        ['regex_tester', 'Regex Tester & Debugger', $txt, 'fa-asterisk',
            'Regex playgrounds with paid plans',
            'Write a regular expression and watch every match highlight live, with capture groups broken out per match.',
            '<h2>See what your pattern actually matches</h2><p>Regular expressions fail in ways that are almost impossible to reason about by staring at them. Highlighting every match against real test text turns a guessing game into something you can debug.</p>'
            . '<h3>What you get</h3><p>Matches highlight in place as you type. Below that, a table lists each match with its position in the string and every capture group broken out and numbered, so you can confirm the groups line up with what your code expects to read. Invalid patterns show the engine\'s own error message rather than silently failing.</p>'
            . '<h3>Flags</h3><p>Set flags in their own field: <code>g</code> to find every match rather than just the first, <code>i</code> for case-insensitive, <code>m</code> to make anchors match at line breaks, <code>s</code> to let a dot match newlines, and <code>u</code> for full Unicode handling.</p>'
            . '<h3>A note on engines</h3><p>This runs the JavaScript regex engine. The core syntax is shared across languages, but lookbehind, named groups and Unicode property escapes vary in support, so verify anything exotic in your target language before shipping it.</p>',
            'Free Regex Tester & Debugger — Live Match Highlighting',
            'Test regular expressions online free with live match highlighting, capture groups and clear error messages. Runs entirely in your browser.',
            'regex tester, regular expression tester, regex debugger, test regex online, regex101 alternative'],

        ['diff_checker', 'Text Diff Checker', $txt, 'fa-code-compare',
            'Subscription diff tools',
            'Compare two blocks of text and see exactly which lines were added, removed or left alone.',
            '<h2>Find the one line that changed</h2><p>Paste an original and a revised version and get a colour-coded, line-by-line comparison: green for additions, red for removals, grey for everything untouched.</p>'
            . '<h3>A real diff, not a guess</h3><p>This uses longest-common-subsequence matching — the same class of algorithm behind version-control diffs. That is what lets it recognise that a block was inserted in the middle rather than reporting every subsequent line as changed, which is where naive line-by-line comparison falls apart.</p>'
            . '<h3>Where it earns its keep</h3><p>Comparing contract revisions, checking what an editor changed in your draft, spotting the difference between a working and a broken config file, or reviewing an AI-rewritten passage against your original.</p>'
            . '<h3>Confidential by default</h3><p>Contracts and config files are exactly the sort of thing you should not paste into a random web service. Nothing here is transmitted; the comparison runs in your browser.</p>',
            'Free Text Diff Checker — Compare Two Texts Online',
            'Compare two texts free and see added, removed and unchanged lines highlighted. Real LCS diff algorithm, runs in your browser, nothing uploaded.',
            'diff checker, compare text online, text comparison tool, find differences between texts, free diff tool'],

        ['jwt_decoder', 'JWT Decoder', $txt, 'fa-user-lock',
            'Paid API debugging suites',
            'Decode a JSON Web Token to read its header and payload, and check whether it has expired.',
            '<h2>Read what is inside the token</h2><p>Paste a JWT and see its header and payload as formatted JSON, with the expiry checked against the current time and shown as a clear valid-or-expired banner.</p>'
            . '<h3>A JWT is not encrypted</h3><p>This is the single most misunderstood thing about JWTs. The header and payload are base64url-encoded, not encrypted — anyone holding the token can read every claim inside it. The signature proves the token has not been tampered with; it does not hide anything. Never put a password, a card number or anything else sensitive in a JWT payload.</p>'
            . '<h3>Why this tool cannot verify the signature</h3><p>Verification requires the signing secret or public key. That value belongs on your server and should never be pasted into a web page, so no browser-based decoder can honestly check it. This tool decodes and inspects; verification stays in your backend where it belongs.</p>'
            . '<h3>Handle production tokens carefully</h3><p>Decoding happens locally here and nothing is transmitted. Even so, a live token is a live credential — the safe habit is to test with expired or staging tokens.</p>',
            'Free JWT Decoder — Decode and Inspect JSON Web Tokens',
            'Decode JWT tokens online free. Read the header and payload, check expiry, all locally in your browser — no token is ever transmitted.',
            'jwt decoder, decode jwt, json web token decoder, jwt debugger, inspect jwt'],

        ['sql_formatter', 'SQL Formatter', $txt, 'fa-database',
            'Paid SQL IDE formatting',
            'Turn a single-line query into properly indented, readable SQL with consistent keyword casing.',
            '<h2>Make the query readable</h2><p>Queries copied out of logs and ORMs arrive as one long line. This breaks them onto sensible lines, indents the clauses, and normalises keyword casing so the structure is visible at a glance.</p>'
            . '<h3>How it formats</h3><p>Major clauses each start a new line. Joins get their own line so the shape of the query is obvious. Conditions joined by AND and OR are indented under their clause. Select lists break one column per line, which makes a missing comma easy to spot.</p>'
            . '<h3>Casing</h3><p>Uppercase keywords against lowercase identifiers is the most widely used convention and the default here, because it makes the query\'s skeleton pop out from the table and column names. Switch to lowercase if your team\'s style guide says otherwise. String literals are left exactly as written.</p>',
            'Free SQL Formatter — Beautify SQL Queries Online',
            'Format and beautify SQL queries free with proper indentation and keyword casing. Runs in your browser, nothing uploaded.',
            'sql formatter, beautify sql, format sql online, sql pretty print, sql beautifier'],

        ['cron_builder', 'Cron Expression Builder', $gen, 'fa-calendar-check',
            'Paid scheduling dashboards',
            'Write a cron expression and get a plain-English description plus the next five times it will actually fire.',
            '<h2>Stop guessing what the asterisks mean</h2><p>Cron syntax is compact and easy to get subtly wrong. Type an expression and this tells you in plain English when it runs, then proves it by calculating the next five firing times against your local clock.</p>'
            . '<h3>The five fields</h3><p>In order: minute, hour, day of month, month, day of week. An asterisk means every value. <code>*/15</code> means every fifteenth. <code>1-5</code> is a range — in the day-of-week field that is Monday through Friday. Commas list specific values.</p>'
            . '<h3>The classic mistake</h3><p>Setting both day-of-month and day-of-week. Most cron implementations treat those as OR rather than AND, so <code>0 0 1 * 1</code> fires on the first of the month <em>and</em> every Monday, which is almost never what people intend. The next-runs list makes this kind of error immediately obvious.</p>'
            . '<h3>Time zones</h3><p>The preview uses your browser\'s local time zone. Servers very often run in UTC, so check what your scheduler is set to before trusting a wall-clock time.</p>',
            'Free Cron Expression Builder — Generator and Explainer',
            'Build and understand cron expressions free. Plain-English descriptions plus the next five run times calculated live. Includes common presets.',
            'cron expression generator, crontab builder, cron schedule explained, cron syntax helper, crontab guru alternative'],

        ['uuid_generator', 'UUID & ID Generator', $gen, 'fa-hashtag',
            'Paid developer toolbelts',
            'Generate UUID v4, ULID, Nano ID or short IDs in bulk using cryptographically secure randomness.',
            '<h2>Four ID formats, up to 500 at a time</h2><p>All generated with <code>crypto.getRandomValues</code>, the browser\'s cryptographically secure random source — not <code>Math.random()</code>, which is predictable and must never be used for identifiers that matter.</p>'
            . '<h3>Picking the right format</h3><p><strong>UUID v4</strong> is the universal default: 122 random bits, understood by every database and language. <strong>ULID</strong> encodes a timestamp in its leading characters, so IDs sort chronologically as plain strings — which keeps database index inserts sequential instead of scattering them, a meaningful performance difference at scale. <strong>Nano ID</strong> packs comparable collision resistance into 21 URL-safe characters. <strong>Short IDs</strong> are eight characters, fine for a URL slug or coupon code but far too small for anything security-sensitive.</p>'
            . '<h3>On collisions</h3><p>A v4 UUID has 122 bits of entropy. You would need to generate billions per second for decades before a collision became likely. For all realistic purposes they are unique without any coordination between machines.</p>',
            'Free UUID Generator — UUID v4, ULID and Nano ID',
            'Generate UUID v4, ULID, Nano ID and short IDs in bulk, free. Cryptographically secure randomness, up to 500 at once, copy all with one click.',
            'uuid generator, guid generator, ulid generator, nano id, random id generator'],

        ['serp_preview', 'Google SERP Preview', $gen, 'fa-magnifying-glass-location',
            'SEO suites charging monthly for SERP preview',
            'See how your page will look in Google results, with real pixel-width measurement for desktop and mobile.',
            '<h2>Preview the listing before you publish</h2><p>Your title and description are the advert for your page. This shows exactly how they will render in a Google result, including where they get cut off.</p>'
            . '<h3>Pixels, not characters</h3><p>This is the detail most character-counting tools get wrong. Google truncates by rendered width, not character count. A title in capitals or full of wide letters runs out of room far sooner than the same number of narrow lowercase characters. This tool measures the actual rendered width of your text and shows it against Google\'s real limits — roughly 600 pixels for desktop titles and about 920 on mobile.</p>'
            . '<h3>Writing a title that earns the click</h3><p>Put the term people actually search at the front, where it survives truncation and gets bolded in the result. Keep the brand name at the end. Say what the page gives them rather than describing what it is.</p>'
            . '<h3>About descriptions</h3><p>Google rewrites meta descriptions for the majority of queries, pulling whatever passage best matches what was searched. A good description is still worth writing — it is what gets used when it does match — but it is an input, not a guarantee.</p>',
            'Free Google SERP Preview Tool — Pixel-Accurate',
            'Preview how your page appears in Google search results free. Real pixel-width truncation for desktop and mobile, not character counting.',
            'serp preview, google snippet preview, meta title length checker, serp simulator, seo title preview'],

        ['readability', 'Readability Analyzer', $txt, 'fa-book-open-reader',
            'Paid writing-clarity editors',
            'Score how hard your writing is to read, and get the specific sentences that are slowing readers down.',
            '<h2>Find out what your writing demands of a reader</h2><p>Paste any text for a Flesch reading-ease score, an approximate grade level, and a list of the exact sentences causing trouble.</p>'
            . '<h3>Reading the score</h3><p>Flesch reading-ease runs from 0 to 100 and higher is easier. Above 60 is plain English that most adults read comfortably, and it is the range most publications and web copy aim for. Between 30 and 60 is heavier going, appropriate for technical or academic material. Below 30 is genuinely difficult and will lose most general readers.</p>'
            . '<h3>What it flags and why</h3><p>Sentences over 25 words are listed individually, because that is roughly where readers start losing the thread and need to re-read. Passive constructions are counted — active voice is shorter and clearer in most cases. Adverbs ending in -ly are counted too, since a stronger verb usually does the job better than a weak verb propped up by one.</p>'
            . '<h3>Use it as a signal, not a rule</h3><p>These are heuristics based on sentence and syllable length. They cannot tell whether your argument makes sense or your terminology suits your audience. A long sentence that reads beautifully should stay long. Treat every flag as a question worth asking, not an instruction.</p>',
            'Free Readability Checker — Flesch Score and Grade Level',
            'Check how readable your writing is free. Flesch reading-ease score, grade level, long-sentence detection, passive voice and adverb counts.',
            'readability checker, flesch reading ease, readability score, grade level checker, hemingway alternative'],

        ['keyword_density', 'Keyword Density Analyzer', $txt, 'fa-chart-pie',
            'SEO platforms gating content analysis',
            'See which words and phrases dominate your content, and whether your target keyword is used at a sane rate.',
            '<h2>What is your page actually about?</h2><p>This counts the words and phrases your content leans on, filters out the filler, and shows the top terms with their frequency and density. Switch between single words, two-word phrases and three-word phrases.</p>'
            . '<h3>Checking a target keyword</h3><p>Enter the term you want the page to rank for and the tool reports its density with a verdict. Roughly 0.5% to 2.5% is a healthy range — enough that the topic is unmistakable, not so much that it reads as manipulation. Zero means the page never actually says the thing it is meant to be about, which is a surprisingly common problem.</p>'
            . '<h3>Density is a diagnostic, not a target</h3><p>Search engines stopped rewarding keyword frequency a very long time ago, and deliberately repeating a phrase to hit a percentage produces worse writing and can trip spam detection. What this tool is genuinely good for is the opposite check: confirming your page covers its topic properly, and catching text that has drifted away from its subject.</p>'
            . '<h3>Phrases beat single words</h3><p>The two- and three-word views are usually more revealing, because that is closer to how people search. A page that mentions "image" and "compressor" a lot but never the phrase "image compressor" is missing something obvious.</p>',
            'Free Keyword Density Checker — Word and Phrase Analysis',
            'Analyse keyword density free. See top words and phrases, filter stop words, and check whether your target keyword is used at a healthy rate.',
            'keyword density checker, keyword analyzer, word frequency counter, seo content analysis, phrase frequency'],

        ['gradient_generator', 'CSS Gradient Generator', $gen, 'fa-fill-drip',
            'Design tools with paid export',
            'Build linear, radial and conic CSS gradients visually and copy the code straight into your stylesheet.',
            '<h2>Design it, then take the CSS</h2><p>Pick your colours, choose the gradient type, drag the angle, and copy production-ready CSS. No account, no export limit, no watermark on the output.</p>'
            . '<h3>The three types</h3><p><strong>Linear</strong> runs colour along a straight axis and is what most interfaces use — 135 degrees, running top-left to bottom-right, is the classic diagonal. <strong>Radial</strong> spreads outward from a centre point, useful for spotlight and glow effects. <strong>Conic</strong> sweeps around a centre like a colour wheel, which is how you build pie charts and loading spinners in pure CSS.</p>'
            . '<h3>Choosing colours that blend well</h3><p>Colours sitting near each other on the colour wheel blend smoothly. Opposites tend to pass through a muddy grey in the middle — if that happens, add a third colour to steer the transition somewhere more attractive. The randomise button is a fast way to find combinations you would not have reached for.</p>'
            . '<h3>Keep text readable</h3><p>A gradient behind text has to clear the contrast bar at its lightest point, not its average. If white text sits comfortably on the dark end but disappears on the light end, darken the light stop or add a translucent overlay.</p>',
            'Free CSS Gradient Generator — Linear, Radial and Conic',
            'Create CSS gradients free with a live preview. Linear, radial and conic types, two or three colour stops, adjustable angle, one-click copy.',
            'css gradient generator, gradient maker, linear gradient css, conic gradient, background gradient generator'],

        ['palette_from_image', 'Colour Palette Extractor', $img, 'fa-eye-dropper',
            'Palette apps with paid tiers',
            'Pull the dominant colours out of any image and export them as ready-to-use CSS variables.',
            '<h2>Build a palette from a photograph</h2><p>Drop in an image and get its dominant colours as hex and RGB values, ordered by how much of the image each one occupies, plus a CSS custom-property block ready to paste.</p>'
            . '<h3>How the colours are chosen</h3><p>The tool uses median-cut quantisation: it repeatedly splits the image\'s colours along whichever channel varies most, then averages each resulting group. That is the same family of algorithm used to build indexed colour palettes, and it produces colours that genuinely represent the image rather than picking a few arbitrary pixels.</p>'
            . '<h3>Where this is useful</h3><p>Deriving a site theme from a hero photograph, matching a design to a client\'s existing brand photography, or pulling a palette from artwork or a film still whose mood you want to borrow.</p>'
            . '<h3>Check contrast before you commit</h3><p>A palette lifted from a photo is a starting point, not a finished design system. Photographic colours are often too similar in lightness to pair as text and background. Test any text-on-background combination against WCAG contrast ratios before shipping it.</p>',
            'Free Colour Palette Extractor — Get Colours from an Image',
            'Extract the dominant colour palette from any image free. Hex and RGB values plus copy-ready CSS variables, generated in your browser.',
            'color palette generator, extract colors from image, image color picker, palette from photo, css color variables'],

        ['image_to_pdf', 'Images to PDF Converter', $conv, 'fa-file-pdf',
            'PDF suites sold by subscription',
            'Combine photos or scans into a single PDF — page order, page size and quality all under your control.',
            '<h2>One PDF from many images</h2><p>Add your images, arrange them, and build a PDF. No page limit, no watermark stamped across the output, and no subscription prompt at the download step.</p>'
            . '<h3>Page size options</h3><p>Fit-each-image gives every page the exact proportions of its picture, which suits photo albums and portfolios. A4 or US Letter centres each image on a standard page, which is what you want for anything destined to be printed or filed — scanned receipts, ID documents, signed forms.</p>'
            . '<h3>Quality and file size</h3><p>Images are re-encoded as JPEG at the quality you choose. Around 85% is visually indistinguishable from the original for photographs while producing a file a fraction of the size. Push higher for artwork or anything with fine text; drop lower if you need to squeeze under an email attachment limit.</p>'
            . '<h3>Built without a server</h3><p>The PDF is assembled byte by byte in your browser, with each image embedded as a JPEG stream. That matters for the documents people most often convert — passports, bank statements, contracts — which really should not be uploaded to a stranger\'s server.</p>',
            'Free Images to PDF Converter — No Upload, No Watermark',
            'Convert JPG and PNG images into one PDF free. Choose page size, order and quality. Built entirely in your browser — files are never uploaded.',
            'image to pdf, jpg to pdf converter, combine images into pdf, photo to pdf, free pdf maker'],

        ['speech_to_text', 'Speech to Text', $conv, 'fa-microphone-lines',
            'Transcription services billed per minute',
            'Dictate straight into your browser and get live text back, in seven languages including Arabic.',
            '<h2>Talk instead of typing</h2><p>Press record and your speech appears as text in real time. Transcription services normally charge by the minute or cap a free tier; this uses the speech recognition already built into your browser.</p>'
            . '<h3>Languages</h3><p>English (US and UK), Arabic, French, Spanish, German and Turkish. Pick the language before you start — recognition accuracy drops sharply if the engine is listening for the wrong one.</p>'
            . '<h3>Getting a cleaner transcript</h3><p>Speak at a normal conversational pace rather than slowly and deliberately, which actually confuses the model. Reduce background noise where you can. Expect to add most punctuation yourself; the text stays editable in the box as you dictate, so you can fix things as you go.</p>'
            . '<h3>Browser support and privacy</h3><p>This needs the Web Speech API — Chrome, Edge and Safari support it; Firefox currently does not. Note that in some browsers, Chrome included, audio is processed by the browser vendor\'s speech service rather than purely on-device, so treat highly confidential dictation with the same caution you would any cloud transcription.</p>',
            'Free Speech to Text — Live Dictation in Your Browser',
            'Convert speech to text free with live dictation in seven languages including Arabic. No sign-up, no per-minute billing, no upload.',
            'speech to text, voice to text, free transcription, dictation tool, arabic speech to text'],

        ['robots_generator', 'Robots.txt Generator', $gen, 'fa-robot',
            'SEO plugins gating robots editing',
            'Build a valid robots.txt, including blocks for AI training crawlers, and download it ready to upload.',
            '<h2>Control what crawlers fetch</h2><p>Set your disallow and allow rules, point to your sitemap, and get a correctly formatted robots.txt to drop at the root of your domain.</p>'
            . '<h3>Blocking AI training crawlers</h3><p>The checkboxes cover the crawlers used to gather training data and power AI answers — GPTBot, ClaudeBot, Google-Extended, CCBot, PerplexityBot and others — alongside the aggressive SEO scrapers that eat bandwidth without sending traffic. Whether to block them is a genuine judgement call: it protects your content from being ingested, but can also reduce your visibility in AI-generated answers that increasingly sit above traditional results.</p>'
            . '<h3>The thing robots.txt does not do</h3><p>It controls crawling, not indexing. A disallowed page that other sites link to can still appear in search results, listed without a description, because the crawler was told not to fetch it but was never told to forget it. To keep a page out of the index you need a <code>noindex</code> meta tag on the page itself — which means the crawler must be <em>allowed</em> to fetch it and see that tag.</p>'
            . '<h3>Be careful what you block</h3><p>Disallowing your CSS or JavaScript stops Google rendering your pages the way visitors see them, which can hurt rankings. Block admin paths and internal endpoints; leave assets alone.</p>',
            'Free Robots.txt Generator — Block AI Crawlers Too',
            'Generate a valid robots.txt free with sitemap, allow and disallow rules, plus one-click blocking for GPTBot, ClaudeBot and other AI crawlers.',
            'robots.txt generator, block gptbot, block ai crawlers, robots txt maker, seo crawler rules'],

        ['meta_generator', 'Meta Tag Generator', $gen, 'fa-tags',
            'Premium SEO plugin meta editors',
            'Generate the full set of SEO, Open Graph and Twitter Card tags, with length gauges for title and description.',
            '<h2>Every social platform wants its own tags</h2><p>Fill in your page details once and get the complete head block: standard SEO tags, Open Graph for Facebook, LinkedIn and WhatsApp, and Twitter Card tags.</p>'
            . '<h3>Why Open Graph is worth the effort</h3><p>Without it, a link shared to social or messaging apps shows whatever those platforms can scrape — often a stray navigation heading and no image. With it, you control the headline, the description and the preview image. It is the difference between a link that gets clicked and one that gets scrolled past.</p>'
            . '<h3>The preview image</h3><p>Use 1200×630 pixels. Smaller images get cropped or downgraded to a small thumbnail. Always use an absolute URL starting with https — relative paths do not work here, since the platform fetches the image from its own servers.</p>'
            . '<h3>Length gauges</h3><p>The title and description bars turn red past roughly 60 and 155 characters, which is where truncation typically starts. For pixel-accurate checking of exactly where Google will cut your title, use the SERP Preview tool.</p>'
            . '<h3>Canonical URLs</h3><p>The canonical tag tells search engines which version of a page is authoritative when the same content is reachable at several URLs. Getting it wrong is one of the more common ways sites accidentally deindex their own pages, so point it at the URL you actually want ranked.</p>',
            'Free Meta Tag Generator — SEO, Open Graph and Twitter Cards',
            'Generate SEO meta tags, Open Graph and Twitter Card markup free, with live length gauges for title and description. Copy the whole block at once.',
            'meta tag generator, open graph generator, twitter card generator, seo meta tags, og tags maker'],
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
