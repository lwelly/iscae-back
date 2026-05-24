<?php
// app/Models/StudentPreloaded.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentPreloaded extends Model
{
    protected $table = 'students_preloaded';

    protected $fillable = [
        'matricule',
        'nni',
        'nom',
        'prenom',
        'email',
        'filiere_code',
        'niveau_code',
        'academic_year',
        'is_registered',
        'registered_at',
        'import_batch',
        'import_file',
    ];

    protected function casts(): array
    {
        return [
            'is_registered' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'preloaded_id');
    }

    public function otpCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OtpCode::class, 'preloaded_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeNotRegistered($query)
    {
        return $query->where('is_registered', false);
    }

    public function scopeRegistered($query)
    {
        return $query->where('is_registered', true);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function markAsRegistered(): void
    {
        $this->update([
            'is_registered' => true,
            'registered_at' => now(),
        ]);
    }

    public function getFullNameAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Vérifie si les données correspondent (matricule + NNI)
     */
    public static function verifyIdentity(
        string $matricule,
        string $nni,
        string $email
    ): ?self {
        return static::where('matricule', $matricule)
            ->where('nni', $nni)
            ->where('email', $email)
            ->where('is_registered', false)
            ->first();
    }
}
