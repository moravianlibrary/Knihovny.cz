#!/usr/bin/env bash
set -e

echo "=== Updating apache config ==="

PHP_FPM_BACKEND=${PHP_FPM_BACKEND:-127.0.0.1}
echo "Setting PHP_FPM_BACKEND to ${PHP_FPM_BACKEND}"
sed -i~ -e "s/PHP-FPM-BACKEND/${PHP_FPM_BACKEND}/g" /etc/apache2/sites-enabled/000-default.conf
exit 0
