#!/bin/sh
# Erster Start: .env kopieren, Dependencies installieren, Migrations ausführen
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> Backend .env anlegen..."
if [ ! -f "$ROOT/backend/.env" ]; then
  cp "$ROOT/backend/.env.example" "$ROOT/backend/.env"
  echo "    .env erstellt"
else
  echo "    .env existiert bereits, wird nicht überschrieben"
fi

echo "==> Composer Dependencies installieren..."
docker compose -f "$ROOT/docker-compose.yml" run --rm backend \
  composer install --no-interaction --prefer-dist

echo "==> APP_KEY generieren..."
docker compose -f "$ROOT/docker-compose.yml" run --rm backend \
  php artisan key:generate

echo "==> Datenbank-Migrationen ausführen..."
docker compose -f "$ROOT/docker-compose.yml" run --rm backend \
  php artisan migrate --force

echo ""
echo "✓ Setup abgeschlossen."
echo "  Backend:  http://localhost:8000"
echo "  Frontend: http://localhost:3000"
