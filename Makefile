# ============================================================
# Brettspiel-Ausleihplattform — Lokale Entwicklung
# ============================================================

.PHONY: up down setup artisan migrate logs ps api-generate demo lint lint-fix test reset-data seed-taxonomy \
        prod-up prod-down prod-logs prod-deploy prod-shell prod-artisan \
        deploy deploy-pull deploy-backend deploy-frontend deploy-fix-pull deploy-verify deploy-backup deploy-reset-data deploy-seed-taxonomy deploy-env-set deploy-env-unset \
        server-update server-update-install server-update-install-all \
        maintenance-on maintenance-off

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

## Lokal: alle Spiel- und Testdaten löschen, User behalten, Mechaniken/Tags aus backend/database/data/*.txt einspielen
reset-data:
	docker compose exec backend php artisan app:reset-test-data

## Mechaniken und Tags aus backend/database/data/*.txt einspielen (lokal, beliebig oft ausführbar)
seed-taxonomy:
	docker compose exec backend php artisan db:seed --class=TaxonomySeeder

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
# Basis-URL für deploy-verify
DEPLOY_URL    := https://alle-unsere-abenteuer.at

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
	curl -sf $(DEPLOY_URL)/api/games > /dev/null && echo "✓ Backend erreichbar" || echo "✗ Backend NICHT erreichbar"
	curl -sf $(DEPLOY_URL)/ > /dev/null && echo "✓ Frontend erreichbar" || echo "✗ Frontend NICHT erreichbar"

## Backup von Datenbank und Uploads auf dem Server nach ~/backups/<Zeitstempel>/
deploy-backup:
	ssh $(DEPLOY_SERVER) 'bash -s' < scripts/server-backup.sh

## Mechaniken und Tags am Server einspielen (deployt vorher das Backend, damit die Listen aktuell sind)
deploy-seed-taxonomy: deploy-backend
	ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/backend && php artisan db:seed --class=TaxonomySeeder --force'

## EINMALIG vor dem Livegang: alle Spiel- und Testdaten auf dem Server löschen, User behalten,
## Mechaniken/Tags aus backend/database/data/*.txt einspielen. Ablauf: Deploy → Backup →
## Wartungsmodus → Reset (fragt nach „RESET“) → Wartungsmodus aus. Bricht der Reset ab,
## bleibt der Wartungsmodus an (mit `make maintenance-off` beenden).
deploy-reset-data: deploy-backend deploy-backup maintenance-on
	ssh -t $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/backend && php artisan app:reset-test-data'
	$(MAKE) maintenance-off

# Setzt MAINTENANCE_MODE=<wert> in backend/.env (legt die Zeile an, falls sie fehlt) und baut den Config-Cache neu
MAINTENANCE_BACKEND = cd $(DEPLOY_DIR)/backend && if grep -q "^MAINTENANCE_MODE=" .env; then sed -i "s/^MAINTENANCE_MODE=.*/MAINTENANCE_MODE=$(1)/" .env; else echo "MAINTENANCE_MODE=$(1)" >> .env; fi && php artisan config:cache

## Wartungsmodus einschalten:
## - Backend: MAINTENANCE_MODE=true in .env + config:cache → nur Admins können sich einloggen,
##   Registrierung gesperrt, bestehende Logins von Nicht-Admins werden beendet
## - Frontend: NUXT_PUBLIC_MAINTENANCE_MODE=true im PM2-Prozess (kein Rebuild nötig)
## `pm2 save` sorgt dafür, dass der Zustand einen Server-Neustart überlebt
maintenance-on:
	ssh $(DEPLOY_SERVER) '$(call MAINTENANCE_BACKEND,true)'
	ssh $(DEPLOY_SERVER) 'NUXT_PUBLIC_MAINTENANCE_MODE=true pm2 restart alle-unsere-abenteuer-frontend --update-env && pm2 save'
	@echo "✓ Wartungsmodus AN (Backend + Frontend) — $(DEPLOY_URL)"

## Wartungsmodus ausschalten (Frontend und Backend)
maintenance-off:
	ssh $(DEPLOY_SERVER) 'NUXT_PUBLIC_MAINTENANCE_MODE=false pm2 restart alle-unsere-abenteuer-frontend --update-env && pm2 save'
	ssh $(DEPLOY_SERVER) '$(call MAINTENANCE_BACKEND,false)'
	@echo "✓ Wartungsmodus AUS (Backend + Frontend) — $(DEPLOY_URL)"

## Eine Variable in backend/.env auf dem Server setzen, z. B.:
##   make deploy-env-set KEY=MOLLIE_KEY
##   make deploy-env-set KEY=MOLLIE_WEBHOOK_URL
## Der Wert wird verdeckt abgefragt und per SSH über stdin übertragen — er landet
## weder in der Shell-History noch in der Prozessliste. Bestehende Zeile wird
## ersetzt, fehlende angehängt; danach wird der Config-Cache neu gebaut.
deploy-env-set:
	@printf '%s' "$(KEY)" | grep -Eq '^[A-Z][A-Z0-9_]*$$' || { echo "Aufruf: make deploy-env-set KEY=NAME (Großbuchstaben, Ziffern, _)"; exit 1; }
	@printf 'Wert für %s: ' "$(KEY)"; stty -echo; read -r value; stty echo; echo; \
	[ -n "$$value" ] || { echo "Leerer Wert — abgebrochen."; exit 1; }; \
	printf '%s' "$$value" | ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/backend && v=$$(cat) && { grep -v "^$(KEY)=" .env; printf "%s=%s\n" "$(KEY)" "$$v"; } > .env.new && cat .env.new > .env && rm .env.new && php artisan config:cache >/dev/null && echo "✓ $(KEY) gesetzt, Config-Cache neu gebaut"'

## Variablen aus backend/.env auf dem Server entfernen, z. B.:
##   make deploy-env-unset KEYS="STRIPE_SECRET_KEY STRIPE_WEBHOOK_SECRET"
## Nennt nur die Namen, die tatsächlich entfernt wurden; Werte werden nie ausgegeben.
deploy-env-unset:
	@[ -n "$(KEYS)" ] || { echo 'Aufruf: make deploy-env-unset KEYS="NAME1 NAME2"'; exit 1; }
	@for k in $(KEYS); do printf '%s' "$$k" | grep -Eq '^[A-Z][A-Z0-9_]*$$' || { echo "Ungültiger Name: $$k"; exit 1; }; done
	@ssh $(DEPLOY_SERVER) 'cd $(DEPLOY_DIR)/backend && pattern="^($(shell echo $(KEYS) | tr ' ' '|'))=" && found=$$(grep -oE "$$pattern" .env | tr -d = | tr "\n" " ") && if [ -z "$$found" ]; then echo "Nichts zu entfernen."; else grep -vE "$$pattern" .env > .env.new && cat .env.new > .env && rm .env.new && php artisan config:cache >/dev/null && echo "✓ Entfernt: $$found— Config-Cache neu gebaut"; fi'

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