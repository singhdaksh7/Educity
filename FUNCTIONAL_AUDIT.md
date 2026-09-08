# Educity functional audit

Audited 2026-09-08 against the current source and the 65 registered `/api/v1` routes.

| Endpoint group | UI integration | Auth | Status / action |
|---|---|---|---|
| `GET programs`, `programs/{slug}` | Program section and application picker | public | Active records load; image URLs are resolved and a selected programme submits an application. |
| `POST enquiries` | Contact form | public | Uses Laravel JSON validation, honeypot, disabled submit and backend confirmation. |
| `POST admission-applications` | Program “Apply now” modal | public | Uses active programme ids, honeypot and displays the returned application number. |
| `GET testimonials`, `GET gallery` | Testimonial and campus sections | public | Live records load with accessible image alt text and visual fallbacks. |
| `GET site-content` | No existing public setting consumer | public | Backend endpoint is present; public components still use their designed static copy. |
| `admin/auth/*` | Login, guard, logout, password reset request, change password | mixed | Correct `/admin/auth/*` API paths; token and user keys are scoped to Educity only. Reset completion needs a password-reset link/token supplied by email. |
| `admin/dashboard` | Dashboard | admin capability | Displays API metrics without hard-coded totals. |
| `admin/enquiries`, `admin/admission-applications` | Lists, detail updates, delete/restore, CSV | enquiry capability | Pagination, search, status filter, notes and status updates are connected. |
| `admin/programs`, `testimonials`, `gallery` | Lists and multipart content forms | content capability | Create/edit/delete/restore workflows use exact resource routes and image previews. |
| `admin/site-settings` | Settings form | settings capability | Loads/saves the server-owned key/value set and shows response errors. |
| `admin/activity-logs` | Admin list | super admin | Navigation is role-hidden and server authorization remains authoritative. |

## Findings repaired

- Replaced the previous generic admin form flow that attempted to edit enquiries and applications as content records.
- Replaced global `localStorage.clear()` with removal of only the two Educity session keys.
- Normalized API URL handling, authorization headers, multipart requests, JSON errors, 204s and CSV downloads.
- Removed direct public `fetch` duplication for testimonials, gallery and programs.
- Added a reachable public admission workflow.

## Verification caveat

Frontend lint/build run from the repository root. Laravel tests now run against the in-memory SQLite test database: 3 passing tests / 6 assertions. This is initial coverage, not yet the extensive suite requested in the handoff.
