#!/bin/bash

function print_usage {
    cat <<EOF

USAGE: $0 [params]

Available params
  -b|--branch    Branch of original VuFind repository (https://github.com/vufind-org/vufind), defaults to "dev"
  -d|--dry-run   Only prints information about available update
  -h|--help      Print usage

EOF
}

DIRNAME=$(dirname "$0");
.  "${DIRNAME}/inc/functions.sh"
FILENAME="${DIRNAME}/../docker/builds/knihovny-cz-base6/Dockerfile"

# Set deafults
repository="vufind-org/vufind"
branch="dev"
dryrun=false

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
         --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

REMOTE_VERSION=$(last_commit $branch $repository)
OUR_VERSION=$(grep "ENV PARAM_VUFIND_COMMIT" "${FILENAME}" | sed 's/ENV PARAM_VUFIND_COMMIT="\(.*\)"/\1/g')

if [ "$REMOTE_VERSION" == "$OUR_VERSION" ]; then
  echo "Remote and local versions are same. No update needed."
  exit 0;
fi;
echo "Is available update to version $REMOTE_VERSION. Your current version is $OUR_VERSION."

if [ "$dryrun" == "true" ]; then
  echo "Running in testing mode. Exiting without making any changes"
  exit 0;
fi

sed -i "s/ENV PARAM_VUFIND_COMMIT=\"\(.*\)\"/ENV PARAM_VUFIND_COMMIT=\"${REMOTE_VERSION}\"/g" "${FILENAME}"

merge_directory local/base/config/vufind config/vufind $OUR_VERSION $REMOTE_VERSION ${repository}
merge_directory themes/KnihovnyCz/templates themes/bootstrap3/templates $OUR_VERSION $REMOTE_VERSION ${repository}

echo "Version was updated."

exit 0;
