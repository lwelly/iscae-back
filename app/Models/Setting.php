<?php
// app/Models/Setting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_public',
        'description',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    // ==========================================
    // Helpers statiques (avec cache)
    // ==========================================

    /**
     * Récupère une valeur de configuration avec cast automatique
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return match($setting->type) {
                'integer' => (int)    $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json'    => json_decode($setting->value, true),
                default   => $setting->value,
            };
        });
    }

    /**
     * Met à jour une valeur et invalide le cache
     */
    public static function setValue(
        string $key,
        mixed  $value,
        ?int   $updatedBy = null
    ): void {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value'      => is_array($value) ? json_encode($value) : (string)$value,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]
        );

        Cache::forget("setting_{$key}");
    }

    /**
     * Retourne tous les paramètres publics
     */
    public static function getPublic(): array
    {
        return Cache::remember('settings_public', 3600, function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(fn($s) => [$s->key => $s->value])
                ->toArray();
        });
    }
}
