#!/bin/bash

phpstan analyse

php-cs-fixer fix --config=tests/vufind.php_cs --dry-run -vvv
php-cs-fixer fix --config=tests/vufind_templates.php_cs --dry-run -vvv

eslint -c tests/.eslintrc.js themes/KnihovnyCz/js/*.js
jshint --config=tests/jshint.json --exclude=themes/*/js/lib ./themes

CONTAINER=${1:-docker_vufind_1}

docker exec $CONTAINER vendor/bin/phpunit
docker exec $CONTAINER vendor/bin/phing phpcs-console
