<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /* ─── Helper : lecture sûre d'une propriété stdClass ─────── */
    private function prop(object $obj, string $key, mixed $default = null): mixed
    {
        return property_exists($obj, $key) ? $obj->{$key} : $default;
    }

    /* ─── GET /student/profile ───────────────────────────────── */
    public function show()
    {
        $user    = Auth::user();
        $student = DB::table('students')
            ->leftJoin('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->leftJoin('niveaux',  'students.niveau_id',  '=', 'niveaux.id')
            ->where('students.user_id', $user->id)
            ->whereNull('students.deleted_at')
            ->select(
                'students.*',
                'filieres.name as filiere_name',
                'filieres.code as filiere_code',
                'niveaux.code  as niveau_code',
                'niveaux.label as niveau_label'
            )
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Profil étudiant introuvable.'], 404);
        }

        /* URL photo */
        $photoPath = $this->prop($student, 'photo_path');
        $photoUrl  = null;
        if ($photoPath) {
            $photoUrl = Storage::disk('public')->exists($photoPath)
                ? Storage::disk('public')->url($photoPath)
                : null;
        }

        return response()->json([
            'success' => true,
            'data'    => [
                /* Données utilisateur */
                'id'                  => $user->id,
                'email'               => $user->email,
                'role'                => $user->role,
                'is_active'           => (bool) $user->is_active,
                'last_login_at'       => $user->last_login_at,
                'password_changed_at' => $this->prop($user, 'password_changed_at'),

                /* Données étudiant */
                'student' => [
                    'id'             => $student->id,
                    'matricule'      => $this->prop($student, 'matricule'),
                    'nni'            => $this->prop($student, 'nni'),
                    'nom'            => $this->prop($student, 'nom'),
                    'prenom'         => $this->prop($student, 'prenom'),
                    'email'          => $this->prop($student, 'email'),
                    'phone'          => $this->prop($student, 'phone'),        // ✅ phone (pas telephone)
                    'date_naissance' => $this->prop($student, 'date_naissance'),
                    'lieu_naissance' => $this->prop($student, 'lieu_naissance'),
                    'nationalite'    => $this->prop($student, 'nationalite'),
                    'adresse'        => $this->prop($student, 'adresse'),
                    'academic_year'  => $this->prop($student, 'academic_year'),
                    'status'         => $this->prop($student, 'status'),
                    'photo_path'     => $photoPath,
                    'photo_url'      => $photoUrl,

                    /* Relations imbriquées (format attendu par le Vue) */
                    'filiere' => [
                        'name' => $this->prop($student, 'filiere_name'),
                        'code' => $this->prop($student, 'filiere_code'),
                    ],
                    'niveau' => [
                        'code'  => $this->prop($student, 'niveau_code'),
                        'label' => $this->prop($student, 'niveau_label'),
                    ],
                ],
            ],
        ]);
    }

    /* ─── PUT /student/profile ───────────────────────────────── */
    public function update(Request $request)
    {
        $user    = Auth::user();
        $student = DB::table('students')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Profil introuvable.'], 404);
        }

        $validated = $request->validate([
            'prenom'         => 'sometimes|string|max:100',
            'nom'            => 'sometimes|string|max:100',
            'email'          => 'sometimes|email|max:191|unique:users,email,' . $user->id,
            'phone'          => 'nullable|string|max:30',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite'    => 'nullable|string|max:100',
            'adresse'        => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            /* Mise à jour table students */
            $studentData = array_filter([
                'prenom'         => $validated['prenom']         ?? null,
                'nom'            => $validated['nom']            ?? null,
                'phone'          => $validated['phone']          ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                'nationalite'    => $validated['nationalite']    ?? null,
                'adresse'        => $validated['adresse']        ?? null,
                'updated_at'     => now(),
            ], fn($v) => !is_null($v));

            if (!empty($studentData)) {
                DB::table('students')
                    ->where('user_id', $user->id)
                    ->update($studentData);
            }

            /* Mise à jour email dans users */
            if (!empty($validated['email']) && $validated['email'] !== $user->email) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'email'      => $validated['email'],
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('[Student ProfileController] update error: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la mise à jour.'], 500);
        }
    }

    /* ─── POST /student/profile/photo ────────────────────────── */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,webp|max:3072',
        ]);

        $user    = Auth::user();
        $student = DB::table('students')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Profil introuvable.'], 404);
        }

        /* Supprimer l'ancienne photo */
        $oldPath = $this->prop($student, 'photo_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        /* Stocker la nouvelle */
        $path    = $request->file('photo')->store('students/photos', 'public');
        $photoUrl = Storage::disk('public')->url($path);

        DB::table('students')
            ->where('user_id', $user->id)
            ->update([
                'photo_path' => $path,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Photo mise à jour avec succès.',
            'data'     => [
                'photo_path' => $path,
                'photo_url'  => $photoUrl,
            ],
        ]);
    }

    /* ─── PUT /student/profile/password ──────────────────────── */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
                'errors'  => ['current_password' => ['Mot de passe incorrect.']],
            ], 422);
        }

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password'            => Hash::make($request->password),
                'password_changed_at' => now(),
                'updated_at'          => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }
}
