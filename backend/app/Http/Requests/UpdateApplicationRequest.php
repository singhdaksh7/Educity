<?php

namespace App\Http\Requests;

use App\Models\AdmissionApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(AdmissionApplication::STATUSES)],
            'admin_notes' => 'nullable|string|max:5000',
        ];
    }
}
