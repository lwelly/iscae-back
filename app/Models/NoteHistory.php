<?php
// app/Models/NoteHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteHistory extends Model
{
    protected $table = 'notes_history';

    public $timestamps = false;

    protected $fillable = [
        'note_id',
        'changed_by',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
