#!/bin/bash
set -e

cd "$(dirname "$0")"
SLUG="meal-menu"
VERSION=$(sed -n 's/.*Version: *\([0-9.]*\).*/\1/p' meal-menu.php | head -1)
TMP="/tmp/${SLUG}-build"
DEST="${TMP}/${SLUG}"

rm -rf "$TMP"
mkdir -p "$DEST"

rsync -a \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='node_modules/' \
  --exclude='docs/' \
  --exclude='blocks/meal-calendar/src/' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  --exclude='opencode.json' \
  --exclude='AGENTS.md' \
  --exclude='.DS_Store' \
  --exclude='build.sh' \
  . "$DEST"

cd "$TMP"
zip -r "${SLUG}-${VERSION}.zip" "$SLUG/"

mv "${SLUG}-${VERSION}.zip" "$OLDPWD/"
rm -rf "$TMP"

echo "✓ ${SLUG}-${VERSION}.zip"
