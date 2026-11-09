<?php

use App\Actions\Categories\SaveCategory;
use App\Enums\PublicationStatus;
use App\Enums\StaffRole;
use App\Enums\UserType;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function staffUser(StaffRole $role): User
{
    $user = User::factory()->create();
    $user->type = UserType::Staff;
    $user->save();
    $user->assignRole($role->value);

    return $user;
}

function buyerUser(): User
{
    return User::factory()->create();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeCategory(string $nl, ?string $en = null, ?Category $parent = null, array $attributes = []): Category
{
    return app(SaveCategory::class)->handle(null, $attributes + [
        'parent_id' => $parent?->id,
        'status' => PublicationStatus::Active->value,
    ], array_filter([
        'nl' => ['name' => $nl],
        'en' => $en ? ['name' => $en] : null,
    ]));
}
