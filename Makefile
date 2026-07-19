.DEFAULT_GOAL := help
.PHONY: help up down build rebuild logs shell install install-local migrate etl schedule test test-file coverage lint format format-check check clean

# Use -it only when attached to a real terminal, so the targets also work in CI.
TTY := $(shell [ -t 0 ] && echo -it)
EXEC := docker exec $(TTY) symfony_cli

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

## ---- Docker ----

up: ## Build and start the containers in the background
	docker compose up -d --build

down: ## Stop and remove the containers (data kept)
	docker compose down

build: ## Build the images
	docker compose build

rebuild: ## Rebuild the images without cache
	docker compose build --no-cache

logs: ## Tail container logs
	docker compose logs -f

shell: ## Open a shell in the Symfony container
	$(EXEC) bash

## ---- Setup ----

install: ## Install PHP dependencies (inside the container)
	$(EXEC) composer install

install-local: ## Copy .env, start containers, install deps, and migrate
	@test -f .env || (cp .env.example .env && echo "Created .env from .env.example.")
	docker compose up -d --build
	$(EXEC) composer install
	$(EXEC) php bin/console doctrine:migrations:migrate --no-interaction
	@echo "Done. Run 'make etl' to populate the warehouse."

migrate: ## Run database migrations
	$(EXEC) php bin/console doctrine:migrations:migrate --no-interaction

## ---- ETL ----

etl: ## Run the ETL pipeline once
	$(EXEC) php bin/console app:run-etl

schedule: ## Consume the scheduler so the pipeline runs on its cron (hourly)
	$(EXEC) php bin/console messenger:consume scheduler_etl_pipeline -vv

## ---- Tests ----

test: ## Run the full test suite
	$(EXEC) ./vendor/bin/phpunit

test-file: ## Run a single test file: make test-file F=tests/Service/ETL/DataExtractorTest.php
	$(EXEC) ./vendor/bin/phpunit $(F)

coverage: ## Run the test suite with an HTML coverage report (var/coverage)
	$(EXEC) ./vendor/bin/phpunit --coverage-html var/coverage

## ---- Code quality ----

format: ## Auto-format code with PHP-CS-Fixer
	$(EXEC) composer format

format-check: ## Check formatting without writing
	$(EXEC) composer format-check

lint: ## Run PHPStan static analysis
	$(EXEC) composer lint

check: format-check lint test ## CI-style: verify formatting, static analysis, and tests

## ---- Housekeeping ----

clean: ## Clear the Symfony cache
	$(EXEC) php bin/console cache:clear
