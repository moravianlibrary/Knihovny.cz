#!/usr/bin/env bash

[ -n "$DEBUG" ] && set -x
set -e

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
  -service service_name    Service to run, available services: vufind6 (default), knihovny-cz, devel6, devel6-10000 - devel6-10009, php-extensions6, apache-shibboleth6
  --container_name|-n name Container name to use, default ""
  -d                       Run docker compose in detached mode
  --image|-i               Image name to use when building container with vufind, default "knihovny_cz"
  --version|-v             Image version to use when building container with vufind, default "latest"
  --push                   Push image after successful build to docker hub
  --push-only              Push image after successful build to docker hub without running container
  --no-run                 Don't run 'docker-compose up'
  --private-registry       Push image after successful build to private registry
  --help|-h                Print usage

EOF
}

function _fail {
  echo "[!] $1" >&2
  exit 1
}

function last_commit {
  local branch=$1
  local data=$(printf '{"query":"{project(fullPath: \\"knihovny.cz/Knihovny-cz\\") { repository { tree(ref: \\"%s\\") { lastCommit { sha }}}}}"}' "$branch")
  local response=`curl -s 'https://gitlab.mzk.cz/api/graphql' \
    --header 'Content-Type: application/json' \
    --header "Private-token: ${GITLAB_API_TOKEN}" \
    --request POST \
    --data "${data}"`
  local last_commit=$(echo $response | php -r "echo (string)json_decode(file_get_contents('php://stdin'))->data->project->repository->tree->lastCommit->sha;" 2> /dev/null)
  [ ${#last_commit} -eq 0 ] &&  _fail "Failed to get last commit for '$branch' branch"
  echo $last_commit
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
push_to_private_registry="false"
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
         --no-run)
            run="false"
            shift
            ;;
         --private-registry)
            push_to_private_registry="true"
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

cd $(dirname "$0")"/../docker" || exit

compose_args=""

if [[ $detached == "true" ]]; then
    compose_args="-d"
fi

env_file="${build_type}.env"
export $(cat $env_file | xargs)

# We need this to enforce docker to run git clone when the branch is updated
LAST_COMMIT=$(last_commit ${branch})
build_args="$build_args --build-arg PARAM_VUFIND_BRANCH=$branch --build-arg PARAM_VUFIND_COMMIT_HASH=${LAST_COMMIT} --build-arg GITLAB_DEPLOY_USER=$GITLAB_DEPLOY_USER --build-arg GITLAB_DEPLOY_PASSWORD=$GITLAB_DEPLOY_PASSWORD"

if [[ ! -z  "$branch" ]]; then
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
export PARAM_VUFIND_CONFIG_DIR=${PARAM_VUFIND_CONFIG_DIR:-knihovny.cz}

cp "../composer.local.json" "./builds/knihovny-cz-base6/"

for srv in php-extensions6 apache-shibboleth6 knihovny-cz; do
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
if [[ $push_to_private_registry == "true" ]]; then
  REGISTRY_URL="${REGISTRY_URL:-localhost:5001}"
  REGISTRY_USER="${REGISTRY_USER:-docker}"
  REGISTRY_PASSWORD="${REGISTRY_PASSWORD:-docker}"

  docker logout "$REGISTRY_URL"
  echo "$REGISTRY_PASSWORD" | docker login "$REGISTRY_URL" --username "$REGISTRY_USER" --password-stdin
  tag="$REGISTRY_URL/$IMAGE_NAME"
  docker tag "localhost/$IMAGE_NAME" "$tag"
  docker push "$tag"
fi
if [[ $run == "true" ]]; then
    docker-compose -f "$docker_compose_file" up $compose_args $service
fi

