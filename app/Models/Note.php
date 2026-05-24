<?php
// app/Models/Note.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    protected $fillable = [
        'student_id',
        'module_id',
        'semestre_id',
        'academic_year',
        'note_controle',
        'note_examen',
        'note_rattrapage',
        'note_finale',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'note_controle'   => 'decimal:2',
            'note_examen'     => 'decimal:2',
            'note_rattrapage' => 'decimal:2',
            'note_finale'     => 'decimal:2',
            'is_published'    => 'boolean',
            'published_at'    => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(NoteHistory::class);
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Calcule la note finale : Σ(note * coef) / Σ(coef)
     * Priorité : rattrapage > examen ; contrôle toujours inclus
     */
    public function calculateFinale(): float
    {
        $module = $this->module;
        $coef   = $module->coefficient;

        $noteExam = $this->note_rattrapage ?? $this->note_examen;
        $noteCC   = $this->note_controle;

        if ($noteCC !== null && $noteExam !== null) {
            // 40% CC + 60% Examen (règle standard ISCAE)
            return round(($noteCC * 0.4) + ($noteExam * 0.6), 2);
        }

        if ($noteExam !== null) {
            return round((float)$noteExam, 2);
        }

        if ($noteCC !== null) {
            return round((float)$noteCC, 2);
        }

        return 0.00;
    }

    public function updateAndPublish(int $publishedBy): void
    {
        $this->update([
            'note_finale'  => $this->calculateFinale(),
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $publishedBy,
        ]);
    }

    public function isPassed(): bool
    {
        return $this->note_finale !== null && $this->note_finale >= 10;
    }
}
