.PHONE: image

BUILD_COMMAND = docker build

IMAGE_TAG ?= latest
IMAGE_NAME ?= localhost/knihovny-vufind:${IMAGE_TAG}

SRC_ROOT_PATH = $(shell dirname $(shell dirname $(CURDIR)))

PARAM_VUFIND_COMMIT_ID ?= unspecified

image:
		echo "Running build in $(SRC_ROOT_PATH)"
		$(BUILD_COMMAND) -f Containerfile -t $(IMAGE_NAME) \
					--build-arg PARAM_VUFIND_COMMIT_ID=$(PARAM_VUFIND_COMMIT_ID) \
					$(SRC_ROOT_PATH)
