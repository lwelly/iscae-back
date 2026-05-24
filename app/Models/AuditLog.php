<?php
// app/Models/AuditLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_role',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Log rapide depuis n'importe où dans l'application
     */
    public static function log(
        string  $action,
        ?int    $userId    = null,
        ?string $userRole  = null,
        ?string $entityType= null,
        ?int    $entityId  = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        ?string $ip        = null,
        ?string $userAgent = null,
        string  $status    = 'success'
    ): void {
        static::create([
            'user_id'     => $userId,
            'user_role'   => $userRole,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
            'status'      => $status,
            'created_at'  => now(),
        ]);
    }
}
