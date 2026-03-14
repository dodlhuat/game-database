# ============================================================
# Brettspiel-Ausleihplattform — Lokale Entwicklung
# ============================================================

.PHONY: up down setup artisan migrate logs ps

## Erster Start (einmalig)
setup:
	sh docker/setup.sh

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

## Shell im Backend-Container öffnen
shell-backend:
	docker compose exec backend sh

## Shell im Frontend-Container öffnen
shell-frontend:
	docker compose exec frontend sh

## PostgreSQL-Shell öffnen
db:
	docker compose exec postgres psql -U postgres -d game_database
