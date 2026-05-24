<?php
// app/Models/LoginAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'login_identifier',
        'ip_address',
        'user_agent',
        'is_successful',
        'failure_reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'attempted_at'  => 'datetime',
        ];
    }

    // ==========================================
    // Helpers
    // ==========================================

    public static function recordAttempt(
        string  $identifier,
        string  $ip,
        ?string $userAgent,
        bool    $success,
        ?string $reason = null
    ): void {
        static::create([
            'login_identifier' => $identifier,
            'ip_address'       => $ip,
            'user_agent'       => $userAgent,
            'is_successful'    => $success,
            'failure_reason'   => $reason,
            'attempted_at'     => now(),
        ]);
    }

    /**
     * Compte les échecs récents pour une IP ou un identifiant
     */
    public static function countRecentFailures(
        string $identifier,
        string $ip,
        int    $minutes = 30
    ): int {
        return static::where(function ($q) use ($identifier, $ip) {
                $q->where('login_identifier', $identifier)
                  ->orWhere('ip_address', $ip);
            })
            ->where('is_successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
}
