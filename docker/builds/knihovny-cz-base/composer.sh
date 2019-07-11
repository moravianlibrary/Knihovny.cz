#!/usr/bin/env bash

# Install Composer
EXPECTED_SIGNATURE="$(curl -s -L -o - https://composer.github.io/installer.sig)"
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
./composer.phar install --no-interaction --no-scripts

# TODO
#phing installswaggerui

#TODO: sentry/sentry still missing
