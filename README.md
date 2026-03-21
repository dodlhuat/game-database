# Brettspiel-Ausleihplattform

Eine Plattform zur Verwaltung und Ausleihe von Brettspielen. Mitglieder können Spiele durchsuchen, ausleihen und bewerten. Administratoren verwalten Spielkopien, Mitglieder und Ausleihvorgänge.

## Tech Stack

| Bereich | Technologie |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Auth | Laravel Sanctum (Token-basiert) |
| Datenbank | PostgreSQL 16 |
| Bilder | Cloudinary + Intervention Image |
| Frontend | Nuxt 4 (Vue 3.5 + TypeScript) |
| Styling | Basix SCSS Komponenten-Library |
| State | Pinia 3 |
| Admin | Custom Nuxt Frontend (/admin) |
| Infrastruktur | Docker Compose (Nginx, PHP-FPM, PostgreSQL, Node) |

## Projektstruktur (Monorepo)

```
gdata/
├── backend/          # Laravel 12 API
├── frontend/         # Nuxt 4 SPA
├── docker/           # Dockerfiles & Nginx-Konfiguration
└── docker-compose.yml
```

## Setup

### Mit Docker (empfohlen)

```bash
docker compose up
```

- Backend: http://localhost:8000
- Frontend: http://localhost:3000

### Ohne Docker

#### Voraussetzungen
- PHP 8.2+
- Composer
- Node.js 20+
- PostgreSQL

#### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

##### Im Container ausführen
```bash
docker-compose exec backend php artisan migrate
```

##### SSH auf Windows
```bash
ssh -i C:\Users\dodlh\.ssh\id_rsa ftp2113443@www34.world4you.com -p22 -o MACs=hmac-sha2-512
```

#### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Umgebungsvariablen

Siehe `backend/.env.example` und `frontend/.env.example` für alle benötigten Variablen.

## Features

- Spielekatalog mit Kategorien (inkl. Trinkspiele, Großgruppenspiele), Tags, Schwierigkeitsgrad
- Spielepakete (kategorie-basiert und kuratiert) mit öffentlicher Übersicht und Admin-Verwaltung
- Ausleihsystem mit Verlängerungs-Workflow
- Reservierungswarteschlange
- Bewertungen & Favoriten
- Schadensmeldungen
- Newsletter-Versand
- Admin-Panel (Mitglieder-Freischaltung, Spiele-, Paket- und Kopienverwaltung)
- DSGVO-konformes Registrierungsformular
