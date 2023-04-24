#!/bin/bash

CONTAINER=${1:-docker_vufind6_1}

docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind.php-cs-fixer.php -vvv
docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind_templates.php-cs-fixer.php -vvv
docker exec $CONTAINER vendor/bin/phing phpcbf
