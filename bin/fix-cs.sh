#!/bin/bash

php-cs-fixer fix --config=tests/vufind.php_cs -vvv
php-cs-fixer fix --config=tests/vufind_templates.php_cs -vvv

CONTAINER=${1:-docker_vufind6_1}

docker exec $CONTAINER vendor/bin/phing phpcbf
