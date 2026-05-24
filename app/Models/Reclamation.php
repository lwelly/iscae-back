<?php
// app/Models/Reclamation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reclamation extends Model
{
    use SoftDeletes;

    // ==========================================
    // Constantes de statuts
    // ==========================================
    const STATUS_SUBMITTED  = 'submitted';
    const STATUS_RECEIVED   = 'received';
    const STATUS_IN_REVIEW  = 'in_review';
    const STATUS_RESOLVED   = 'resolved';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_ESCALATED  = 'escalated';

    const TYPE_CONTROLE     = 'controle';
    const TYPE_EXAMEN       = 'examen';
    const TYPE_RATTRAPAGE   = 'rattrapage';

    protected $fillable = [
        'reference_number',
        'student_id',
        'module_id',
        'semestre_id',
        'note_id',
        'academic_year',
        'type',
        'note_actuelle',
        'note_reclamee',
        'justification',
        'status',
        'is_escalated',
        'escalated_at',
        'escalated_to',
        'assigned_to',
        'admin_response',
        'responded_by',
        'responded_at',
        'meeting_scheduled_at',
        'meeting_location',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'note_actuelle'        => 'decimal:2',
            'note_reclamee'        => 'decimal:2',
            'is_escalated'         => 'boolean',
            'escalated_at'         => 'datetime',
            'responded_at'         => 'datetime',
            'meeting_scheduled_at' => 'datetime',
            'resolved_at'          => 'datetime',
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

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function escalatedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'escalated_to');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'responded_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReclamationHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReclamationAttachment::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_RECEIVED,
            self::STATUS_IN_REVIEW,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_REJECTED,
        ]);
    }

    public function scopeEscalated($query)
    {
        return $query->where('is_escalated', true);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_RECEIVED,
            self::STATUS_IN_REVIEW,
        ]);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_RESOLVED,
            self::STATUS_REJECTED,
        ]);
    }

    public function canBeUpdated(): bool
    {
        return !$this->isClosed();
    }

    /**
     * Génère un numéro de référence unique
     */
    public static function generateReference(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('RECL-%d-%06d', $year, $count);
    }

    /**
     * Change le statut et enregistre dans l'historique
     */
   /**
 * Change le statut et enregistre dans l'historique
 */
public function changeStatus(
    string $newStatus,
    User   $changedBy,
    ?string $comment = null,
    ?string $ip = null
): void {
    $oldStatus = $this->status;

    $this->update([
        'status'      => $newStatus,
        'resolved_at' => in_array($newStatus, [
            self::STATUS_RESOLVED,
            self::STATUS_REJECTED,
        ]) ? now() : $this->resolved_at,
    ]);

    ReclamationHistory::create([
        'reclamation_id' => $this->id,
        'changed_by'     => $changedBy->id,   // ✅ $changedBy est un User valide
        'old_status'     => $oldStatus,
        'new_status'     => $newStatus,
        'comment'        => $comment,
        'ip_address'     => $ip,
        'created_at'     => now(),             // ✅ corrigé : plus 'changed_at'
    ]);
}

}
