<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id', 'code', 'type',
        'ip_address', 'user_agent',
        'is_used', 'attempts', 'expires_at',
    ];

    protected $casts = [
        'is_used'    => 'boolean',
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->is_used
            && $this->attempts < 5
            && $this->expires_at->isFuture();
    }
}
