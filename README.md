# PulseDesk

A multi-tenant support-desk SaaS, built by orchestrating AI agents over Slack.

## Stack

- **Backend:** Laravel 11, PHP 8.2+, MySQL 8, Laravel Sanctum
- **Frontend:** React 19, Vite, Tailwind CSS
- **CI/CD:** GitHub Actions

## Project Structure

```
├── backend/          # Laravel 11 REST API
├── frontend/         # React 19 + Vite SPA
├── .github/workflows/ # GitHub Actions CI
├── agents/           # Agent configs
├── sprints/          # Sprint documentation
└── evidence/         # Screenshots and evidence
```

## Setup

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8
- Node.js 18+
- npm

### Backend (Laravel + MySQL)

```bash
cd backend
cp .env.example .env          # set DB_* for your MySQL
composer install
php artisan key:generate
php artisan migrate
php artisan serve             # http://127.0.0.1:8000
```

Verify the API is running:

```bash
curl http://127.0.0.1:8000/api/health
# {"status":"ok","service":"PulseDesk API","version":"1.0.0"}
```

### Frontend (React + Vite)

```bash
cd frontend
cp .env.example .env          # VITE_API_URL=http://127.0.0.1:8000
npm install
npm run dev                   # http://127.0.0.1:5173
```

## CI/CD

GitHub Actions runs on every PR and push to `main`:

- **Backend job:** installs PHP dependencies, configures MySQL service, runs migrations, executes tests
- **Frontend job:** installs Node dependencies, builds the production bundle

## EastRouter Models

- Hermes (planning / product owner): `z-ai/glm-5.1`
- OpenClaw (coding): `z-ai/glm-5.1`

## Live URL

Runs locally per the steps above.

## Where Evidence Lives

- `agents/` — agent configs (secrets redacted)
- `agent-log.md` — the human → Hermes → OpenClaw loop
- `sprints/` — one doc per sprint
- `slack-export/` — Slack export
- `evidence/screenshots/` — app, agents-running, CI screenshots
