#!/bin/bash

set -e
if [ -n "$DEBUG" ]; then
  set -x
fi


echo === Configuring memcache ===
# configure shibboleth and apache
if [ "${MEMCACHED_SERVICE}" = "" ]; then
  export MEMCACHED_SERVICE=127.0.0.1:11211
fi
echo Memcached is "$MEMCACHED_SERVICE"

echo === Configuring CACHE_DIR ===
if [ -d "${VUFIND_CACHE_DIR}"  ]; then
  chown www-data.www-data ${VUFIND_CACHE_DIR}
else
  echo "skipped - not found"
fi

echo === Configuring SHARED_CACHE_DIR ===
if [ -d "${VUFIND_SHARED_CACHE_DIR}"  ]; then
  chown www-data.www-data ${VUFIND_SHARED_CACHE_DIR}
else
  echo "skipped - not found"
fi

echo === Starting onstart files ===
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

echo === Copying vufind config files ===

envsubst.a8m -no-unset -i /etc/vufind/config.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/config.local.ini
envsubst.a8m -no-unset -i /etc/vufind/EDS.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/EDS.local.ini
envsubst.a8m -no-unset -i /etc/vufind/Search2.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/Search2.local.ini
envsubst.a8m -no-unset -i /etc/vufind/content.local.ini -o /var/www/knihovny-cz/local/knihovny.cz/config/vufind/content.local.ini

echo === Executing final command "$1" ===

# start Shibboleth or Apache
if [ "$1" = "shibd" -o "$1" = "shibboleth" ]; then
    exec shibd -f -F
elif [ "$1" = "apache" ]; then
    exec apache2 -DFOREGROUND
elif [ "$1" = "php-fpm" -o "$1" = "php" ]; then
    exec php-fpm
elif [ "$1" = "sh" ]; then
    exec bash
elif [ "$1" = "sleep" ]; then
    # sleep for a few days
     sleep 365d
     exit 0
else
    echo "Wrong agument given. Only apache, shibboleth or php is possible."
    exit 1
fi
