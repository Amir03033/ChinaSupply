<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('app:create-admin {--email=} {--name=}')]
#[Description('Create a staff user with a staff role (password asked interactively, never passed as an argument)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?? text('Email', required: true);
        $name = $this->option('name') ?? text('Name', required: true);
        $role = select('Role', collect(StaffRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->value])->all(), StaffRole::Administrator->value);
        $plain = password('Password (min. 12 characters)', required: true);

        $validator = Validator::make(compact('email', 'name') + ['password' => $plain], [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $plain]);
        $user->type = UserType::Staff;
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole($role);

        $this->info("Staff user {$email} created with role {$role}. Set up two-factor authentication at first login.");

        return self::SUCCESS;
    }
}
