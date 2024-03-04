#!make

init: docker-clear docker-build docker-up composer-install migrate messenger-init messenger-run
up: docker-up
down: docker-down
restart: docker-down docker-up

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-clear:
	docker-compose down -v --remove-orphans

docker-build:
	docker-compose build --pull

app-tests:
	docker-compose exec php bin/phpunit

clear:
	docker-compose exec php symfony console cache:clear

app:
	docker-compose exec php bash

composer-install:
	docker-compose exec php composer install

composer-update:
	docker-compose exec php composer update
	docker-compose exec php composer dump-autoload -o

messenger-init:
	docker-compose exec php symfony console messenger:setup-transports

messenger-run:
	docker-compose exec php symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async -vv

migration:
	docker-compose exec php symfony console make:migration

migrate:
	docker-compose exec php symfony console doctrine:migrations:migrate --all-or-nothing --query-time --no-interaction --env=dev

migrate-prod:
	docker-compose exec php symfony console doctrine:migrations:migrate --all-or-nothing --query-time --no-interaction

log:
	docker-compose exec php symfony server:log

stop:
	docker-compose exec php symfony server:stop

status:
	docker-compose exec php symfony server:status

