#!/bin/bash

CONTAINER=${1:-docker_vufind6_1}

docker exec $CONTAINER vendor/bin/phpstan --configuration=../knihovny-cz-extension/tests/phpstan.neon --memory-limit=2G analyse

docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind.php_cs --dry-run -vvv
docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind_templates.php_cs --dry-run -vvv
docker exec $CONTAINER vendor/bin/phing phpcs-console

docker exec $CONTAINER eslint -c .eslintrc.js ../knihovny-cz-extension/themes/KnihovnyCz/js/*.js
docker exec $CONTAINER jshint --verbose --config=tests/jshint.json --exclude=../knihovny-cz-extension/themes/**/js/lib/ ../knihovny-cz-extension/themes

docker exec $CONTAINER vendor/bin/phpunit
