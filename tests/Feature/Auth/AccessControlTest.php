<?php

use App\Enums\StaffRole;
use App\Enums\UserType;
use App\Models\User;
use Filament\Facades\Filament;

it('only lets staff with a staff role into the admin panel', function () {
    $panel = Filament::getPanel('admin');

    $buyer = buyerUser();
    $staffWithoutRole = User::factory()->create(['type' => UserType::Staff]);
    $buyerWithRole = buyerUser();
    $buyerWithRole->assignRole(StaffRole::Administrator->value);

    expect($buyer->canAccessPanel($panel))->toBeFalse()
        ->and($staffWithoutRole->canAccessPanel($panel))->toBeFalse()
        ->and($buyerWithRole->canAccessPanel($panel))->toBeFalse()
        ->and(staffUser(StaffRole::SourcingManager)->canAccessPanel($panel))->toBeTrue();
});

it('blocks buyers from admin URLs', function () {
    $this->actingAs(buyerUser())->get('/admin')->assertForbidden();
});

it('sends guests to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('registers new users as buyers even when a type is injected', function () {
    $this->post('/register', [
        'name' => 'Mark',
        'email' => 'mark@example.nl',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        'terms' => '1',
        'type' => 'staff',
    ])->assertRedirect();

    $user = User::where('email', 'mark@example.nl')->sole();
    expect($user->type)->toBe(UserType::Buyer)->and($user->roles)->toHaveCount(0);
});

it('requires accepting the terms on registration', function () {
    $this->post('/register', [
        'name' => 'Mark', 'email' => 'mark@example.nl',
        'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery',
    ])->assertSessionHasErrors('terms');
});

it('redirects staff to the admin panel and buyers to their account after login', function () {
    $staff = staffUser(StaffRole::Administrator);
    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect(url('/admin'));
    $this->post('/logout');

    $buyer = buyerUser();
    $this->post('/login', ['email' => $buyer->email, 'password' => 'password'])->assertRedirect('/account');
    expect($buyer->fresh()->last_login_at)->not->toBeNull();
});

it('requires a verified email for the account area', function () {
    $this->actingAs(User::factory()->unverified()->create())->get('/account')->assertRedirect('/email/verify');
    $this->actingAs(buyerUser())->get('/account')->assertOk();
});

it('grants role permissions as designed', function () {
    $admin = staffUser(StaffRole::Administrator);
    $sourcing = staffUser(StaffRole::SourcingManager);
    $verification = staffUser(StaffRole::VerificationSpecialist);

    expect($admin->can('categories.manage'))->toBeTrue()
        ->and($sourcing->can('categories.manage'))->toBeFalse()
        ->and($sourcing->can('contact_enquiries.manage'))->toBeTrue()
        ->and($verification->can('contact_enquiries.manage'))->toBeFalse()
        ->and($verification->can('users.manage'))->toBeFalse()
        ->and($admin->can('users.manage'))->toBeTrue();
});

it('creates staff only through the console command with a strong password', function () {
    $this->artisan('app:create-admin', ['--email' => 'admin@chinasupply.nl', '--name' => 'Admin'])
        ->expectsQuestion('Role', 'administrator')
        ->expectsQuestion('Password (min. 12 characters)', 'Sterk-Wachtwoord-2026')
        ->assertSuccessful();

    $user = User::where('email', 'admin@chinasupply.nl')->sole();
    expect($user->isAdministrator())->toBeTrue()->and($user->hasVerifiedEmail())->toBeTrue();
});
