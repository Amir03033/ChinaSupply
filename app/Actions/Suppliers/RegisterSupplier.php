<?php

namespace App\Actions\Suppliers;

use App\Enums\SupplierPipelineStatus;
use App\Enums\SupplierSource;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterSupplier
{
    /**
     * @param  array{company_name: string, country: string, name: string, email: string, password: string}  $data
     */
    public function handle(array $data, string $locale): User
    {
        return DB::transaction(function () use ($data, $locale) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'locale' => $locale]);
            $user->type = UserType::Supplier;
            $user->save();

            $supplier = app(SaveSupplier::class)->handle(null, [
                'name' => $data['company_name'],
                'country' => $data['country'],
                'source' => SupplierSource::SelfRegistration,
            ]);
            $supplier->pipeline_status = SupplierPipelineStatus::Applied;
            $supplier->save();

            $supplier->users()->attach($user->id, ['role' => 'owner']);
            $supplier->contacts()->create(['name' => $data['name'], 'email' => $data['email'], 'is_primary' => true]);

            event(new Registered($user));

            return $user;
        });
    }
}
