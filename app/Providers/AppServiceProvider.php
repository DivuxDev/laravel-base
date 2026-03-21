<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Default API rate limiter (used by throttle:api middleware)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Registration endpoint: 5 attempts per minute per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Admin endpoints: 30 requests per minute per authenticated user
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Authenticated user endpoints: 60 requests per minute per user or IP
        RateLimiter::for('user-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->logAppStart();
    }

    /**
     * Log structured information when the application boots.
     * Runs once per process start (not on every request in FPM/Apache).
     */
    private function logAppStart(): void
    {
        Log::channel('app')->info('[APP START]', [
            'app'       => config('app.name'),
            'env'       => config('app.env'),
            'debug'     => config('app.debug'),
            'php'       => PHP_VERSION,
            'server_ip' => gethostbyname((string) gethostname()),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
