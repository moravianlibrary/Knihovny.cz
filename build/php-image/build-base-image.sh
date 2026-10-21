#!/bin/sh
# Build the official docker.io/php base image locally from docker-library/php
# with a newer PHP version than is available on Docker Hub (e.g. for an urgent
# security release). The tarball is verified by SHA256 and by the release
# managers' GPG signature, same as in the official build.
#
# Usage: ./build-base-image.sh <php-version> <tar.xz-sha256> [variant] [docker-library-ref]
#   e.g. ./build-base-image.sh 8.4.27 dcf939...
# Produces: localhost/php:<php-version>-<variant>  (default variant: fpm-bookworm)
# Requires only curl (or git), tar and sed (works in CI runner images).
set -eu

PHP_VERSION="${1:?usage: $0 <php-version> <sha256> [variant] [docker-library-ref]}"
PHP_SHA256="${2:?usage: $0 <php-version> <sha256> [variant] [docker-library-ref]}"
VARIANT="${3:-fpm-bookworm}"
DOCKER_LIBRARY_REF="${4:-9dac3d048ebdcdb3b6a27350b7572ddee58c70df}"
BUILD_COMMAND="${BUILD_COMMAND:-docker build}"

MINOR="${PHP_VERSION%.*}"
DISTRO="${VARIANT#*-}"
FLAVOR="${VARIANT%%-*}"

WORKDIR=$(mktemp -d)
trap 'rm -rf "$WORKDIR"' EXIT
# CI runner image (docker:*-git) has git but no curl
if command -v curl >/dev/null; then
	curl -fsSL -o "$WORKDIR/src.tar.gz" "https://github.com/docker-library/php/archive/${DOCKER_LIBRARY_REF}.tar.gz"
	tar -xzf "$WORKDIR/src.tar.gz" -C "$WORKDIR" --strip-components=1
else
	git -C "$WORKDIR" init -q
	git -C "$WORKDIR" fetch -q --depth 1 https://github.com/docker-library/php.git "$DOCKER_LIBRARY_REF"
	git -C "$WORKDIR" checkout -q FETCH_HEAD
fi
CONTEXT="$WORKDIR/${MINOR}/${DISTRO}/${FLAVOR}"

sed -i \
	-e "s|^ENV PHP_VERSION .*|ENV PHP_VERSION ${PHP_VERSION}|" \
	-e "s|^ENV PHP_URL=.*|ENV PHP_URL=\"https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz\" PHP_ASC_URL=\"https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz.asc\"|" \
	-e "s|^ENV PHP_SHA256=.*|ENV PHP_SHA256=\"${PHP_SHA256}\"|" \
	"$CONTEXT/Dockerfile"
grep -E '^ENV PHP_(VERSION|URL|SHA256)' "$CONTEXT/Dockerfile"

$BUILD_COMMAND --pull -t "localhost/php:${PHP_VERSION}-${VARIANT}" "$CONTEXT"
