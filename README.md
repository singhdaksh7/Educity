# Educity

Educity's public website (React + Vite) with a Laravel 12 API backend.

- `backend/` — Laravel 12 API (`/api/v1`), Sanctum auth, MySQL/MariaDB. See [`BACKEND_PLAN.md`](BACKEND_PLAN.md), [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) and [`DEPLOYMENT.md`](DEPLOYMENT.md).
- `src/` — React/Vite frontend (public site + admin foundation).

## Quick start

```bash
# Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed   # set ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD in .env first
php artisan storage:link
php artisan serve

# Frontend (in a separate terminal, repo root)
npm install
npm run dev
```

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for full environment configuration and Hostinger deployment steps.

## Frontend tooling

Built with Vite + React. Notable plugins:

- [@vitejs/plugin-react](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react) — Babel-based Fast Refresh
- [@vitejs/plugin-react-swc](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react-swc) — SWC-based Fast Refresh
