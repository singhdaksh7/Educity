# Educity Backend — Architecture

Laravel 12 API backend (`/backend`) serving the existing React/Vite frontend via `/api/v1`.

## Stack
PHP 8.2, Laravel 12, Sanctum (bearer tokens), MySQL/MariaDB in production (SQLite for local dev), database-driven queue, `public` filesystem disk (S3-ready).

## Layers
- **Controllers** (`app/Http/Controllers/Api`) — thin HTTP handlers, one per resource (Auth, Enquiry, AdmissionApplication, Program, Testimonial, Gallery, SiteSetting, Dashboard, ActivityLog).
- **Form Requests** (`app/Http/Requests`) — all validation, including normalization (`prepareForValidation`) and cross-field checks.
- **Services** (`app/Services`) — `MediaService` (upload/replace/delete images safely), `CsvExportService` (streamed, chunked, formula-safe CSV), `ActivityLogger` (sanitized audit trail).
- **Models** (`app/Models`) — Eloquent models with explicit `$fillable`, casts, relationships, `SoftDeletes` on user-facing content tables.
- **Authorization** — Laravel Gates defined in `AppServiceProvider` (one ability per resource + role), enforced via the `can:` route middleware and `Gate::before` for `super_admin`.
- **Mail** (`app/Mail`, `resources/views/emails`) — 4 queued Mailables using Markdown templates.

## Authorization model
Permissions are Gate abilities (`enquiries.manage`, `applications.manage`, `programs.manage`, `testimonials.manage`, `gallery.manage`, `settings.manage`, `settings.manage-public`, `dashboard.view`, `records.restore`, `records.forceDelete`, `logs.view`) mapped per role in `AppServiceProvider::ROLE_PERMISSIONS`. `super_admin` bypasses all checks via `Gate::before`. Restoring a record uses the same ability as managing that resource; permanent deletion and activity-log access are `super_admin`-only.

## Data model
`users, programs, enquiries, admission_applications, testimonials, gallery, site_settings, activity_logs, personal_access_tokens, password_reset_tokens, jobs, job_batches, failed_jobs`. Soft deletes on `programs, enquiries, admission_applications, testimonials, gallery`. `admission_applications.program_id` uses `restrictOnDelete()` so archiving a program never orphans existing applications (programs are soft-deleted, never hard-deleted while applications reference them).

## API responses
Every endpoint returns `{ success, message, data }` (or `errors` on failure). A global exception renderer (`bootstrap/app.php`) guarantees JSON — never HTML — for `401/403/404/422/429/500` on any `api/*` route.

## Deferred to a later session
Automated test suite (feature/unit tests). This session performed structural verification only (`route:list`, `config:show`, `about`, PHP lint, targeted manual smoke tests via `tinker`/`curl`).
