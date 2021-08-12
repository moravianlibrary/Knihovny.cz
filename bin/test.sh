#!/bin/bash

CONTAINER=${1:-docker_vufind6_1}

docker exec $CONTAINER vendor/bin/phpunit
