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
  -t            Build type - devel, deploy, local, tech. Defaults to 'devel'
  -l            Enable shibboleth login [true|false], default enabled
  --help|-h     Print usage

EOF
}

branch="master"
config_dir=""
shib_login="true"
type="devel"
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
        -t)
            case "$2" in
                "") shift 2 ;;
                *) type=$2;
                   echo "Processing argument: $1 = $2";
                   shift 2 ;;
            esac ;;
         -l)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   shib_login=$2
                   shift 2 ;;
            esac ;;
        --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

port=0
port_start=10000
port_end=10009
if [ "$shib_login" == "false" ]; then
    port_start=10010
    port_end=10019
fi
for i in $(seq "$port_start" "$port_end"); do
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
container_name="knihovny-$type-$branch"

PROJECT_PATH=`dirname $(readlink -nf $0)`/..
cd $PROJECT_PATH

CURRENT_BRANCH=$(git symbolic-ref --short -q HEAD)
CURRENT_BRANCH=${CURRENT_BRANCH:-HEAD}

git fetch
git checkout "origin/$branch"
if [[ $? != 0 ]]; then
  echo 'Cannot run git checkout, ensure you have ale changes commited'
  exit 1
fi


$PROJECT_PATH/bin/run.sh -d -t $type -p $http_port -s $https_port -b $branch -service $service -n $container_name

git checkout "$CURRENT_BRANCH"

echo "URL:"
echo "https://cpk-front.mzk.cz:${port}/"
