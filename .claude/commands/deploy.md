---
description: Deploy committed changes to production (ostrovski.studio)
---

Deploy the OSTROVSKI site to production — DigitalOcean droplet, SSH alias `ostrovski`
(user `ostrovski`), app at `/var/www/ostr`, live at `https://ostrovski.studio`.
The server deploys from `origin/main`, so only committed-and-pushed code goes live.

Work through these steps in order. Stop and report if any step fails.

## 1. Pre-flight — local git state

Run `git status --short`, then `git fetch origin --quiet` and `git log origin/main..HEAD --oneline`.

- **Uncommitted changes** → stop. Ask the user to commit them first; `/deploy` ships only
  committed code. Do not deploy a partial state.
- **Local `main` ahead of `origin/main`** → list the unpushed commits and ask the user to
  confirm a push. Push only after explicit confirmation (project rule: never push without
  it). If the user declines, stop.
- **Clean and in sync** → continue.

## 2. Preview what will deploy

Get the server's current commit: `ssh ostrovski 'git -C /var/www/ostr rev-parse --short HEAD'`.
Show `git log <server-commit>..origin/main --oneline` — the commits about to go live.
If the server is already on `origin/main`, say so and ask whether to redeploy anyway
(a plain rebuild).

## 3. Run the deploy

Run `ssh ostrovski 'bash -s' < bin/deploy.sh` — this pipes the repo's deploy script to
the server and runs it as `ostrovski`. (Piping via stdin means the script's own
`git reset --hard` cannot clobber the running process.)

`bin/deploy.sh` does, in order: `git fetch` → media-originals check (list derived from
origin/main's `config('ostrovski.media')`) → maintenance mode on →
`git reset --hard origin/main` → `composer install --no-dev` → `optimize:clear` →
`npm ci` (skipped when the lockfile is unchanged) + `npm run build` →
`php artisan media:optimize` → `optimize` → maintenance mode off. (No migrations and no
queue restart — this app runs without a database and with the sync queue.)

It can take a few minutes (composer + npm build) — run it in the background (never a
foreground call that a tool timeout could kill mid-deploy) and wait for the final
`==> done` line.

## 4. Verify

- `curl -s -o /dev/null -w "%{http_code}\n" https://ostrovski.studio/` → expect `200`,
  or `302` to `/de` when curling from a German IP (the GeoIP redirect) — follow it and
  expect `200`.
- `curl -s -o /dev/null -w "%{http_code}\n" https://ostrovski.studio/de` → expect `200`
  (a cookie-less GET to /de never redirects).
- `curl -s -o /dev/null -w "%{http_code}\n" https://www.ostrovski.studio/` → expect
  `301` to the apex. (Fails until the `www` CNAME is added in Cloudflare — surface that
  to the user rather than treating it as a deploy failure.)
- Confirm `ssh ostrovski 'git -C /var/www/ostr rev-parse --short HEAD'` now matches `origin/main`.
- Scan the deploy output for errors (build failure, media:optimize failure).

## 5. Report

State the deployed commit and the HTTP check results. On failure: the script's trap
auto-restores the site out of maintenance mode, but the restored site may be
inconsistent (new code with old vendor/build) — re-run the Verify curls and say what
state the site is in. Only if it still returns `503`, clear maintenance mode manually:
`ssh ostrovski 'cd /var/www/ostr && php8.5 artisan up'`.

## Notes

- New photos: rsync the JPG originals into `/var/www/ostr/public/media/` first (they are
  outside git), add their entries to `config('ostrovski.media')`, commit, then deploy —
  the deploy script checks the originals against the incoming config and regenerates
  the WebP variants; nothing else needs updating.
- TLS: the `ostrovski.studio` nginx vhost uses the Cloudflare origin certificate shared
  with Heels On (`/etc/ssl/cloudflare/heels-origin.pem`, referenced by BOTH vhosts,
  valid to 2041). When decommissioning heels or touching its TLS, reissue a cert for
  ostrovski.studio first — removing that file kills this site's TLS too.
- The server clone pulls over anonymous HTTPS — deploys depend on the GitHub repo
  staying public. If it ever goes private, add a deploy key and switch the remote to SSH.
- The deploy script is `bin/deploy.sh` in this repo — edit it there to change deploy behaviour.
- This command never commits, and pushes only after explicit confirmation.
