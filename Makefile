#Makefile
DOCKER_COMPOSE = docker-compose
.PHONY: setup up down restart logs shell \
composer artisan migrate fresh seed \
test smoke lint lint-fix analyse \
refactor refactor-dry \
quality pre-commit

setup:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
	fi

	$(DOCKER_COMPOSE) up -d --build
	$(DOCKER_COMPOSE) exec app composer install

	@if ! $(DOCKER_COMPOSE) exec app php artisan key:show > /dev/null 2>&1; then \
		$(DOCKER_COMPOSE) exec app php artisan key:generate; \
	fi

	$(DOCKER_COMPOSE) exec app php artisan migrate

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) restart

logs:
	$(DOCKER_COMPOSE) logs -f

shell:
	$(DOCKER_COMPOSE) exec app bash

install:
	$(DOCKER_COMPOSE) exec app composer install

artisan:
	$(DOCKER_COMPOSE) exec app php artisan

migrate:
	$(DOCKER_COMPOSE) exec app php artisan migrate

fresh:
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

seed:
	$(DOCKER_COMPOSE) exec app php artisan db:seed

test:
	$(DOCKER_COMPOSE) exec app php artisan test

smoke:
	$(DOCKER_COMPOSE) exec app php artisan config:clear
	$(DOCKER_COMPOSE) exec app php artisan test --testsuite=Smoke

lint:
	$(DOCKER_COMPOSE) exec app vendor/bin/pint --test

lint-fix:
	$(DOCKER_COMPOSE) exec app vendor/bin/pint

analyse:
	$(DOCKER_COMPOSE) exec app vendor/bin/phpstan analyse

refactor-dry:
	$(DOCKER_COMPOSE) exec app vendor/bin/rector process --dry-run

refactor:
	$(DOCKER_COMPOSE) exec app vendor/bin/rector process

quality:
	$(MAKE) lint
	$(MAKE) analyse
	$(MAKE) refactor-dry
	$(MAKE) smoke

pre-commit:
	$(MAKE) quality

status:
	$(DOCKER_COMPOSE) ps
