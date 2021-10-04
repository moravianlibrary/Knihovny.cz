.PHONY: image

PARAM_VUFIND_COMMIT_ID = "undefined"

image:
	make -C php-image
	make -C vufind-image PARAM_VUFIND_COMMIT_ID=${PARAM_VUFIND_COMMIT_ID}

