#!/usr/bin/env bash
set -e

echo "=== Updating apache config ==="

export PHP_FPM_BACKEND=${PHP_FPM_BACKEND:-127.0.0.1}
echo "Setting PHP_FPM_BACKEND to ${PHP_FPM_BACKEND}"

envsubst.a8m -no-unset -i /etc/apache2/sites-enabled/000-default.conf.tmpl -o /etc/apache2/sites-enabled/000-default.conf

exit 0
