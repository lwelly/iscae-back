<?php
// app/Http/Controllers/API/Admin/ProfileController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // Helper : lire une propriété stdClass sans risque (PHP 8.4)
    // ─────────────────────────────────────────────────────────
    private function prop(mixed $obj, string $key, mixed $default = null): mixed
    {
        if ($obj === null) return $default;
        return property_exists($obj, $key) ? $obj->$key : $default;
    }

    // ─────────────────────────────────────────────────────────
    // GET /api/v1/admin/profile
    // ─────────────────────────────────────────────────────────
    public function show(): JsonResponse
    {
        $user  = Auth::user();
        $admin = DB::table('admins')->where('user_id', $user->id)->first();

        // Construire l'URL publique de la photo
        $photoPath = $this->prop($admin, 'photo_path');
        $photoUrl  = null;
        if ($photoPath) {
            $photoUrl = str_starts_with($photoPath, 'http')
                ? $photoPath
                : Storage::disk('public')->url($photoPath);
        }

        // Construire le full_name proprement
        $firstName = $this->prop($admin, 'first_name', '');
        $lastName  = $this->prop($admin, 'last_name',  '');
        $fullName  = trim($firstName . ' ' . $lastName) ?: null;

        return response()->json([
            'success' => true,
            'data'    => [
                // ── Champs users ──────────────────────────────
                'id'                  => $user->id,
                'email'               => $user->email,
                'login_identifier'    => $user->login_identifier    ?? null,
                'role'                => $user->role                 ?? null,
                'is_active'           => (bool) ($user->is_active    ?? true),
                'last_login_at'       => $user->last_login_at        ?? null,
                'password_changed_at' => $user->password_changed_at  ?? null,
                'created_at'          => $user->created_at            ?? null,

                // ── Champs admins ─────────────────────────────
                'admin' => $admin ? [
                    'id'                      => $this->prop($admin, 'id'),
                    'employee_id'             => $this->prop($admin, 'employee_id'),
                    'department_id'           => $this->prop($admin, 'department_id'),
                    'first_name'              => $firstName,
                    'last_name'               => $lastName,
                    'full_name'               => $fullName,
                    'email'                   => $this->prop($admin, 'email', $user->email),
                    'phone'                   => $this->prop($admin, 'phone', ''),
                    'photo_path'              => $photoPath,
                    'photo_url'               => $photoUrl,
                    'role_label'              => $this->prop($admin, 'role_label'),
                    'is_department_head'      => (bool) $this->prop($admin, 'is_department_head',      false),
                    'can_approve_escalations' => (bool) $this->prop($admin, 'can_approve_escalations', false),
                    'max_reclamations'        => (int)  $this->prop($admin, 'max_reclamations',        0),
                    'is_active'               => (bool) $this->prop($admin, 'is_active',               true),
                    'last_login_at'           => $this->prop($admin, 'last_login_at'),
                ] : null,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/v1/admin/profile
    // ─────────────────────────────────────────────────────────
    public function update(Request $request): JsonResponse
    {
        $user  = Auth::user();
        $admin = DB::table('admins')->where('user_id', $user->id)->first();

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'email'      => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Mise à jour de users (email uniquement)
            if ($request->filled('email') && $request->email !== $user->email) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => $request->email, 'updated_at' => now()]);
            }

            $adminData = [
                'first_name' => $request->input('first_name', $this->prop($admin, 'first_name', '')),
                'last_name'  => $request->input('last_name',  $this->prop($admin, 'last_name',  '')),
                'phone'      => $request->input('phone',      $this->prop($admin, 'phone',      '')),
                'email'      => $request->input('email',      $this->prop($admin, 'email', $user->email)),
                'updated_at' => now(),
            ];

            if ($admin) {
                DB::table('admins')->where('user_id', $user->id)->update($adminData);
            } else {
                DB::table('admins')->insert(array_merge($adminData, [
                    'user_id'    => $user->id,
                    'created_at' => now(),
                ]));
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('[ProfileController::update] ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/v1/admin/profile/photo
    // ─────────────────────────────────────────────────────────
    public function updatePhoto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user  = Auth::user();
        $admin = DB::table('admins')->where('user_id', $user->id)->first();

        // Supprimer l'ancienne photo si elle existe
        $oldPath = $this->prop($admin, 'photo_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Stocker la nouvelle photo
        $path = $request->file('photo')->store('admins/photos', 'public');

        if ($admin) {
            DB::table('admins')
                ->where('user_id', $user->id)
                ->update(['photo_path' => $path, 'updated_at' => now()]);
        } else {
            DB::table('admins')->insert([
                'user_id'    => $user->id,
                'email'      => $user->email,
                'photo_path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Vérifier que le symlink existe, sinon construire l'URL manuellement
        $photoUrl = Storage::disk('public')->url($path);

        return response()->json([
            'success' => true,
            'message' => 'Photo de profil mise à jour avec succès.',
            'data'    => [
                'photo_url'  => $photoUrl,
                'photo_path' => $path,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // PUT /api/v1/admin/profile/password
    // ─────────────────────────────────────────────────────────
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password'          => 'required|string',
            'new_password'              => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password'            => Hash::make($request->new_password),
            'password_changed_at' => now(),
            'updated_at'          => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }
}
