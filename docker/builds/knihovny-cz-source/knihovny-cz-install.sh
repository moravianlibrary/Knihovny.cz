#!/usr/bin/env bash

PARAM_VUFIND_BRANCH="$1"

if [ ! -z "$PARAM_VUFIND_BRANCH" ]; then
    rm -rf "$PARAM_VUFIND_SRC/log"
    echo git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "$PARAM_VUFIND_SRC"
    git clone --depth 1 -b "$PARAM_VUFIND_BRANCH" "https://github.com/moravianlibrary/Knihovny.cz.git" "$PARAM_VUFIND_SRC"
    mkdir -p "$PARAM_VUFIND_SRC/log"
    chown www-data:www-data "$PARAM_VUFIND_SRC/log"
    cd "$PARAM_VUFIND_SRC"

    # Install Composer
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
    then
        >&2 echo 'ERROR: Invalid installer signature'
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php
    rm composer-setup.php

    # Install dependencies
    ./composer.phar install --no-interaction

#TODO: update path
    php util/cssBuilder.php
fi;
