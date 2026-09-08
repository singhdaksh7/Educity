<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:200',
            'slug' => ['sometimes', 'alpha_dash', 'max:220', Rule::unique('programs', 'slug')->ignore($this->route('program'))],
            'short_description' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'degree_type' => 'sometimes|string|max:120',
            'duration' => 'nullable|string|max:120',
            'eligibility' => 'nullable|string',
            'display_order' => 'sometimes|integer|min:0',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
