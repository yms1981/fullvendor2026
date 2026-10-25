# FullVendor — comandos de prueba
# Uso: make test | make test-unit | make test-dusk

.PHONY: test test-api test-api-unit test-api-feature test-mvc test-dusk selenium-up help

COMPOSE ?= docker compose -f api/docker-compose.yml
API_EXEC ?= docker exec fullvendor-1_1x-laravel.test-1
MVC_EXEC ?= docker exec fullvendor-1_1x-fullvendor-1

help:
	@echo "Targets:"
	@echo "  make test              - unitarios API + MVC"
	@echo "  make test-api          - PHPUnit Unit+Feature (API)"
	@echo "  make test-api-unit     - solo Unit (API)"
	@echo "  make test-api-feature  - solo Feature (API)"
	@echo "  make test-mvc          - PHPUnit MVC"
	@echo "  make test-dusk         - Selenium/Dusk (browser)"
	@echo "  make selenium-up       - asegura contenedor selenium"

selenium-up:
	@$(COMPOSE) up -d selenium
	@echo "Esperando Selenium..."
	@for i in 1 2 3 4 5 6 7 8 9 10; do \
		docker exec fullvendor-1_1x-laravel.test-1 curl -sf http://selenium:4444/wd/hub/status >/dev/null 2>&1 && exit 0; \
		sleep 2; \
	done; \
	echo "Selenium no respondió a tiempo"; exit 1

test-dusk: selenium-up
	$(API_EXEC) bash -lc 'cd /var/www/html && php artisan dusk --configuration=phpunit.dusk.xml'

test: test-api-unit test-mvc
	@echo "OK: unitarios API + MVC"
