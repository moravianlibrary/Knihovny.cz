#!/usr/bin/env bash

if ! grep -q docker /proc/1/cgroup; then
    echo "This script needs to be run from container"
    exit 1
fi

cd /var/www/

VUFIND_LOCAL_DIR="local/$PARAM_VUFIND_CONFIG_DIR" VUFIND_LOCAL_MODULES="KnihovnyCz,KnihovnyCzConsole" php public/index.php util/clear_cache
