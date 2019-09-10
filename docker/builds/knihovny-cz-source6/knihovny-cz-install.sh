#!/usr/bin/env bash

PARAM_VUFIND_BRANCH="$1"

if [ ! -z "$PARAM_VUFIND_BRANCH" ]; then
    echo git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "/var/www/knihovny-cz-extension"
    git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "/var/www/knihovny-cz-extension"
    cd "$PARAM_VUFIND_SRC"

#TODO: update path
    php util/cssBuilder.php
fi;
