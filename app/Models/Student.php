<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'preloaded_id',
        'filiere_id',
        'niveau_id',
        'matricule',
        'nom',
        'prenom',
        'email',
        'date_naissance',
        'lieu_naissance',
        'nni',
        'phone',
        'address',
        'photo_path',
        'academic_year',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    // ── Relations ──────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function preloaded()
    {
        return $this->belongsTo(StudentPreloaded::class, 'preloaded_id');
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function reclamations()
    {
        return $this->hasMany(Reclamation::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // ── Accesseurs ─────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? asset('storage/' . $this->photo_path)
            : null;
    }

    // ── Helpers ────────────────────────────────────────────
    public function getAllowedSemestreCodes(): array
    {
        return match($this->niveau?->code) {
            'L1' => ['S1', 'S2'],
            'L2' => ['S3', 'S4'],
            'L3' => ['S5', 'S6'],
            default => [],
        };
    }

    public function canAccessModule($module): bool
{
    $moduleId = (is_object($module) && isset($module->id)) ? $module->id : (int) $module;
    $moduleObj = \App\Models\Module::find($moduleId);
    if (!$moduleObj) return false;

    $allowedCodes = $this->getAllowedSemestreCodes();

    return $moduleObj->filiere_id === $this->filiere_id
        && in_array($moduleObj->semestre?->code, $allowedCodes);
}

public function canSubmitReclamation($module): bool
{
    $moduleId = (is_object($module) && isset($module->id)) ? $module->id : (int) $module;
    $moduleObj = \App\Models\Module::find($moduleId);
    if (!$moduleObj || !$moduleObj->semestre) return false;

    return $this->canAccessModule($moduleObj)
        && $moduleObj->semestre->isAcceptingReclamations();
}
}