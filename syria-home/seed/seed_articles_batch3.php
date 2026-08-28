<?php
/** Third wave of original English articles — genuinely new topics, same
 *  long-form editorial standard. Safe to re-run: INSERT IGNORE on slug. */
function seed_articles_batch3(PDO $pdo): void {
    $catMap = [];
    foreach ($pdo->query("SELECT id, slug FROM categories WHERE type='article'") as $r) $catMap[$r['slug']] = (int)$r['id'];

    $A = [];

    $A[] = [
        'title' => 'Ad Blockers Explained: How They Work and What They Actually Cost Publishers',
        'cat' => 'internet-culture', 'type' => 'article', 'icon' => 'fa-ban', 'gradient' => 'g4', 'trending' => 0,
        'tags' => 'ad blockers, online advertising, publishers, internet culture',
        'meta_kw' => 'how ad blockers work, do ad blockers hurt websites, should i use an ad blocker',
        'excerpt' => 'Ad blockers are on a huge share of browsers, but few people understand exactly what they block, how sites detect them, or what the trade-off actually looks like for the free content they use every day.',
        'body' => <<<HTML
<p>A large share of internet users run an ad blocker without fully understanding what it's doing under the hood, or what the actual trade-off is for the free sites they visit daily. The technology is simple in concept but the economics behind it are genuinely more complicated than "ads are annoying, block them."</p>

<h2>How ad blockers actually work</h2>
<p>Most ad blockers work from a maintained list of known ad-serving domains and page patterns. When your browser tries to load a resource matching that list, the blocker intercepts the request before it ever reaches the ad network's server — the ad never loads, never tracks you, and never counts as an impression. More advanced blockers also hide leftover empty space and strip tracking scripts bundled alongside ads, which is where a meaningful part of the privacy benefit actually comes from.</p>

<h2>What gets blocked beyond the ad itself</h2>
<p>The privacy angle is often the bigger draw than simply avoiding visual clutter. Many ad networks bundle tracking code that follows your browsing across sites to build an advertising profile, and blocking the ad request often blocks that tracking code at the same time. This is why ad blocking and privacy tools overlap so heavily — for a lot of users, the actual goal is reducing tracking, and blocking ads is a side effect of that.</p>

<h2>What this actually costs the sites you visit</h2>
<p>Free content has to be paid for somehow, and for most publishers, that's still primarily advertising revenue. When a large share of visitors block ads, the same traffic generates meaningfully less revenue, which pushes sites toward a few responses: more aggressive ad placement for non-blocking visitors, a hard paywall, asking blockers to whitelist the site, or in smaller cases, shutting down entirely. None of these outcomes are hypothetical — they're the reality many independent sites and small publishers actually face.</p>

<h2>Why some sites detect blockers and ask you to disable them</h2>
<p>Detecting a blocker is technically straightforward — the site checks whether an expected ad element actually loaded, and if it didn't, shows a message. This isn't usually hostility toward the visitor; it's a direct response to the revenue gap described above, especially from smaller sites that don't have subscription revenue or other income to fall back on.</p>

<h2>A middle-ground approach worth considering</h2>
<p>Most ad blockers support per-site whitelisting — you keep the blocker on everywhere by default, but allow ads specifically on sites you actually value and want to keep funded. This preserves most of the privacy and clutter benefit broadly while still supporting the small number of sites you'd genuinely be disappointed to see disappear.</p>
<ul>
<li>Whitelist sites you visit regularly and want to keep sustainable.</li>
<li>Keep blocking on everything else for privacy and load speed.</li>
<li>Consider a direct subscription instead, on sites that offer one, if ads specifically bother you but you still want to support the content.</li>
</ul>

<h2>The privacy-conscious alternative to full blocking</h2>
<p>Some tools specifically target tracking scripts while leaving basic, non-tracking ads intact — a middle path that addresses the privacy concern without fully cutting off ad revenue. This is a reasonable option for anyone who wants the tracking protection more than the ad-removal itself.</p>

<p><strong>Do ad blockers slow down or speed up page loading?</strong> Typically speed pages up — ads and their tracking scripts add real load time, so blocking them usually makes pages load faster, not slower.</p>
<p><strong>Is it illegal to use an ad blocker?</strong> No, using one on your own device is legal; it's simply a browser configuration choice, though individual sites are free to restrict access in response.</p>
<p><strong>Can whitelisting a site expose me to more tracking?</strong> Yes, potentially — whitelisting allows that specific site's ads and any tracking bundled with them, which is exactly why selective whitelisting rather than blanket disabling is the more privacy-conscious middle ground.</p>
HTML,
    ];

    $A[] = [
        'title' => 'USB Hubs and Docking Stations: What the Ports on the Box Actually Mean',
        'cat' => 'hardware-gadgets', 'type' => 'tutorial', 'icon' => 'fa-plug-circle-bolt', 'gradient' => 'g2', 'trending' => 0,
        'tags' => 'usb hubs, docking stations, buying guide, laptop accessories',
        'meta_kw' => 'usb hub vs docking station, what does usb-c hub support, buying a laptop dock',
        'excerpt' => 'A hub or dock spec sheet is a wall of port names and speed numbers that mean very little at a glance. Here is what each one actually enables, so you can tell which box genuinely fits your setup.',
        'body' => <<<HTML
<p>Modern laptops often ship with very few ports, pushing hubs and docking stations from a nice-to-have into something close to a necessity for anyone connecting more than a charger and one cable. The spec sheets for these devices are dense with port names and speed numbers, and picking the wrong one usually means discovering a missing capability only after you've already bought it.</p>

<h2>Hub vs docking station: not the same thing</h2>
<p>A hub is a simple port multiplier — it takes one connection from your laptop and splits it into several, generally without much added intelligence. A docking station does more: it typically adds full video output capable of driving external monitors, higher-power charging passthrough, and sometimes wired networking, effectively turning a laptop into a desktop setup with a single cable connected. Docks cost more, but for a fixed desk setup, they remove far more daily friction than a basic hub.</p>

<h2>What the port types actually enable</h2>
<ul>
<li><strong>USB-A</strong> — the familiar rectangular port, for older peripherals like mice, keyboards, and flash drives that haven't moved to the newer connector.</li>
<li><strong>USB-C (data only)</strong> — the modern reversible connector, but "USB-C" alone doesn't guarantee any specific speed or capability; that depends entirely on the standard implemented behind it.</li>
<li><strong>USB-C with video output</strong> — a USB-C port explicitly capable of carrying a video signal to an external display, distinct from a port that only handles data or charging.</li>
<li><strong>HDMI / DisplayPort</strong> — dedicated video ports for connecting external monitors directly, generally more reliable than routing video over a USB-C hub for demanding setups.</li>
<li><strong>Ethernet</strong> — wired networking, meaningfully faster and more stable than Wi-Fi for anyone doing large file transfers or needing a rock-solid connection.</li>
</ul>

<h2>The power delivery detail that trips people up</h2>
<p>Many hubs and docks can pass power through to charge your laptop while you're using the other ports, but the wattage they can deliver varies a lot between models. A hub rated for lower wattage than your laptop actually needs will still charge it — just slower than plugging in directly, or not at all under heavy load. Check your laptop's actual charging wattage requirement against the hub's rated passthrough power before assuming it'll fully replace your charger.</p>

<h2>Why some hubs "don't work" with certain monitors</h2>
<p>Driving one or more high-resolution external monitors through a single USB-C connection requires real bandwidth, and cheaper hubs sometimes can't deliver full resolution and refresh rate simultaneously across multiple displays, even though the ports are physically present. This is one of the most common disappointment points — the ports exist, but the underlying bandwidth to drive them all at full quality at once doesn't.</p>

<h2>A practical way to choose</h2>
<ol>
<li>List the specific peripherals and monitors you'll actually connect, not just "a few things."</li>
<li>Check your laptop's actual port capabilities first — not every USB-C port on every laptop supports video output or full-speed data equally.</li>
<li>For driving external monitors, check the hub's documented supported resolution and refresh rate per monitor, not just "supports 4K" as a vague headline claim.</li>
<li>If replacing your charger matters to you, confirm the passthrough wattage meets or exceeds what your laptop actually needs.</li>
</ol>

<p><strong>Can I use a cheap hub for charging plus a mouse and keyboard only?</strong> Yes — for low-bandwidth peripherals and basic charging, an inexpensive hub is usually perfectly adequate; the complications mostly show up with high-resolution displays and fast external storage.</p>
<p><strong>Why does my external monitor flicker or drop connection through a hub?</strong> Often a bandwidth or power delivery limitation under real load; trying a different port on the hub, a shorter/higher-quality cable, or a dock with dedicated video output often resolves it.</p>
<p><strong>Do all docking stations work with all laptop brands?</strong> Most modern USB-C docks are broadly cross-compatible, but always check the dock's compatibility list for driver requirements or brand-specific quirks before buying, especially for docks bundled with a specific laptop brand.</p>
HTML,
    ];

    $A[] = [
        'title' => 'Browser Extensions: How to Tell a Useful One from a Privacy Risk',
        'cat' => 'cybersecurity', 'type' => 'how-to', 'icon' => 'fa-puzzle-piece', 'gradient' => 'g3', 'trending' => 1,
        'tags' => 'browser extensions, privacy, cybersecurity, browser security',
        'meta_kw' => 'are browser extensions safe, how to check if an extension is safe, browser extension privacy risk',
        'excerpt' => 'Browser extensions can see and change almost everything you do in your browser. Here is how to evaluate one honestly before installing it, and warning signs that an extension has become a liability.',
        'body' => <<<HTML
<p>A browser extension isn't a small, isolated add-on the way it feels — depending on the permissions it requests, it can potentially see every page you visit, read what you type, and modify what you see on screen. Most extensions are genuinely useful and harmless, but the ones that aren't can cause real damage precisely because they operate with that level of access.</p>

<h2>What extension permissions actually mean</h2>
<p>When installing an extension, you're typically shown what it can access — "read and change all your data on websites you visit" is a common, broad permission that sounds alarming but is genuinely necessary for extensions that need to interact with page content, like ad blockers or password managers. The question isn't whether an extension asks for broad permissions; many legitimate ones need to. The question is whether that permission level makes sense for what the extension claims to do.</p>

<h2>A practical way to evaluate one before installing</h2>
<ul>
<li><strong>Does the permission match the function?</strong> A simple unit converter asking for access to read all your browsing data on every site is a mismatch worth questioning; a password manager asking for the same permission is expected and necessary.</li>
<li><strong>Who built it, and are they identifiable?</strong> A named developer or company with a real support presence is a better sign than a generic, unattributed listing.</li>
<li><strong>How recently was it updated?</strong> An extension untouched for years may simply be abandoned — not actively malicious, but also not receiving security fixes for newly discovered issues.</li>
<li><strong>What do recent reviews actually say?</strong> Look specifically for recent complaints about unexpected behavior, not just the overall star rating, which can lag behind a recent change in the extension's behavior.</li>
</ul>

<h2>Why an extension can turn risky after you've already installed it</h2>
<p>This is the scenario people underestimate most: an extension can be perfectly trustworthy when installed, then change ownership later — sold to a different company that pushes an update turning it into an aggressive tracker or injecting unwanted ads, all without you ever re-approving the (unchanged) permission level you originally granted. This is a real, documented pattern, which is part of why periodically reviewing what's actually installed matters, not just vetting an extension once at install time.</p>

<h2>Warning signs an installed extension has gone bad</h2>
<ul>
<li>Your browser homepage or default search engine changed without you doing it.</li>
<li>Unusual pop-ups or redirects start appearing on sites that never had them before.</li>
<li>The browser feels noticeably slower after a specific extension's update.</li>
<li>You notice ads appearing on sites that don't normally run any.</li>
</ul>

<h2>A regular maintenance habit worth adopting</h2>
<p>Periodically open your browser's extensions list and actually look at what's installed — it's common to accumulate extensions installed for a one-time task months ago and forgotten since. For each one, ask honestly whether you still use it and whether its permission level still makes sense. Remove anything you can't immediately justify keeping.</p>

<h2>Minimizing risk without giving up extensions entirely</h2>
<ol>
<li>Install only from official browser extension stores, not from third-party download sites.</li>
<li>Prefer extensions with a large, established user base and a clear, identifiable developer.</li>
<li>Review installed extensions every few months, not just once at install time.</li>
<li>Remove anything you're not actively and regularly using.</li>
</ol>

<p><strong>Are official browser extension stores fully safe?</strong> Safer than third-party sources due to review processes, but not risk-free — malicious extensions do occasionally slip through, which is why ongoing evaluation still matters even for store-listed extensions.</p>
<p><strong>Do extensions work the same way across different browsers?</strong> The permission model is broadly similar across major browsers, though the exact wording and granularity of what's disclosed to you can differ.</p>
<p><strong>Is it safer to use fewer extensions overall?</strong> Generally yes — every installed extension is additional attack surface and additional trust placed in a third party, so keeping only what you actually use regularly is a reasonable default habit.</p>
HTML,
    ];

    $A[] = [
        'title' => 'Note-Taking Apps Compared: Which One Actually Fits How You Think',
        'cat' => 'comparisons', 'type' => 'comparison', 'icon' => 'fa-note-sticky', 'gradient' => 'g6', 'trending' => 0,
        'tags' => 'note-taking apps, productivity, comparison, organization',
        'meta_kw' => 'best note taking app, note app comparison, which note app should i use',
        'excerpt' => 'Every note-taking app claims to organize your brain for you, but they are built around genuinely different mental models. Here is how to figure out which structure actually matches how you think.',
        'body' => <<<HTML
<p>Note-taking apps get compared mostly on surface features — dark mode, offline support, price — but the decision that actually matters most is structural: how the app expects you to organize information. Pick one that fights your natural thinking style and you'll abandon it within weeks, regardless of how many features it has.</p>

<h2>Folder-based, hierarchical apps</h2>
<p>These organize notes into folders and subfolders, much like a traditional file system. This model suits people who think in clear categories and like knowing exactly where something lives before they even write it — you decide the folder first, then create the note inside it. The limitation shows up with notes that genuinely span multiple categories at once, forcing an artificial choice about which single folder they "really" belong in.</p>

<h2>Tag-based apps</h2>
<p>Instead of one rigid location, notes get one or more tags, and you find things later by filtering on those tags rather than browsing a folder tree. This suits people whose thinking doesn't sort cleanly into single categories — the same note can be tagged under a project, a topic, and a status simultaneously, then surfaced through any of those tags. The trade-off is that a note with no tags, or tags you forget to apply consistently, becomes genuinely hard to find later.</p>

<h2>Networked / bidirectional-linking apps</h2>
<p>These focus on links between individual notes rather than any folder or tag structure — you write a note, link it to related notes as connections occur to you, and over time a web of connected ideas emerges organically rather than being planned upfront. This suits people doing genuine long-term research or connected thinking, where the relationships between ideas are often as valuable as the ideas themselves. It has a real learning curve and can feel unstructured and directionless at first, especially before enough notes exist for the connections to become useful.</p>

<h2>Simple, linear apps</h2>
<p>No folders, no tags, no linking — just a straightforward list of notes, sometimes just one continuous scrolling document. This suits people who want zero organizational overhead and mainly need quick capture: a shopping list, a meeting note, a quick idea jotted down before it's forgotten. It becomes a real liability at scale — past a few dozen notes, finding anything specific later depends entirely on search working well, since there's no other retrieval structure to fall back on.</p>

<h2>A practical way to actually decide</h2>
<ol>
<li>Think honestly about your last real research or planning project — did you organize it into clean categories, cross-referenced tags, or a connected web of related ideas? That instinct is a strong signal for which structure will actually stick.</li>
<li>Try capturing a genuinely messy, real week of notes, not a clean demo — that's when an app's actual fit (or mismatch) with your thinking becomes obvious.</li>
<li>Check search quality specifically, since you'll eventually rely on it regardless of which organizational model you pick.</li>
<li>Confirm reliable export options exist before committing years of notes to any single app's ecosystem.</li>
</ol>

<h2>It's fine to mix approaches</h2>
<p>Some people genuinely need one tool for extremely fast, structure-free daily capture and a completely separate tool for slower, more deliberate long-term knowledge organization. There's no rule requiring a single app to serve both quick capture and deep long-term structure well — trying to force one tool to do both is a common reason people bounce between apps looking for one that "finally works," when the real fix is using two tools for two different jobs.</p>

<p><strong>Is a more expensive note app automatically better?</strong> No — price often correlates with extra features like advanced collaboration or storage limits, not with whether the underlying organizational model actually fits how you think, which matters far more for daily use.</p>
<p><strong>Should I worry about switching apps later and losing my notes?</strong> Check the app's export format before committing heavily; plain text or widely supported formats make a future switch far less painful than a proprietary format only that one app can read.</p>
<p><strong>Do I need to fully commit to one organizational style?</strong> Not necessarily — many apps support a hybrid approach (folders plus tags, for instance), so the "pure" categories above are useful for understanding the trade-offs, not a rule you must follow rigidly.</p>
HTML,
    ];

    $A[] = [
        'title' => 'What Actually Happens When You Clear Your Cache and Cookies',
        'cat' => 'how-to-guides', 'type' => 'how-to', 'icon' => 'fa-broom', 'gradient' => 'g5', 'trending' => 0,
        'tags' => 'browser cache, cookies, troubleshooting, how-to',
        'meta_kw' => 'what does clearing cache do, clearing cookies explained, when to clear browser cache',
        'excerpt' => '"Clear your cache and cookies" is common troubleshooting advice, but few people know what it actually removes or why it fixes what it fixes. Here is a clear explanation, plus when it helps and when it does not.',
        'body' => <<<HTML
<p>"Try clearing your cache and cookies" is one of the most common pieces of tech troubleshooting advice, repeated so often it's become a reflex response to almost any browser problem. It genuinely does fix a specific category of issues — but understanding what it actually removes makes it much easier to know when it will actually help versus when it's just a guess.</p>

<h2>What the cache actually is</h2>
<p>Your browser saves local copies of files from sites you visit — images, stylesheets, scripts — so that revisiting the same site loads faster the second time, since the browser doesn't have to re-download unchanged files. This is a genuine, meaningful speed benefit for normal browsing. The problem arises when a site updates one of these files but your browser keeps serving the old cached version, because it doesn't yet realize the file has changed. You end up seeing an outdated version of the site: old styling, a broken layout, or a feature that was supposed to be fixed but visibly isn't.</p>

<h2>What cookies actually are</h2>
<p>Cookies are small pieces of data a site stores in your browser to remember information between visits — most commonly, that you're logged in, so you don't have to enter your password on every single page. They're also used extensively for tracking preferences, shopping cart contents, and, by advertisers, for tracking behavior across sites for targeted advertising purposes.</p>

<h2>Why clearing them fixes certain problems</h2>
<p>When a site is behaving strangely — a broken layout, a feature stuck showing old data, a login loop that won't resolve — a corrupted or outdated cached file, or a cookie holding onto stale session data, is a genuinely common cause. Clearing forces your browser to download everything completely fresh from the server and start any session data from scratch, which resolves exactly this class of problem reliably.</p>

<h2>The real trade-offs of clearing</h2>
<ul>
<li><strong>You'll be logged out of everything.</strong> Since login state is typically stored in cookies, clearing them signs you out of every site, not just the one giving you trouble.</li>
<li><strong>Sites will load slightly slower temporarily.</strong> Without cached files, the next visit to every site re-downloads everything from scratch, until the cache naturally rebuilds itself through normal browsing.</li>
<li><strong>Saved preferences on some sites may reset.</strong> Anything stored purely in cookies rather than tied to an actual logged-in account may be lost — regional settings, dismissed banners, and similar small preferences.</li>
</ul>

<h2>When clearing everything is overkill</h2>
<p>For a single misbehaving site, most browsers let you clear cache and cookies for just that one site rather than wiping everything across every site you use. This solves the same problem with far less collateral disruption to your other logged-in sessions elsewhere — worth doing first before reaching for the nuclear "clear everything" option.</p>

<h2>A more targeted troubleshooting order to try first</h2>
<ol>
<li>Try a hard refresh first (forces a fresh reload of the current page specifically, bypassing cache for just that page).</li>
<li>If that doesn't help, clear cache and cookies for just the one problem site, not your entire browser.</li>
<li>Only clear everything across all sites if the issue is genuinely browser-wide rather than specific to one site.</li>
</ol>

<p><strong>Will clearing cache and cookies delete my bookmarks or saved passwords?</strong> No — those are stored separately from cache and cookies and aren't affected by this specific action.</p>
<p><strong>How often should I clear cache and cookies as routine maintenance?</strong> For most people, only when troubleshooting a specific problem is genuinely necessary — it's not something that needs regular routine clearing otherwise.</p>
<p><strong>Does clearing cookies improve privacy?</strong> Yes, meaningfully — it removes tracking cookies that have accumulated, though new ones begin accumulating again immediately as you keep browsing afterward.</p>
HTML,
    ];

    /* Insert */
    $stmt = $pdo->prepare("INSERT IGNORE INTO articles
        (title, slug, category_id, content_type, excerpt, body, hero_icon, hero_gradient, meta_title, meta_description, meta_keywords, tags, author, status, trending, reading_time, published_at)
        VALUES (:title,:slug,:category_id,:content_type,:excerpt,:body,:hero_icon,:hero_gradient,:meta_title,:meta_description,:meta_keywords,:tags,:author,'published',:trending,:reading_time,:published_at)");

    $daysAgo = 0;
    foreach ($A as $a) {
        $slug = slugify($a['title']);
        $stmt->execute([
            'title' => $a['title'],
            'slug' => $slug,
            'category_id' => $catMap[$a['cat']] ?? null,
            'content_type' => $a['type'] === 'how-to' ? 'tutorial' : $a['type'],
            'excerpt' => $a['excerpt'],
            'body' => $a['body'],
            'hero_icon' => $a['icon'],
            'hero_gradient' => $a['gradient'],
            'meta_title' => $a['title'],
            'meta_description' => $a['excerpt'],
            'meta_keywords' => $a['meta_kw'],
            'tags' => $a['tags'],
            'author' => 'Editorial Team',
            'trending' => $a['trending'],
            'reading_time' => reading_time_from_html($a['body']),
            'published_at' => date('Y-m-d H:i:s', strtotime("-{$daysAgo} days")),
        ]);
        $daysAgo += 1;
    }
}
