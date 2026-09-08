<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_name' => 'sometimes|string|max:150',
            'course_or_role' => 'nullable|string|max:150',
            'quote' => 'sometimes|string|max:3000',
            'rating' => 'nullable|integer|between:1,5',
            'display_order' => 'sometimes|integer|min:0',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
