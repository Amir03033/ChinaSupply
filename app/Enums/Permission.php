<?php

namespace App\Enums;

/**
 * Single source of truth for permission names. New phases add cases here;
 * RolesAndPermissionsSeeder syncs them to the database.
 */
enum Permission: string
{
    case ViewAdminDashboard = 'admin.dashboard.view';
    case ViewCategories = 'categories.view';
    case ManageCategories = 'categories.manage';
    case ManagePages = 'pages.manage';
    case ManageContactEnquiries = 'contact_enquiries.manage';
    case ManageUsers = 'users.manage';
    case ViewActivityLog = 'activity_log.view';
    case ViewSuppliers = 'suppliers.view';
    case ManageSuppliers = 'suppliers.manage';
    case PublishSuppliers = 'suppliers.publish';
    case ManageVerification = 'verification.manage';
    case ImportExportSuppliers = 'suppliers.import_export';
    case ManageOutreachTemplates = 'outreach_templates.manage';
}
