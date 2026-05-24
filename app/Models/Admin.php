<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model
{
    use SoftDeletes;

    protected $table = 'admins';

    protected $fillable = [
        'user_id',
        'department_id',
        'nom',
        'prenom',
        'role_label',
        'two_fa_enabled',
        'two_fa_reask_days',
        'last_two_fa_at',
    ];

    protected $casts = [
        'two_fa_enabled'  => 'boolean',
        'last_two_fa_at'  => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // ── Accesseur nom complet ──────────────────────────────
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    // ── Accesseur role_label lisible ───────────────────────
    public function getRoleLabelReadableAttribute(): string
    {
        return match($this->role_label) {
            'super_admin'      => 'Super Administrateur',
            'department_head'  => 'Chef de Département',
            'secretary'        => 'Secrétaire',
            default            => $this->role_label,
        };
    }

    // ── Helpers ────────────────────────────────────────────
    public function requires2FA(): bool
    {
        return (bool) $this->two_fa_enabled;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role_label === 'super_admin';
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeSuperAdmins($query)
    {
        return $query->where('role_label', 'super_admin');
    }

    public function scopeDepartmentHeads($query)
    {
        return $query->where('role_label', 'department_head');
    }
}
