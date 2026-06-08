#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
WWW_ROOT="/var/www/knihovny-cz/"

function print_usage {
    cat <<EOF
Usage: $0 <major|minor|patch|rollback> [--branch <name>]

Prepares a release: updates library translations and increments the application version.

Steps:
  1. Verifies the branch and sync with origin
  2. Runs util/generate_sigla_translations command and commits translations
  3. Increments version according to semantic versioning and commits

Parameters:
  major     Bumps the major version (X.0.0)
  minor     Bumps the minor version (x.Y.0)
  patch     Bumps the patch version (x.y.Z)
  rollback  Reset git to state before the last prepare-release run

Options:
  --branch <name>  Branch to operate on (default: dev)
EOF
}

BRANCH="dev"
ARGS=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch) BRANCH="$2"; shift 2 ;;
        *) ARGS+=("$1"); shift ;;
    esac
done
set -- "${ARGS[@]}"

# --- parameter ---
if [[ $# -ne 1 ]]; then
    echo "Error: exactly one parameter expected." >&2
    print_usage
    exit 1
fi

BUMP="$1"
if [[ "$BUMP" != "major" && "$BUMP" != "minor" && "$BUMP" != "patch" && "$BUMP" != "rollback" ]]; then
    echo "Error: parameter must be 'major', 'minor', 'patch' or 'rollback'." >&2
    print_usage
    exit 1
fi

STATE_FILE="$REPO_ROOT/.git/PREPARE_RELEASE_ORIG_HEAD"

# --- branch check ---
CURRENT_BRANCH=$(git -C "$REPO_ROOT" rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
    echo "Error: current branch is '$CURRENT_BRANCH', expected '$BRANCH'." >&2
    print_usage
    exit 1
fi

# --- rollback ---
if [[ "$BUMP" == "rollback" ]]; then
    if [[ ! -f "$STATE_FILE" ]]; then
        echo "Error: no rollback state found. Was prepare-release.sh run before?" >&2
        exit 1
    fi
    ORIG_HEAD=$(cat "$STATE_FILE")
    echo "Rolling back to $ORIG_HEAD..." >&2
    git -C "$REPO_ROOT" reset --hard "$ORIG_HEAD"
    rm "$STATE_FILE"
    echo "Rollback complete." >&2
    exit 0
fi

# --- sync with origin ---
echo "Checking sync with origin/$BRANCH..." >&2
git -C "$REPO_ROOT" fetch origin "$BRANCH" --quiet

LOCAL=$(git -C "$REPO_ROOT" rev-parse HEAD)
REMOTE=$(git -C "$REPO_ROOT" rev-parse "origin/$BRANCH")
BASE=$(git -C "$REPO_ROOT" merge-base HEAD "origin/$BRANCH")

if [[ "$LOCAL" != "$REMOTE" ]]; then
    if [[ "$LOCAL" == "$BASE" ]]; then
        echo "Error: $BRANCH branch is behind origin/$BRANCH. Run 'git pull'." >&2
    elif [[ "$REMOTE" == "$BASE" ]]; then
        echo "Error: $BRANCH branch is ahead of origin/$BRANCH. Run 'git push'." >&2
    else
        echo "Error: $BRANCH branch and origin/$BRANCH have diverged. Resolve conflicts manually." >&2
    fi
    exit 1
fi

echo "Branch $BRANCH is in sync with origin." >&2
git -C "$REPO_ROOT" rev-parse HEAD > "$STATE_FILE"

# --- library translations ---
echo "Generating library translations..." >&2
docker exec -it \
    -e VUFIND_LOCAL_DIR="$WWW_ROOT/local/base" \
    -e VUFIND_LOCAL_MODULES="KnihovnyCz,KnihovnyCzConsole" \
    docker-vufind6-1 \
    php "$WWW_ROOT/public/index.php" util/generate_sigla_translations

CS_FILE="$REPO_ROOT/local/base/languages/Sigla/cs.ini"
EN_FILE="$REPO_ROOT/local/base/languages/Sigla/en.ini"

if git -C "$REPO_ROOT" diff --quiet "$CS_FILE" "$EN_FILE"; then
    echo "Translations unchanged, commit skipped." >&2
else
    git -C "$REPO_ROOT" add "$CS_FILE" "$EN_FILE"
    git -C "$REPO_ROOT" commit -m "Update libraries titles translations"
    echo "Translations committed." >&2
fi

# --- version bump ---
CURRENT_VERSION=$(jq -r '.version' "$REPO_ROOT/package.json")

IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

case "$BUMP" in
    major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
    minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
    patch) PATCH=$((PATCH + 1)) ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
echo "Current version: $CURRENT_VERSION → new version: $NEW_VERSION" >&2

bash "$SCRIPT_DIR/setVersion.sh" "$NEW_VERSION"

CONFIG_FILE="$REPO_ROOT/local/base/config/vufind/config.ini"
git -C "$REPO_ROOT" add "$REPO_ROOT/package.json" "$CONFIG_FILE"
git -C "$REPO_ROOT" commit -m "Update version to $NEW_VERSION"

echo "Done! Version $NEW_VERSION is ready for release." >&2
