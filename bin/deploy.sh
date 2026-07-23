#!/usr/bin/env bash
# OSTROVSKI — production deploy script.
# Runs on the prod server as user `ostrovski` (app at /var/www/ostr).
# Invoked by the /deploy slash command, piped over SSH:
#   ssh ostrovski 'bash -s' < bin/deploy.sh
# (Piping via stdin means the `git reset --hard` below cannot clobber the
#  running process — bash reads the script from stdin, not from disk.)
set -euo pipefail

PHP=php8.5

main() {
    cd /var/www/ostr

    # Fetch first: touches only .git/, safe for the live app — and the
    # media check below needs origin/main's config before anything else.
    echo "==> fetch origin"
    git fetch --prune origin

    # Photos live in public/media/ which is gitignored and synced
    # separately. Fail fast (before touching maintenance mode) if an
    # original referenced by the INCOMING config is missing — the list is
    # derived from origin/main's config('ostrovski.media') so it can never
    # drift from the code about to deploy. The env() shim lets the config
    # file load outside Laravel.
    echo "==> check media originals (against origin/main config)"
    cfg=$(mktemp)
    git show origin/main:config/ostrovski.php > "$cfg"
    names=$($PHP -r 'function env($k, $d = null) { return $d; } foreach (array_keys((require $argv[1])["media"]) as $n) { echo $n, PHP_EOL; }' "$cfg")
    rm -f "$cfg"
    if [ -z "$names" ]; then
        echo "ERROR: could not read the media map from origin/main config" >&2
        exit 1
    fi
    missing=()
    for name in $names; do
        if [ ! -f "public/media/$name.JPG" ]; then
            missing+=("$name.JPG")
        fi
    done
    if [ ${#missing[@]} -gt 0 ]; then
        echo "ERROR: public/media/ is missing: ${missing[*]}" >&2
        echo "  rsync media originals from local before deploying." >&2
        exit 1
    fi

    # If a step below fails, `set -e` aborts the script and this trap takes
    # the app back out of maintenance mode. The restored site may be
    # inconsistent (new code, old vendor/build) — the /deploy command
    # re-verifies the homepage after every failure. Installed AFTER the
    # pre-flight checks on purpose: keep new fail-fast checks above it so
    # their failures don't run a pointless `artisan up`.
    on_exit() {
        local code=$?
        if [ "$code" -ne 0 ]; then
            echo "==> deploy FAILED (exit $code) — exiting maintenance mode (site may be inconsistent, verify it)"
            $PHP artisan up || true
        fi
    }
    trap on_exit EXIT

    echo "==> maintenance mode"
    # `|| true` is load-bearing: after a half-failed deploy vendor/ may be
    # broken and artisan fatals — proceeding lets composer repair it.
    $PHP artisan down --retry=15 || true

    echo "==> pull origin/main"
    git reset --hard origin/main

    echo "==> composer"
    # Explicit interpreter: bare `composer` runs under whatever `php` the
    # shebang finds, which may not be 8.5 on this multi-version server.
    $PHP "$(command -v composer)" install --no-dev --optimize-autoloader --no-interaction

    # Clear caches BEFORE media:optimize — with a cached config Laravel
    # ignores config/ostrovski.php entirely, so a photo added in this
    # deploy would silently never get its WebP generated.
    echo "==> clear caches"
    $PHP artisan optimize:clear

    echo "==> assets"
    # `npm ci` only when the lockfile changed — a full reinstall is the
    # biggest chunk of the maintenance window and most deploys don't touch
    # dependencies. --include=dev: vite/tailwind are devDependencies and a
    # server-profile NODE_ENV=production would silently omit them.
    lock_hash=$(sha256sum package-lock.json | awk '{print $1}')
    if [ ! -x node_modules/.bin/vite ] || [ "$(cat node_modules/.lock-hash 2>/dev/null || true)" != "$lock_hash" ]; then
        npm ci --no-audit --no-fund --include=dev
        echo "$lock_hash" > node_modules/.lock-hash
    else
        echo "    lockfile unchanged — skipping npm ci"
    fi
    npm run build

    echo "==> media variants"
    $PHP artisan media:optimize

    echo "==> caches"
    $PHP artisan optimize

    echo "==> up"
    $PHP artisan up

    echo "==> done"
}

# stdin feeds bash the script itself — a child command that reads stdin
# would swallow the remaining script bytes and end the run silently with
# exit 0 (skipping the trap). Redirecting main's stdin closes that hole.
main < /dev/null
