<?php
/** Second wave of original English articles — genuinely new topics, not
 *  translations, written to the same long-form editorial standard as
 *  seed_articles.php. Safe to re-run: INSERT IGNORE on the unique slug. */
function seed_articles_batch2(PDO $pdo): void {
    $catMap = [];
    foreach ($pdo->query("SELECT id, slug FROM categories WHERE type='article'") as $r) $catMap[$r['slug']] = (int)$r['id'];

    $A = [];

    $A[] = [
        'title' => 'Password Managers Explained: Why You Need One and How to Choose the Right Type',
        'cat' => 'cybersecurity', 'type' => 'how-to', 'icon' => 'fa-vault', 'gradient' => 'g3', 'trending' => 1,
        'tags' => 'password manager, cybersecurity, online safety, digital hygiene',
        'meta_kw' => 'best password manager, do i need a password manager, password manager types explained',
        'excerpt' => 'Reusing passwords is still the single biggest reason accounts get breached. Here is what a password manager actually does, the real differences between the types available, and how to pick one without overthinking it.',
        'body' => <<<HTML
<p>Ask most people why they reuse the same password across a dozen accounts and the answer is almost always the same: remembering dozens of unique, strong passwords is genuinely hard, so people don't. A password manager solves that specific problem — it remembers so you don't have to, and it can generate passwords far stronger than anything a human would invent and actually use twice.</p>

<h2>What a password manager actually does</h2>
<p>At its core, a password manager is an encrypted vault. You unlock it with one master password (or your fingerprint/face, which unlocks the underlying stored credential), and it fills in the right username and password for whichever site you're on. Everything inside the vault is encrypted, and in well-built managers, even the company running the service cannot read your stored passwords — the encryption and decryption happen on your device using a key derived from your master password, which they never see.</p>

<h2>Why "just remember strong passwords" doesn't work</h2>
<p>The math is against you. A genuinely strong, unique password looks like a string of random characters — not a memorable phrase — and you'd need a different one for every single account: banking, email, shopping, work tools, streaming services, and so on. Nobody memorizes forty random strings. The realistic alternatives are reusing passwords (dangerous — one breached site exposes every account using that password) or writing them down somewhere insecure. A password manager removes the need to choose between convenience and security.</p>

<h2>The main types of password managers</h2>
<h3>Cloud-synced managers</h3>
<p>These store your encrypted vault on the provider's servers and sync it across your phone, laptop, and browser automatically. The convenience is real — add a password on one device, it's available everywhere instantly. The trade-off is that your encrypted vault lives on someone else's infrastructure, so the security of the whole system depends on the strength of your master password and the provider's encryption implementation holding up.</p>
<h3>Local-only managers</h3>
<p>These keep your vault as a file on your own device with no cloud sync built in. Nothing about your passwords ever touches a company's servers, which appeals to people who want maximum control. The cost is manual work — you're responsible for backing up that file yourself, and getting it onto a new device means transferring it manually rather than just logging in.</p>
<h3>Browser-built-in managers</h3>
<p>Every major browser now includes a basic password manager for free. These are genuinely fine for casual use and dramatically better than reusing passwords, but they typically lack features serious users want: secure notes, password sharing with family members, breach monitoring, and cross-browser support if you ever switch browsers.</p>
<h3>Hardware-backed managers</h3>
<p>Some managers pair with a physical security key so that even your master password alone isn't enough to unlock the vault — you need the physical device too. This is the strongest option against remote attacks but adds friction and the real risk of being locked out if you lose the hardware without a backup plan.</p>

<h2>What actually matters when choosing one</h2>
<ul>
<li><strong>Cross-platform support</strong> — it needs to work everywhere you actually log in: phone, laptop, every browser you use.</li>
<li><strong>Zero-knowledge encryption</strong> — the provider should be architecturally unable to read your vault, not just promise not to look.</li>
<li><strong>A generator you'll actually use</strong> — built-in random password generation removes the temptation to type something memorable (and weak) under time pressure.</li>
<li><strong>Emergency access</strong> — a way to recover your vault if you forget your master password, without that same mechanism becoming a backdoor anyone else could exploit.</li>
</ul>

<h2>Setting one up without the overwhelm</h2>
<p>Don't try to migrate every password on day one. Install the manager, let it capture new logins as you naturally use them over the next few weeks, and separately go update your five or six most important accounts first — email, banking, and anything tied to account recovery for everything else. Email is the highest priority: whoever controls your email can usually reset the password on nearly every other account you own.</p>

<h2>A note on the master password</h2>
<p>Your master password is the one password you still have to remember, so it needs to be both memorable and strong — a long, unusual passphrase works better here than a short complex string. Length matters more than symbols for this specific password, since you're the only one who needs to recall it and it protects everything else.</p>

<p><strong>What happens if the password manager company gets breached?</strong> With a properly zero-knowledge design, attackers get encrypted data they can't read without your master password, which never leaves your device. This is exactly why choosing a manager with real zero-knowledge architecture matters more than almost any other feature.</p>
<p><strong>Is it safe to store banking passwords in one?</strong> Yes — that's precisely the use case password managers are built for, and it's considerably safer than reusing a memorable password across banking and lower-security sites.</p>
<p><strong>What if I forget my master password?</strong> This depends entirely on the manager's recovery design; some offer account recovery through a separate verified method, while strict zero-knowledge tools may have no recovery at all, which is the trade-off for that architecture. Check this before committing to one.</p>
<p><strong>Can I use a password manager's generator for this site's own tools?</strong> This site's own <a href="/tool/strong-password-generator">password generator</a> works the same way — cryptographically random, generated locally, nothing transmitted — useful any time you need one password without opening your full manager.</p>
HTML,
    ];

    $A[] = [
        'title' => '4K vs HDR vs Refresh Rate: What Actually Matters When Buying a Screen',
        'cat' => 'hardware-gadgets', 'type' => 'how-to', 'icon' => 'fa-tv', 'gradient' => 'g2', 'trending' => 0,
        'tags' => 'buying guide, monitors, TVs, display technology',
        'meta_kw' => '4k vs hdr, refresh rate explained, what to look for buying a monitor or tv',
        'excerpt' => 'Every TV and monitor spec sheet throws three numbers at you at once. Here is what resolution, HDR, and refresh rate each actually change about what you see, and which one to prioritize for your specific use.',
        'body' => <<<HTML
<p>Walk into any electronics section and every screen boasts about the same three things: resolution, HDR, and refresh rate. Marketing treats them as equally important, but they solve completely different problems, and the right screen for you depends entirely on what you'll actually watch or do on it — not on maxing out all three numbers.</p>

<h2>Resolution (4K and beyond): how much detail fits on screen</h2>
<p>Resolution is simply a pixel count — more pixels means finer detail, sharper text, and images that hold up when you sit closer or the screen is larger. The catch is that the benefit depends heavily on screen size and viewing distance. On a large TV across a living room, 4K is a clear, visible upgrade over lower resolution. On a small monitor sitting close to your face, the jump from good to ultra-high resolution matters less than people assume, because your eyes can already resolve most of the available detail at the lower resolution.</p>
<p>Higher resolution also demands more from whatever is producing the image — more graphics power for gaming, more bandwidth for streaming, larger file sizes for video editing. It's not a free upgrade; everything downstream has to keep up.</p>

<h2>HDR: how bright and how dark, not how detailed</h2>
<p>High Dynamic Range is often confused with resolution, but it's solving a different problem entirely — the range between the brightest whites and darkest blacks a screen can show simultaneously, plus a wider range of colors. A well-implemented HDR screen makes a sunset scene actually look bright while shadows stay genuinely dark, instead of everything getting compressed into a narrower, flatter range.</p>
<p>The critical detail most buyers miss: HDR quality depends almost entirely on the screen's actual brightness and contrast hardware, not just a logo on the box. A budget screen with an HDR label but weak backlight hardware often looks worse in HDR content than the same screen would in standard range, because it can't actually hit the brightness levels HDR content expects. HDR is a spec worth paying attention to, but only on screens with the physical hardware to back it up.</p>

<h2>Refresh rate: how smooth motion looks</h2>
<p>Refresh rate measures how many times per second the screen redraws the image, and it's the one that matters most for anything with fast motion — competitive gaming, sports, fast camera pans. A higher refresh rate makes motion look noticeably smoother and reduces the blur you see when things move quickly across the screen. For static content like reading documents or watching a slow-paced show, refresh rate barely matters at all.</p>
<p>Like resolution, a higher refresh rate is only useful if the source can actually deliver it — a high refresh rate monitor paired with a graphics card that can't produce enough frames per second, or streaming content locked to a lower rate, won't show any benefit from the extra capability sitting unused.</p>

<h2>Matching the spec to what you'll actually do</h2>
<ul>
<li><strong>Competitive or fast-paced gaming:</strong> prioritize refresh rate first, resolution second, HDR a distant third.</li>
<li><strong>Movies and TV in a living room:</strong> resolution and genuine HDR hardware matter most; refresh rate beyond a moderate level adds little for this content.</li>
<li><strong>Office work, coding, reading:</strong> resolution and text sharpness matter most; HDR and high refresh rate add almost nothing to this use case.</li>
<li><strong>Photo or video editing:</strong> color accuracy and consistent brightness matter more than any of the three headline specs — look for accuracy certifications instead.</li>
</ul>

<h2>The trap of chasing every number at once</h2>
<p>Screens that genuinely excel at all three specs simultaneously exist, but they sit at a steep price premium, and for most single-use cases you're paying for capability you won't use. A gaming-focused screen with a mediocre HDR implementation but an excellent refresh rate will serve a competitive gamer far better than a "does everything" screen at the same price point with compromises across the board.</p>

<h2>Practical shopping checklist</h2>
<ol>
<li>Decide your primary use case first — don't shop by spec sheet before deciding what you'll actually watch or do.</li>
<li>For HDR, check the screen's actual peak brightness rating, not just whether it carries an HDR label.</li>
<li>For refresh rate, confirm whatever will connect to the screen (console, graphics card, streaming source) can actually produce content at that rate.</li>
<li>See the screen in person if at all possible — spec sheets don't capture real-world panel quality differences between similarly-specced screens.</li>
</ol>

<p><strong>Do I need 4K for a small monitor sitting close to my desk?</strong> The benefit is smaller than on a large TV at distance, but text sharpness still improves noticeably on most desk setups — it's a matter of diminishing rather than absent returns.</p>
<p><strong>Is HDR worth paying extra for?</strong> Only on a screen with genuinely capable brightness hardware; an HDR label on a budget panel often provides little real benefit over standard range.</p>
<p><strong>What refresh rate is "enough" for casual gaming?</strong> A moderate step up from the baseline is noticeable to most people; going much higher matters mainly for competitive or fast-action genres.</p>
HTML,
    ];

    $A[] = [
        'title' => 'Two-Factor Authentication Methods Ranked: SMS vs Authenticator Apps vs Hardware Keys',
        'cat' => 'cybersecurity', 'type' => 'comparison', 'icon' => 'fa-mobile-retro', 'gradient' => 'g4', 'trending' => 1,
        'tags' => 'two-factor authentication, 2FA, account security, cybersecurity',
        'meta_kw' => 'best 2fa method, sms vs authenticator app, hardware security key vs app',
        'excerpt' => 'Not all two-factor authentication is equally secure. Here is an honest ranking of the common methods, what each actually protects against, and where each one still falls short.',
        'body' => <<<HTML
<p>Two-factor authentication adds a second check beyond your password — something you have or something you are, on top of something you know. Turning it on at all is a bigger security improvement than which specific method you choose, but the methods are genuinely not equally secure, and knowing the real differences helps you protect your most important accounts appropriately.</p>

<h2>SMS text message codes</h2>
<p>A code arrives by text message and you type it in after your password. It's the most widely supported method and requires no extra app or hardware, which is exactly why it's still the default on many services. The weakness is well documented: phone numbers can be hijacked through a process called SIM swapping, where an attacker convinces a carrier to transfer your number to a device they control, after which they receive your codes directly. It's also vulnerable if someone has ongoing access to your messages through a compromised account linked to your carrier.</p>
<p>SMS 2FA is still meaningfully better than no second factor at all, and for lower-value accounts it's a reasonable baseline. For anything financially or personally critical, it shouldn't be your only option if a stronger one is available.</p>

<h2>Authenticator apps</h2>
<p>An app on your phone generates a new short-lived code every thirty seconds or so, based on a secret shared only between the app and the service when you set it up. Because the code is generated locally on your device rather than transmitted over the phone network, it isn't vulnerable to SIM swapping at all. The main practical risk is losing the device without having saved backup codes, which can lock you out of your own account — a real hassle, but a security-preferable failure mode over being silently hijacked.</p>
<p>This is a strong, free, widely supported middle-ground option, and for most people's most important accounts, it's a reasonable default choice.</p>

<h2>Push notification approval</h2>
<p>Instead of typing a code, you get a notification on a trusted device and simply approve or deny the login attempt. It's more convenient than typing codes and still avoids the SIM-swapping weakness of SMS. The risk here is "approval fatigue" attacks, where an attacker who already has your password sends repeated approval requests hoping you'll tap approve out of habit or confusion. Services that show login location and device details on the approval screen — and that you actually read before tapping — mitigate this significantly.</p>

<h2>Hardware security keys</h2>
<p>A small physical device you plug in or tap to approve a login. This is currently the strongest widely available option against remote attacks, because the cryptographic proof of "you have this specific physical object" can't be phished, intercepted, or replicated remotely the way a code can be. Even a very convincing fake login page can't extract what it needs from a hardware key the way it can trick someone into typing a code.</p>
<p>The trade-offs are real: cost, the need to carry the physical key, and a genuine risk of lockout if you lose it without a registered backup key or alternate recovery method set up in advance. For most people, this level of protection is worth reserving for their single most critical account or two — usually the primary email address that can reset everything else.</p>

<h2>A practical ranking for most people</h2>
<ol>
<li><strong>Hardware key</strong> — strongest, worth it for your most critical one or two accounts.</li>
<li><strong>Authenticator app</strong> — strong, free, and practical as a default for most important accounts.</li>
<li><strong>Push notification approval</strong> — convenient and solid, provided you actually check the details before approving.</li>
<li><strong>SMS codes</strong> — better than nothing, acceptable for lower-stakes accounts, avoid as the only option on anything critical.</li>
</ol>

<h2>The most important step regardless of method</h2>
<p>Whatever method you choose, save the backup/recovery codes the service gives you when you set it up, and store them somewhere secure and separate from the device providing your second factor — inside a password manager's secure notes, for instance. Losing your second factor without backup codes is a far more common real-world problem than any of the attacks above, and it's entirely avoidable with thirty seconds of setup.</p>

<p><strong>Is SMS 2FA still worth turning on if it's my only option?</strong> Yes — it blocks the overwhelming majority of automated password-based attacks and is a significant improvement over no second factor at all.</p>
<p><strong>Can I use more than one 2FA method on the same account?</strong> Most services allow registering multiple methods, which is genuinely the best practice — a strong primary method plus a backup so you're never locked out by losing one device.</p>
<p><strong>Do hardware keys work on phones too, not just laptops?</strong> Modern hardware keys generally support both, connecting via USB or tapping wirelessly to phones that support the required short-range connection.</p>
HTML,
    ];

    $A[] = [
        'title' => 'How Search Engines Actually Decide What Ranks First',
        'cat' => 'how-to-guides', 'type' => 'article', 'icon' => 'fa-magnifying-glass', 'gradient' => 'g1', 'trending' => 0,
        'tags' => 'SEO, search engines, how google works, content strategy',
        'meta_kw' => 'how google ranks pages, how search engines work, seo basics explained',
        'excerpt' => 'Search rankings can feel like a black box, but the underlying process is fairly consistent across engines. Here is a clear, non-hyped explanation of how a page actually gets evaluated and ranked.',
        'body' => <<<HTML
<p>Search engine ranking gets treated like mysterious magic by a lot of marketing content, but the underlying process is fairly logical once you break it into its actual stages: finding pages, understanding what they're about, and deciding which ones best answer a given query. Understanding these stages honestly is more useful than chasing whatever the latest "SEO trick" claims to be.</p>

<h2>Stage one: crawling — finding that a page exists</h2>
<p>Before anything can rank, a search engine has to discover the page exists at all. Automated programs called crawlers follow links from page to page across the web, and also read sitemap files that site owners can submit directly to speed up discovery. A page with no links pointing to it and no sitemap entry may simply never get found, no matter how good its content is — this is the single most common reason a real, quality page never appears in search results at all.</p>

<h2>Stage two: indexing — understanding what a page is about</h2>
<p>Once found, a page gets analyzed and stored in a massive index — essentially the search engine's own catalog of the entire crawled web. This is where the page's actual content, structure, and signals get parsed: what topic it covers, what other pages link to it and with what surrounding text, how the content is organized with headings, and increasingly, structured data that explicitly tells the engine what type of content it's looking at (an article, a product, a recipe, and so on).</p>
<p>A page can be crawled but not indexed — for instance, if it explicitly tells crawlers not to index it, or if the engine judges it too thin or too similar to content that already exists elsewhere.</p>

<h2>Stage three: ranking — deciding the order for a specific search</h2>
<p>This is the stage most people mean when they say "SEO," and it happens fresh for every individual search query. The engine evaluates every indexed page that seems relevant to that specific query and ranks them using hundreds of signals working together, not any single factor in isolation. Some of the broad categories that consistently matter:</p>
<ul>
<li><strong>Relevance</strong> — how directly the content actually answers what was searched, based on the words, structure, and topic coverage of the page.</li>
<li><strong>Authority and trust signals</strong> — how many other credible pages link to this content, treated roughly as a vote of confidence from the rest of the web.</li>
<li><strong>User experience signals</strong> — page load speed, mobile usability, and whether visitors tend to quickly leave and go back to the search results (a sign the page didn't actually satisfy what they were looking for).</li>
<li><strong>Freshness</strong> — for queries where recency matters (news, fast-changing topics), more recently updated content is favored; for timeless topics, this signal matters far less.</li>
</ul>

<h2>Why "keyword stuffing" stopped working years ago</h2>
<p>Early search engines relied heavily on simple keyword matching, which is exactly why content stuffed with repeated keywords used to rank well. Modern engines evaluate meaning and context far more than exact keyword repetition, which is why unnaturally repetitive text now reads as a spam signal rather than a relevance signal. Writing naturally for an actual human reader, while still clearly covering the topic a searcher cares about, consistently outperforms mechanically repeating a target phrase.</p>

<h2>What actually moves the needle for a real page</h2>
<ol>
<li><strong>Answer the actual question thoroughly</strong> — thin content that doesn't fully address what someone searched for rarely holds a good position, regardless of other optimization.</li>
<li><strong>Structure content clearly</strong> — headings, lists, and logical organization help both readers and engines understand what the page covers section by section.</li>
<li><strong>Earn genuine links</strong> — content other sites actually want to reference remains one of the strongest trust signals available, and it can't be faked convincingly at scale.</li>
<li><strong>Keep technical basics solid</strong> — a fast-loading, mobile-friendly page with correct metadata removes friction that would otherwise work against otherwise-good content.</li>
<li><strong>Use structured data honestly</strong> — markup that accurately describes the content (an article, a product, an FAQ) helps engines display it correctly and can unlock richer search result appearances.</li>
</ol>

<h2>A realistic timeline</h2>
<p>New content typically takes real time to be crawled, indexed, and then to accumulate the trust signals that support a strong ranking — this is measured in weeks and months, not hours, for most sites and topics. Anyone promising guaranteed rankings on a fast fixed timeline is oversimplifying a process that depends on genuine competition from every other page targeting the same query.</p>

<p><strong>Does submitting a sitemap guarantee fast indexing?</strong> It speeds up discovery significantly but doesn't guarantee indexing — the content still has to clear the engine's quality bar to be added to the index.</p>
<p><strong>Do social media shares directly affect ranking?</strong> Most evidence suggests they aren't a direct ranking factor, though the extra visibility can indirectly lead to more genuine links and traffic, which do matter.</p>
<p><strong>Is it worth using this site's own SEO tools while writing?</strong> Yes — checking real-time title/description length against typical result truncation, or previewing exactly how a page will appear in results, catches avoidable mistakes before publishing rather than after.</p>
HTML,
    ];

    $A[] = [
        'title' => 'Cloud Storage Compared: Which Service Actually Fits How You Use Files',
        'cat' => 'comparisons', 'type' => 'comparison', 'icon' => 'fa-cloud-arrow-up', 'gradient' => 'g5', 'trending' => 0,
        'tags' => 'cloud storage, comparison, file backup, productivity',
        'meta_kw' => 'best cloud storage, cloud storage comparison, which cloud storage to choose',
        'excerpt' => 'Every cloud storage service claims to be the best, but the right one depends entirely on how you actually work. Here is a practical way to compare them that goes beyond the free storage number on the pricing page.',
        'body' => <<<HTML
<p>Cloud storage comparisons usually lead with the free storage tier, but that number is close to the least important factor for most people once they actually start using one daily. What matters far more is how well a service fits into tools you already use, how it handles conflicts and large files, and what happens if you ever need to leave.</p>

<h2>Start with where your files already live</h2>
<p>If your work already runs primarily through a specific productivity suite, the storage service built around that same ecosystem usually integrates far more smoothly than a third-party alternative — documents open directly for editing rather than needing to be downloaded and re-uploaded, and permissions sync automatically with accounts you're already using. Fighting your existing ecosystem to use a "better" standalone service often costs more in daily friction than it gains in raw storage or price.</p>

<h2>Sync behavior matters more than most people realize</h2>
<p>Two files edited offline by different people, then both reconnecting to the internet, is a genuinely common scenario — and services handle it very differently. Good sync engines create clearly labeled conflict copies so no work is silently lost. Weaker ones can overwrite changes with far less warning. If you regularly work offline or collaborate with others editing the same files, test this behavior deliberately before trusting a service with anything irreplaceable.</p>

<h2>Large file and media handling</h2>
<p>Storing thousands of small documents is a very different workload from storing large video files or extensive photo libraries. Some services impose individual file size caps that make them impractical for video work specifically, while others are built with media-heavy use in mind, including features like automatic photo organization or video preview generation. Check actual file size limits, not just total storage, if your files run large.</p>

<h2>Sharing and permissions</h2>
<p>How granular is access control — can you share a single file versus an entire folder, set an expiration date on a shared link, or require a password to view? For anyone sharing files with clients or people outside their organization regularly, these details matter enormously and vary a lot between services that otherwise look similar on a feature comparison chart.</p>

<h2>What happens if you need to leave</h2>
<p>This is the question almost nobody asks until it's urgent. Can you bulk-export everything easily, including folder structure and file metadata, or does leaving mean manually re-downloading everything file by file? A service that makes leaving deliberately painful is a red flag regardless of how good its other features look, because it signals the company is optimizing for lock-in rather than for actually serving you well.</p>

<h2>A practical way to actually decide</h2>
<ol>
<li>List the two or three tools you use most for creating and editing files, and check which storage service integrates most directly with them.</li>
<li>Estimate your realistic file sizes (photos, video, documents) and check the service's actual per-file limits, not just total storage.</li>
<li>If you collaborate with others, test sync-conflict behavior with a throwaway file before trusting it with real work.</li>
<li>Confirm there's a straightforward bulk-export option before you commit anything irreplaceable to the service.</li>
</ol>

<h2>Don't underestimate the value of a genuine backup habit</h2>
<p>Whichever service you choose, cloud storage syncing is not automatically the same thing as a real backup — if a file gets corrupted or deleted and that change syncs immediately, the "backup" in the cloud reflects the same broken state. Check whether your service keeps real version history you can roll back to, and treat that version history, not just the sync itself, as your actual safety net.</p>

<p><strong>Is more free storage always the better deal?</strong> Not if it comes from a service that doesn't fit how you actually work — the daily friction cost of a poor-fit tool usually outweighs a larger free tier within the first few weeks of real use.</p>
<p><strong>Can I use more than one cloud storage service at once?</strong> Yes, and many people do — one for work documents tied to a work ecosystem, another for personal photos, for instance. Just be deliberate about it so you don't lose track of where a specific file actually lives.</p>
<p><strong>How do I know if a service has real version history?</strong> Check the account settings or help documentation for "version history" or "file recovery" specifically — it's a distinct feature from basic sync and not every plan tier includes a meaningful retention window.</p>
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
