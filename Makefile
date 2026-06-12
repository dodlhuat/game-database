# ============================================================
# Brettspiel-Ausleihplattform — Lokale Entwicklung
# ============================================================

.PHONY: up down setup artisan migrate logs ps api-generate demo lint lint-fix test

## Erster Start (einmalig)
setup:
	sh docker/setup.sh
	git config core.hooksPath .githooks

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
