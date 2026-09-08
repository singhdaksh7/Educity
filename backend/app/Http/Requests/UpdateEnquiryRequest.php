<?php

namespace App\Http\Requests;

use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(Enquiry::STATUSES)],
            'assigned_to' => 'nullable|integer|exists:users,id',
            'admin_notes' => 'nullable|string|max:5000',
        ];
    }
}
