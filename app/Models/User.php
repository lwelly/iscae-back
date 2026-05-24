<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'role',
        'login_identifier',
        'email',
        'password',
        'is_active',
        'is_verified',
        'email_verified_at',
        'failed_login_count',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'deleted_at',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_verified'        => 'boolean',
        'email_verified_at'  => 'datetime',
        'locked_until'       => 'datetime',
        'last_login_at'      => 'datetime',
        'failed_login_count' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // ── Accesseurs — nom complet ────────────────────────────
    public function getNameAttribute(): string
    {
        if ($this->role === 'admin' && $this->admin) {
            return trim($this->admin->prenom . ' ' . $this->admin->nom);
        }
        if ($this->role === 'student' && $this->student) {
            return trim($this->student->prenom . ' ' . $this->student->nom);
        }
        return $this->login_identifier;
    }

    // ── Accesseur — status unifié ───────────────────────────
    public function getStatusAttribute(): string
    {
        if (!$this->is_active)                        return 'inactive';
        if ($this->locked_until && $this->isLocked()) return 'locked';
        return 'active';
    }

    // ── Helpers rôle ───────────────────────────────────────
    public function isStudent(): bool { return $this->role === 'student'; }
    public function isAdmin(): bool   { return $this->role === 'admin';   }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin'
            && $this->admin
            && $this->admin->role_label === 'super_admin';
    }

    // ── Login lock ─────────────────────────────────────────
    public function incrementFailedLogin(): void
    {
        $max     = (int) Cache::remember('setting_login_max_attempts', 3600,
                        fn() => \App\Models\Setting::getValue('login_max_attempts', 5));
        $lockout = (int) Cache::remember('setting_login_lockout_minutes', 3600,
                        fn() => \App\Models\Setting::getValue('login_lockout_minutes', 30));

        $this->increment('failed_login_count');
        if ($this->failed_login_count >= $max) {
            $this->update(['locked_until' => now()->addMinutes($lockout)]);
        }
    }

    public function resetFailedLogin(): void
    {
        $this->update([
            'failed_login_count' => 0,
            'locked_until'       => null,
            'last_login_at'      => now(),
            'last_login_ip'      => request()->ip(),
        ]);
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('locked_until');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }
}
