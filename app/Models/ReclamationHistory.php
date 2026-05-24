<?php
// app/Models/ReclamationHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamationHistory extends Model
{
    protected $table = 'reclamation_history';

    public $timestamps = false;

    protected $fillable = [
        'reclamation_id',
        'changed_by',
        'old_status',
        'new_status',
        'comment',
        'ip_address',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
