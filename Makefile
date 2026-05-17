DC=docker compose
BACKEND=$(DC) exec backend
FRONTEND=$(DC) exec frontend
BACK_PORT?=8000
FRONT_PORT?=5174

.PHONY: up dev dev-local down build logs sh-back sh-front composer-install npm-install db migrate fixtures communes-import test lint cs-fix

up:
	$(DC) up -d

dev:
	$(DC) up db backend frontend nginx

dev-local:
	cd backend && php -S 127.0.0.1:$(BACK_PORT) -t public public/index.php & \
	BACK_PID=$$!; \
	cd frontend && VITE_API_BASE_URL=http://127.0.0.1:$(BACK_PORT)/api npm run dev -- --host 127.0.0.1 --port $(FRONT_PORT) & \
	FRONT_PID=$$!; \
	trap 'kill $$BACK_PID $$FRONT_PID 2>/dev/null' INT TERM EXIT; \
	wait $$BACK_PID $$FRONT_PID

down:
	$(DC) down

build:
	$(DC) build

logs:
	$(DC) logs -f

sh-back:
	$(BACKEND) sh

sh-front:
	$(FRONTEND) sh

composer-install:
	$(BACKEND) composer install

npm-install:
	$(FRONTEND) npm install

db:
	$(BACKEND) php bin/console doctrine:database:create --if-not-exists

migrate:
	$(BACKEND) php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	$(BACKEND) php bin/console doctrine:fixtures:load --no-interaction

communes-import:
	$(BACKEND) php bin/console app:communes:import

test:
	$(BACKEND) vendor/bin/phpunit
	$(FRONTEND) npm test -- --run

lint:
	$(BACKEND) php -d memory_limit=512M vendor/bin/phpstan analyse --debug
	$(FRONTEND) npm run lint

cs-fix:
	$(BACKEND) vendor/bin/php-cs-fixer fix --sequential
	$(FRONTEND) npm run format
