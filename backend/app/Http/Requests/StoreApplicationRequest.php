<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'email' => trim((string) $this->input('email')),
            'phone' => trim((string) $this->input('phone')),
            'previous_qualification' => $this->filled('previous_qualification') ? trim((string) $this->input('previous_qualification')) : null,
            'message' => $this->filled('message') ? trim((string) $this->input('message')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:150',
            'email' => 'required|email:rfc|max:255',
            'phone' => 'required|string|max:30',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'program_id' => 'required|integer|exists:programs,id',
            'previous_qualification' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:4000',
            // Honeypot: real users never populate this hidden field.
            'website' => 'nullable|size:0',
        ];
    }
}
