<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:200',
            'items.*.id' => 'required|integer',
            'items.*.display_order' => 'required|integer|min:0',
        ];
    }
}
