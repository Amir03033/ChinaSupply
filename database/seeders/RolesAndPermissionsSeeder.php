<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to run on every deploy to sync new permissions.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (StaffRole::cases() as $staffRole) {
            Role::findOrCreate($staffRole->value, 'web')
                ->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, $staffRole->permissions()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
