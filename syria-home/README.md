# Syria Home

A self-contained PHP/MySQL content + tools site: news/articles/comparisons/tutorials, 20 free client-side web tools, an admin panel with a scoped AI content assistant, and OAuth-based integrations for Google AdSense Management, Search Console, Analytics (GA4) and Google Ads.

Built as an independent app inside this repo (`syria-home/`) so it can be deployed to its own subdomain (e.g. `syria-home.yassota.com`) without touching the existing `yassota` app-store site in the rest of this repository.

## 1. Deploy the files

Point your subdomain's document root at the `syria-home/` folder (or upload its contents to that subdomain's `public_html`). Requirements: PHP 8.0+ with `PDO_MySQL`, `cURL`, and `GD`; MySQL 5.7+/MariaDB; a host that supports `.htaccess` (Apache/LiteSpeed with `mod_rewrite`).

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
- Admin panel: Dashboard, Articles, Tools, Categories, AI Assistant, Google Insights, Settings (General/API Keys/Advertisements/SEO/Security/Social).
- Dynamic `sitemap.php`/`robots.php`/`ads.php`, pretty-URL rules in `.htaccess`.

## 7. Security notes

- `config.generated.php` and `install/install.lock` are git-ignored — never commit real DB credentials.
- Never paste API keys/secrets into chat, commits, or anywhere but the Settings form — they're stored server-side only.
- The AI assistant's write access is scoped and validated server-side (see `admin/pages/ai-assistant.php`); it cannot execute code or touch files.
