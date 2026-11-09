# Phase 2 report — Suppliers, CRM and verification

## 1. Implemented

**Supplier data model.** Identity (display/Chinese/legal name, USCC), company type, location, private
address and contact, capabilities (MOQ, capacity, lead times, OEM/ODM/private label, export markets,
languages), NL/EN descriptions, logo/cover, categories (many-to-many), products, certifications,
contacts, documents.

**Evidence-based verification.** Ten check types (brief §5), one row per supplier per type, each with
status, date, expiry, evidence document, reviewer, public "how checked" text and private notes.
`VerificationLevelCalculator` is the only code that sets the level:
level 1 = identity + contact + website · level 2 = + registration + address · level 3 = + factory existence.
A check counts only while passed and unexpired; a level never skips a lower one. Default validity 12
months (inspection 24). Daily `suppliers:refresh-verification` (02:00) recalculates, flags
re-verification 30 days ahead and creates one follow-up task. When a supplier edits a field a passed
check relied on (e.g. registration number), that check returns to "pending" and the level is
recalculated. An architecture test ensures the calculator never references payment/sponsoring code.

**Supplier CRM (Filament).** Supplier list with filters (status, level, type, source, owner, category,
re-verification, overdue follow-up, deleted), edit page with relation tabs (Verification, Communication,
Follow-ups, Internal notes, Contacts, Documents, Certifications, Products), "Change status" and
"Log outreach" (template → copy/send → logged + follow-up task) actions, pipeline Kanban (drag & drop,
"mine" filter), Follow-ups list with badge, outreach templates (5 seeded), certification catalogue
(18 seeded). Pipeline statuses from the brief plus `applied` for self-registrations. Publishing requires
level ≥ 1, a category and a description, and only verification staff/admin may publish.

**CSV import/export** (Filament importer/exporter, queued). Import matches existing suppliers by
registration number → website host → name and updates instead of duplicating; blank cells never
overwrite data; status and verification are never imported.

**Supplier self-registration and portal.** `/en/supplier-registration` (`/nl/leverancier-aanmelden`)
creates a supplier user + supplier (`applied`) + contact. The portal (`/supplier-portal`) has 7 steps
with per-field autosave (whitelisted fields only), category picker (max 10), private document uploads
(MIME detected from content, 10 MB, random file names), products, a live checklist and submit for
review (→ `verification_pending`, staff mail + review task, once). Buyer/supplier/staff areas redirect
each other; email verification required.

**Public supplier profile** (`/en/suppliers/{slug}`, `/nl/leveranciers/{slug}`): only published
suppliers with level ≥ 1. Shows every check (passed with date and method, expired, or not checked),
"provided by supplier" labels, certificate status (checked vs. claimed), products with indicative prices.
Address, email, phone, registration number, notes and evidence are never rendered unless the supplier
allowed contact details. Category pages list published suppliers of the category and its subcategories,
sorted by verification level. "Request an introduction" pre-fills the contact form.

## 2. Files and database

New tables: `suppliers`, `supplier_translations`, `supplier_users`, `category_supplier`,
`supplier_contacts`, `supplier_documents`, `certifications`, `supplier_certifications`,
`verification_checks`, `products`, `outreach_templates`, `supplier_communications`, `tasks`, `notes`,
plus Filament `imports`, `exports`, `failed_import_rows`. New permissions synced by
`RolesAndPermissionsSeeder`. Main code: `app/Enums/*`, `app/Models/{Supplier*,Product,…}`,
`app/Services/VerificationLevelCalculator.php`, `app/Actions/{Suppliers,Verification}/*`,
`app/Filament/Resources/{Suppliers,Tasks,OutreachTemplates}`, `app/Filament/Pages/SupplierPipeline.php`,
`app/Filament/{Imports,Exports}`, `app/Livewire/SupplierPortal.php`, supplier views and `lang/*/{portal,suppliers}.php`.

## 3–4. Tests

`./vendor/bin/pest`: **128 tests, 449 assertions, all passing** (MySQL). Pint: passing. Larastan level 5:
0 errors. Route/config/view caching works. Browser-checked: supplier login → portal, autosave, step
navigation, public profile with verification panel.

Bugs found and fixed during this phase: Filament closures must use injectable argument names
(`$query`, `$record`) — `fn (Builder $q)` silently received an empty query; Livewire 4's
`wire:model.blur` no longer sends a request (now `wire:model.live.blur`) — PHP tests could not catch
this, the browser check did.

## 5. Remaining issues

- No virus scanning of uploads yet (ClamAV on the VPS, Phase 5). Files are private, type-checked by
  content and served as attachments with `nosniff`.
- Supplier portal is NL/EN; Simplified Chinese strings are prepared for (separate `portal.php`) but not
  translated — needs a native speaker.
- Uploaded images are not converted/resized yet.
- Outreach is logged, not sent from the platform (by design for now).
- The "Supplier enquiries" and "Subscriptions" portal sections arrive with Phases 3 and 4.

## 6. Security

Supplier-editable fields are whitelisted in the model (`Supplier::SUPPLIER_EDITABLE`) and enforced in
`UpdateSupplierProfile`; status/level/publication cannot be set by suppliers (tested via Livewire
tampering). Evidence documents are never downloadable by suppliers. Document downloads go through a
policy-checked, throttled route. Recording checks and publishing are restricted to verification staff
in the actions themselves (not only in the UI). Relation managers become read-only without the
relevant permission.

## 7. Manual configuration

- Run `php artisan migrate --force` and `php artisan db:seed --force` (idempotent; adds certifications,
  templates and new permissions).
- Scheduler cron on the VPS: `* * * * * php /path/artisan schedule:run` (needed for daily verification
  expiry) and a running queue worker (imports/exports and mails are queued).
- `APP_TIMEZONE=Europe/Amsterdam` (now the default).

## 8. Next phase

Phase 3 — buyer experience: supplier directory with filters, compare and favourites, the full sourcing
request wizard with uploads (guest-friendly), buyer dashboard and the staff matching workflow.
