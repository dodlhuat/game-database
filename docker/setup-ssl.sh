#!/usr/bin/env bash
# Einmalig auf dem vServer ausführen, um Let's Encrypt-Zertifikat zu holen.
# Voraussetzung: .env.prod ist befüllt, Port 80 ist erreichbar.
set -euo pipefail

if [[ ! -f .env.prod ]]; then
  echo "Fehler: .env.prod nicht gefunden"
  exit 1
fi

DOMAIN=$(grep -E '^DOMAIN=' .env.prod | cut -d= -f2 | tr -d ' "')
EMAIL=$(grep -E '^MAIL_FROM_ADDRESS=' .env.prod | cut -d= -f2 | tr -d ' "')

if [[ -z "$DOMAIN" || -z "$EMAIL" ]]; then
  echo "Fehler: DOMAIN oder MAIL_FROM_ADDRESS fehlt in .env.prod"
  exit 1
fi

echo "==> SSL-Zertifikat für $DOMAIN holen..."

# Nginx temporär nur auf HTTP starten (ohne SSL-Block)
# → Für den allerersten Start gibt es noch kein Zertifikat.
# Daher: Certbot standalone nutzen, nginx noch nicht laufen.

docker run --rm -it \
  -p 80:80 \
  -v "$(pwd)/letsencrypt:/etc/letsencrypt" \
  certbot/certbot certonly \
    --standalone \
    --preferred-challenges http \
    --agree-tos \
    --non-interactive \
    --email "$EMAIL" \
    -d "$DOMAIN"

# Let's Encrypt Options-Datei herunterladen (für nginx include)
curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf \
  > letsencrypt/options-ssl-nginx.conf 2>/dev/null || \
  docker run --rm certbot/certbot \
    install-certbot-check > /dev/null || true

echo "✓ Zertifikat erstellt. Jetzt mit 'make prod-up' starten."
