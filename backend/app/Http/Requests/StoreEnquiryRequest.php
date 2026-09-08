<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'phone' => trim((string) $this->input('phone')),
            'subject' => $this->filled('subject') ? trim((string) $this->input('subject')) : null,
            'message' => trim((string) $this->input('message')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'nullable|email:rfc|max:255',
            'phone' => 'required|string|max:30',
            'subject' => 'nullable|string|max:160',
            'message' => 'required|string|max:4000',
            // Honeypot: real users never populate this hidden field.
            'website' => 'nullable|size:0',
        ];
    }
}
