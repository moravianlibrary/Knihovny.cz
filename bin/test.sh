#!/bin/bash

CONTAINER=${1:-docker_vufind_1}

docker exec $CONTAINER vendor/bin/phpunit
