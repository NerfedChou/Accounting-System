# Makefile for Docker-based Development

.PHONY: help up down restart build logs shell mysql composer test migrate seed fresh clean

# Default environment
ENV ?= dev

help: ## Show this help message
	@echo "Accounting System - Docker Commands"
	@echo ""
	@echo "Usage: make [command] ENV=[dev|prod]"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Start Docker containers
	@docker-compose -f docker-compose.$(ENV).yml up -d
	@echo "✓ Containers started!"
	@echo "→ Application: http://localhost:8080"

down: ## Stop Docker containers
	@docker-compose -f docker-compose.$(ENV).yml down
	@echo "✓ Containers stopped!"

restart: ## Restart Docker containers
	@docker-compose -f docker-compose.$(ENV).yml restart
	@echo "✓ Containers restarted!"

build: ## Build Docker images
	@docker-compose -f docker-compose.$(ENV).yml build --no-cache
	@echo "✓ Build complete!"

logs: ## Show container logs
	@docker-compose -f docker-compose.$(ENV).yml logs -f

shell: ## Access app container shell
	@docker-compose -f docker-compose.$(ENV).yml exec app sh

mysql: ## Access MySQL shell
	@docker-compose -f docker-compose.$(ENV).yml exec mysql mysql -u accounting_user -p accounting_system

composer-install: ## Install composer dependencies
	@docker-compose -f docker-compose.$(ENV).yml exec app composer install

composer-update: ## Update composer dependencies
	@docker-compose -f docker-compose.$(ENV).yml exec app composer update

test: ## Run tests
	@docker-compose -f docker-compose.$(ENV).yml exec app composer test

test-coverage: ## Run tests with coverage
	@docker-compose -f docker-compose.$(ENV).yml exec app composer test:coverage

migrate: ## Run database migrations
	@docker-compose -f docker-compose.$(ENV).yml exec app php migrate up

migrate-rollback: ## Rollback last migration
	@docker-compose -f docker-compose.$(ENV).yml exec app php migrate down

seed: ## Seed database
	@docker-compose -f docker-compose.$(ENV).yml exec app php seed

fresh: ## Fresh database setup
	@docker-compose -f docker-compose.$(ENV).yml exec app php migrate fresh
	@docker-compose -f docker-compose.$(ENV).yml exec app php seed
	@echo "✓ Database fresh setup complete!"

lint: ## Run code linter
	@docker-compose -f docker-compose.$(ENV).yml exec app composer lint

analyse: ## Run static analysis
	@docker-compose -f docker-compose.$(ENV).yml exec app composer analyse

clean: ## Remove containers and volumes
	@docker-compose -f docker-compose.$(ENV).yml down -v
	@echo "✓ Cleanup complete!"

# Development shortcuts
dev-up: ENV=dev ## Start development environment
dev-up: up

dev-down: ENV=dev ## Stop development environment
dev-down: down

prod-build: ENV=prod ## Build production image
prod-build: build

prod-up: ENV=prod ## Start production environment
prod-up: up

prod-down: ENV=prod ## Stop production environment
prod-down: down
