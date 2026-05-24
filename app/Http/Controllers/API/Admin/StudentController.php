<?php
// app/Http/Controllers/API/Admin/StudentController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // HELPER — lire propriété stdClass sans risque PHP 8.4
    // ══════════════════════════════════════════════════════════════════
    private function prop(mixed $obj, string $key, mixed $default = null): mixed
    {
        if ($obj === null) return $default;
        return property_exists($obj, $key) ? $obj->$key : $default;
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER — formater un étudiant
    // ══════════════════════════════════════════════════════════════════
    private function formatStudent(object $s): array
    {
        $photoPath = $this->prop($s, 'photo_path');
        $photoUrl  = null;
        if ($photoPath) {
            $photoUrl = str_starts_with($photoPath, 'http')
                ? $photoPath
                : Storage::disk('public')->url($photoPath);
        }

        return [
            'id'             => $this->prop($s, 'id'),
            'user_id'        => $this->prop($s, 'user_id'),
            'matricule'      => $this->prop($s, 'matricule', '—'),
            'nni'            => $this->prop($s, 'nni'),
            'nom'            => $this->prop($s, 'nom', ''),
            'prenom'         => $this->prop($s, 'prenom', ''),
            'full_name'      => trim($this->prop($s, 'prenom', '') . ' ' . $this->prop($s, 'nom', '')),
            'email'          => $this->prop($s, 'email', ''),
            'phone'          => $this->prop($s, 'phone'),
            'photo_path'     => $photoPath,
            'photo_url'      => $photoUrl,
            'filiere_id'     => $this->prop($s, 'filiere_id'),
            'filiere_name'   => $this->prop($s, 'filiere_name', '—'),
            'filiere_code'   => $this->prop($s, 'filiere_code', '—'),
            'niveau_id'      => $this->prop($s, 'niveau_id'),
            'niveau_label'   => $this->prop($s, 'niveau_label', '—'),
            'academic_year'  => $this->prop($s, 'academic_year'),
            'date_naissance' => $this->prop($s, 'date_naissance'),
            'lieu_naissance' => $this->prop($s, 'lieu_naissance'),
            'nationalite'    => $this->prop($s, 'nationalite'),
            'adresse'        => $this->prop($s, 'adresse'),
            'status'         => $this->prop($s, 'status', 'active'),
            'has_account'    => !is_null($this->prop($s, 'user_id')), // ← nouveau
            'is_active'      => (bool) $this->prop($s, 'is_active', false),
            'last_login_at'  => $this->prop($s, 'last_login_at'),
            'created_at'     => $this->prop($s, 'created_at'),
            'reclamations_count'  => (int) $this->prop($s, 'reclamations_count',  0),
            'reclamations_open'   => (int) $this->prop($s, 'reclamations_open',   0),
            'reclamations_closed' => (int) $this->prop($s, 'reclamations_closed', 0),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/admin/students
    // ══════════════════════════════════════════════════════════════════
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $search  = trim($request->input('search', ''));
        $filiere = $request->input('filiere_id');
        $niveau  = $request->input('niveau_id');
        $status  = $request->input('status');
        $year    = $request->input('academic_year');
        $sort    = $request->input('sort', 'created_at');
        $dir     = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['nom', 'prenom', 'matricule', 'created_at', 'last_login_at', 'academic_year'];
        if (!in_array($sort, $allowedSorts)) $sort = 'created_at';

        $query = DB::table('students as s')
            // ✅ leftJoin au lieu de join → inclut les étudiants SANS compte
            ->leftJoin('users as u',    'u.id',  '=', 's.user_id')
            ->leftJoin('filieres as f', 'f.id',  '=', 's.filiere_id')
            ->leftJoin('niveaux as n',  'n.id',  '=', 's.niveau_id')
            ->leftJoinSub(
                DB::table('reclamations')
                    ->whereNull('deleted_at')
                    ->selectRaw('student_id,
                        COUNT(*) as total,
                        SUM(status IN ("submitted","received","in_review","escalated")) as open_count,
                        SUM(status IN ("resolved","rejected")) as closed_count')
                    ->groupBy('student_id'),
                'rc',
                'rc.student_id', '=', 's.id'
            )
            ->whereNull('s.deleted_at')
            ->select(
                's.id', 's.user_id', 's.matricule', 's.nni',
                's.nom', 's.prenom', 's.email', 's.phone',
                's.photo_path', 's.filiere_id', 's.niveau_id',
                's.academic_year', 's.date_naissance', 's.lieu_naissance',
                's.nationalite', 's.adresse', 's.status', 's.created_at',
                'u.is_active', 'u.last_login_at',
                'f.name as filiere_name', 'f.code as filiere_code',
                'n.label as niveau_label',
                DB::raw('COALESCE(rc.total, 0) as reclamations_count'),
                DB::raw('COALESCE(rc.open_count, 0) as reclamations_open'),
                DB::raw('COALESCE(rc.closed_count, 0) as reclamations_closed')
            );

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('s.nom',        'like', $like)
                  ->orWhere('s.prenom',   'like', $like)
                  ->orWhere('s.matricule','like', $like)
                  ->orWhere('s.email',    'like', $like)
                  ->orWhere('s.nni',      'like', $like);
            });
        }
        if ($filiere) $query->where('s.filiere_id', $filiere);
        if ($niveau)  $query->where('s.niveau_id',  $niveau);
        if ($year)    $query->where('s.academic_year', $year);
        if ($status === 'active')   $query->where('u.is_active', 1);
        if ($status === 'inactive') $query->where('u.is_active', 0);

        $sortCol = match ($sort) {
            'last_login_at' => 'u.last_login_at',
            default         => 's.' . $sort,
        };
        $query->orderBy($sortCol, $dir);

        $total    = (clone $query)->count();
        $offset   = ($page - 1) * $perPage;
        $rows     = $query->offset($offset)->limit($perPage)->get();
        $students = $rows->map(fn($s) => $this->formatStudent($s));

        $filieres = DB::table('filieres')->whereNull('deleted_at')->orderBy('name')
                      ->select('id', 'name', 'code')->get();
        $niveaux  = DB::table('niveaux')->orderBy('order_index')
                      ->select('id', 'code', 'label')->get();
        $years    = DB::table('students')->whereNull('deleted_at')
                      ->distinct()->orderByDesc('academic_year')
                      ->pluck('academic_year');

        return response()->json([
            'success' => true,
            'data'    => $students,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
            'filters' => [
                'filieres' => $filieres,
                'niveaux'  => $niveaux,
                'years'    => $years,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/v1/admin/students
    // Créer un étudiant pré-enregistré (sans compte utilisateur)
    // ══════════════════════════════════════════════════════════════════
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'      => 'required|string|max:20|unique:students,matricule',
            'nom'            => 'required|string|max:100',
            'prenom'         => 'required|string|max:100',
            'email'          => 'required|email|max:255|unique:students,email',
            'filiere_id'     => 'required|integer|exists:filieres,id',
            'niveau_id'      => 'required|integer|exists:niveaux,id',
            'academic_year'  => 'required|string|max:20',
            'nni'            => 'nullable|string|max:20',
            'phone'          => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite'    => 'nullable|string|max:50',
            'adresse'        => 'nullable|string|max:255',
        ]);

        // Vérifier doublon matricule/email
        $existingMatricule = DB::table('students')
            ->whereNull('deleted_at')
            ->where('matricule', strtoupper(trim($request->matricule)))
            ->first();

        if ($existingMatricule) {
            return response()->json([
                'success' => false,
                'message' => 'Ce matricule existe déjà.',
            ], 422);
        }

        $existingEmail = DB::table('students')
            ->whereNull('deleted_at')
            ->where('email', strtolower(trim($request->email)))
            ->first();

        if ($existingEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé.',
            ], 422);
        }

        try {
            $id = DB::table('students')->insertGetId([
                'user_id'        => null,         // ← pas de compte encore
                'preloaded_id'   => null,
                'matricule'      => strtoupper(trim($request->matricule)),
                'nom'            => trim($request->nom),
                'prenom'         => trim($request->prenom),
                'email'          => strtolower(trim($request->email)),
                'nni'            => $request->nni     ? trim($request->nni)     : null,
                'phone'          => $request->phone   ? trim($request->phone)   : null,
                'filiere_id'     => $request->filiere_id,
                'niveau_id'      => $request->niveau_id,
                'academic_year'  => $request->academic_year,
                'date_naissance' => $request->date_naissance ?? null,
                'lieu_naissance' => $request->lieu_naissance ?? null,
                'nationalite'    => $request->nationalite    ?? null,
                'adresse'        => $request->adresse        ?? null,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $student = DB::table('students as s')
                ->leftJoin('filieres as f', 'f.id', '=', 's.filiere_id')
                ->leftJoin('niveaux as n',  'n.id', '=', 's.niveau_id')
                ->where('s.id', $id)
                ->select('s.*', 'f.name as filiere_name', 'f.code as filiere_code', 'n.label as niveau_label')
                ->first();

            Log::info('[Admin] Étudiant créé', [
                'student_id' => $id,
                'matricule'  => $request->matricule,
                'email'      => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Étudiant créé avec succès. Il peut maintenant créer son compte.',
                'data'    => $this->formatStudent($student),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('[Admin] Erreur création étudiant', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/admin/students/{id}
    // ══════════════════════════════════════════════════════════════════
    public function show(int $id): JsonResponse
    {
        $s = DB::table('students as s')
            ->leftJoin('users as u',       'u.id',  '=', 's.user_id')
            ->leftJoin('filieres as f',    'f.id',  '=', 's.filiere_id')
            ->leftJoin('niveaux as n',     'n.id',  '=', 's.niveau_id')
            ->whereNull('s.deleted_at')
            ->where('s.id', $id)
            ->select(
                's.*',
                'u.is_active', 'u.last_login_at', 'u.email as user_email',
                'u.failed_login_count', 'u.locked_until',
                'f.name as filiere_name', 'f.code as filiere_code',
                'n.label as niveau_label'
            )
            ->first();

        if (!$s) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable.'], 404);
        }

        $reclamations = DB::table('reclamations as r')
            ->leftJoin('modules as m',    'm.id',  '=', 'r.module_id')
            ->leftJoin('semestres as sm', 'sm.id', '=', 'r.semestre_id')
            ->whereNull('r.deleted_at')
            ->where('r.student_id', $id)
            ->select(
                'r.id', 'r.reference_number', 'r.type', 'r.status',
                'r.note_actuelle', 'r.note_reclamee', 'r.created_at',
                'm.name as module_name', 'sm.label as semestre_label'
            )
            ->orderByDesc('r.created_at')
            ->get();

        $data = $this->formatStudent($s);
        $data['user_email']         = $this->prop($s, 'user_email');
        $data['failed_login_count'] = (int) $this->prop($s, 'failed_login_count', 0);
        $data['locked_until']       = $this->prop($s, 'locked_until');
        $data['reclamations']       = $reclamations;

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PUT /api/v1/admin/students/{id}
    // Mettre à jour les infos d'un étudiant
    // ══════════════════════════════════════════════════════════════════
    public function update(Request $request, int $id): JsonResponse
    {
        $student = DB::table('students')->whereNull('deleted_at')->where('id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable.'], 404);
        }

        $request->validate([
            'nom'            => 'sometimes|string|max:100',
            'prenom'         => 'sometimes|string|max:100',
            'email'          => 'sometimes|email|max:255|unique:students,email,' . $id,
            'matricule'      => 'sometimes|string|max:20|unique:students,matricule,' . $id,
            'filiere_id'     => 'sometimes|integer|exists:filieres,id',
            'niveau_id'      => 'sometimes|integer|exists:niveaux,id',
            'academic_year'  => 'sometimes|string|max:20',
            'nni'            => 'nullable|string|max:20',
            'phone'          => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'nationalite'    => 'nullable|string|max:50',
            'adresse'        => 'nullable|string|max:255',
        ]);

        $updateData = array_filter([
            'nom'            => $request->nom            ? trim($request->nom)    : null,
            'prenom'         => $request->prenom         ? trim($request->prenom) : null,
            'email'          => $request->email          ? strtolower(trim($request->email)) : null,
            'matricule'      => $request->matricule      ? strtoupper(trim($request->matricule)) : null,
            'filiere_id'     => $request->filiere_id,
            'niveau_id'      => $request->niveau_id,
            'academic_year'  => $request->academic_year,
            'nni'            => $request->nni,
            'phone'          => $request->phone,
            'date_naissance' => $request->date_naissance,
            'lieu_naissance' => $request->lieu_naissance,
            'nationalite'    => $request->nationalite,
            'adresse'        => $request->adresse,
            'updated_at'     => now(),
        ], fn($v) => $v !== null);

        DB::table('students')->where('id', $id)->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Étudiant mis à jour avec succès.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PUT /api/v1/admin/students/{id}/status
    // ══════════════════════════════════════════════════════════════════
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_active' => 'required|boolean',
            'reason'    => 'nullable|string|max:500',
        ]);

        $student = DB::table('students')->whereNull('deleted_at')->where('id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable.'], 404);
        }

        if ($student->user_id) {
            DB::table('users')
                ->where('id', $student->user_id)
                ->update([
                    'is_active'  => $request->boolean('is_active'),
                    'updated_at' => now(),
                ]);
        }

        $label = $request->boolean('is_active') ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Compte étudiant {$label} avec succès.",
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // DELETE /api/v1/admin/students/{id}
    // Soft delete
    // ══════════════════════════════════════════════════════════════════
    public function destroy(int $id): JsonResponse
    {
        $student = DB::table('students')->whereNull('deleted_at')->where('id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Étudiant introuvable.'], 404);
        }

        DB::table('students')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Étudiant supprimé avec succès.',
        ]);
    }
}
