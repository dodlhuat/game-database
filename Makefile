# ============================================================
# Brettspiel-Ausleihplattform — Lokale Entwicklung
# ============================================================

.PHONY: up down setup artisan migrate logs ps api-generate demo lint lint-fix test \
        prod-up prod-down prod-logs prod-deploy prod-shell prod-artisan

## Erster Start (einmalig)
setup:
	sh docker/setup.sh
	git config core.hooksPath .githooks

## start frontend with cleared cache
fe-up:
	cd frontend && rm -rf .nuxt && npm run dev

## Container starten
up:
	docker compose up -d

## Container stoppen
down:
	docker compose down

## Container-Status
ps:
	docker compose ps

## Logs verfolgen (alle Services)
logs:
	docker compose logs -f

## Logs eines einzelnen Services: make logs-backend
logs-%:
	docker compose logs -f $*

## Artisan-Befehl ausführen: make artisan CMD="migrate:fresh --seed"
artisan:
	docker compose exec backend php artisan $(CMD)

## Migrationen ausführen
migrate:
	docker compose exec backend php artisan migrate

## Migrationen zurücksetzen und neu ausführen
migrate-fresh:
	docker compose exec backend php artisan migrate:fresh --seed

## Datenbank zurücksetzen und Demo-Daten laden
demo:
	docker compose exec backend php artisan db:demo

## Shell im Backend-Container öffnen
shell-backend:
	docker compose exec backend sh

## Shell im Frontend-Container öffnen
shell-frontend:
	docker compose exec frontend sh

## PostgreSQL-Shell öffnen
db:
	docker compose exec postgres psql -U postgres -d game_database

## OpenAPI-Spec exportieren und TypeScript-Typen generieren
api-generate:
	docker compose exec backend php artisan scramble:export
	cd frontend && npm run api:generate

## Backend-Tests ausführen
test:
	docker compose exec backend php artisan config:clear --ansi
	docker compose exec backend php artisan test

## Linter für Frontend (ESLint) und Backend (Pint) ausführen
lint:
	cd frontend && npm run lint
	docker compose exec backend ./vendor/bin/pint --test

## Linter ausführen und Fehler automatisch beheben
lint-fix:
	cd frontend && npm run lint:fix
	cd frontend && npm run format
	docker compose exec backend ./vendor/bin/pint 2>/dev/null || echo "⚠ Backend-Container nicht aktiv, Pint übersprungen"

# ============================================================
# Produktion
# ============================================================

## Prod-Container starten
prod-up:
	docker compose -f docker-compose.prod.yml --env-file .env.prod up -d

## Prod-Container stoppen
prod-down:
	docker compose -f docker-compose.prod.yml down

## Prod-Logs verfolgen
prod-logs:
	docker compose -f docker-compose.prod.yml logs -f

## Auf Server deployen: make prod-deploy SERVER=user@yourserver.com
prod-deploy:
	./deploy.sh $(SERVER)

## Shell im Prod-Backend öffnen
prod-shell:
	docker compose -f docker-compose.prod.yml exec backend sh

## Artisan-Befehl auf Prod: make prod-artisan CMD="migrate:status"
prod-artisan:
	docker compose -f docker-compose.prod.yml exec backend php artisan $(CMD)
