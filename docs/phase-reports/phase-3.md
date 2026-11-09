# Phase 3 report — Buyer experience

## 1. Implemented

**Supplier directory** (`/nl/leveranciers`, `/en/suppliers`): search across supplier names, descriptions
(MySQL FULLTEXT), product names and category names; filters for category (incl. subcategories), company
type, MOQ ceiling, OEM/ODM, minimum verification level, province and **ChinaSupply-verified** certificates;
sort by relevance, verification level or newest. Only publicly visible suppliers are queried. Ordering never
depends on payment (stated on the page). Filtered pages are `noindex`. Invalid filter values are ignored.

**Compare and favourites.** Up to 3 suppliers side by side (session-based, also for guests): level, type,
location, MOQ, lead time, capacity, custom production, markets, verified certificates, passed checks.
Buyers can save suppliers (heart) and see them under *Saved suppliers*.

**Sourcing request wizard** (`/nl/sourcing-aanvraag`, `/en/sourcing-request`): 5 steps (product with up to
8 reference files, details/customisation/certifications, quantity & budget, delivery/Incoterm, contact &
consent). No account needed; session draft survives reloads; plain-language help on MOQ, OEM/ODM and
Incoterms; preferred supplier (`?supplier=`), category (`?category=`) and search term (`?q=`) can be
pre-filled. Server re-validates every step on submit; honeypot; 5 submissions per IP per hour; uploads
are private and type-checked by content (a bad file rolls back the whole submission). Requests get a
reference like `CS-2026-00042`, a timeline entry, a staff review task, a confirmation mail to the buyer
(in their language) and a staff mail. All "Start a request" buttons now open the wizard.

**Buyer dashboard** (`/account`): overview with "action needed" banner, open requests and next step in
plain language; *My requests* (open/closed); request page with status, next step, timeline (buyer-visible
events only), shared shortlist with quote summary (price, MOQ, lead time, sample cost, Incoterm) and
ChinaSupply's advice, "Choose this supplier", messages with ChinaSupply, request details and files.
Guests get a **signed, expiring (180 days), read-only** tracking link. Guest requests are linked to a buyer
account automatically **only after that buyer verifies the same email address** (never to supplier accounts).

**Staff matching workflow (Filament → Leads → Sourcing requests).** List with status/assignee filters
(open by default), unread buyer replies and match counts; request view with Assign to me / Assign / Change
status (+ optional message to buyer) / Message buyer / Share shortlist; tabs for supplier matches (add with
rule-based suggestions, "add top suggestions", quote summary, advice vs. internal notes, mark contacted,
reorder), messages, buyer files, timeline and internal notes. `SupplierMatcher` scores category fit, MOQ fit,
OEM/ODM, verification level and publication — no paid boosting. Only published, verified suppliers can be
shared with a buyer. Dashboard shows open/new requests and published/pending suppliers.

## 2. Files and database

New tables: `sourcing_requests`, `sourcing_request_files`, `sourcing_request_matches`,
`sourcing_request_events`, `sourcing_request_messages`, `favorites`; FULLTEXT indexes on supplier names and
descriptions. New permission `sourcing_requests.manage` (administrator, sourcing manager). Main code:
`app/Actions/Sourcing/*`, `app/Services/SupplierMatcher.php`, `app/Support/{SupplierDirectory,SourcingOptions,RequestLinks}.php`,
`app/Livewire/SourcingWizard.php`, `app/Http/Controllers/{SupplierDirectoryController,SourcingRequestController}.php`,
`app/Filament/Resources/SourcingRequests/*`, `app/Notifications/*Request*`, views under `resources/views/{suppliers,sourcing,account}`,
`lang/*/{requests,directory}.php`.

## 3–4. Tests

`./vendor/bin/pest`: **166 tests, 624 assertions, all passing** (MySQL). Pint passing, Larastan level 5:
0 errors, route/config/view caching works. Browser-checked: directory, wizard step navigation, session draft
restore, scroll-to-top between steps.

Tests cover: directory visibility and every filter, verified-only certificate filter, wildcard escaping,
noindex; compare limit; favourites per user type; wizard validation per step, guest submission with private
files, consent/honeypot/rate limit, disguised file rejection with rollback, buyer email forcing, preferred
supplier only when published; guest→account linking only after verification and never for suppliers;
dashboard ownership (404 for others), shortlist visibility (no internal notes / unshared matches),
buyer reply re-opening "needs info", selection only of shared matches, signed tracking (unsigned/expired
refused, read-only), file access for buyer/staff; permissions for status changes and the admin section;
matcher ranking; all admin pages and relation managers render.

## 5. Remaining issues

- Paid packages and checkout are Phase 4: the wizard says the first request is free and that any paid help
  is quoted in advance; nothing is charged yet.
- Emails are queued: the production queue worker must run (see deployment notes).
- Suppliers do not yet receive RFQs in their portal; staff contact suppliers themselves and record the
  quote summary (planned together with supplier subscriptions).
- Search uses MySQL FULLTEXT (min. 3 characters); Meilisearch can be added when the catalogue grows.

## 6. Security

Request pages are scoped to the owner (`404` otherwise, references not guessable for others); guests only
get signed read-only links; private files are served through ownership/permission checks with `nosniff`;
buyer emails cannot be redirected to another address by logged-in users; only verified buyers' emails link
guest requests; staff actions check permissions inside the actions; unpublished suppliers can never be
shared with or preferred by buyers.

## 7. Manual configuration

After deploying: `php artisan migrate --force` and `php artisan db:seed --force` (adds the new permission).
Make sure the queue worker and scheduler run, and that `MAIL_*` is configured so buyers get confirmations.

## 8. Next phase

Phase 4 — service packages and payments: admin-editable packages (Shortlist, Full Sourcing, inspection
coordination), orders for ChinaSupply services, Mollie integration via a provider interface (hosted
checkout, webhooks with authoritative status fetch, idempotency, refunds), payment history for buyers and
payment administration — verifying the current Mollie SDK/API before implementation.
