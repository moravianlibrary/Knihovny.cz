#!/usr/bin/env bash
set -e

PARAM_VUFIND_BRANCH="$1"
USER="$2"
PASS="$3"

if [ ! -z "$PARAM_VUFIND_BRANCH" ]; then
    echo git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "git@gitlab.mzk.cz:knihovny.cz/Knihovny-cz.git" "/var/www/knihovny-cz-extension"
    git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://$USER:$PASS@gitlab.mzk.cz/knihovny.cz/Knihovny-cz.git" "/var/www/knihovny-cz-extension"
    cd "$PARAM_VUFIND_SRC"
fi;
