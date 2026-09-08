<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:3000',
            'alt_text' => 'required|string|max:255',
            'display_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
