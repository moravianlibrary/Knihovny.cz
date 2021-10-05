#!/bin/bash

set -e
if [ -n "$DEBUG" ]; then
  set -x
fi


# configure shibboleth and apache
if [ "${MEMCACHED_SERVICE}" = "" ]; then
  export MEMCACHED_SERVICE=127.0.0.1:11211
fi

# Run all start files
if test -d /onstart.d; then
    for FILE in /onstart.d/*; do
        if [ -x "$FILE" ]; then
            "$FILE" || [ "${IGNORE_BOOTSTRAP_FAILURE:-false}" = "true" ] || exit $?
        else
            echo "Warning: found non-executable file at $FILE" >&2
            exit 2
        fi
    done
fi

envsubst.a8m -no-unset -i /etc/vufind/config.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/config.local.ini
envsubst.a8m -no-unset -i /etc/vufind/EDS.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/EDS.local.ini
envsubst.a8m -no-unset -i /etc/vufind/Search2.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/Search2.local.ini
envsubst.a8m -no-unset -i /etc/vufind/content.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/content.local.ini

# start Shibboleth or Apache
if [ "$1" = "shibd" -o "$1" = "shibboleth" ]; then
    exec shibd -f -F
elif [ "$1" = "apache" ]; then
    exec apache2-foreground
elif [ "$1" = "sh" ]; then
    exec bash
else
    echo "Wrong agument given. Only apache or shibboleth is possible."
    exit 1
fi
