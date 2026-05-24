<?php
// app/Models/Niveau.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    protected $table = 'niveaux';

    protected $fillable = [
        'code',
        'label',
        'order_index',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function semestres(): HasMany
    {
        return $this->hasMany(Semestre::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function getSemestreCodes(): array
    {
        // L1 → [S1,S2] | L2 → [S3,S4] | L3 → [S5,S6]
        return match($this->code) {
            'L1' => ['S1', 'S2'],
            'L2' => ['S3', 'S4'],
            'L3' => ['S5', 'S6'],
            default => [],
        };
    }
}
