#!/usr/bin/env bash

function print_usage {
    cat <<EOF
Script for deploying testing containers on cpk-front.mzk.cz.
After successful build and deploy, script echoes URL on which container
is running.

USAGE: deploy_cpk [-b branch] [-c directory_name]

  -b                   Branch to use for build
  -c                   Config directory to use
  --help|-h            Print usage

EOF
}

branch="master"
config_dir=""
while true ; do
    case "$1" in
        -b)
            case "$2" in
                "") shift 2 ;;
                *) branch=$2;
                   echo "Processing argument: $1 = $2";
                   shift 2 ;;
            esac ;;
        -c)
            case "$2" in
                "") shift 2 ;;
                *) config_dir=$2;
                   echo "Processing argument: $1 = $2";
                   shift 2 ;;
            esac ;;
        --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

port=0
for i in $(seq 10000 10009); do
    docker_port=$((i+10000));
    if ! netstat -ln | grep ":$docker_port " | grep -q 'LISTEN'; then
        port=$i
        break
    fi
done

if [ $port = 0 ]; then
    echo "No free available port"
    exit 1
fi

if [ ! -z "$config_dir" ]; then
    export PARAM_VUFIND_CONFIG_DIR="$config_dir"
fi

service="devel6-${port}"
http_port=$(($port+10000))
https_port=$(($port+10443))
container_name="knihovny-devel6-$branch"

`dirname $0`/run.sh -d -t devel -p $http_port -s $https_port -b $branch -service $service -n $container_name

echo "URL:"
echo "https://cpk-front.mzk.cz:${port}/"
