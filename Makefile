up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f --tail=200

shell:
	docker compose exec php bash

install:
	docker compose exec -T php composer install

check-style:
	PHP_CS_FIXER_IGNORE_ENV=1 docker compose exec -T php php-cs-fixer fix --dry-run --diff

stan:
	docker compose exec -T php vendor/bin/phpstan analyse --no-progress

test:
	docker compose exec -T php vendor/bin/phpunit --configuration phpunit.xml.dist

