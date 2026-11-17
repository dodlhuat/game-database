#!/usr/bin/env bash
# Backup of the live database and uploads. Runs ON the server, started from the
# laptop with: ssh hetzner-deploy 'bash -s' < scripts/server-backup.sh
# Reads the DB credentials from backend/.env and writes to ~/backups/<timestamp>/.
set -euo pipefail

APP=/var/www/alle-unsere-abenteuer/backend
DEST="$HOME/backups/$(date +%Y%m%d-%H%M%S)"

env_val() {
  grep -E "^$1=" "$APP/.env" | tail -1 | cut -d= -f2- | sed -E "s/^\"(.*)\"\$/\1/; s/^'(.*)'\$/\1/"
}

CONN=$(env_val DB_CONNECTION)
HOST=$(env_val DB_HOST)
PORT=$(env_val DB_PORT)
DB=$(env_val DB_DATABASE)
USER_=$(env_val DB_USERNAME)
PASS=$(env_val DB_PASSWORD)

umask 077
mkdir -p "$DEST"

case "$CONN" in
  mysql|mariadb)
    DUMP=$(command -v mysqldump || command -v mariadb-dump)
    MYSQL_PWD="$PASS" "$DUMP" --single-transaction --routines -h "${HOST:-127.0.0.1}" -P "${PORT:-3306}" -u "$USER_" "$DB" | gzip > "$DEST/database.sql.gz"
    ;;
  pgsql)
    PGPASSWORD="$PASS" pg_dump -h "${HOST:-127.0.0.1}" -p "${PORT:-5432}" -U "$USER_" "$DB" | gzip > "$DEST/database.sql.gz"
    ;;
  *)
    echo "Unbekannte DB_CONNECTION: '$CONN'" >&2
    exit 1
    ;;
esac

gzip -t "$DEST/database.sql.gz"
tar -czf "$DEST/uploads.tar.gz" -C "$APP/storage/app/public" .

ls -lh "$DEST"
echo "✓ Backup unter $DEST"
