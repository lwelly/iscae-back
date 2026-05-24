<?php
// app/Models/Semestre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    protected $fillable = [
        'niveau_id',
        'code',
        'label',
        'order_index',
        'academic_year',
        'is_open',
        'open_at',
        'close_at',
    ];

    protected function casts(): array
    {
        return [
            'is_open'      => 'boolean',
            'open_at'      => 'datetime',
            'close_at'     => 'datetime',
            'order_index'  => 'integer',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
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

    public function scopeOpen($query)
    {
        return $query->where('is_open', true)
                     ->where('close_at', '>', now());
    }

    public function scopeCurrentYear($query)
    {
        $year = Setting::getValue('current_academic_year', '2024-2025');
        return $query->where('academic_year', $year);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isCurrentlyOpen(): bool
{
    return (bool) $this->is_open;
}

public function isAcceptingReclamations(): bool
{
    return (bool) $this->is_open;
}

}
