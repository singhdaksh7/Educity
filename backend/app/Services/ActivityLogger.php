<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Fields that must never be persisted in activity log metadata, even
     * if a caller accidentally includes them.
     */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'token',
        'remember_token', 'bearer_token', 'authorization', 'secret',
        'mail_password', 'smtp_password', 'api_key',
    ];

    public static function log(Request $request, string $action, string $type, ?int $id, string $description, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'resource_type' => $type,
            'resource_id' => $id,
            'description' => $description,
            'metadata' => self::sanitize($metadata),
            'ip_address' => $request->ip(),
        ]);
    }

    private static function sanitize(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $metadata[$key] = self::sanitize($value);

                continue;
            }

            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (stripos((string) $key, $sensitive) !== false) {
                    unset($metadata[$key]);

                    break;
                }
            }
        }

        return $metadata;
    }
}
