#!/bin/bash

CONTAINER=${1:-docker-vufind6-1}

docker exec $CONTAINER vendor/bin/phpunit
