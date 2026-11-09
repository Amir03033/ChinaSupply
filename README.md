# ChinaSupply.nl

B2B sourcing and supplier discovery platform connecting European buyers with Chinese
manufacturers. Laravel 13 · Filament 5 · Livewire 4 · Tailwind 4 · MySQL 8.4.

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # set DB_* for MySQL
php artisan migrate --seed                          # roles, 20 main categories, legal page drafts
php artisan app:create-admin                        # first staff user (2FA set up at first login)
php artisan serve
```

- Public site: `/nl`, `/en` · Admin: `/admin` (staff only, two-factor authentication required)
- Tests: `./vendor/bin/pest` (uses the `chinasupply_testing` MySQL database)
- Code style / static analysis: `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`
- Queue worker (notifications): `php artisan queue:work`

## Documentation

- `docs/00-repository-audit.md` — starting point, versions, decisions
- `docs/01-implementation-plan.md` — phases and architecture rules
- `docs/phase-reports/` — report per completed phase
