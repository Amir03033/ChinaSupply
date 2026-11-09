<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserType;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * The admin panel only creates staff accounts. Buyers register themselves;
 * suppliers come through supplier onboarding.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = new User(collect($data)->only(['name', 'email', 'password'])->all());
        $user->type = UserType::Staff;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
