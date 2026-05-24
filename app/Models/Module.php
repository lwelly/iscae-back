<?php
// app/Models/Module.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'filiere_id',
        'semestre_id',
        'code',
        'name',
        'coefficient',
        'credits',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'coefficient' => 'integer',
            'credits'     => 'integer',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFiliere($query, int $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
    }

    public function scopeForSemestre($query, int $semestreId)
    {
        return $query->where('semestre_id', $semestreId);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function belongsToFiliere(int $filiereId): bool
    {
        return $this->filiere_id === $filiereId;
    }
}
