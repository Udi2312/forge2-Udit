# PulseDesk Frontend

This is the React 19 + Vite frontend for the PulseDesk application.

## Setup
```bash
cd frontend
npm install
cp .env.example .env
# update VITE_API_URL to backend API, e.g. http://127.0.0.1:8000
npm run dev
```

## Build
```bash
npm run build
```

## Notes
- The frontend consumes the Laravel backend API via `VITE_API_URL`
- Authentication uses Sanctum tokens and Axios interceptors
- Main pages include login, register, dashboard, ticket detail, and insights
