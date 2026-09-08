<?php

namespace App\Http\Requests;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array|min:1|max:50',
            'settings.*.key' => ['required', 'string', Rule::in(array_keys(SiteSetting::KEYS))],
            'settings.*.value' => 'nullable|string|max:10000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('settings', []) as $index => $setting) {
                $key = $setting['key'] ?? null;
                $value = $setting['value'] ?? null;
                $type = SiteSetting::KEYS[$key] ?? null;

                if (! $value || ! $type) {
                    continue;
                }

                if ($type === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add("settings.$index.value", 'Must be a valid email address.');
                }

                if ($type === 'url' && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    $validator->errors()->add("settings.$index.value", 'Must be a valid URL.');
                }
            }
        });
    }
}
