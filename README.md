# Brettspiel-Ausleihplattform

Eine Plattform zur Verwaltung und Ausleihe von Brettspielen. Mitglieder können Spiele durchsuchen, ausleihen und bewerten. Administratoren verwalten Spielkopien, Mitglieder und Ausleihvorgänge.

## Tech Stack

| Bereich | Technologie |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Auth | Laravel Sanctum (Token-basiert) |
| Datenbank | PostgreSQL |
| Mail | Laravel Mail + Resend (SMTP) |
| Bilder | Cloudinary |
| Cron | Laravel Task Scheduling |
| Frontend | Nuxt.js 3 (Vue 3 + TypeScript) |
| Styling | Eigene SCSS Komponenten-Library |
| State | Pinia |
| Admin | Custom Nuxt.js Frontend (/admin) |

## Projektstruktur (Monorepo)

```
game-database/
├── backend/     # Laravel 11 API
└── frontend/    # Nuxt.js 3 SPA
```

## Setup

### Voraussetzungen
- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Umgebungsvariablen

Siehe `backend/.env.example` und `frontend/.env.example` für alle benötigten Variablen.

## Features

- Spielekatalog mit Kategorien, Tags, Schwierigkeitsgrad
- Ausleihsystem mit Verlängerungs-Workflow
- Reservierungswarteschlange
- Bewertungen & Favoriten
- Schadensmeldungen
- Newsletter-Versand
- Admin-Panel (Mitglieder-Freischaltung, Spielverwaltung)
- DSGVO-konformes Registrierungsformular
