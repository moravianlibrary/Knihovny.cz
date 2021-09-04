.PHONE: image

BUILD_COMMAND = docker build

IMAGE_TAG = latest
IMAGE_NAME = localhost/knihovny-php:${IMAGE_TAG}

image:
		$(BUILD_COMMAND) -f Containerfile -t $(IMAGE_NAME) .
