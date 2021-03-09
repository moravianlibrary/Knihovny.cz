REGISTRY_USER ?= docker
REGISTRY_PASSWORD ?= docker

auth/registry.password:
	htpasswd -b -c auth/registry.password $(REGISTRY_USER) $(REGISTRY_PASSWORD)

up:
	docker-compose up -d

down:
	docker-compose down

check:
	curl http://localhost:5001/v2/_catalog \
		 --user $(REGISTRY_USER):$(REGISTRY_PASSWORD) \
		 | jq
