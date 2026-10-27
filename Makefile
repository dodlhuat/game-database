# ============================================================
# Brettspiel-Ausleihplattform — Lokale Entwicklung
# ============================================================

.PHONY: up down setup artisan migrate logs ps api-generate demo lint lint-fix test \
        prod-up prod-down prod-logs prod-deploy prod-shell prod-artisan \
        deploy deploy-pull deploy-backend deploy-frontend deploy-fix-pull deploy-verify \
        server-update server-update-install server-update-install-all

## Erster Start (einmalig)
setup:
	sh docker/setup.sh
	git config core.hooksPath .githooks

## start frontend with cleared cache
up-dev:
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

## phpstan
phpstan:
	cd backend && php -d memory_limit=1G vendor/bin/phpstan analyse > phpstan-report.txt

# ============================================================
# Deploy — echter Server (kein Docker, siehe DEPLOYMENT.md)
# git pull + composer/artisan + npm build/pm2, alles per SSH.
# Setzt funktionierenden Key-basierten SSH-Zugriff auf den Server voraus.
# ============================================================
DEPLOY_SERVER := hetzner-deploy
DEPLOY_DIR    := /var/www/alle-unsere-abenteuer

## Backend + Frontend auf dem Server aktualisieren (git pull, composer, migrate, npm build, pm2 restart)
deploy: deploy-backend deploy-frontend

## Nur git pull auf dem Server (Backend/Frontend-Targets hängen automatisch davon ab)
deploy-pull:
	ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR) && git pull && git log -1 --oneline'

## Backend-Schritte auf dem Server: composer install, migrate, config/route/view-cache
deploy-backend: deploy-pull
	ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/backend && composer install --no-dev --optimize-autoloader --no-interaction && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache'

## Frontend-Schritte auf dem Server: npm ci, build, PM2-Neustart
deploy-frontend: deploy-pull
	ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/frontend && npm ci && npm run build && pm2 restart alle-unsere-abenteuer-frontend && pm2 logs alle-unsere-abenteuer-frontend --lines 20 --nostream'

## Fix für git pull-Konflikt durch lokale package-lock.json-Änderungen auf dem Server (siehe DEPLOYMENT.md 5.2)
deploy-fix-pull:
	ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR) && git checkout -- . && git pull && git log -1 --oneline'

## Nach dem Deploy: Backend-API und Frontend von außen erreichbar?
deploy-verify:
	curl -sf http://65.109.232.115/api/games > /dev/null && echo "✓ Backend erreichbar" || echo "✗ Backend NICHT erreichbar"
	curl -sf http://65.109.232.115/ > /dev/null && echo "✓ Frontend erreichbar" || echo "✗ Frontend NICHT erreichbar"

# ============================================================
# Server-Updates (Ubuntu-Pakete, z.B. PHP, nginx, OpenSSL)
# Der deploy-User hat kein passwordless sudo -> ssh -t, sudo fragt einmal nach dem Passwort.
# ============================================================

## Prüfen, ob Sicherheitsupdates anstehen (installiert nichts; aktualisiert nur die Paketlisten)
server-update:
	ssh -t $(DEPLOY_SERVER) 'sudo apt-get update -qq && echo "--- Ausstehende Sicherheitsupdates ---" && (apt list --upgradable 2>/dev/null | grep -i security || echo "keine") && echo "--- Sonstige Updates (nicht automatisch) ---" && (apt list --upgradable 2>/dev/null | grep -v -i -E "security|^Listing" || echo "keine") && echo "--- Neustart nötig? ---" && (cat /var/run/reboot-required 2>/dev/null || echo "nein")'

## Sicherheitsupdates installieren (nur Security-Quellen, via unattended-upgrade) und Dienste prüfen
server-update-install:
	ssh -t $(DEPLOY_SERVER) 'sudo apt-get update -qq && sudo unattended-upgrade -v && echo "--- Neustart nötig? ---" && (cat /var/run/reboot-required 2>/dev/null || echo "nein") && echo "--- Dienste ---" && systemctl is-active nginx php8.5-fpm && pm2 list'

## ALLE ausstehenden Updates installieren (auch nicht-sicherheitsrelevante, z.B. systemd) — danach ggf. Neustart nötig
server-update-install-all:
	ssh -t $(DEPLOY_SERVER) 'sudo apt-get update -qq && sudo apt-get upgrade -y && echo "--- Neustart nötig? ---" && (cat /var/run/reboot-required 2>/dev/null || echo "nein") && echo "--- Dienste ---" && systemctl is-active nginx php8.5-fpm && pm2 list'

server-reboot:
	-ssh -t $(DEPLOY_SERVER) 'sudo reboot'
	@echo "Warte auf Neustart ..."
	@sleep 20
	@until ssh -o BatchMode=yes -o ConnectTimeout=5 $(DEPLOY_SERVER) true 2>/dev/null; do sleep 5; done
	@echo "--- Server wieder erreichbar, Uptime: ---"
	@ssh $(DEPLOY_SERVER) 'uptime -p; echo "--- Fehlgeschlagene Dienste ---"; systemctl --failed --no-legend || true; echo "--- PM2 ---"; pm2 list'
	@sleep 5
	@$(MAKE) --no-print-directory deploy-verify