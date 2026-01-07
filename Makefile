#!make

init: docker-clear docker-build docker-up composer-install migrate messenger-init
up: docker-up
down: docker-down
restart: docker-down docker-up

docker-up:
	docker compose up -d

docker-down:
	docker compose down

docker-clear:
	docker compose down -v --remove-orphans

docker-build:
	docker compose build --pull

app-tests:
	docker compose exec app bin/phpunit

clear:
	docker compose exec app symfony console cache:clear

app:
	docker compose exec app sh

composer-install:
	docker compose exec app composer install

composer-update:
	docker compose exec app composer update -W
	docker compose exec app composer dump-autoload -o

recipes-update:
	docker compose exec app composer recipes:update

messenger-init:
	docker compose exec app symfony console messenger:setup-transports
	make messenger-run

messenger-run:
	docker compose exec app symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async -vv

migration:
	docker compose exec app symfony console make:migration

migrate:
	docker compose exec app symfony console doctrine:migrations:migrate --all-or-nothing --query-time --no-interaction --env=dev

migrate-prod:
	docker compose exec app symfony console doctrine:migrations:migrate --all-or-nothing --query-time --no-interaction

log:
	docker compose exec app symfony server:log

stop:
	docker compose exec app symfony server:stop

status:
	docker compose exec app symfony server:status

