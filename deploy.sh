#!/usr/bin/env bash
# Copyright © 2026 ApiSpi
#
# Production deploy for SiteGround (~/www/time.apispi.com). Run ON the server:
#
#   ./deploy.sh          # pull + dependencies + migrate + rebuild caches + verify
#
# From your own machine, `./cx.sh 20` runs this over SSH after checking that
# everything is pushed.
#
# The server deploys by pulling from git, so the sequence is one command and
# the steps cannot drift apart: a pull without `composer install` deploys code
# without its packages, and a pull without `migrate` deploys code without its
# tables.
#
# No frontend build step: the pages are plain Blade with inline CSS, so the
# server needs neither Node nor a committed public_html/build.

set -euo pipefail
cd "$(dirname "$0")"

# A value with a space must be quoted in .env (APP_NAME="MCP Time"). Unquoted,
# Laravel cannot parse the file and every request is a 500 — and so is every
# artisan command below, leaving a deploy half done. Check before pulling.
BAD_ENV=$(grep -nE '^[A-Za-z_][A-Za-z0-9_]*=[^"'"'"'#]*[^"'"'"'#[:space:]][[:space:]]+[^#[:space:]]' .env 2>/dev/null || true)
if [ ! -f .env ]; then
    echo "✗ .env is missing — copy .env.example to .env, fill it in, then re-run."
    exit 1
elif [ -n "$BAD_ENV" ]; then
    echo "✗ .env has unquoted values containing spaces — wrap each value in double quotes:"
    echo "$BAD_ENV" | sed -E 's/^([0-9]+):([^=]+)=(.*)$/    line \1:  \2=\3   →   \2="\3"/'
    echo "  Nothing was deployed."
    exit 1
fi

# SiteGround edits tracked files in place (public_html/.htaccess, for password
# protection and PHP version). That drift is expected and the pull keeps it;
# it is printed so that a pull which stops on a conflict explains itself.
DRIFT=$(git status --short --untracked-files=no)
if [ -n "$DRIFT" ]; then
    echo "ℹ local changes on the server (kept):"
    echo "$DRIFT" | sed 's/^/    /'
fi

# A server-side edit that has since been committed (e.g. the AddHandler line
# in public_html/.htaccess) still blocks the pull, even when the two are
# identical. Drop the local copy only when it matches upstream exactly.
git fetch --quiet && for f in $(git diff --name-only); do [ "$(git hash-object "$f")" = "$(git rev-parse "@{u}:$f" 2>/dev/null)" ] && git checkout -- "$f" && echo "ℹ $f: server edit is now in the repo — kept the repo copy"; done; true

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
# --ff-only: the server never commits, so anything but a fast-forward means
# something is wrong and should stop here rather than create a merge.
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -f vendor/autoload.php ]; then
    echo "→ composer install (lockfile changed)"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Drop the cached config first, so the steps below read the current .env and
# not whatever was cached by the previous deploy.
php artisan optimize:clear >/dev/null

# The SQLite file is gitignored, so a fresh checkout has none and the first
# migrate would fail instead of creating it.
if grep -qE '^DB_CONNECTION=sqlite' .env && ! grep -qE '^DB_DATABASE=' .env; then
    if [ ! -f database/database.sqlite ]; then
        echo "→ creating database/database.sqlite"
        touch database/database.sqlite
        chmod 600 database/database.sqlite
    fi
fi

echo "→ migrate"
php artisan migrate --force

echo "→ cache config/routes/views"
php artisan optimize

echo "→ verify"
# The MCP endpoint is the product. If its route did not survive the route
# cache, say so here instead of leaving it to the first client to find out.
if ! php artisan route:list --path=mcp 2>/dev/null | grep -q 'mcp'; then
    echo "✗ the /mcp route is not registered — check routes/ai.php"
    exit 1
fi
if grep -qE '^APP_DEBUG=true' .env; then
    echo "⚠ APP_DEBUG=true on the server — stack traces are public. Set it to false."
fi
if ! grep -qE '^GOOGLE_CLIENT_ID=.+' .env; then
    echo "ℹ GOOGLE_CLIENT_ID is not set — the Google sign-in button stays hidden."
fi

echo "✓ deployed $(git log --oneline -1)"
