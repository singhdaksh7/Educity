<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Same shape as StoreEnquiryRequest, but for authenticated students: name,
 * email, and phone fall back to the student's own profile when omitted, so
 * the student portal doesn't have to force re-typing details already on file.
 */
class StoreStudentEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $this->merge([
            'name' => trim((string) ($this->input('name') ?: $user->name)),
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : $user->email,
            'phone' => trim((string) ($this->input('phone') ?: $user->phone)),
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
