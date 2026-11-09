<?php

use App\Actions\Categories\SaveCategory;
use App\Actions\Suppliers\SaveSupplier;
use App\Actions\Suppliers\TransitionPipelineStatus;
use App\Actions\Verification\RecordVerificationCheck;
use App\Enums\PublicationStatus;
use App\Enums\StaffRole;
use App\Enums\SupplierPipelineStatus;
use App\Enums\UserType;
use App\Enums\VerificationCheckType;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

/**
 * @param  array<string, mixed>  $attributes
 */
function makeSupplier(array $attributes = []): Supplier
{
    return app(SaveSupplier::class)->handle(null, $attributes + ['name' => 'Ningbo Test Packaging Co.']);
}

/**
 * Passes the given check types directly (test shortcut around the UI).
 *
 * @param  list<VerificationCheckType>  $types
 */
function passChecks(Supplier $supplier, array $types, ?Carbon $expiresAt = null): Supplier
{
    $specialist = staffUser(StaffRole::VerificationSpecialist);
    foreach ($types as $type) {
        app(RecordVerificationCheck::class)->handle($supplier, $type, [
            'status' => 'passed', 'method' => 'Checked in test', 'expires_at' => $expiresAt,
        ], $specialist);
    }

    return $supplier->refresh();
}

function levelOneChecks(): array
{
    return [VerificationCheckType::CompanyIdentity, VerificationCheckType::Contact, VerificationCheckType::WebsiteDomain];
}

function publishedSupplier(array $attributes = [], ?Category $category = null): Supplier
{
    $category ??= makeCategory('Verpakking', 'Packaging');
    $supplier = app(SaveSupplier::class)->handle(null, $attributes + ['name' => 'Published Supplier Ltd'],
        ['en' => ['description' => 'We make boxes.']], [$category->id]);
    passChecks($supplier, levelOneChecks());
    app(TransitionPipelineStatus::class)->handle($supplier, SupplierPipelineStatus::Published, staffUser(StaffRole::Administrator));

    return $supplier->refresh();
}
