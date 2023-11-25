.PHONY: image

# get VUFIND_COMMIT_ID from .gitlab-ci.yml
#
SRC_ROOT := $(abspath ..)
CI_FILENAME := ${SRC_ROOT}/.gitlab-ci.yml
PARAM_VUFIND_COMMIT_ID = $(shell grep "VUFIND_COMMIT_ID" "${CI_FILENAME}" | sed 's/\s*VUFIND_COMMIT_ID: \(.*\)/\1/g' )

image:
	echo "=== running make php-image ==="
	make -C php-image
	echo "=== running make vufind-image ==="
	make -C vufind-image PARAM_VUFIND_COMMIT_ID=${PARAM_VUFIND_COMMIT_ID}

