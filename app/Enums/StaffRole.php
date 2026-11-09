<?php

namespace App\Enums;

/**
 * Staff roles (spatie/laravel-permission). Buyers and suppliers are separated by
 * users.type, not by roles, so a buyer can never be granted admin rights by
 * accidentally attaching a role.
 */
enum StaffRole: string
{
    case Administrator = 'administrator';
    case SourcingManager = 'sourcing_manager';
    case VerificationSpecialist = 'verification_specialist';

    public function label(): string
    {
        return __('enums.staff_role.'.$this->value);
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Administrator => Permission::cases(),
            self::SourcingManager => [
                Permission::ViewAdminDashboard,
                Permission::ViewCategories,
                Permission::ManageContactEnquiries,
                Permission::ViewSuppliers,
                Permission::ManageSuppliers,
                Permission::ImportExportSuppliers,
                Permission::ManageOutreachTemplates,
                Permission::ManageSourcingRequests,
            ],
            self::VerificationSpecialist => [
                Permission::ViewAdminDashboard,
                Permission::ViewCategories,
                Permission::ViewSuppliers,
                Permission::ManageSuppliers,
                Permission::PublishSuppliers,
                Permission::ManageVerification,
            ],
        };
    }
}
