---
description: (user) Ship the current branch — chain commit → push → deploy. Default runs the full chain to production. Pass a stage to stop earlier.
argument-hint: [commit | push | deploy]
---

Ship the OSTROVSKI site through the release pipeline. Run all stages from the start up to
(and including) the requested stage. Default: `deploy` (full chain).

> **Authorization:** користувач, що викликав `/ship`, тим самим явно дозволив **весь обсяг**,
> який команда виконує (commit + push у `main` + deploy) — окремих підтверджень на happy path
> не питати (global CLAUDE.md, виняток для `/ship`). Зупиняйся й питай лише на помилках
> (failing tests, push reject, конфлікт, deploy fail, чутлива security-знахідка).

## Stages

| `$1` | Що робить (включно з усіма попередніми) |
|---|---|
| `commit` | git add (explicit files) + commit з auto-generated English message |
| `push` | + push поточної гілки на `origin` |
| `deploy` (default) | + (умовно) security-review → делегування `/deploy` → health-check на проді |

## Project coordinates (fixed — не парсити з git remote)

- Repo: `denostr/ostrovski-studio` · main branch: `main`
- Робочий процес — **прямо в `main`** (цей проєкт не використовує feature-гілки / PR).
- Prod: DigitalOcean droplet, SSH alias `ostrovski`, app `/var/www/ostr`, live `https://ostrovski.studio`.
- Сервер деплоїться з `origin/main` — у прод іде лише закомічене-й-запушене.
- Локальний тулінг — завжди через `./vendor/bin/sail` (artisan / npm / composer / pint / test).

## Autopilot rules

- **Без додаткових питань** на happy path. Тільки на помилках: failing tests, push reject,
  конфлікт із `origin/main`, deploy fail, confirmed-critical security-знахідка.
- Commit message — **English**, auto-generated. Якщо user явно попросив українську — переключитись.
- НЕ `--no-verify` / НЕ `--force` / НЕ `--amend` без явного запиту користувача (global rule).
- Не виконувати destructive операції поза тим, що включає поточний `$1`.

## Pre-flight checks

Перед стартом:

1. `git status --short` — є staged/unstaged changes?
   - Якщо ні **і** `git log origin/main..HEAD` непорожній → нічого комітити, але є незапушене:
     можна одразу до `push`/`deploy` (skip commit stage).
   - Якщо ні **і** HEAD == `origin/main` → нічого ship-ити; якщо stage=`deploy`, спитати, чи
     зробити plain-rebuild деплой (сервер міг відстати) — інакше повідомити user-у й вийти.
2. `git branch --show-current` — очікувано `main`. Якщо на іншій гілці — це нетипово для
   цього проєкту; продовжуй на ній, але виведи one-liner
   `ℹ️ on branch <name> (проєкт зазвичай шипить із main)`, щоб user помітив.
3. `git fetch origin --quiet && git log origin/main..HEAD --oneline` — що піде у прод.

---

## Stage 1: commit (тригери: commit, push, deploy)

1. **Показати diff-контекст**:
   ```bash
   git status --short
   git diff --stat HEAD
   git diff HEAD | head -200
   ```
2. **Визначити змінені файли** — `git diff --name-only HEAD` + `git ls-files --others --exclude-standard`.
   - **Відсіяти чутливі файли** (`.env`, `*.key`, `*-credentials.json`, `auth.json`) — НЕ
     стейджити, попередити user-а, якщо такі змінено.
   - Стейджити явними шляхами: `git add <path1> <path2> …`. `git add -A` припустимо лише
     коли всі untracked-файли явно частина цієї роботи і серед них нема чутливих.
3. **Згенерувати commit message** (English, project conventions):
   - 1-рядковий subject (≤72 chars), imperative, описує ЩО змінилось.
   - Опційний body — ЧОМУ (мотивація, зміна поведінки, edge-case).
   - Стиль як у наявній історії проєкту.
4. **Закомітити через HEREDOC** (зберігає форматування):
   ```bash
   git commit -m "$(cat <<'EOF'
   <subject>

   <body>
   EOF
   )"
   ```
5. **Pint** на змінених PHP-файлах: `./vendor/bin/sail bin pint --dirty --format agent`.
   Якщо Pint щось виправив — окремий follow-up commit `Apply Pint formatting`
   (no-amend rule) і включити його в той самий push.

If stage = `commit` → STOP. Report `✅ Commit <sha>: <subject>`.

---

## Stage 2: push (тригери: push, deploy)

```bash
git fetch origin --quiet
```

- Якщо `origin/main` **поїхав уперед** (`git rev-list --count HEAD..origin/main` > 0) —
  спробувати лінійно наздогнати: `git pull --rebase origin main`. Якщо rebase **чисто** →
  continue. Якщо **конфлікт** → `git rebase --abort`, **STOP**, передати user-у список
  конфліктних файлів (`git diff --name-only --diff-filter=U`).
- Push:
  ```bash
  git push origin <current-branch>
  ```
  Якщо push rejected (non-fast-forward) попри rebase — **STOP**, попередити (НЕ force).

If stage = `push` → STOP. Report `✅ Pushed to origin/<branch>`.

---

## Stage 2.5: security-review (умовний — гейтить deploy; тригер: deploy)

Авто-запускає вбудований `/security-review` **лише коли** зміни чіпають чутливу поверхню.
Інакше — тихий skip. Окремий шар від `/code-review` (коректність).

**Детектор «доречно»** — diff проти `origin/main` ДО цього ship-у:
```bash
RANGE="origin/main..HEAD"   # якщо вже запушили у Stage 2, бери діапазон попереднього зрізу: HEAD~<n>..HEAD
SCOPE=(-- 'app/**' 'config/**' 'routes/**' 'bootstrap/**')
FILES=$(git diff $RANGE --name-only "${SCOPE[@]}")
BODY=$(git diff $RANGE "${SCOPE[@]}")
SENSITIVE=0
echo "$FILES" | grep -Eiq 'Http/Middleware/|Rules/|EnquiryRequest|EnquiryController|config/(auth|cors|session|services)\.php|Turnstile|Honeypot' && SENSITIVE=1
echo "$BODY"  | grep -Eq 'Crypt|Hash::|encrypt\(|decrypt\(|hasValidSignature|signedRoute|Gate::|request\(\)->file|->store\(|Storage::|RateLimiter|throttle|\{!!|Mail::|reply_?[Tt]o|cf-turnstile|prohibited' && SENSITIVE=1
echo "SENSITIVE=$SENSITIVE"
```
Покриває цей проєкт: middleware (SetLocale/SecurityHeaders), enquiry-форму та anti-spam
(Turnstile/honeypot/throttle), відправлення пошти (header injection / reply-to),
XSS через `{!! !!}`. БД/адмінки/аплоадів у проєкту немає.

**Дії:**
- `SENSITIVE=0` → liveness-рядок `🔒 security-review skip — diff не чіпає чутливих поверхонь`, далі до deploy.
- `SENSITIVE=1` → виконати Skill `/security-review`, тоді тріаж:
  - **confirmed critical** (spam-gate bypass, secret leak, injection, header injection)
    → **STOP перед deploy**, передати user-у / фіксити;
  - **lower** → нотатка, не блокер — deploy продовжується;
  - чисто → `🔒 security-review: чисто (охоплено: <які поверхні>)`.

Skip Stage 2.5, якщо stage зупинився на `push` чи раніше.

---

## Stage 3: deploy (default, тригер: deploy чи no-arg)

**Делегуй наявному `/deploy`** (`.claude/commands/deploy.md` — єдине джерело процедури
деплою; не дублюй кроки тут). Виконай його pre-flight → preview → `bin/deploy.sh` →
verify (запуск деплою — тільки у фоні, чекати `==> done`).

**На failure deploy** — показати точну помилку, **STOP**, НЕ робити rollback автоматично.
Скрипт сам виводить сайт із maintenance mode (trap), але стан може бути неконсистентним —
перезапустити verify-курли з `/deploy` і сказати, в якому стані сайт.

Report:
```
✅ Deploy success
- commit on prod: <sha>  (== origin/main)
- HTTP /: 200 · /de: 200
- test: <прод-лінк, якщо diff чіпав фронт — інакше пропустити>
```

**Test link (якщо diff цього релізу чіпав публічний фронт):**
`resources/views/**`, `resources/js|css/**`, `routes/web.php`, lang-файли
→ `https://ostrovski.studio` (+ best-effort якір на змінену секцію: `#services-dj`,
`#services-show`, `/impressum` тощо).

---

## Що НЕ робить ця команда

- НЕ amend-ить і НЕ force-push (global CLAUDE.md) — лише нові коміти, fast-forward push.
- НЕ rollback при невдалому deploy (окрема ручна процедура).
- НЕ запускає повний test-suite автоматично — якщо тести впали локально, user фіксить
  або свідомо шипить далі (`./vendor/bin/sail artisan test`).
- НЕ створює feature-гілок / PR — цей проєкт шипить прямо в `main`.

## Related

- Deploy procedure — `.claude/commands/deploy.md` · скрипт — `bin/deploy.sh`
- Project conventions — `CLAUDE.md`
- Frontend design-system — `/frontend` skill (`.claude/skills/frontend/`)
