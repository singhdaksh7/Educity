<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'slug' => 'nullable|alpha_dash|max:220|unique:programs,slug',
            'short_description' => 'required|string|max:500',
            'description' => 'nullable|string',
            'degree_type' => 'required|string|max:120',
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
