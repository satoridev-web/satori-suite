#!/usr/bin/env bash
set -euo pipefail

# -------------------------------------------------
# SATORI link-modules.sh (macOS / bash 3.2 compatible)
# Links Core and selected modules from your monorepo
# into a LocalWP site's wp-content/plugins directory.
# Usage: bash scripts/link-modules.sh
# Optional: bash scripts/link-modules.sh --unlink  (removes links)
# -------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
CORE_DIR="$REPO_ROOT/core"
MODULES_DIR="$REPO_ROOT/modules"

UNLINK_MODE=false
if [ "${1:-}" = "--unlink" ]; then
  UNLINK_MODE=true
fi

echo "SATORI Suite repo: $REPO_ROOT"
echo "Core: $CORE_DIR"
echo "Modules dir: $MODULES_DIR"
echo

# List available LocalWP sites
LOCAL_SITES="$HOME/Local Sites"
if [ ! -d "$LOCAL_SITES" ]; then
  echo "❌ Can't find LocalWP sites at: $LOCAL_SITES"
  echo "   Please install LocalWP or adjust the path in this script."
  exit 1
fi

echo "Available LocalWP sites:"
ls -1 "$LOCAL_SITES" || true
echo
printf "Enter your LocalWP site folder name (as shown above): "
read SITE_NAME
SITE_PLUGINS="$LOCAL_SITES/$SITE_NAME/app/public/wp-content/plugins"

if [ ! -d "$SITE_PLUGINS" ]; then
  echo "❌ Plugins folder not found: $SITE_PLUGINS"
  exit 1
fi

echo "Target plugins folder: $SITE_PLUGINS"
echo

link_one () {
  src="$1"
  dest_name="$2"
  dest="$SITE_PLUGINS/$dest_name"

  if [ "$UNLINK_MODE" = true ]; then
    if [ -L "$dest" ] || [ -d "$dest" ]; then
      echo "🔗 Removing link/folder: $dest"
      rm -rf "$dest"
    else
      echo "ℹ️  Nothing to remove: $dest"
    fi
    return
  fi

  if [ ! -d "$src" ]; then
    echo "❌ Source not found: $src"
    return
  fi

  if [ -e "$dest" ] || [ -L "$dest" ]; then
    printf "⚠️  %s exists. Overwrite? (y/N): " "$dest"
    read ans
    ans=${ans:-N}
    case "$ans" in
      y|Y) rm -rf "$dest" ;;
      *) echo "↪️  Skipping $dest_name"; return ;;
    esac
  fi
  ln -s "$src" "$dest"
  echo "✅ Linked $dest_name → $src"
}

# Always handle Core
echo "== Core =="
link_one "$CORE_DIR" "satori-core"
echo

# Enumerate modules (bash 3.2 compatible, no mapfile)
echo "== Modules =="
if [ ! -d "$MODULES_DIR" ]; then
  echo "⚠️  No modules directory found at $MODULES_DIR"
  exit 0
fi

# Build list of module directories
OLDIFS="$IFS"
IFS=$'\n'
for path in $(find "$MODULES_DIR" -mindepth 1 -maxdepth 1 -type d | sort); do
  slug="$(basename "$path")"
  printf "Link module '%s'? (y/N): " "$slug"
  read ans
  ans=${ans:-N}
  case "$ans" in
    y|Y) link_one "$MODULES_DIR/$slug" "satori-$slug" ;;
    *) echo "↪️  Skipped $slug" ;;
  esac
done
IFS="$OLDIFS"

echo
if [ "$UNLINK_MODE" = true ]; then
  echo "Done. Unlinked Core and selected modules from: $SITE_PLUGINS"
else
  echo "Done. Check WordPress → Plugins to activate SATORI Core and selected modules."
fi
