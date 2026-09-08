# Educity Backend — Implementation Report

This session audited the existing partial Laravel backend against the full specification and implemented every remaining requirement. All claims below were verified against the actual source in `backend/`, not against prior session reports.

## Summary

- **65 API routes** under `/api/v1` (verified via `php artisan route:list --path=api`).
- All PHP files pass `php -l` (no syntax errors).
- `php artisan optimize:clear`, `route:list`, `config:show database`, and `php artisan about` all run cleanly.
- Manual smoke tests (via `php artisan tinker` + `curl` against `php artisan serve`) exercised: public program/testimonial/gallery/site-content endpoints, enquiry submission, admission application submission with generated application number, admin login, unauthenticated 401, cross-role 403 (enquiry_manager blocked from `/admin/programs`), dashboard metrics, enquiry CSV export (formula-safe, correct headers), gallery image upload (MIME/extension validated), and 422 validation error shape. Two real bugs were found and fixed during this testing (see "Bugs found and fixed").

## Completed modules

1. **Database** — `programs, enquiries, admission_applications, testimonials, gallery, site_settings, activity_logs, users, personal_access_tokens, password_reset_tokens, jobs, job_batches, failed_jobs` all present with soft deletes, foreign keys (`nullOnDelete`/`restrictOnDelete` as appropriate), indexes, unique constraints, and correct casts. Added an additive migration (`2026_09_08_000001_add_role_and_is_active_to_users_table`) to bring already-migrated local databases in line with the users schema.
2. **Admin authentication** — full login/logout/me/password/forgot/reset flow with Sanctum tokens, per-IP+email rate limiting on login and forgot-password, generic invalid-login and forgot-password responses, token revocation on logout/password-change/reset, activity logging, and a role/permission matrix enforced via Laravel Gates (`AppServiceProvider`) + `can:` route middleware.
3. **Enquiries** — public submission (honeypot, trimmed/normalized input, IP hashing, user-agent truncation, mail-failure-safe), full admin CRUD with search/filter/sort/pagination, assignment, status transitions with `contacted_at` stamping, soft delete/restore/permanent-delete (`super_admin`), and a streamed, chunked, formula-safe CSV export with activity logging.
4. **Admission applications** — public submission with active-program check, unique application-number generation with collision retry backed by a DB unique constraint, acknowledgement + admin notification mail, full admin CRUD with reviewer/reviewed-at tracking, CSV export, restore/permanent-delete.
5. **Programs** — public list/detail (slug), full admin CRUD, unique slug (auto-generated or validated), activate/deactivate, feature/unfeature, image + icon upload/replacement (old file removed only after a successful DB update; new file removed if the DB write fails), transactional reorder, soft delete/restore/permanent-delete. Soft-deleting a program never breaks existing admission applications (`restrictOnDelete` + soft delete only).
6. **Testimonials** — same CRUD pattern, rating validated 1–5, image upload, reorder, feature/unfeature.
7. **Gallery** — same CRUD pattern, required image on create, reorder.
8. **Site settings** — strict key allowlist (15 keys), type-aware validation (email/url), public/private split, cached public endpoint (1h, cleared on update), role-scoped update (`content_manager` limited to already-public keys).
9. **Dashboard** — real aggregate queries only (no hardcoded values), N+1-safe, database-driver-aware monthly grouping (MySQL/MariaDB `DATE_FORMAT` in production, SQLite `strftime` in local dev — this was one of the bugs found during testing).
10. **Media storage** — `MediaService`: `public` disk (S3-ready via `filesystems.php`), random filenames, extension + MIME + `getimagesize` validation (jpg/jpeg/png/webp only, no SVG/executable content), 5MB limit, transactional replace-then-delete-old pattern, safe delete restricted to `uploads/` paths.
11. **Mail** — `NewEnquiryAdminMail`, `EnquiryConfirmationMail`, `NewAdmissionApplicationAdminMail`, `AdmissionAcknowledgementMail`, all `ShouldQueue`, Markdown templates, `MAIL_MAILER=log` safe for local dev, mail failures logged and never lose the underlying record (DB write always happens first).
12. **Activity logging** — every write path (auth events, CRUD, restore, permanent delete, CSV export, settings changes) logs via `ActivityLogger`, which strips sensitive keys from metadata; log listing is `super_admin`-only with pagination/filters.
13. **Seeders** — idempotent (`updateOrCreate`), administrator sourced from `ADMIN_NAME`/`ADMIN_EMAIL`/`ADMIN_PASSWORD` (skipped with a warning if unset — no weak default is ever seeded), 3 programs, 4 testimonials, 3 gallery entries, 11 public site settings.
14. **API documentation** — `API_DOCUMENTATION.md` rewritten to match the real route table exactly.
15. **Configuration** — `backend/.env.example` matches the spec exactly; `backend/.env` (local disposable SQLite dev DB) updated with non-secret keys (`APP_NAME`, `FILESYSTEM_DISK=public`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, blank `ADMIN_*`/`ADMIN_NOTIFICATION_EMAIL` placeholders) — no real credentials were touched. Root and backend `.env` files confirmed untracked by git; root `.gitignore` updated to ignore `.env*` (it previously ignored nothing).
16. **Completeness audit** — grepped for TODO/FIXME/placeholder/dd()/dump()/var_dump()/Web3Forms/stray localhost references in `app/`, `database/`: none found. Every route in `routes/api.php` maps to an existing controller method with Form Request validation and Gate-based authorization.

## Authorization matrix (implemented as Gates)

| Ability | super_admin | admin | content_manager | enquiry_manager |
| --- | :-: | :-: | :-: | :-: |
| dashboard.view | ✅ | ✅ | | |
| enquiries.manage | ✅ | ✅ | | ✅ |
| applications.manage | ✅ | ✅ | | ✅ |
| programs.manage | ✅ | ✅ | ✅ | |
| testimonials.manage | ✅ | ✅ | ✅ | |
| gallery.manage | ✅ | ✅ | ✅ | |
| settings.manage | ✅ | ✅ | | |
| settings.manage-public | ✅ | ✅ | ✅ | |
| records.restore | ✅ | ✅ | (via resource ability) | (via resource ability) |
| records.forceDelete | ✅ | | | |
| logs.view | ✅ | | | |

## Services

`MediaService`, `CsvExportService`, `ActivityLogger`.

## Mail classes

`NewEnquiryAdminMail`, `EnquiryConfirmationMail`, `NewAdmissionApplicationAdminMail`, `AdmissionAcknowledgementMail`.

## Seeders

`DatabaseSeeder` → `EducitySeeder` (administrator, programs, testimonials, gallery, site settings).

## Commands actually run this session

```
php artisan optimize:clear
php artisan route:list --path=api
php artisan config:show database
php artisan about
php artisan storage:link
php artisan migrate --force              # additive only, against the local disposable SQLite dev DB
php artisan db:seed --force               # same disposable DB
php -l on every file in app/, database/seeders/, routes/, bootstrap/, config/
php artisan tinker (smoke tests, cleaned up afterwards)
php artisan serve --port=8123 (background, for curl smoke tests, stopped afterwards)
```

`migrate:fresh` was **not** run — a `migrate:fresh --seed` request was blocked by the sandbox's destructive-command guard; instead, a proper additive migration was written to bring the users table's schema up to date without touching data.

## Bugs found and fixed during verification

1. **Gallery model/table mismatch**: `Gallery` model had no explicit `$table`, so Eloquent pluralized it to `galleries`, but the migration created a table named `gallery`. Fixed by setting `protected $table = 'gallery'`.
2. **Guest requests to protected routes threw `RouteNotFoundException` instead of 401**: Sanctum's `auth` middleware tried to redirect non-JSON-accepting guests to a named `login` route that doesn't exist in this API-only backend. Fixed with `Authenticate::redirectUsing(fn () => null)` in `AppServiceProvider::boot()`, letting the custom `AuthenticationException` JSON renderer in `bootstrap/app.php` handle it correctly (verified: now returns `401` with the standard error shape).
3. **Dashboard monthly counts used MySQL-only `DATE_FORMAT`**: works in production (MySQL/MariaDB) but breaks on SQLite (local dev default). Made driver-aware (`DATE_FORMAT` on MySQL/MariaDB, `strftime` on SQLite).
4. **Users table schema drift**: the local SQLite dev database had been created before `role`/`is_active` were added to the base migration, so those columns were missing even though the migration file itself was correct. Added an additive, idempotent migration rather than forcing a destructive rebuild.

## Deferred to a later session (per instructions)

- Comprehensive automated test suite (PHPUnit/Pest feature and unit tests).
- Deeper frontend/backend integration (the React `src/api.js`/`src/Admin.jsx` admin foundation are minimal stubs from a prior session and were left untouched — the new route paths under `admin/auth/*` should be reconciled with the frontend in a follow-up session).

## Genuine external blockers

None. All required backend functionality was implementable with local tooling; no external credentials (SMTP, S3, production database) were available or required for this session's scope.

## Sandbox limitation encountered

File deletion (`rm`, `Remove-Item`) was blocked by the harness's destructive-command guard. `app/Http/Controllers/Api/ContentController.php`, `app/Http/Controllers/Api/AdminController.php`, and `app/Http/Resources/PublicResource.php` were superseded by `ProgramController`/`TestimonialController`/`GalleryController`/`DashboardController`/`ActivityLogController` and are no longer referenced by any route, but could not be removed from disk. They are inert (not autoloaded by anything reachable) but should be deleted by hand (or in a session with delete permission) for cleanliness.

## Required manual environment configuration before going live

- Set real `DB_*` (MySQL/MariaDB), `MAIL_*`, `ADMIN_NOTIFICATION_EMAIL`, `APP_URL`, `FRONTEND_URL`, and `CORS_ALLOWED_ORIGINS` in `backend/.env`.
- Set `ADMIN_NAME`/`ADMIN_EMAIL`/`ADMIN_PASSWORD` to a strong, real password before running `php artisan db:seed` in any non-local environment.
- Set `APP_DEBUG=false` and run `php artisan config:cache && php artisan route:cache` in production.
- Provision a persistent queue worker (Supervisor on a VPS, or a cron-driven `queue:work --stop-when-empty` on shared hosting) — the four Mailables are queued.
- Run `php artisan storage:link` on the deployment target.
