<?php

use App\Enums\StaffRole;
use App\Models\ContactEnquiry;
use App\Models\Page;
use Database\Seeders\PageSeeder;

it('renders every admin page for an administrator with 2FA set up', function () {
    $this->seed(PageSeeder::class);
    $root = makeCategory('Root', 'Root');
    makeCategory('Kind', null, $root);
    $enquiry = ContactEnquiry::create([
        'topic' => 'general', 'name' => 'A', 'email' => 'a@example.nl', 'message' => 'Hallo daar', 'locale' => 'nl', 'status' => 'new',
    ]);

    $admin = staffUser(StaffRole::Administrator);
    $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($admin);

    $page = Page::first();

    foreach ([
        '/admin',
        '/admin/categories',
        "/admin/categories?parent={$root->id}",
        '/admin/categories/create',
        "/admin/categories/{$root->id}/edit",
        '/admin/pages',
        "/admin/pages/{$page->id}/edit",
        '/admin/contact-enquiries',
        "/admin/contact-enquiries/{$enquiry->id}",
        '/admin/users',
        '/admin/users/create',
        '/admin/activity-logs',
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('hides admin-only sections from a sourcing manager', function () {
    $user = staffUser(StaffRole::SourcingManager);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($user);

    $this->get('/admin/contact-enquiries')->assertOk();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/pages')->assertForbidden();
    $this->get('/admin/activity-logs')->assertForbidden();
});
