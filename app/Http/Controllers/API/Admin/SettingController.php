<?php
// app/Http/Controllers/API/Admin/SettingController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // Helper : cast la valeur selon le type stocké
    // ─────────────────────────────────────────────────────────
    private function castValue(string $type, mixed $raw): mixed
    {
        return match ($type) {
            'integer' => (int) $raw,
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($raw, true) ?? [],
            default   => (string) $raw,
        };
    }

    // ─────────────────────────────────────────────────────────
    // Helper : sérialise la valeur avant écriture en base
    // ─────────────────────────────────────────────────────────
    private function serializeValue(string $type, mixed $value): string
    {
        if ($type === 'json') {
            return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        return (string) $value;
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/v1/admin/settings
    // ─────────────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $rows = DB::table('settings')
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        // Grouper par "group" et caster les valeurs
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->group][] = [
                'id'        => $row->id,
                'key'       => $row->key,
                'value'     => $this->castValue($row->type, $row->value),
                'type'      => $row->type,
                'label'     => $row->label,
                'is_public' => (bool) $row->is_public,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $grouped,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/v1/admin/settings
    // Reçoit { settings: { key: value, ... } }
    // ─────────────────────────────────────────────────────────
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings'   => 'required|array',
            'settings.*' => 'present',
        ]);

        $adminId  = Auth::id();
        $payload  = $request->input('settings');
        $updated  = [];
        $errors   = [];

        foreach ($payload as $key => $newValue) {
            $row = DB::table('settings')->where('key', $key)->first();

            if (!$row) {
                $errors[] = "Clé inconnue : {$key}";
                continue;
            }

            $serialized = $this->serializeValue($row->type, $newValue);

            DB::table('settings')
                ->where('key', $key)
                ->update([
                    'value'      => $serialized,
                    'updated_by' => $adminId,
                    'updated_at' => now(),
                ]);

            $updated[] = $key;
        }

        if (!empty($errors) && empty($updated)) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $errors),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($updated) . ' paramètre(s) mis à jour avec succès.',
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }
}
