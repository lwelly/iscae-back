<?php
// app/Models/Filiere.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
