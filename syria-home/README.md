# Syria Home

A self-contained PHP/MySQL content + tools site: news/articles/comparisons/tutorials, 20 free client-side web tools, an admin panel with a scoped AI content assistant, and OAuth-based integrations for Google AdSense Management, Search Console, Analytics (GA4) and Google Ads.

Built as an independent app inside this repo (`syria-home/`) so it can be deployed to its own subdomain (e.g. `syria-home.yassota.com`) without touching the existing `yassota` app-store site in the rest of this repository.

## 1. Deploy the files

Point your subdomain's document root at the `syria-home/` folder (or upload its contents to that subdomain's `public_html`). Requirements: PHP 8.0+ with `PDO_MySQL`, `cURL`, `DOM`, and `GD`; MySQL 5.7+/MariaDB; a host that supports `.htaccess` (Apache/LiteSpeed with `mod_rewrite`).

## 2. Run the install wizard

Visit the subdomain in a browser — you'll be redirected automatically to `/install/`. The wizard will:

1. Check server requirements.
2. Ask for your site name/tagline/description.
3. Ask for MySQL connection details (it tests the connection before continuing — no manual SQL import needed, tables are created automatically).
4. Create your admin account.
5. Seed the database with 21 ready-to-publish articles and all 20 web tools, then lock itself (`install/install.lock`) so it can't be run again by accident.

Afterwards: **Homepage** at `/`, **Admin panel** at `/admin/`.

## 3. Connect Google APIs (AdSense · Search Console · Analytics · Ads)

This is the part that needs *your* Google Cloud project — no one can do this for you, since it requires your own Google account and (for AdSense/Ads) your own advertiser/publisher accounts.

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → create or select a project.
2. **APIs & Services → Library**: enable these four APIs:
   - AdSense Management API
   - Google Search Console API
   - Google Analytics Data API
   - Google Ads API
3. **APIs & Services → OAuth consent screen**: set it up (External user type is fine for a single-admin site), add the four scopes used by this app (see `includes/google/GoogleOAuth.php`), and add yourself as a test user if the app stays in "Testing" mode.
4. **APIs & Services → Credentials → Create Credentials → OAuth client ID → Web application**. Add this exact Authorized redirect URI:
   ```
   https://your-domain/admin/google-callback.php
   ```
5. Copy the **Client ID** and **Client Secret** into the admin panel: **Settings → API Keys**.
6. Click **Connect Google account** in that same tab and approve the consent screen.
7. For **Google Ads** specifically, you also need a **developer token** (Google Ads → Tools → API Center on a Manager account) and your **Customer ID** — both go in Settings → API Keys. Google issues developer tokens with limited/test access by default; production-level access requires Google's separate approval.
8. For **Analytics**, add your **GA4 Property ID** (Analytics admin → Property Settings). For **Search Console**, make sure the site URL in Settings matches a property you've verified in Search Console.

Once connected, live figures show up in **Admin → Google Insights**.

## 4. AdSense — what this app does vs. what only Google can do

This app is built to be AdSense-ready: fast pages, original content, clear navigation, Privacy Policy / Terms / Editorial Policy pages, an `ads.txt` endpoint, and ad zones that stay invisible until you configure them. What it **cannot** do is get you approved — that review is done manually by Google after you apply with your Publisher ID in **Settings → Advertisements**. No ads render anywhere on the site until both a Publisher ID *and* a slot ID are set for a given placement.

## 5. Gemini / AI Assistant

Add a Gemini API key (from [Google AI Studio](https://aistudio.google.com/apikey)) or an OpenRouter key in **Settings → API Keys** to enable:
- "Generate with AI" on the Articles page (fills a draft for you to review before saving).
- The **AI Assistant** chat in the sidebar — it can draft/edit articles, edit tool copy, and update a small whitelist of site settings (name, tagline, description, social links). It intentionally **cannot** publish an article on its own, edit any file, run SQL, or touch API keys/passwords — those stay behind manual actions in the admin UI. Every AI-created article is saved as a draft.

If no Gemini key is set, the assistant automatically falls back to OpenRouter's free models.

## 6. What's included

- **21 original English articles** (news/tutorials/comparisons/reviews) with per-page SEO (title/description/keywords, canonical, Open Graph, JSON-LD `Article`/`NewsArticle`), original CSS-graphic headers (no stock photos, no copyright risk).
- **20 free client-side tools** (image converter, compressor, QR generator, password generator, JSON formatter, Base64, word counter, case converter, Lorem Ipsum, Markdown→HTML, CSV→JSON, hash generator, URL encoder, color converter, unit converter, BMI/age calculators, timestamp converter, CSS minifier, text-to-speech) — each with its own SEO'd page and JSON-LD `SoftwareApplication` schema.
- **10-product digital store** (`products.php` / `product.php`) — scripts, templates and toolkits, each with its own SEO'd page, JSON-LD `Product` schema, original SVG artwork (no stock imagery), a features/what's-included breakdown, and either real crypto checkout (see below) or a "Request to buy" form (saved to the admin **Orders & Payments** inbox), or a direct link to your own checkout page (Gumroad/Paddle/Stripe Payment Link/etc. — set per product in **Admin → Store Products**).
- Admin panel: Dashboard, Articles, Tools, Categories, Store Products, Orders & Payments, AI Assistant, Google Insights, Settings (General/API Keys/Payments/Advertisements/SEO/Security/Social).
- Dynamic `sitemap.php`/`robots.php`/`ads.php`, pretty-URL rules in `.htaccess` (including `/product/{slug}`).
- Store legal pages: `refund-policy.php` and `license.php`, linked from the footer.

**A deliberate copywriting choice in the store:** no product claims to guarantee Google AdSense approval — that's a decision only Google makes, manually, based on the buyer's own domain and content. Products are described as "AdSense-ready" (built to meet the technical/policy prerequisites) with a guarantee we can actually keep: free updates, setup support, and a 14-day refund window. See `refund-policy.php` for exactly what that covers.

## 7. Real crypto payments (NOWPayments)

This is the one payment gateway actually wired in end-to-end — everything else (Gumroad/Stripe/etc. links) is just a URL field. Setup:

1. Create a free account at [nowpayments.io](https://nowpayments.io) and grab your **API key** from the dashboard.
2. In your NOWPayments account, set the **IPN callback URL** to `https://your-domain/payment-webhook.php` and note the **IPN secret** it gives you.
3. In the admin panel, go to **Settings → Payments** and paste both the API key and the IPN secret. That single connection turns on real checkout in three places:
   - **Store products** — the "Pay with crypto" button on every product page (`product.php`) replaces the plain request form once this is configured.
   - **Tips** — a "Support the site" widget on every article and tool page, with configurable preset amounts.
   - **Premium content** — mark any article or tool "premium" with a price in its admin edit form; visitors then see an excerpt/teaser and a real "Unlock for $X" button instead of the full content. Unlocks are remembered per-browser via a cookie, granted automatically the moment `payment-webhook.php` confirms the payment.
4. `payment-webhook.php` is the **only** thing that ever marks a payment paid or grants an unlock — it verifies NOWPayments' HMAC-SHA512 signature on every call, so nothing else on the site (including a visitor manually hitting the checkout page) can fake a confirmed payment.

`checkout.php` creates one invoice per order (address, amount, QR code) and polls `payment-status.php` for confirmation — refreshing the page never creates a duplicate invoice. Without an API key configured, all three flows above fall back gracefully (request forms, external payment links, or a "not configured yet" message) instead of breaking.

## 8. SEO toolkit

- **SEO Score** — a live, in-browser checklist (title/meta length, keyword presence, subheadings, link count, word count) on every article's edit form. Nothing is sent anywhere; it's plain JavaScript reading the form fields.
- **Schema Generator** — build extra FAQ, How-To, or Review JSON-LD blocks for any saved article (Admin → edit an article → Schema Generator section), rendered alongside the automatic Article schema on that page.
- **Broken Link Checker** (Admin → Broken Link Checker) — on-demand scan of every link inside published article bodies, internal and external, with real HTTP status codes. No cron needed; click "Run scan."
- **Automated keyword linking** — when saving an article with "Auto-link matching keywords" checked, the first mention of another published article's or tool's title gets automatically linked to that page (never touches existing links).
- **Keyword Rank Tracker** (Admin → Google Insights) — real average position, clicks and impressions per query, pulled from your connected Search Console property. This intentionally does **not** claim to "track 50,000 keywords" — there's no honest, free way to report rank for keywords a site has no search visibility on yet. What's shown is exactly what Google Search itself reports, up to 250 rows per request.

## 9. Independent subdomain sites (cPanel)

**Admin → Subdomain Sites** provisions a real, fully independent copy of this entire script on a new subdomain of your choice — its own subdomain, its own MySQL database, its own admin account, seeded with the same 21 articles / 20 tools / 10 products, optionally topped up with a few AI-written articles for a niche you specify. This performs **real, live changes** on your hosting account; there is no simulation mode.

Setup, in **Settings → Subdomains**:
1. In cPanel: **Security → Manage API Tokens → Create**, and copy the token.
2. Enter your cPanel host, username, that API token, and your root domain.
3. Enter your account's **home directory** (visible in cPanel → File Manager, e.g. `/home/yourusername`) — subdomains created under the same cPanel account share this server's filesystem, so provisioning works by writing files directly rather than over FTP/SSH.

Each subdomain site gets its own document root at `public_html/{subdomain}` and its own database — nothing is shared with the main site except, if you choose, your Gemini/OpenRouter/AdSense keys (copied over automatically so the new site's AI features and ads work immediately). Provisioning requires explicitly checking a confirmation box in the admin UI before it runs.

## 10. Security notes

- `config.generated.php` and `install/install.lock` are git-ignored — never commit real DB credentials.
- Never paste API keys/secrets into chat, commits, or anywhere but the Settings form — they're stored server-side only.
- The AI assistant's write access is scoped and validated server-side (see `admin/pages/ai-assistant.php`); it cannot execute code or touch files.
- Your NOWPayments **IPN secret** is what makes `payment-webhook.php` trustworthy — without it set, incoming webhook calls are rejected outright rather than trusted blindly.
- Your cPanel **API token** can create real subdomains and databases on your account — treat it like a password, and prefer a scoped token if your host offers one.
