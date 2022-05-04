#!/bin/bash

CONTAINER=${1:-docker_vufind6_1}

docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind.php_cs -vvv
docker exec $CONTAINER vendor/bin/php-cs-fixer fix --config=../knihovny-cz-extension/tests/vufind_templates.php_cs -vvv
docker exec $CONTAINER vendor/bin/phing phpcbf
