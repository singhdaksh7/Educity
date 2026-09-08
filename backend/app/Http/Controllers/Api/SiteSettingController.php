<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSiteSettingsRequest;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class SiteSettingController extends Controller
{
    private const PUBLIC_CACHE_KEY = 'site-settings:public';

    public function publicContent(): JsonResponse
    {
        $settings = Cache::remember(self::PUBLIC_CACHE_KEY, 3600, function () {
            return SiteSetting::where('is_public', true)->pluck('value', 'key');
        });

        return response()->json(['success' => true, 'message' => 'Site content retrieved.', 'data' => $settings]);
    }

    public function index(): JsonResponse
    {
        if (! Gate::any(['settings.manage', 'settings.manage-public'])) {
            return response()->json(['success' => false, 'message' => 'You are not authorized for this action.', 'errors' => (object) []], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings retrieved.',
            'data' => SiteSetting::orderBy('key')->get(),
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): JsonResponse
    {
        $canManageAll = Gate::allows('settings.manage');
        $canManagePublic = Gate::allows('settings.manage-public');

        if (! $canManageAll && ! $canManagePublic) {
            return response()->json(['success' => false, 'message' => 'You are not authorized for this action.', 'errors' => (object) []], 403);
        }

        foreach ($request->validated()['settings'] as $setting) {
            $key = $setting['key'];
            $definitionType = SiteSetting::KEYS[$key];
            $existing = SiteSetting::where('key', $key)->first();

            // content_manager may only touch keys that are already public.
            if (! $canManageAll && $existing && ! $existing->is_public) {
                return response()->json([
                    'success' => false,
                    'message' => "You are not authorized to update setting: {$key}.",
                    'errors' => (object) [],
                ], 403);
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $setting['value'] ?? null, 'type' => $definitionType, 'is_public' => $existing?->is_public ?? true]
            );
        }

        Cache::forget(self::PUBLIC_CACHE_KEY);

        ActivityLogger::log($request, 'settings_updated', 'site_settings', null, 'Updated site settings', ['keys' => array_column($request->validated()['settings'], 'key')]);

        return $this->index();
    }
}
