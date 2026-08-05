#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# SessionStart hook — yassota (PHP 8 / MySQL, no dependency manager)
#
# This repo has no composer.json, package.json, or lockfile: it is plain PHP
# with zero third-party libraries, so there is genuinely nothing to "install".
# What a session here actually needs is:
#   1. the PHP extensions the app requires to be present (fail loudly if not),
#   2. the runtime directories the app writes to, with their access guards,
#   3. a fast syntax check so a broken file is known at session start rather
#      than discovered mid-edit.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# Web sessions only — a local checkout already has its own environment.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-$(dirname "$0")/../..}"

echo "▶ yassota session setup"

# ── 1. PHP + required extensions ─────────────────────────────────────────────
if ! command -v php >/dev/null 2>&1; then
  echo "‼ php not found on PATH — the site cannot be linted or run." >&2
  exit 1
fi
echo "  php: $(php -r 'echo PHP_VERSION;')"

# README requires PHP 8.0+ with PDO_MySQL, GD and cURL; mbstring is used
# throughout for Arabic string handling, so treat it as required too.
missing=""
for ext in pdo_mysql gd curl mbstring json; do
  php -m | grep -qix "$ext" || missing="$missing $ext"
done
if [ -n "$missing" ]; then
  echo "‼ missing required PHP extension(s):$missing" >&2
  echo "  the app will fatal at runtime without these." >&2
  exit 1
fi
echo "  extensions: pdo_mysql gd curl mbstring json ✓"

# ── 2. Runtime directories ───────────────────────────────────────────────────
# Both are created lazily by the app, but pre-creating them means a session
# never hits a first-write permission surprise — and it lets us install the
# access guards below, which the lazy mkdir does not do.
mkdir -p static-cache data uploads/icons uploads/screenshots uploads/.cache

# static-cache/ holds rendered HTML snapshots of pages that already exist at
# their own canonical URLs. Left web-readable, /static-cache/app-foo.html is a
# crawlable duplicate of the real page — exactly the duplicate-content problem
# the sitemap/robots work is trying to remove. Deny direct access.
if [ ! -f static-cache/.htaccess ]; then
  printf '# Internal render snapshots — served via readfile(), never directly.\n# Direct access would expose crawlable duplicates of canonical pages.\nRequire all denied\n' > static-cache/.htaccess
  echo "  created static-cache/.htaccess (blocks duplicate-content crawling)"
fi

# data/ holds internal cache/lock files (notification credential cache, the
# static-cache kill switch). Never meant to be web-readable.
if [ ! -f data/.htaccess ]; then
  printf '# Block all direct web access to this folder — it only holds internal\n# cache/lock files (notification credentials cache, etc.)\nRequire all denied\n' > data/.htaccess
  echo "  created data/.htaccess"
fi

# ── 3. Syntax smoke check ────────────────────────────────────────────────────
# ~1.5s across ~200 files. Reports but does not fail the session: a session
# started specifically to FIX a syntax error must still be allowed to start.
lint_out="$(mktemp)"
# `|| true` inside the child is deliberate: php -l exits 255 on a parse error,
# and xargs aborts the whole run on a non-zero child. Without this, the first
# broken file stops the sweep and every file after it goes unchecked — the
# check would be least reliable exactly when something is actually broken.
find . -name '*.php' -not -path './.git/*' -print0 \
  | xargs -0 -P 4 -n 20 sh -c 'php -l "$@" 2>&1 || true' _ >"$lint_out"

clean_count="$(grep -c '^No syntax errors' "$lint_out" || true)"
if grep -qv '^No syntax errors' "$lint_out"; then
  echo "‼ php -l found syntax error(s) — $clean_count file(s) clean:"
  grep -v '^No syntax errors' "$lint_out" | head -20
else
  echo "  php -l: $clean_count file(s) clean ✓"
fi
rm -f "$lint_out"

# ── 4. Session convenience ───────────────────────────────────────────────────
# There is no package.json, so there is no `npm run lint` to reach for. Expose
# the real command instead of leaving it to be rediscovered each session.
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
  echo "export YASSOTA_LINT=\"find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -P 4 -n 20 php -l\"" >> "$CLAUDE_ENV_FILE"
fi

echo "▶ ready"
