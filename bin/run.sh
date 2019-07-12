#!/usr/bin/env bash

function print_usage {
    cat <<EOF

USAGE: run.sh [params]

Available params
  -t type                  Build type, default "local":
                               local  - for development on local machine, source code is mounted from external source
                               deploy - for deployment on production/testing environment, source code is downloaded and included in image
                               devel - for deployment on our testing server
  --branch|-b branchname   Use this git branch as source code, build type must be deploy, default "master"
  -p port_number           HTTP port to run apache on, default 80
  -s port_number           HTTPS port to run apache on, default 443
  -service service_name    Service to run, available services: vufind (default), knihovny-cz, devel, devel-10000 - devel-10009, php-extensions, apache-shibboleth
  --container_name|-n name Container name to use, default ""
  -d                       Run docker compose in detached mode
  --image|-i               Image name to use when building container with vufind, default "knihovny_cz"
  --version|-v             Image version to use when building container with vufind, default "latest"
  --push                   Push image after successful build to docker hub
  --push-only              Push image after successful build to docker hub without running container
  --help|-h                Print usage

EOF
}

function last_commit {
    local branch=$1
    local url="https://api.github.com/repos/moravianlibrary/Knihovny.cz/commits?sha=$branch"
    local sha=`curl "$url" | php -r "echo (string) json_decode(file_get_contents('php://stdin'))[0]->sha;"`
    echo $sha
}

# default variable values
branch="master"
build_type="local"
push="false"
run="true"
version="latest"
http_port="80"
https_port="443"
image_name="knihovny_cz"
version="latest"
detached=false
service=vufind
container_name=""
# extract options and their arguments into variables.
while true ; do
    case "$1" in
         --branch|-b)
            case "$2" in
                "") shift 2 ;;
                *) branch=$2;
                   echo "Processing argument: $1 = $2";
                   shift 2 ;;
            esac ;;
         -t)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   build_type=$2
                   shift 2 ;;
            esac ;;
         -p)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   http_port=$2
                   shift 2 ;;
            esac ;;
         -s)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   https_port=$2
                   shift 2 ;;
            esac ;;
         -service)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   service=$2
                   shift 2 ;;
            esac ;;
         -d)
            detached=true
            shift
            ;;
         --push)
            push="true"
            shift
            ;;
         --push-only)
            push="true"
            run="false"
            shift
            ;;
         --image|-i)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   image_name=$2
                   shift 2 ;;
            esac ;;
         --container_name|-n)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   container_name=$2
                   shift 2 ;;
            esac ;;
         --version|-v)
            case "$2" in
                "") shift 2 ;;
                *) echo "Processing argument: $1 = $2";
                   version=$2
                   shift 2 ;;
            esac ;;
         --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

LAST_COMMIT=`last_commit $branch`

build_args="--build-arg PARAM_VUFIND_COMMIT_HASH=${LAST_COMMIT}"
compose_args=""

if [[ $detached == "true" ]]; then
    compose_args="-d"
fi

if [[ ! -z  "$branch" ]]; then
    build_args="$build_args --build-arg PARAM_VUFIND_BRANCH=$branch"
    if [[ -z "$image_name" ]]; then
        image_name="knihovny_cz_${branch}"
    fi
fi

docker_compose_file="docker-compose.${build_type}.yaml"

export IMAGE_NAME="${image_name}"
export HTTP_PORT="${http_port}"
export HTTPS_PORT="${https_port}"
export IMAGE_NAME="${image_name}"
export IMAGE_VERSION="${version}"
export CONTAINER_NAME="${container_name}"

cd $(dirname "$0")"/../docker"

for srv in php-extensions apache-shibboleth knihovny-cz; do
    docker-compose build "$srv"
    if [ $? -ne 0 ]; then
        echo "Can't build $srv, exiting"
        exit 1
    fi
done

docker-compose -f "$docker_compose_file" build $build_args $service
if [ $? -ne 0 ]; then
    echo "Can't build Knihovny.cz containers, exiting"
    exit 3
fi
if [[ $push == "true" ]]; then
    docker-compose -f "$docker_compose_file" push $service
fi
if [[ $run == "true" ]]; then
    docker-compose -f "$docker_compose_file" up $compose_args $service
fi
