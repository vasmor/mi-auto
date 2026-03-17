#!/usr/bin/env bash

# ===== Настройки =====
REMOTE_HOST="u1791919@31.31.196.177"
REMOTE_PATH="/var/www/u1791919/data/www/miaut.dev-dynamic.ru/wp-content/themes/mi-auto/"
LOCAL_PATH="/mnt/c/projects/mi-auto/"

# ===== Исключения =====
EXCLUDES=(
    --exclude=".git/"
    --exclude=".claude/"
    --exclude=".DS_Store"
    --exclude=".gitignore"
    --exclude="_workflow/"
    --exclude="_figma/"
    --exclude="_new-resourse/"
    --exclude="_other-helpers/"
    --exclude="node_modules/"
    --exclude="docs/"
    --exclude="html/"
    --exclude="skrin/"
    --exclude="*.md"
    --exclude="deploy.sh"
    --exclude="*.log"
    --exclude=".env"
)

# ===== Dry-run режим =====
DRY_RUN=""
if [[ "$1" == "--dry-run" || "$1" == "-n" ]]; then
    DRY_RUN="--dry-run"
    echo ">>> DRY-RUN: изменения не будут применены <<<"
    echo ""
fi

# ===== Запуск =====
echo "Деплой: $LOCAL_PATH → $REMOTE_HOST:$REMOTE_PATH"
echo ""

rsync -rltz \
    "${EXCLUDES[@]}" \
    $DRY_RUN \
    --progress \
    "$LOCAL_PATH" \
    "$REMOTE_HOST:$REMOTE_PATH"

echo ""
echo "Готово."
