.PHONE: image

BUILD_COMMAND = docker build

BASE_PHP_IMAGE_TAG = latest
BASE_PHP_IMAGE_NAME = localhost/knihovny-php:${BASE_PHP_IMAGE_TAG}

image:
		$(BUILD_COMMAND) -f Containerfile -t $(BASE_PHP_IMAGE_NAME) .
