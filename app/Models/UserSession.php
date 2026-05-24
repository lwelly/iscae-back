<?php
// app/Models/UserSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'user_id',
        'device_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
        'is_active',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'last_activity_at' => 'datetime',
            'expires_at'       => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('expires_at', '>', now());
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function terminate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function refresh(): void
    {
        $lifetime = (int) Setting::getValue('session_lifetime_minutes', 120);
        $this->update([
            'last_activity_at' => now(),
            'expires_at'       => now()->addMinutes($lifetime),
        ]);
    }
}
