<?php
// app/Models/UserDevice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_name',
        'browser',
        'os',
        'ip_address',
        'is_trusted',
        'trust_token_hash',
        'trusted_at',
        'trusted_until',
        'last_seen_at',
    ];

    protected $hidden = [
        'trust_token_hash',
        'device_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted'   => 'boolean',
            'trusted_at'   => 'datetime',
            'trusted_until'=> 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'device_id');
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isTrusted(): bool
    {
        return $this->is_trusted
            && $this->trusted_until
            && $this->trusted_until->isFuture();
    }

    public function markTrusted(int $days = 30): void
    {
        $this->update([
            'is_trusted'    => true,
            'trusted_at'    => now(),
            'trusted_until' => now()->addDays($days),
            'last_seen_at'  => now(),
        ]);
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
