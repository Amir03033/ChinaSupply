<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: every seeder is idempotent and never overwrites content
     * edited in Filament. No users are seeded; create the first administrator
     * with `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategorySeeder::class,
            PageSeeder::class,
        ]);
    }
}
