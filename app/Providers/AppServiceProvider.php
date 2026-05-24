<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

// Services
use App\Services\OtpService;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\AuthService;
use App\Services\ReclamationService;

// Observer
use App\Models\Reclamation;
use App\Observers\ReclamationObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OtpService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(NotificationService::class);

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(OtpService::class),
                $app->make(AuditService::class),
                $app->make(NotificationService::class)
            );
        });

        $this->app->singleton(ReclamationService::class, function ($app) {
            return new ReclamationService(
                $app->make(AuditService::class),
                $app->make(NotificationService::class)
            );
        });
    }

    public function boot(): void
    {
        // ── Observer Réclamations ──────────────────────────────────────
        // Déclenche NotificationService automatiquement à chaque update
        Reclamation::observe(ReclamationObserver::class);

        // ── Rate Limiters ─────────────────────────────────────────────

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn() => response()->json([
                    'success' => false,
                    'message' => 'Trop de requêtes. Veuillez réessayer dans 1 minute.',
                    'code'    => 'RATE_LIMIT_EXCEEDED',
                ], 429));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn() => response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives de connexion. Réessayez dans 1 minute.',
                    'code'    => 'LOGIN_RATE_LIMIT',
                ], 429));
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn() => response()->json([
                    'success' => false,
                    'message' => 'Trop de demandes OTP. Réessayez dans 1 minute.',
                    'code'    => 'OTP_RATE_LIMIT',
                ], 429));
        });
    }
}
