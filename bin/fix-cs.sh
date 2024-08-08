#!/bin/bash

CONTAINER=${1:-docker-vufind6-1}

docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind.php-cs-fixer.php -vvv
docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind_templates.php-cs-fixer.php -vvv
docker exec $CONTAINER vendor/bin/phpcbf --standard=../knihovny-cz-extension/tests/phpcs.xml
