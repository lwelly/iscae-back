<?php
// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'type',
        'title',
        'stored_name',
        'storage_path',
        'mime_type',
        'file_size',
        'academic_year',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'file_size'    => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->storage_path);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}
