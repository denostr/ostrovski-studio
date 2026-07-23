#!/usr/bin/env bash
#
# PreToolUse-хук: коли Claude редагує фронтенд-файл публічного сайту OSTROVSKI,
# вкидає в контекст нагадування застосувати скіл `frontend`.
#
# Спрацьовує на Edit/Write/MultiEdit для:
#   resources/css/app.css · resources/js/app.js
#   resources/views/**/*.blade.php
# Виключає email-шаблони (resources/views/emails/).
#
# Тул НЕ блокується — хук лише додає контекст (permissionDecision не
# виставляємо: звичайний permission-флоу лишається недоторканим).
#
# Fail loudly: без jq хук не може працювати — і мусить про це закричати
# (exit 2 + stderr), а не мовчки прикидатися здоровим no-op-ом.

if ! command -v jq >/dev/null 2>&1; then
  echo "frontend-skill-reminder.sh: jq не знайдено — хук не може розібрати вхідний JSON. Встанови jq або вимкни хук у .claude/settings.json." >&2
  exit 2
fi

input=$(cat)
file_path=$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty')

# Не файловий інструмент або шлях невідомий — мовчки виходимо.
if [ -z "$file_path" ]; then
  exit 0
fi

is_frontend=false
case "$file_path" in
  */resources/css/app.css)          is_frontend=true ;;
  */resources/js/app.js)            is_frontend=true ;;
  */resources/views/*.blade.php)    is_frontend=true ;;
esac

# Виключення: поза скоупом скіла `frontend`.
case "$file_path" in
  */resources/views/emails/*)       is_frontend=false ;;
esac

if [ "$is_frontend" != true ]; then
  exit 0
fi

context="Редагується фронтенд-файл публічного сайту OSTROVSKI. Зміни мають відповідати дизайн-системі проєкту: графітова палітра (--bg #16171a, --accent #c8a37a), типографіка Cormorant Garamond + Archivo + Space Mono, рукописний семантичний CSS у app.css (НЕ Tailwind-утиліти в Blade), конвенції Blade/Alpine та i18n EN+DE через lang-файли. Якщо скіл \`frontend\` ще не завантажено в цій сесії — завантаж його через інструмент Skill, перш ніж продовжувати правки фронтенду."

jq -n --arg ctx "$context" '{
  hookSpecificOutput: {
    hookEventName: "PreToolUse",
    additionalContext: $ctx
  }
}'
