# Educity API Documentation

Base URL: `{APP_URL}/api/v1`

All responses are JSON. Successful responses:

```json
{ "success": true, "message": "Human-readable message", "data": {} }
```

Validation errors (422):

```json
{ "success": false, "message": "The given data was invalid.", "errors": { "field": ["message"] } }
```

Other error responses (`401`, `403`, `404`, `429`, `500`) follow the same `{ success, message, errors }` shape. The API never returns an HTML error page.

## Authentication

Admin endpoints require a Sanctum bearer token:

```
Authorization: Bearer {token}
```

### Roles & permissions

| Role | Permissions |
| --- | --- |
| `super_admin` | Everything, including activity logs and permanent deletion |
| `admin` | Dashboard, enquiries, applications, programs, testimonials, gallery, site settings, restore records |
| `content_manager` | Programs, testimonials, gallery, public site settings |
| `enquiry_manager` | Enquiries, admission applications |

Restoring a soft-deleted record requires the same permission as managing that resource type. Permanently deleting a record is restricted to `super_admin`. Viewing activity logs is restricted to `super_admin`.

---

## Public Endpoints

### `POST /enquiries`
No auth required. Rate limited (10/min per IP).

Body: `name` (required, string, max 120), `email` (nullable, email), `phone` (required, string, max 30), `subject` (nullable, string, max 160), `message` (required, string, max 4000), `website` (honeypot — must be empty).

```json
{ "success": true, "message": "Your enquiry has been received...", "data": { "id": 1 } }
```

### `POST /admission-applications`
No auth required. Rate limited (10/min per IP).

Body: `full_name` (required), `email` (required, email), `phone` (required), `date_of_birth` (nullable, date, before today), `program_id` (required, must reference an active program), `previous_qualification` (nullable), `message` (nullable), `website` (honeypot).

```json
{ "success": true, "message": "Your application has been received.", "data": { "application_number": "EDU-2026-000123" } }
```

### `GET /programs`
Query: `featured=1` optional. Returns active, non-deleted programs ordered by `display_order`.

### `GET /programs/{slug}`
Returns a single active program or 404.

### `GET /testimonials`
Returns active testimonials ordered by `display_order`.

### `GET /gallery`
Returns active gallery items ordered by `display_order`.

### `GET /site-content`
Returns only `is_public = true` settings as a flat `{ key: value }` object. Cached for 1 hour; cache clears automatically on admin update.

---

## Admin Authentication

### `POST /admin/auth/login`
Rate limited 5/min per IP+email. Body: `email`, `password`.

```json
{ "success": true, "data": { "token": "...", "user": { "id":1,"name":"...","email":"...","role":"admin","is_active":true } } }
```
Invalid credentials → generic 422 `"Invalid credentials."` (does not reveal whether the account exists or is inactive).

### `POST /admin/auth/forgot-password`
Rate limited 3/min per IP+email. Body: `email`. Always returns the same generic success message, regardless of whether the account exists.

### `POST /admin/auth/reset-password`
Body: `token`, `email`, `password`, `password_confirmation`. Revokes all existing tokens on success.

### `GET /admin/auth/me` — auth required
Returns the current admin's `id, name, email, role, is_active`.

### `POST /admin/auth/logout` — auth required
Revokes the current access token.

### `PATCH /admin/auth/password` — auth required
Body: `current_password`, `password`, `password_confirmation` (min 12 chars). Revokes all other tokens.

---

## Dashboard

### `GET /admin/dashboard` — roles: `super_admin`, `admin`
Returns: `total_enquiries`, `new_enquiries`, `enquiries_this_month`, `enquiries_by_status`, `total_applications`, `applications_this_month`, `applications_by_status`, `active_programs`, `active_testimonials`, `active_gallery_items`, `recent_enquiries` (5), `recent_applications` (5), `monthly_enquiry_counts` (12 months), `monthly_application_counts` (12 months).

---

## Enquiries — roles: `super_admin`, `admin`, `enquiry_manager`

### `GET /admin/enquiries`
Query params: `search` (name/email/phone), `status`, `assigned_to`, `from`, `to` (dates), `sort` (`created_at|name|status|contacted_at`), `direction` (`asc|desc`), `trashed=1` (soft-deleted only), `page`, `per_page` (max 100).

### `GET /admin/enquiries/{enquiry}` — full record + assignee
### `PATCH /admin/enquiries/{enquiry}`
Body (all optional): `status` (`new|in_progress|contacted|closed|spam`), `assigned_to` (user id), `admin_notes`. Setting `status=contacted` auto-stamps `contacted_at` if unset.

### `DELETE /admin/enquiries/{enquiry}` — soft delete
### `POST /admin/enquiries/{id}/restore`
### `DELETE /admin/enquiries/{id}/force` — `super_admin` only, permanent
### `GET /admin/enquiries/export`
Streams a formula-safe CSV of the current filters (same query params as the list endpoint). Logged to the activity log.

---

## Admission Applications — roles: `super_admin`, `admin`, `enquiry_manager`

### `GET /admin/admission-applications`
Query params: `search` (name/email/number), `status`, `program_id`, `from`, `to`, `sort` (`created_at|full_name|status|reviewed_at`), `direction`, `trashed=1`, `page`, `per_page`.

### `GET /admin/admission-applications/{admissionApplication}`
### `PATCH /admin/admission-applications/{admissionApplication}`
Body: `status` (`submitted|under_review|contacted|accepted|rejected|withdrawn`), `admin_notes`. Setting `status` stamps `reviewed_by`/`reviewed_at` with the acting admin.

### `DELETE /admin/admission-applications/{admissionApplication}` — soft delete
### `POST /admin/admission-applications/{id}/restore`
### `DELETE /admin/admission-applications/{id}/force` — `super_admin` only
### `GET /admin/admission-applications/export` — streamed, formula-safe CSV

---

## Programs — roles: `super_admin`, `admin`, `content_manager`

### `GET /admin/programs`
Query: `search`, `is_active` (`0|1`), `trashed=1`, `page`, `per_page`.

### `POST /admin/programs` (multipart for images)
Body: `title`, `slug` (optional, auto-generated from title if omitted, must be unique), `short_description`, `description`, `degree_type`, `duration`, `eligibility`, `display_order`, `is_featured`, `is_active`, `image` (jpg/jpeg/png/webp, max 5MB), `icon` (same constraints).

### `GET /admin/programs/{program}` · `PATCH /admin/programs/{program}` · `DELETE /admin/programs/{program}` (soft delete — existing admission applications keep their `program_id`)
### `PATCH /admin/programs/{program}/activate` · `/deactivate` · `/feature` · `/unfeature`
### `PATCH /admin/programs/reorder`
Body: `items: [{ id, display_order }]`. Applied in a single transaction.
### `POST /admin/programs/{id}/restore` · `DELETE /admin/programs/{id}/force` (`super_admin` only)

---

## Testimonials — roles: `super_admin`, `admin`, `content_manager`

Same shape as Programs, without slug/icon. `POST/PATCH` body: `student_name`, `course_or_role`, `quote`, `rating` (1–5), `display_order`, `is_featured`, `is_active`, `image`.
Endpoints: `GET/POST /admin/testimonials`, `GET/PATCH/DELETE /admin/testimonials/{testimonial}`, `PATCH .../activate|deactivate|feature|unfeature`, `PATCH /admin/testimonials/reorder`, `POST /admin/testimonials/{id}/restore`, `DELETE /admin/testimonials/{id}/force` (`super_admin`).

---

## Gallery — roles: `super_admin`, `admin`, `content_manager`

`POST/PATCH` body: `title`, `description`, `alt_text`, `display_order`, `is_active`, `image` (required on create).
Endpoints: `GET/POST /admin/gallery`, `GET/PATCH/DELETE /admin/gallery/{gallery}`, `PATCH .../activate|deactivate`, `PATCH /admin/gallery/reorder`, `POST /admin/gallery/{id}/restore`, `DELETE /admin/gallery/{id}/force` (`super_admin`).

---

## Site Settings

### `GET /admin/site-settings` — roles with `settings.manage` or `settings.manage-public`
Returns every stored setting row (`key, value, type, is_public`).

### `PATCH /admin/site-settings` — `super_admin`/`admin` may update any allowlisted key; `content_manager` may only update keys already marked public
Body: `settings: [{ key, value }]`. `key` must be one of the allowlisted setting keys (see below); `email`/`url` typed keys are format-validated. Clears the public settings cache and logs the change.

Allowlisted keys: `institution_name, contact_email, contact_phone, address, hero_title, hero_description, hero_button_label, hero_button_link, about_title, about_description, footer_text, facebook_url, instagram_url, linkedin_url, youtube_url`.

---

## Activity Logs — `super_admin` only

### `GET /admin/activity-logs`
Query: `user_id`, `action`, `resource_type`, `from`, `to`, `sort` (`created_at|action|resource_type`), `direction`, `page`, `per_page`.
