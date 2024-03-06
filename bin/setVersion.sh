#!/bin/bash

function print_usage {
	echo "Usage:"
	echo "	$0 x.y.z"
}

if [ -z $1 ]; then
	echo "Parameter	version is not set"
	print_usage
	exit 1
fi

VERSION="$1"
FORMAT='[0-9]\+\.[0-9]\+\.[0-9]\+'

COUNT=$(echo "$1" | sed -n "/^$FORMAT$/p" | wc -l)

if ! [ $COUNT -gt 0 ]; then
	echo "Parameter version is not in the right format"
	print_usage
	exit 2
fi

sed -i "s/generator = \"Knihovny.cz $FORMAT /generator = \"Knihovny.cz $VERSION /" local/base/config/vufind/config.ini
sed -i "s/\"version\": \"$FORMAT\",/\"version\": \"$VERSION\",/" package.json

echo "Version updated to $VERSION"

exit 0
