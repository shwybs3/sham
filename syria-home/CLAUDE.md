# Syria Home — project notes for unattended agent runs

This file is auto-loaded by Claude Code when it runs with this directory as
its working directory — including headless runs launched from
Admin → Agent Console (`admin/pages/agent-console.php`), which run with
`--permission-mode bypassPermissions` and no human confirming each step.
Read this before making changes.

## What this is
A self-hosted PHP CMS (no framework) powering syria-home.yassota.com and,
via the subsite provisioner, other subdomains on the same Yassota network.
MySQL via PDO (`$pdo`, bootstrapped in `config.php`). No build step — PHP
files are served directly.

## Live and irreversible
- `includes/payments/NOWPayments.php`, `checkout.php`, `payment-status.php`,
  `payment-webhook.php` handle real crypto payments and real orders. Treat
  the `orders` and `payments` tables as production financial records — don't
  delete/mutate rows outside the app's own validated code paths, and be
  extra careful editing the webhook signature verification.
- `includes/cpanel/CPanelClient.php` and `SiteProvisioner.php` make REAL,
  live changes on the cPanel hosting account (subdomains, MySQL databases/
  users) — there is no dry-run. Confirm before calling provisioning methods
  with guessed/placeholder values.
- Credentials live in the `settings` table via `setting()`/`set_setting()`
  (see `includes/functions.php`), not in code or `.env`. Never print API
  keys, tokens, or `agent_anthropic_api_key` into logs, commit messages, or
  files under the webroot.

## Conventions
- Public pages: `partials.php` has the shared `site_header()`/`site_footer()`
  used by every public page — edit there once, not per-page.
- Admin pages: routed via `admin/index.php` (`?page=<key>`), each page a
  file under `admin/pages/`, registered in `ADMIN_NAV` in `admin/_layout.php`.
  All routes go through `admin/_guard.php` (session auth) automatically.
- Settings are per-tab forms in `admin/pages/settings.php`, or a
  self-contained settings block on a feature's own admin page (see
  `indexing.php`, `agent-console.php`) when the setting is specific to one
  feature.
- IndexNow/Google indexing: `includes/indexnow.php`. The IndexNow key is
  fixed at `d12a9522b79d420992b2f46d4ab34062` and shared across all Yassota
  sites — don't regenerate it without being asked.
- Run `php -l` on every changed/added PHP file before considering a task done.

## Subdomain/subsite management
Don't reach for raw shell/WHM commands to manage subdomains — the app
already has a real, tested path: `CPanelClient::createSubdomain()` /
`createDatabase()` / `createDbUser()` / `grantAllPrivileges()`, orchestrated
by `includes/cpanel/SiteProvisioner.php` and exposed at
Admin → Subdomain Sites. Use those instead of inventing a new one, unless
asked to change that system itself.
