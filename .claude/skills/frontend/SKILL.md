---
name: frontend
description: Дизайн-система публічного сайту OSTROVSKI — графітові токени, шрифти Cormorant Garamond / Archivo / Space Mono, CSS-архітектура та конвенції Blade/Alpine. ОБОВ'ЯЗКОВО застосовувати при створенні чи зміні будь-якого фронтенд-компонента, секції, сторінки, стилю, розмітки чи інтерактиву публічного сайту — файли resources/css/app.css, resources/js/app.js, resources/views/**/*.blade.php (крім email-шаблонів). Гарантує, що новий або змінений UI відповідає усталеній стилістиці сайту. Можна викликати вручну (/frontend) або він задіюється автоматично при правці фронтенд-файлів.
---

# Frontend — дизайн-система OSTROVSKI

Джерело істини для фронтенду сайту **ostrovski.studio** (Katya Ostrovski — DJ,
хореографка, creative director; бізнес у Німеччині). Естетика **вже зафіксована**
дизайн-прототипом Claude Design (`tmp/Ostrovski.dc.html`, палітра **graphite**):
темний кінематографічний editorial-стиль — великі serif-заголовки Cormorant
Garamond, моноширинні технічні підписи Space Mono у CAPS з широким трекінгом,
тонкі лінії-роздільники, єдиний пісочний акцент. **Завдання скіла — не вигадувати
новий дизайн, а тримати єдину стилістику.**

> **Поза скоупом:** email-шаблони (`resources/views/emails/` — інлайн-стилі для
> поштових клієнтів) і сайт Heels On (окремий проєкт зі своєю системою).

## Коли застосовувати

ЗАВЖДИ, коли створюєш або змінюєш Blade-компонент, секцію чи сторінку; правиш
`resources/css/app.css` (стилі) чи `resources/js/app.js` (Alpine/інтерактив);
додаєш кнопку, форму, модалку, картку — будь-який видимий UI.

## 1. Стек і архітектура

| Шар | Технологія |
|---|---|
| Шаблони | Blade-компоненти (`resources/views/components/`) |
| Інтерактив | Alpine.js v3 (`resources/js/app.js`) |
| Стилі | Tailwind CSS v4 (CSS-first, лише reset + `@theme`) + **рукописний семантичний CSS** |
| Збірка | Vite + `laravel-vite-plugin` |
| Шрифти | `@fontsource/*` (локально, не CDN/Google Fonts) |

**Ключове:** це **НЕ utility-first проєкт**. Tailwind під'єднано лише заради
reset і токенів у `@theme`. Увесь UI стилізується рукописним семантичним CSS в
`resources/css/app.css` (класи `.hero`, `.service-card`, `.modal`, `.btn` тощо).
Не розкидай Tailwind-утиліти по Blade.

Збірка після змін CSS/JS: `./vendor/bin/sail npm run build` (або `npm run dev`).
Сайт локально: https://ostr.dev.geek.cx.ua (Traefik dev-VM).

## 2. Токени (палітра graphite)

```css
--bg: #16171a;        /* фон */
--panel: #1e2024;     /* панелі: модалка, cookie */
--fg: #eae8e4;        /* основний текст */
--muted: #8f8c86;     /* другорядний текст */
--line: rgba(234,232,228,.14);        /* хірлайни */
--line-strong: rgba(234,232,228,.22); /* межі інпутів/кнопок */
--accent: #c8a37a;    /* єдиний акцент (пісочний) */
--error: #e5867a;     /* помилки форм */
--white: #ffffff;     /* ТІЛЬКИ поверх фото/скрімів: топбар, кноб перемикача */
--pad-x: clamp(20px, 4vw, 64px);      /* горизонтальний падінг секцій */
```

Бекдропи модалок/шітів — скріми `rgba(5,5,6,.7–.78)` з прототипу (дозволені).
Трійка bg/fg/accent і шрифти визначені один раз у `@theme` (Tailwind), а
`:root`-токени аліасять їх через `var(--color-*)`/`var(--font-*)` — правити
палітру треба лише у `@theme`. Нових кольорів не вводити. Акцент — дозовано:
кікери, номери секцій, ховери.

## 3. Типографіка

| Роль | Шрифт | Вага | Приклади |
|---|---|---|---|
| Display (заголовки, логотип) | Cormorant Garamond | 500/600 | `.hero-name`, `.services-headline`, `.modal-title` |
| Body | Archivo | 400/500/600 | параграфи, описи |
| Технічні підписи | Space Mono | 400 | кікери, лейбли, кнопки, футер-мета |

Патерни: display-заголовки з `line-height` ~1 і `clamp()`-розмірами; технічні
підписи — uppercase з letter-spacing .14–.32em; параграфи — `text-wrap: pretty`,
обмеження ширини в `ch`. Розміри — завжди `clamp(min, vw, max)`, не фіксовані
брейкпоінти.

## 4. Форми та інтерактив

- Інпути: без рамок, лише `border-bottom`, прозорий фон, 16px (проти zoom на iOS).
- Кнопки: `.btn` + `.btn-solid` / `.btn-outline` / `.btn-small`, radius 999px.
- Модалки/шіти: підняті знизу (`align-items: flex-end`), radius `20px 20px 0 0`,
  фон `--panel`, анімація `om-fadeup`.
- Alpine: компоненти реєструються через `Alpine.data()` в `app.js`; відкриття
  модалки — window-івент (`enquiry-open`), не прямі виклики між компонентами.
- Мобільне меню: бургер (`.burger`, 3 span, морфиться в X) + `.menu-overlay`.
  Стан — `Alpine.data('mobileMenu')` на `.app`-обгортці (layout); оверлей
  телепортується в `<body>` (`x-teleport`), інакше його z-index замкнувся б у
  stacking-контексті фіксованого топбару. При відкритті: scroll-lock на
  `documentElement` (НЕ body — `html{overflow-x:hidden}` не пускає overflow
  body до viewport), `inert` на `main`/`.footer`, повернення фокусу на бургер
  при закритті.
- **z-index — тільки зі шкали** (коментар-драбина зверху app.css): 60 топбар /
  70 cookie-бар / 80 меню-оверлей / 85 топбар-при-відкритому-меню / 90 модалка /
  95 cookie-шіт / 100 skip-link. Новий fixed-шар — на вільну сходинку, не
  довільне число.
- Ховери: колір/бордер із transition .3s ease; зум фото в картках
  `transform .9s cubic-bezier(.2,.7,.2,1)`.

## 5. i18n та контент

- Усі UI-тексти — через lang-файли `lang/en` + `lang/de`, НЕ хардкод у Blade.
- URL: EN у корені, DE під `/de`; посилання в Blade — тільки через `loc_route()`.
- Фото — `public/media/` (поза git), підключення через `asset('media/...')`.
