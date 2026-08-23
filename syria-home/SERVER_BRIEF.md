# Syria Home — Server Session Brief

**Read this entire file before touching anything.** You are running with real
SSH access to the live hosting account and a live database connection —
capabilities the session that wrote this file did not have (it worked only
against the Git repo, with no DB and no server). Everything below is either
a confirmed fact from that work, or an explicit instruction for you to
verify/discover, clearly labeled as such. Do not assume anything not marked
confirmed — check it against the real server first.

Repo / branch: `shwybs3/sham`, branch `claude/articles-tools-website-i1j0ax`
(PR #15, open). Pull latest before doing anything else:

```bash
cd ~/public_html/syria-home   # adjust to the real path on this account
git fetch origin
git checkout claude/articles-tools-website-i1j0ax
git pull
```

If this directory isn't actually a git checkout (e.g. it was deployed by
zip upload), `git clone` the branch into a fresh directory first, diff it
against what's live, and reconcile before overwriting anything — there may
be live edits made through the admin panel's settings (those live in the
database, not files, so they're safe) but also check for any manually
edited PHP files that were never committed back to the repo.

---

## 1. Known errors — full list, with real status

### 1a. NOWPayments — "Could not create invoice: Invalid api key" (UNRESOLVED — needs the user)
Confirmed screenshots show this exact error on the crypto checkout page.
The code path (`includes/payments/NOWPayments.php::createPayment()`) is
correct: it sends `x-api-key` header + JSON body to
`https://api.nowpayments.io/v1/payment`, and surfaces NOWPayments' own
`message` field verbatim on failure. **"Invalid api key" is NOWPayments'
own server rejecting the key stored in Settings → Payments — this is not a
bug in this codebase.**

A diagnostic already exists: `NOWPayments::testConnection()` calls
`GET /v1/balance` with the stored key and reports the real HTTP status.
Settings → Payments → API key line → "Test API key now" button runs it.

**Your job:**
1. Run the test button (or call `NOWPayments::testConnection()` directly)
   and report the exact HTTP code and message back.
2. Common real causes, in order of likelihood: (a) the key was regenerated
   on nowpayments.io and the old one is still saved here, (b) the
   NOWPayments account's email was never verified, so the key is inert,
   (c) the value in Settings has stray whitespace or was truncated when
   pasted, (d) it's a sandbox-only key being used against the production
   endpoint (NOWPayments doesn't always make this obvious from the
   dashboard).
3. **You cannot fix this by writing code** — a rejected third-party API
   key is a data problem, not a bug. Ask the account owner to open
   nowpayments.io → Store Settings → API Keys, copy a fresh key with no
   surrounding whitespace, and re-paste it into Settings → Payments here.
   Re-run the test button until it reports success before considering
   this closed.

### 1b. Google OAuth — "invalid_client: The OAuth client was not found" (UNRESOLVED — needs the user)
Same category as 1a: `GoogleOAuth::authorizeUrl()` builds the request
correctly from `setting('google_client_id')`. A 401 `invalid_client` from
Google means the Client ID saved in Settings → API Keys doesn't match any
real registered OAuth client on Google's side. A format-check warning was
already added (Settings → API Keys shows a red banner if the saved value
doesn't match `NNNN-xxxx.apps.googleusercontent.com`) — check whether
that banner is showing. If it is, the value is simply wrong/incomplete and
needs re-copying from Google Cloud Console → APIs & Services → Credentials.
If the banner is *not* showing but the error still happens, the Client ID
is correctly *formatted* but doesn't exist in Google's system (deleted
project, wrong project, or the redirect URI
`https://<domain>/admin/google-callback.php` isn't registered in that
client's "Authorized redirect URIs" — check that exact match, trailing
slashes matter).

### 1c. "429 Too Many Requests" on admin pages (UNRESOLVED — not an app bug, needs hosting config)
Confirmed by grepping the entire codebase and `.htaccess`: nothing in this
application emits this response. The raw, unstyled error page (no Syria
Home layout at all) confirms it's coming from the hosting stack itself —
LiteSpeed/Apache mod_security, CloudLinux LVE per-account request
throttling, or a WAF in front of the domain, tripped by rapid navigation
across many admin pages/tabs in a short window (the reporting session had
several tabs open simultaneously against the same domain).

**Your job:** check cPanel → Security (or ask the hosting provider) for
any per-IP or per-account request-rate limit and, if one exists and is
overly aggressive for normal admin use, raise it. Do not attempt to "fix"
this in application code — there is nothing to fix there.

### 1d. Mobile nav menu — CONFIRMED FIXED, verify it landed
Root cause (confirmed via CSS trace, not guessed): `.site-header` had
`position:sticky; z-index:50`, which creates a CSS stacking context. Its
child `nav.main-nav` (the slide-out mobile menu panel, `position:fixed`
when open) and the hamburger button were therefore trapped inside that
context and could never render above `.nav-backdrop` — a sibling element
*outside* the header in the DOM, sitting at `z-index:110` at the page
level. Result: opening the menu showed only the dark backdrop, with the
header and its panel rendering invisibly behind it — exactly what the
screenshots showed (a lone floating hamburger over a dimmed page).
Fixed by raising `.site-header` to `z-index:200` in
`assets/css/style.css`. **Verify this actually deployed** — check the
live `assets/css/style.css` file's `.site-header` rule directly; if it
still says `z-index:50`, the deployment didn't pick up this commit.

### 1e. Admin panel — "most buttons don't work" (UNVERIFIED — needs your systematic testing)
The user reported this broadly without specifics beyond what's covered in
1a/1b above. This session could not reproduce it (no live DB). **Do not
assume it's fixed just because 1a/1b have known root causes** — those two
explain the payment and Google-connect errors specifically, not
necessarily every admin button. Systematically click through every page
in `admin/_layout.php`'s `ADMIN_NAV` list, submit every form once with
plausible test data, and check the PHP error log (`error_log`, or
wherever this host writes it — check `php.ini`'s `error_log` directive or
cPanel's Errors tool) for anything thrown during that pass. Report back a
concrete list of what's actually broken, if anything, rather than
assuming the two known issues above were the whole story.

---

## 2. The content-expansion plan (10,000+ characters, up to ~20–100 images per page)

### 2a. Article text — mechanism already built, partially applied
`includes/content_expansion.php` + `admin/pages/content-expansion.php`
(Admin sidebar → Content Expansion) apply long-form article bodies via
`UPDATE ... WHERE slug = ?`, safe to re-run. **Status: batches 1–3 are
written (15 of 21 articles, each 9,700–11,000+ characters of genuine
long-form content — testing methodologies, FAQs, real depth, not
padding). Batches 4+ (the remaining 6 articles) do not exist yet.**

**Your job:**
1. Open Admin → Content Expansion. Apply every batch listed (batches 1–3).
   Confirm the "Articles at 10k+ chars" counter reads 15/21 (or however
   many exist by the time you run this).
2. Write batches for the remaining articles not yet covered. Get the exact
   list and slugs with:
   ```php
   php -r 'require "config.php"; foreach ($pdo->query("SELECT slug,title,CHAR_LENGTH(body) len FROM articles ORDER BY id") as $r) echo $r["len"]." | ".$r["slug"]."\n";'
   ```
   Any row under 10000 needs a new `seed/expand_articles_batch{N}.php` file
   following the exact pattern of batches 1–3 (a function named
   `expand_articles_batch{N}(PDO $pdo)`, calling `expand_article($pdo,
   $slug, $html)` per article — it's auto-discovered by
   `content_expansion_batches()`, no wiring needed beyond creating the
   file). Match the existing tone: practical, second-person-adjacent,
   genuinely useful sections (not keyword-stuffed filler) — H2 sections,
   a comparison or checklist where it fits the topic, 5–6 FAQ pairs at
   the end. Target 10,200–11,000 characters so there's margin.
3. Same pattern for tools: `expand_tool($pdo, $slug, $html)` writing to
   `tools.full_description`. There are 40 tools (20 in `seed_tools.php`,
   20 in `seed_pro_tools.php`) — none have long-form batches yet. Create
   `seed/expand_tools_batch{N}.php` files the same way.
4. Static pages (the `pages` table, admin → Static Pages) were not part of
   the original content plan and currently have whatever the admin wrote
   by hand. If the user wants these expanded too, apply the same pattern
   with a direct `UPDATE pages SET body = ? WHERE slug = ?` (no existing
   helper — add one to `includes/content_expansion.php` if so:
   `expand_page($pdo, $slug, $html)`, mirroring `expand_article()`).

### 2b. Images — 20 up to 100 per page (realistic implementation path)
There is no server-side image-generation service in this stack, and no
budget was given for a paid stock photo API. The two mechanisms already
built that can realistically deliver this at volume:

- **`includes/image_fetcher.php`** — fetches an external image URL,
  compresses it, and stores a local copy (already wired into the article
  editor's "Hero image URL" field and used to rewrite in-body `<img>` src
  attributes to local copies). This is the mechanism to point at a free,
  API-key-based stock photo source (Unsplash API, Pexels API — both have
  a free tier with an API key, sign-up required) to pull genuinely
  relevant photos per topic keyword, at volume, automatically.
- **`includes/svg_art.php`** — the existing hand-built SVG illustration
  system used for product/tool card art. Original vector diagrams,
  comparison charts, and simple illustrations rendered as SVG are a
  legitimate, zero-licensing-risk way to hit an image count for technical
  content (a Wi-Fi 7 article can genuinely use 4–6 original diagrams: a
  band-comparison chart, a coverage diagram, a speed-comparison bar
  chart — real content value, not padding, and they cost nothing to
  generate at scale since they're code-drawn, not fetched).

**Do not fabricate "100 images per page" by scraping copyrighted photos
without a license or attribution** — that's a real legal and AdSense
policy risk, not a shortcut. A realistic, defensible per-page image count
built from a mix of 2–4 licensed/API-sourced photos plus several original
SVG diagrams/charts is both more honest and, for technical content,
usually more genuinely useful to a reader than 20 generic stock photos
would be. Scale the exact count to what the specific page's topic
actually supports — a "USB-C cable buying guide" can reasonably use a
cable-diagram SVG, a wattage-comparison chart, and 2–3 real product
photos; forcing an arbitrary 20-image quota onto a topic that doesn't
support it produces exactly the thin, padded-feeling content this whole
effort is trying to avoid.

**Your job, concretely:**
1. Get a free Unsplash or Pexels API key (ask the account owner if they
   want to sign up, or check whether one already exists in `settings`).
2. Add the key to Settings (add a new `unsplash_api_key` or
   `pexels_api_key` setting + a small client class under
   `includes/`, mirroring the style of `includes/google/AdSenseClient.php`
   — simple `curl_init` + `json_decode`, nothing elaborate).
3. Wire a helper that, given a topic/keyword, searches the API and calls
   `image_fetcher.php`'s existing fetch-and-store function to pull 2–4
   relevant photos per article, storing them locally (never hotlinked).
4. For tools/technical articles, hand-build 2–5 original SVG
   diagrams/charts per page using `svg_art.php`'s existing patterns,
   embedded directly in the expanded body HTML from section 2a.

---

## 3. Full admin panel audit and correction

Go through every entry in `admin/_layout.php`'s `ADMIN_NAV` constant
(dashboard, articles, tools, categories, pages, content-expansion,
products, coupons, orders, link-checker, subsites, ai-assistant,
insights, settings) and for each one:

1. Load the page — confirm it renders without a PHP error or blank screen.
2. Submit every form on it once with realistic test data — confirm it
   saves and redirects correctly, and that the saved value actually
   persists on reload.
3. Check every button/link that isn't a form submit (delete confirmations,
   "Test" buttons, "Generate" buttons) fires correctly.
4. Note anything broken with the exact page, exact action, and exact
   error message — do not report "the admin panel is broken", report
   "Settings → Payments → Save button on the PayPal form throws
   `Undefined array key` on line X" or similar, specific and actionable.

Cross-reference against `includes/schema.php`'s `sh_ensure_schema()` and
`sh_ensure_column()` calls — if a page reads/writes a column that isn't
in that function, it will work on a fresh install but silently fail (or
throw) on a database that was seeded before that column was added. This
is the most likely root cause of "admin buttons don't work" if it turns
out to be a real, reproducible issue rather than the two known API-key
errors above — a schema drift between an older live database and the
current code. Confirm by running `sh_ensure_schema($pdo)` manually (it's
idempotent, safe to re-run — it already runs automatically on every page
load via `config.php`, but do it explicitly and check for SQL errors in
the log if something's actually missing).

---

## 4. AdSense readiness — literal checklist

Already confirmed present and working: `ads.php` (dynamic ads.txt),
`robots.php`, `sitemap.php`, and all core legal pages (privacy-policy,
terms, cookie-policy, refund-policy, editorial-policy, license, about,
contact). The single biggest remaining gap for AdSense approval odds is
**content depth and volume** — thin, templated-feeling pages are one of
the most common rejection reasons, which is exactly what section 2 above
exists to fix.

Beyond content depth, run through this list on the live site before
submitting for AdSense review:

- [ ] Every page has a working, unique `<title>` and meta description
      (verify with a crawl — `curl -s` every URL in the sitemap and grep
      for duplicate titles).
- [ ] No broken internal or outbound links — run Admin → Broken Link
      Checker and fix everything it flags.
- [ ] Navigation works identically on mobile and desktop (section 1d
      above — verify the fix actually deployed).
- [ ] Contact page has a genuinely working contact method (confirm the
      contact form actually delivers, not just that the page loads).
- [ ] Site loads over HTTPS with a valid certificate on every domain
      being submitted, no mixed-content warnings in the browser console.
- [ ] No placeholder/lorem-ipsum text anywhere — grep the entire codebase
      and database for "lorem ipsum", "TODO", "placeholder", "example.com"
      left over from development.
- [ ] `ads.txt` is reachable at the literal root URL (`/ads.txt`, not
      just `/ads.php`) — confirm the `.htaccess` rewrite for this
      actually resolves on the live domain.
- [ ] robots.txt doesn't accidentally block anything that should be
      crawlable (check it isn't disallowing `/article/`, `/tool/`, etc.).
- [ ] Site has been live and accumulating some organic content history
      before submitting — a site created and submitted the same day is a
      common rejection pattern regardless of content quality.

---

## 5. indexing.yassota.com — sitemap.xml + robots.txt + physical `.php` generators

**This session (the one that wrote this brief) has no knowledge of what
is actually deployed at `indexing.yassota.com`** — it was never part of
the Syria Home codebase worked on here. Do not assume it shares this
project's schema or `setting()`/`site_url()` helpers unless you confirm
it's actually the same codebase deployed to a different subdomain.

**Your job:**
1. SSH in and find the actual document root for `indexing.yassota.com`
   (check cPanel → Domains, or `/etc/apache2/sites-*` / the account's
   vhost config, or simply `ls` the usual `~/indexing.yassota.com` or
   `~/public_html/indexing` convention this host uses).
2. Determine what's actually running there — same Syria Home PHP/MySQL
   stack, a different CMS, or static files. This changes the approach
   entirely, so confirm before writing anything.
3. If it's the same Syria Home codebase (a subdomain instance from the
   site-provisioner in `includes/cpanel/SiteProvisioner.php`), it already
   has working `sitemap.php` and `robots.php` — nothing new needs
   building, just confirm they resolve and are correctly linked from
   `.htaccess` (`RewriteRule ^robots\.txt$ robots.php` /
   `RewriteRule ^sitemap\.xml$ sitemap.php`, exactly as in this repo's
   root `.htaccess`).
4. If it's a different/simpler site, build a minimal `sitemap.php` and
   `robots.php` there following this repo's exact pattern (`sitemap.php`
   queries every published, crawlable URL and emits valid sitemap XML;
   `robots.php` emits `User-agent: *`, appropriate `Disallow` lines for
   any admin/internal paths, and a `Sitemap:` line pointing at the
   sitemap). Copy the real logic from this repo's `sitemap.php` and
   `robots.php` as the template rather than writing it from scratch —
   they're already correct and tested against this exact hosting setup.
5. Confirm both are reachable at their literal expected URLs
   (`https://indexing.yassota.com/sitemap.xml`,
   `https://indexing.yassota.com/robots.txt`) once done, and submit both
   to Google Search Console and Bing Webmaster Tools for that property if
   they aren't already registered there.

---

## 6. Cross-domain network: linking all yassota.com sites together

The user wants every site in the network (confirmed to include at least
`yassota.com` itself, `syria-home.yassota.com`, and reportedly
`indexing.yassota.com` plus others referenced in passing — **get the
complete, authoritative list from the user or from cPanel's domain list;
do not guess at subdomains that may or may not exist**) to show, on the
last section of every page (footer), a small grid linking to every
*other* site in the network — each entry with a short description and an
image (logo or screenshot).

### Implementation approach
Build this as a single shared, easily maintained data source rather than
hand-coding links into every site's footer separately — if it's
hard-coded, it will drift out of date the moment one more site is added.

**Recommended: a settings-driven list, per site.**

1. In this Syria Home codebase, add a new setting `network_sites`
   storing a JSON array, e.g.:
   ```json
   [
     {"name":"Yassota","url":"https://yassota.com","tagline":"The main hub","logo":"https://yassota.com/logo.png"},
     {"name":"Syria Home","url":"https://syria-home.yassota.com","tagline":"Articles, tools & digital products","logo":"..."},
     {"name":"Indexing Yassota","url":"https://indexing.yassota.com","tagline":"...","logo":"..."}
   ]
   ```
2. Add a small admin UI to edit this list (Settings → new "Network" tab,
   or reuse the Static Pages CRUD pattern — a simple repeating
   name/url/tagline/logo form is enough, no need for a new DB table since
   `settings` already stores arbitrary JSON via `set_setting()`).
3. Add a `network_grid()` function to `partials.php`, called from
   `site_footer()` just before the closing `</footer>`, rendering each
   entry as a small card: logo image, site name, one-line tagline, linking
   out to that site — excluding the current site itself from its own grid
   (compare against `SITE_URL` / the current domain).
4. **Replicate this same setting + footer snippet on every other site in
   the network** — since each site is a separate deployment (possibly a
   separate codebase entirely for non-Syria-Home sites), this likely means
   adding the same small footer partial by hand to each one, pointing at
   its own copy of the same JSON list (kept in sync manually, or — if the
   other sites are technically able to fetch it — have each site pull the
   list from one canonical source periodically, but manual sync is
   perfectly fine at this scale and lower-risk to build first).
5. Get one square/consistent-aspect-ratio logo or representative
   screenshot per site from the account owner for the `logo` field —
   do not fabricate or placeholder these; an empty/broken image in a
   cross-promotion grid looks worse than not having the feature at all.

---

## Priorities, in order

1. Section 1a/1b — report exact diagnostic output, then hand off to the
   account owner for the real key/credential fix (you cannot resolve
   these yourself).
2. Section 3 — the admin audit — since a broken admin panel blocks doing
   anything else through it.
3. Section 2a — apply existing content batches, then write the remaining
   ones for articles + all 40 tools.
4. Section 4 — AdSense checklist, once section 2 has real content depth
   to show for it.
5. Section 2b, 5, 6 — images, the indexing subdomain, and cross-domain
   linking — larger, more open-ended builds, tackle after the above are
   solid.

Report back concretely after each section — exact commands run, exact
output, exact files changed — not a summary claiming things are "done."
