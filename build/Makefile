.PHONY: image

PARAM_VUFIND_COMMIT_ID = "da392b009322a02965106205fc8bfe597a4d7fc4"

image:
	make -C php-image
	make -C vufind-image PARAM_VUFIND_COMMIT_ID=${PARAM_VUFIND_COMMIT_ID}

