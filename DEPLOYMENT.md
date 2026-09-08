# Deployment Guide

## Local development

```bash
cd backend
cp .env.example .env      # if .env doesn't already exist
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed        # requires ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD in .env
php artisan storage:link
php artisan serve
```

Run the queue worker (required for enquiry/application emails, since the Mailables are queued):

```bash
php artisan queue:work
```

Frontend (repo root):

```bash
npm install
npm run dev
```

Set `VITE_API_URL` (root `.env`) to the backend's `/api/v1` URL.

## Environment variables

See `backend/.env.example` for the full list. Required for a working deployment:

- `APP_KEY` — generate with `php artisan key:generate`
- `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS` — must match your real domains
- `DB_*` — MySQL/MariaDB connection
- `FILESYSTEM_DISK=public` (or an `s3`-compatible disk once configured)
- `QUEUE_CONNECTION=database` and a running queue worker (or Supervisor/cron-driven `queue:work --stop-when-empty`)
- `MAIL_*`, `ADMIN_NOTIFICATION_EMAIL` — set to a real SMTP transport in production (`MAIL_MAILER=log` only for local dev)
- `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` — used once by the seeder to create the first `super_admin`; do not leave a weak or default password in any deployed environment

`APP_DEBUG` must be `false` in any production environment — it defaults to `false` unless explicitly overridden, and `.env.example` sets it to `true` for local convenience only.

## Hostinger (shared/VPS PHP hosting) deployment steps

1. Create the MySQL database and a database user in hPanel; note host/port/database/username/password.
2. Upload the repository (or deploy via Git) so `backend/` sits either at the domain root or one level above `public_html`, with `public_html` pointing at `backend/public` (recommended for security — keeps `.env` and `app/` outside the web root).
3. Set the PHP version to 8.2+ in hPanel.
4. Copy `backend/.env.example` to `backend/.env` and fill in real values (`APP_KEY`, `DB_*`, `MAIL_*`, `ADMIN_*`, `CORS_ALLOWED_ORIGINS`, `FRONTEND_URL`). Never commit this file.
5. Via SSH (or Hostinger's terminal):
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --force   # first deploy only
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   ```
6. Point the domain's document root at `backend/public`.
7. Configure a cron job (hPanel → Cron Jobs) to run Laravel's scheduler every minute:
   ```
   * * * * * php /home/USER/domains/YOURDOMAIN/backend/artisan schedule:run >> /dev/null 2>&1
   ```
8. Configure a persistent queue worker. On shared hosting without long-running processes, add a cron entry instead of `queue:work`:
   ```
   * * * * * php /home/USER/domains/YOURDOMAIN/backend/artisan queue:work --stop-when-empty >> /dev/null 2>&1
   ```
   On a VPS, run `queue:work` under Supervisor for a persistent worker.
9. Build and deploy the React frontend (`npm run build`) to its own domain/subdomain or static hosting, with `VITE_API_URL` pointing at the backend's public `/api/v1` URL.
10. Re-run `php artisan config:cache` and `route:cache` after any `.env` change (`config:clear` first).

## Notes
- `DB_CONNECTION=sqlite` is fine for local development only; production must use MySQL/MariaDB per the schema's MariaDB-compatible migrations.
- CORS origins are entirely environment-driven (`CORS_ALLOWED_ORIGINS`) — update this whenever the frontend domain changes.
