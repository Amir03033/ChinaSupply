# Implementation plan

Phases follow the brief, adjusted to the empty starting repository. Each phase ends
with: full test suite, Pint, Larastan, migration check, authorization tests, responsive
check, route check, translation-completeness test and a written phase report.

| Phase | Scope | Status |
| --- | --- | --- |
| 0 | Repository audit, plan | Done |
| 1 | Foundation: Laravel 13, auth (Fortify), user types + staff roles, required 2FA for staff, NL/EN routing, design system, homepage + core pages, hierarchical categories (+ Filament admin, seed of 20 main categories), CMS pages for legal content, contact enquiries, audit log, dashboard with real metrics | Done |
| 2 | Suppliers & CRM: supplier model, supplier-category relations, products, documents (private disk), CRM pipeline (statuses from the brief), contacts, communications, outreach templates, tasks/follow-ups, source tracking, CSV import/export, evidence-based verification checks + level calculator + expiry, supplier self-registration (multi-step, autosave) + supplier portal | Next |
| 3 | Buyer experience: supplier directory with filters, public supplier profiles (no private data), compare, favourites, sourcing request wizard (guest-friendly, uploads), buyer dashboard, staff matching workflow | |
| 4 | Payments & services: service packages (admin-editable prices/VAT), orders for ChinaSupply services, payment provider interface + Mollie implementation (hosted checkout), webhooks (fetch authoritative state, idempotent), refunds, payment admin, invoices; payment types `platform_service`, `platform_addon` active; `supplier_order`, `supplier_subscription` modelled but disabled | |
| 5 | Launch preparation: sitemap/structured data, performance, security review + headers/CSP, accessibility pass, legal content finalised by a lawyer, production config, backups (spatie/laravel-backup), monitoring, deploy runbook | |

## Architecture rules

- Modular monolith; business logic in `app/Actions/*`, never in controllers, Livewire
  components or Filament resources.
- `users.type` (buyer / supplier / staff) separates the three worlds; staff permissions via
  spatie roles. `canAccessPanel()` requires `type = staff` **and** a staff role.
- Every model change that matters is audit-logged (spatie/activitylog).
- No fake data or placeholder numbers in production views. Seeders are idempotent and
  never overwrite content edited in Filament.
