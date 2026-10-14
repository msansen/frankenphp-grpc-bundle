CURRENT_UID := $(shell id -u):$(shell id -g)
IMAGE_NAME := "frankenphp-grpc-bundle:php-8.2"
RUN_PHP := docker run --rm -v ${PWD}:/app -u ${CURRENT_UID} ${IMAGE_NAME}

docker-build:								## Builds the local docker image running php/composer
	@docker build -f Dockerfile.php -t ${IMAGE_NAME} .

install: c=install --ansi					## Runs composer install
install: composer

test: docker-build							## Runs phpunit
	@docker run --rm -v ${PWD}:/app -u ${CURRENT_UID} ${IMAGE_NAME} vendor/bin/phpunit

lint: docker-build						## Fixes Symfony coding standards with PHP-CS-Fixer
	@$(RUN_PHP) vendor/bin/php-cs-fixer fix --verbose --show-progress=dots

check: lint test

.PHONY: install test lint check docker-build

composer: docker-build
	@$(eval c ?=)
	@docker run --rm -v ${PWD}:/app -u ${CURRENT_UID} ${IMAGE_NAME} composer $(c)

.PHONY: composer

.DEFAULT_GOAL:= help
help:
	@grep -Eh '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

.PHONY: help
