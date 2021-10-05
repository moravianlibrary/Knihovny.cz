#!/bin/bash

function print_usage {
    cat <<EOF

USAGE: $0 [params]

Available params
  -b|--branch    Branch of original VuFind repository (https://github.com/vufind-org/vufind), defaults to "dev"
  -g|--debug     Show debug messages
  -d|--dry-run   Only prints information about available update
  -h|--help      Print usage

EOF
}

DIRNAME=$(dirname "$0");
.  "${DIRNAME}/inc/functions.sh"
FILENAME="${DIRNAME}/../docker/builds/knihovny-cz-base6/Dockerfile"
CI_FILENAME="${DIRNAME}/../.gitlab-ci.yml"

# Set deafults
repository="vufind-org/vufind"
branch="dev"
dryrun=false
commit_message_file=".commit"

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
         --debug|-g)
            debug=true
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
OUR_CI_VERSION=$(grep "VUFIND_COMMIT_ID" "${CI_FILENAME}" | sed 's/\s*VUFIND_COMMIT_ID: \(.*\)/\1/g')
if [ "$OUR_VERSION" != "$OUR_CI_VERSION" ]; then
  echo "Local build version ($OUR_VERSION) and CI build version ($OUR_CI_VERSION) are not equal. Please make manual adjustment"
  echo "Local build version is defined in file $FILENAME"
  echo "Local build version is defined in file $CI_FILENAME"
  exit 1;
fi;

if [ "$REMOTE_VERSION" == "$OUR_VERSION" ]; then
  echo "Remote and local versions are same. No update needed."
  exit 0;
fi;
echo "Is available update to version $REMOTE_VERSION. Your current version is $OUR_VERSION."
echo "Update base VuFind" > $commit_message_file
echo "" >> $commit_message_file
echo "See changes here: https://github.com/vufind-org/vufind/compare/$OUR_VERSION...$REMOTE_VERSION" >> $commit_message_file

if [ "$dryrun" == "true" ]; then
  echo "Running in testing mode. Exiting without making any changes"
  exit 0;
fi

sed -i "s/ENV PARAM_VUFIND_COMMIT=\"\(.*\)\"/ENV PARAM_VUFIND_COMMIT=\"${REMOTE_VERSION}\"/g" "${FILENAME}"
sed -i "s/\(\s*\)VUFIND_COMMIT_ID: \(.*\)/\1VUFIND_COMMIT_ID: ${REMOTE_VERSION}/g" "${CI_FILENAME}"

exit 0;

merge_directory local/base/config/vufind config/vufind $OUR_VERSION $REMOTE_VERSION ${repository}
merge_directory themes/KnihovnyCz/templates themes/bootstrap3/templates $OUR_VERSION $REMOTE_VERSION ${repository}
merge_directory themes/KnihovnyCz/templates/searchapi themes/root/templates/searchapi $OUR_VERSION $REMOTE_VERSION ${repository}

echo "Version was updated."
echo "Please commit with command: "
echo "git commit -eF .commit"

exit 0;
