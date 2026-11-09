<?php

namespace App\Enums;

enum UserType: string
{
    case Buyer = 'buyer';
    case Supplier = 'supplier';
    case Staff = 'staff';

    public function label(): string
    {
        return __('enums.user_type.'.$this->value);
    }
}
