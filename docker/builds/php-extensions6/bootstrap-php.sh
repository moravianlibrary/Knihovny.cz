#!/usr/bin/env bash

main() {
    if [ "$PARAM_XDEBUG_ENABLED" == true ]; then
        docker-php-ext-enable xdebug
    fi;
    if [ "$PARAM_AGGRESSIVE_OPCACHE" == true ]; then
        echo "opcache.validate_timestamps=0\n" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
    fi;
}

main "$@"
exit $?
