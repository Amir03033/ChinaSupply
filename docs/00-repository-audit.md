# Phase 0 — Repository audit (2026-11-09)

## Starting point

The project folder `/Users/amir/websites/ChinaSupply` was **empty**: no code, no git
repository, no database. There was no existing functionality to preserve. The
business and technical plan ("ChinaSupply.nl — Projectplan", Claude Doc) is the
product reference.

## Environment checked

| Item | Local | Note |
| --- | --- | --- |
| PHP | 8.4.2 | Several packages now require **PHP ≥ 8.4** (see below). The Plesk VPS must run PHP 8.4. |
| Composer | 2.8.4 | |
| Node / npm | 24.21 / 11.19 | Only needed at build time. |
| MySQL | 8.4.11 | `chinasupply` and `chinasupply_testing` databases, utf8mb4_unicode_ci. |
| PHP extensions | bcmath, exif, gd, intl, pdo_mysql, sodium, zip | Same set needed on the VPS. |

## Package versions chosen (verified on Packagist, 2026-11-09)

| Package | Version | Requires |
| --- | --- | --- |
| laravel/framework | 13.40 | PHP ^8.3 |
| filament/filament | 5.11.2 | PHP ^8.2 (plan mentioned v4; v5 is current and supports Laravel 13) |
| livewire/livewire | 4.4.8 | Laravel ^13 supported |
| spatie/laravel-permission | 8.5 | PHP ^8.3, Laravel ^12/^13 |
| spatie/laravel-activitylog | 5.1.4 | **PHP ^8.4** |
| laravel/fortify | 1.41 | |
| pestphp/pest | 5.3.5 | **PHP ^8.4**, PHPUnit 13 |
| larastan/larastan | 3.14 | |
| mollie/mollie-api-php | 4.1.0 (Phase 4) | PHP ^8.2 — v4 released 2026-09; re-verify API before Phase 4 |
| mollie/laravel-mollie | 5.0.0 (Phase 4) | Laravel ^11–^13 |

## Decisions that update the original plan

1. **Dark design** instead of light-mode-only (new explicit requirement). Colour rules
   kept: blue = brand/actions, green = verification only, violet = sponsored only.
2. **Filament 5** instead of 4.
3. **Translations as rows** (`*_translations` tables, one row per locale) instead of JSON
   columns, because slugs must be unique per language; a `unique(locale, slug)` index
   enforces this in the database for any future language (incl. zh_CN).
4. **Tests run on MySQL**, not SQLite, so collation/index behaviour matches production.
5. The sourcing wizard is Phase 3; until then the "Start a request" CTA goes to the
   contact form with topic "Sourcing request" (honest, working path).

## Risks identified

- VPS must provide PHP 8.4 and the extensions above (Plesk supports this; confirm).
- Mollie API PHP v4 is new; its method names must be checked against the official docs
  at the start of Phase 4, not assumed.
- Budget (€500–€2,500) rules out paid search/monitoring SaaS at launch: database queue,
  MySQL FULLTEXT search, free tiers (e.g. Sentry developer plan) or self-hosted options.
