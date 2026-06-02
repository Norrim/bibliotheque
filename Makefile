# Makefile — Bibliothèque API
# Raccourcis pour piloter l'application (Docker Compose + Symfony).
# Lance `make` ou `make help` pour la liste des cibles.

DC       = docker compose
PHP      = $(DC) exec php
CONSOLE  = $(PHP) bin/console
COMPOSER = $(PHP) composer

GREEN := \033[0;32m
YELLOW:= \033[0;33m
NC    := \033[0m

.DEFAULT_GOAL := help

.PHONY: help
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-16s$(NC) %s\n", $$1, $$2}'

## —— Docker ————————————————————————————————————————————————————————

.PHONY: build
build: ## Construit les images Docker
	$(DC) build --pull

.PHONY: up
up: ## Démarre l'application en arrière-plan (attend que tout soit healthy)
	$(DC) up -d --wait

.PHONY: start
start: build up ## Build puis démarrage

.PHONY: stop
stop: ## Stoppe les conteneurs
	$(DC) stop

.PHONY: down
down: ## Stoppe et supprime les conteneurs
	$(DC) down --remove-orphans

.PHONY: restart
restart: stop up ## Redémarre les conteneurs

.PHONY: logs
logs: ## Suit les logs (Ctrl+C pour quitter)
	$(DC) logs -f --tail=100

.PHONY: sh
sh: ## Ouvre un shell dans le conteneur php
	$(PHP) sh

.PHONY: ps
ps: ## Liste l'état des conteneurs
	$(DC) ps

## —— Application ———————————————————————————————————————————————————

.PHONY: install
install: ## Installe les dépendances Composer
	$(COMPOSER) install

.PHONY: setup
setup: up jwt migrate init ## Installe tout : démarrage + JWT + migrations + démo

.PHONY: jwt
jwt: ## Génère la paire de clés JWT (idempotent)
	$(CONSOLE) lexik:jwt:generate-keypair --skip-if-exists

.PHONY: migrate
migrate: ## Joue les migrations Doctrine
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

.PHONY: init
init: ## Initialise le jeu de données de démo (comptes + catalogue)
	$(CONSOLE) app:demo:init

.PHONY: sync
sync: ## Synchronise le catalogue depuis OpenLibrary
	$(CONSOLE) app:books:sync

.PHONY: cache
cache: ## Vide le cache applicatif
	$(CONSOLE) cache:clear

.PHONY: console
console: ## Commande console libre, ex : make console c="debug:router"
	$(CONSOLE) $(c)

## —— Qualité & tests ———————————————————————————————————————————————

.PHONY: test
test: ## Lance la suite de tests (PHPUnit)
	$(PHP) bin/phpunit

.PHONY: test-setup
test-setup: ## Prépare la base de test (création + migrations)
	$(CONSOLE) --env=test doctrine:database:create --if-not-exists
	$(CONSOLE) --env=test doctrine:migrations:migrate --no-interaction

.PHONY: cs
cs: ## Vérifie le style de code (PHP-CS-Fixer, dry-run)
	$(COMPOSER) cs

.PHONY: cs-fix
cs-fix: ## Corrige automatiquement le style de code
	$(COMPOSER) cs:fix

.PHONY: stan
stan: ## Analyse statique (PHPStan)
	$(COMPOSER) stan

.PHONY: qa
qa: cs stan test ## Rejoue les gates de la CI (cs + stan + test)
