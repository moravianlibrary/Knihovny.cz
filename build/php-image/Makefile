.PHONY: image base-image

BUILD_COMMAND = docker build

BASE_PHP_IMAGE_TAG = latest
BASE_PHP_IMAGE_NAME = localhost/knihovny-php:${BASE_PHP_IMAGE_TAG}

# Build the PHP base image ourselves (from docker-library/php) when a security
# release is not yet available on Docker Hub. Set PHP_VERSION empty to use the
# PHP_BASE_IMAGE default from Containerfile instead.
# SHA256 of php-$(PHP_VERSION).tar.xz from https://www.php.net/downloads
PHP_VERSION ?= 8.4.27
PHP_SHA256 ?= dcf93923b0e38e0d5252b25b796520b5a47761a08b3488b6d0b5d1115764d3f0
PHP_BASE_IMAGE_ARG = $(if $(PHP_VERSION),--build-arg PHP_BASE_IMAGE=localhost/php:$(PHP_VERSION)-fpm-bookworm)

image: $(if $(PHP_VERSION),base-image)
		$(BUILD_COMMAND) $(PHP_BASE_IMAGE_ARG) -f Containerfile -t $(BASE_PHP_IMAGE_NAME) .

base-image:
		BUILD_COMMAND="$(BUILD_COMMAND)" sh ./build-base-image.sh $(PHP_VERSION) $(PHP_SHA256)
