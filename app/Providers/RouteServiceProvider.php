<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // General API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Authentication specific rate limiting
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perDay(50)->by($request->ip()),
            ];
        });

        // Verification code rate limiting
        RateLimiter::for('verification', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
                Limit::perDay(30)->by($request->ip()),
            ];
        });

        // Email sending rate limiting
        RateLimiter::for('mail', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perHour(15)->by($request->ip()),
            ];
        });

        // SMS sending rate limiting
        RateLimiter::for('sms', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
                Limit::perDay(20)->by($request->ip()),
            ];
        });

        // OAuth rate limiting
        RateLimiter::for('oauth', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perHour(50)->by($request->ip()),
            ];
        });

        // Password reset rate limiting
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
