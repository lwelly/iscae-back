<?php
// routes/console.php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\OtpCode;
use App\Models\LoginAttempt;
use App\Models\Semestre;
use App\Models\UserSession;

// ============================================================
// Commande inspire (défaut Laravel)
// ============================================================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// TÂCHES PLANIFIÉES
// ============================================================

// Nettoyer les OTP expirés — toutes les heures
Schedule::call(function () {
    $deleted = OtpCode::where('expires_at', '<', now())
                      ->orWhere('is_used', true)
                      ->where('created_at', '<', now()->subDays(1))
                      ->delete();

    logger()->info("[CRON] OTP nettoyés : {$deleted}");
})->hourly()->name('otp:cleanup')->withoutOverlapping();

// Nettoyer les tentatives de connexion anciennes — quotidien
Schedule::call(function () {
    $deleted = LoginAttempt::where('attempted_at', '<', now()->subDays(30))->delete();
    logger()->info("[CRON] LoginAttempts nettoyés : {$deleted}");
})->daily()->name('login-attempts:cleanup')->withoutOverlapping();

// Fermer les semestres dont la date est passée — toutes les heures
Schedule::call(function () {
    $closed = Semestre::where('is_open', true)
                      ->where('close_at', '<', now())
                      ->update(['is_open' => false]);

    if ($closed > 0) {
        logger()->info("[CRON] Semestres fermés automatiquement : {$closed}");
    }
})->hourly()->name('semestres:auto-close')->withoutOverlapping();

// Nettoyer les sessions expirées — toutes les 6 heures
Schedule::call(function () {
    $deleted = UserSession::where('is_active', false)
                          ->where('updated_at', '<', now()->subDays(7))
                          ->delete();

    $expired = UserSession::where('is_active', true)
                          ->where('expires_at', '<', now())
                          ->update(['is_active' => false]);

    logger()->info("[CRON] Sessions nettoyées : supprimées={$deleted}, expirées={$expired}");
})->everySixHours()->name('sessions:cleanup')->withoutOverlapping();
