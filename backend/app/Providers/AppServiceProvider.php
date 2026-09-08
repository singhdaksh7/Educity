<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Permissions each admin role is granted. super_admin bypasses this
     * entirely via Gate::before and always has full access.
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'dashboard.view',
            'enquiries.manage',
            'applications.manage',
            'programs.manage',
            'testimonials.manage',
            'gallery.manage',
            'settings.manage',
            'records.restore',
        ],
        'content_manager' => [
            'programs.manage',
            'testimonials.manage',
            'gallery.manage',
            'settings.manage-public',
        ],
        'enquiry_manager' => [
            'enquiries.manage',
            'applications.manage',
        ],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // This backend is API-only and has no "login" web route to redirect
        // guests to; always respond with the JSON 401 handled in bootstrap/app.php.
        Authenticate::redirectUsing(fn () => null);

        Gate::before(function ($user, string $ability) {
            return $user->role === 'super_admin' ? true : null;
        });

        $abilities = array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS)));

        foreach ($abilities as $ability) {
            Gate::define($ability, function ($user) use ($ability) {
                return in_array($ability, self::ROLE_PERMISSIONS[$user->role] ?? [], true);
            });
        }

        // super_admin only, enforced solely through Gate::before above.
        Gate::define('logs.view', fn ($user) => false);
        Gate::define('records.forceDelete', fn ($user) => false);

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
