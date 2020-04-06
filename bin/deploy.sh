#!/usr/bin/env bash

function print_usage {
    name=$(basename $0)
    cat <<EOF
Script for deploying testing containers on cpk-front.mzk.cz.
After successful build and deploy, script echoes URL on which container
is running.

USAGE: $name [-b branch] [-c directory_name]

  -b            Branch to use for build. Defaults to master
  -c            Config directory to use, aka view name. Defaults to knihovny.cz
  --help|-h     Print usage

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
container_name="knihovny-devel-$branch"

PROJECT_PATH=`dirname $(readlink -nf $0)`/..
cd $PROJECT_PATH

CURRENT_BRANCH=$(git symbolic-ref --short -q HEAD)
CURRENT_BRANCH=${CURRENT_BRANCH:-HEAD}

git checkout "$branch"
if [[ $? != 0 ]]; then
  echo 'Cannot run git checkout, ensure you have ale changes commited'
  exit 1
fi

$PROJECT_PATH/bin/run.sh -d -t devel -p $http_port -s $https_port -b $branch -service $service -n $container_name

git checkout "$CURRENT_BRANCH"

echo "URL:"
echo "https://cpk-front.mzk.cz:${port}/"
