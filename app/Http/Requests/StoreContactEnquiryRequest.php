<?php

namespace App\Http\Requests;

use App\Enums\ContactTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', Rule::enum(ContactTopic::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'privacy' => ['accepted'],
            // Honeypot: real users never see or fill this field.
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return trans('contact.attributes');
    }
}
