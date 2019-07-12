#!/usr/bin/env bash

PARAM_VUFIND_BRANCH="$1"

if [ ! -z "$PARAM_VUFIND_BRANCH" ]; then
    rm -rf "$PARAM_VUFIND_SRC/log"
    echo git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "$PARAM_VUFIND_SRC"
    git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "$PARAM_VUFIND_SRC"
    mkdir -p "$PARAM_VUFIND_SRC/log"
    chown www-data:www-data "$PARAM_VUFIND_SRC/log"
    cd "$PARAM_VUFIND_SRC"

#TODO: update path
    php util/cssBuilder.php
fi;
