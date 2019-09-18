#!/bin/bash

function print_usage {
    cat <<EOF

USAGE: $0 [params]

Available params
  -b|--branch    Branch of original VuFind repository (https://github.com/vufind-org/vufind), default "master"
  -d|--dry-run   Only prints information about available update
  -c|--commit    Create  git commit automatically
  -h|--help      Print usage

EOF
}

DIRNAME=$(dirname "$0");
.  "${DIRNAME}/inc/functions.sh"
FILENAME="${DIRNAME}/../docker/builds/knihovny-cz-base6/Dockerfile"

# Set deafults
repository="vufind-org/vufind"
branch="master"
dryrun=false
commit=false

while true ; do
    case "$1" in
         --branch|-b)
            case "$2" in
                "") shift 2 ;;
                *) branch=$2;
                   shift 2 ;;
            esac ;;
         --dry-run|-d)
            dryrun=true
            shift
            ;;
         --commit|-c)
            commit=true
            shift
            ;;
         --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

REMOTE_VERSION=$(last_commit $branch $repository)
OUR_VERSION=$(grep "ENV PARAM_VUFIND_COMMIT" "${FILENAME}" | sed 's/ENV PARAM_VUFIND_COMMIT="\(.*\)"/\1/g')

if [ "$REMOTE_VERSION" == "$OUR_VERSION" ]; then
  echo "Remote and local versions are some. No update needed."
  exit 0;
fi;
echo "Is available update to version $REMOTE_VERSION. Your current version is $OUR_VERSION."

if [ "$dryrun" == "true" ]; then
  echo "Running in testing mode. Exiting without making any changes"
  exit 0;
fi

sed -i "s/ENV PARAM_VUFIND_COMMIT=\"\(.*\)\"/ENV PARAM_VUFIND_COMMIT=\"${REMOTE_VERSION}\"/g" "${FILENAME}"
#TODO 3-way merge of themes
echo "Version was updated."

if [ "$commit" == "true" ]; then
  git add "${FILENAME}"
  git commit -m "Update base VuFind to ${REMOTE_VERSION}"
fi

exit 0;