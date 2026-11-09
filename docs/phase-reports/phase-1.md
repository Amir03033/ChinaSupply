# Phase 1 report — Platform foundation

## 1. Implemented

- Laravel 13.40 app on MySQL; Filament 5.11 admin at `/admin`; Fortify auth (login, register,
  password reset, email verification, 2FA endpoints) with custom dark views.
- User types `buyer` / `supplier` / `staff` (`users.type`) + staff roles `administrator`,
  `sourcing_manager`, `verification_specialist` (spatie/permission, permissions in
  `App\Enums\Permission`). Public registration always creates a buyer. Staff are created via
  `php artisan app:create-admin` or by an administrator in Filament.
- **Two-factor authentication is mandatory for all staff** in the admin panel (Filament app
  authentication + recovery codes). Staff log in → redirected to `/admin`.
- NL/EN localisation: locale-prefixed routes with translated URL segments
  (`/nl/categorieen`, `/en/categories`), hreflang + x-default, language switcher that keeps the
  equivalent page (per-locale slugs), Accept-Language redirect on `/`. Admin is English.
- Design system (dark, technical B2B): Tailwind 4 tokens, self-hosted Inter, Blade components
  (layout, header with mobile menu, footer, button, alert, empty state, breadcrumbs, form fields
  with accessible errors, category card/icon, verification level badge).
- Public pages: home (two routes), categories index + search, category detail, how it works,
  verification explained, for suppliers, about, contact (stored + staff mail notification,
  honeypot, rate limit, consent), CMS pages (`/nl/info/{slug}`), account placeholder for
  verified buyers.
- Hierarchical categories: unlimited depth (adjacency + materialised path), NL/EN translations
  in `category_translations` with DB-enforced unique slug per locale, SEO title/description,
  icon, image, draft/active/inactive, featured, ordering. Visibility requires every ancestor to
  be active. Cycle prevention and descendant path rewrite on move.
- Initial taxonomy: 20 main categories, 121 subcategories, 33 product types (NL + EN), seeded
  once and never overwritten.
- Filament: Categories (browse per level, drag-and-drop ordering, search across levels,
  translation tabs, parent tree select), Pages (rich text, sanitised, "legal review pending"
  flag), Contact enquiries (status workflow, nav badge), Users (staff creation + roles), read-only
  Audit log, dashboard with real counts.
- Legal content structure: terms, privacy, cookies, payment terms, cancellation/refunds,
  supplier agreement, verification disclaimer — outlines only, published with a visible
  "draft, not reviewed by a lawyer" notice.

## 2. Files and database

- Migrations: users (type, locale, MFA columns, soft deletes), permission tables, activity_log,
  notifications, `categories`, `category_translations`, `pages`, `page_translations`,
  `contact_enquiries`.
- Main code: `app/Actions/{Categories,Pages}`, `app/Models`, `app/Policies`,
  `app/Filament/Resources/*`, `app/Http/{Controllers,Middleware,Requests,Responses}`,
  `app/Support/{Locales,Alternates,SafeHtml}`, `resources/views/**`, `lang/{nl,en}/*`,
  `database/seeders/*`, `database/data/categories.php`.

## 3–4. Tests

`./vendor/bin/pest`: **78 tests, 262 assertions, all passing** (MySQL). Pint: passing.
Larastan level 5: 0 errors. `php artisan optimize` (route/config/view cache) works. All public
routes return 200/expected redirects; checked at desktop and 375px mobile (no horizontal
scroll); admin login with 2FA checked in the browser.

## 5. Remaining issues

- Legal texts are outlines; a lawyer must write/approve them.
- Category images are stored as-is (no WebP conversion yet; planned with medialibrary in Phase 2).
- Subcategories show a generic icon unless an icon is set.
- No sitemap/structured data yet (Phase 5).

## 6. Security

- Staff 2FA mandatory; panel access needs staff type + role; buyers cannot get roles via
  registration (tested). Policies on every resource; audit log read-only.
- CMS HTML sanitised on save and on output (allow-list). Contact form: CSRF, honeypot,
  3/min + 20/day per IP, staff mail does not contain the message body.
- Password reset messages do not reveal whether an account exists. Session encryption on.
- Passwords: min 12 chars; in production also mixed case, numbers and breached-password check.

## 7. Manual configuration

- Production `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, DB credentials,
  mail provider (SMTP/API) with SPF/DKIM/DMARC, `COMPANY_*` values (legal name, KvK, VAT,
  address) and `STAFF_NOTIFICATION_EMAIL`.
- VPS: PHP 8.4 with bcmath, exif, gd, intl, pdo_mysql, sodium, zip; a queue worker
  (`php artisan queue:work`, Supervisor or Plesk scheduled task) and the scheduler cron.
- After first deploy: `php artisan migrate --seed --force` and `php artisan app:create-admin`.

## 8. Next phase

Phase 2 — suppliers & CRM (see `docs/01-implementation-plan.md`).
