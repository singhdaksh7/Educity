<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Only merge keys that were actually sent — merging an explicit null
        // would make "sometimes" treat the field as present-but-empty and
        // fail "required" for fields the caller never touched.
        $merge = [];

        if ($this->filled('name')) {
            $merge['name'] = trim((string) $this->input('name'));
        }
        if ($this->filled('email')) {
            $merge['email'] = trim((string) $this->input('email'));
        }
        if ($this->has('phone')) {
            $merge['phone'] = $this->filled('phone') ? trim((string) $this->input('phone')) : null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:150',
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => 'sometimes|nullable|string|max:30',
        ];
    }
}
