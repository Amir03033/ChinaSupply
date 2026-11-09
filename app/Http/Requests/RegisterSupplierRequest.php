<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\SupplierOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:190'],
            'country' => ['required', Rule::in(array_keys(SupplierOptions::countries()))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'agreement' => ['accepted'],
            'website_url' => ['prohibited'], // honeypot
        ];
    }

    public function attributes(): array
    {
        return trans('portal.register.attributes');
    }
}
