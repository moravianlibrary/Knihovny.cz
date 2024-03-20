#!/usr/bin/env bash

function print_usage {
    name=$(basename $0)
    cat <<EOF
Script for copying new config file to config base and add configs for all instances

USAGE: $name [-d|--directory directory_name] [-c|--config config_name]

  -d|--directory Directory of original VuFind code base root
  -c|--config    Config file name (with extension)
  --help|-h      Print usage

EOF
}

while true ; do
    case "$1" in
        -d|--directory)
            case "$2" in
                "")
                  print_usage;
                  exit 1;;
                *)
                  VUFIND_DIR=$2;
                  shift 2 ;;
            esac ;;
        -c|--config)
            case "$2" in
                "")
                  print_usage;
                  exit 1;;
                *) CONFIG_NAME=$2;
                   shift 2 ;;
            esac ;;
        --help|-h)
            print_usage;
            exit 0;;
        *) break ;;
    esac
done

if [ "$VUFIND_DIR" = "" -o "$CONFIG_NAME" = "" ] ; then
  print_usage
  exit 1
fi

if [ ! -d "$VUFIND_DIR" ] ; then
  echo "Directory $VUFIND_DIR does not exists"
  exit 1
fi

CONFIG_PATH="$VUFIND_DIR/config/vufind/$CONFIG_NAME"

if [ ! -f "$CONFIG_PATH" ] ; then
  echo "Configuration file $CONFIG_NAME on path $CONFIG_PATH does not exists"
  exit 1
fi

INSTANCES="knihovny.cz irel kiv mus tech mzk geo knav nkp"
PROJECT_PATH=`dirname $(readlink -nf $0)`/..
BASE_CONFIG_DIR="$PROJECT_PATH/local/base/config/vufind/"
CONFIG_FILE_EXT="${CONFIG_NAME##*.}"
TEMPLATE_FILE="$PROJECT_PATH/local/templates/config/vufind/view.$CONFIG_FILE_EXT"

echo "Copying $CONFIG_NAME from codebase in $VUFIND_DIR"
cp "$CONFIG_PATH" $BASE_CONFIG_DIR

for VIEW in $INSTANCES ; do
  VIEW_CONFIG_FULLPATH="$PROJECT_PATH/local/$VIEW/config/vufind/$CONFIG_NAME"
  echo "Creating view config file: $VIEW_CONFIG_FULLPATH"
  sed -e "s#__CONFIG_NAME__#$CONFIG_NAME#" "$TEMPLATE_FILE" > $VIEW_CONFIG_FULLPATH
done

