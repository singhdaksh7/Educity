<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    /**
     * Strict allowlist of settings keys the backend recognises. Anything
     * outside this list is rejected by UpdateSiteSettingsRequest.
     */
    public const KEYS = [
        'institution_name' => 'text',
        'contact_email' => 'email',
        'contact_phone' => 'text',
        'address' => 'text',
        'hero_title' => 'text',
        'hero_description' => 'text',
        'hero_button_label' => 'text',
        'hero_button_link' => 'url',
        'about_title' => 'text',
        'about_description' => 'text',
        'footer_text' => 'text',
        'facebook_url' => 'url',
        'instagram_url' => 'url',
        'linkedin_url' => 'url',
        'youtube_url' => 'url',
    ];

    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
