<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReclamationController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // HELPER – étudiant connecté
    // ══════════════════════════════════════════════════════════════════
    private function getStudent(): ?object
    {
        return DB::table('students')
            ->where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->first();
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER – mapper type frontend → DB
    // ══════════════════════════════════════════════════════════════════
    private function mapTypeToDb(string $type): string
    {
        return match ($type) {
            'cc'         => 'controle',
            'examen'     => 'examen',
            'rattrapage' => 'rattrapage',
            default      => $type,
        };
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER – mapper type DB → frontend
    // ══════════════════════════════════════════════════════════════════
    private function mapTypeFromDb(string $type): string
    {
        return match ($type) {
            'controle'   => 'cc',
            'examen'     => 'examen',
            'rattrapage' => 'rattrapage',
            default      => $type,
        };
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER – formater une réclamation
    // ══════════════════════════════════════════════════════════════════
    private function formatReclamation(object $r): array
    {
        return [
            'id'               => $r->id,
            'reference_number' => $r->reference_number,
            'reference'        => $r->reference_number,   // alias compat frontend
            'type'             => $this->mapTypeFromDb($r->type),
            'type_db'          => $r->type,               // valeur brute DB
            'status'           => $r->status,
            'note_actuelle'    => $r->note_actuelle,
            'note_reclamee'    => $r->note_reclamee,
            'justification'    => $r->justification,
            'admin_response'   => $r->admin_response    ?? null,
            'is_escalated'     => (bool) ($r->is_escalated ?? false),
            'escalation_reason'=> $r->escalation_reason ?? null,
            'academic_year'    => $r->academic_year,
            'created_at'       => $r->created_at,
            'updated_at'       => $r->updated_at,
            'resolved_at'      => $r->resolved_at       ?? null,
            'responded_at'     => $r->responded_at      ?? null,
            'escalated_at'     => $r->escalated_at      ?? null,
            'meeting'          => ($r->meeting_scheduled_at ?? null) ? [
                'scheduled_at' => $r->meeting_scheduled_at,
                'location'     => $r->meeting_location  ?? null,
                'notes'        => $r->meeting_notes     ?? null,
            ] : null,
            'module'           => [
                'id'          => $r->module_id,
                'code'        => $r->module_code         ?? null,
                'name'        => $r->module_name         ?? null,
                'coefficient' => $r->module_coefficient  ?? null,
                'credits'     => $r->module_credits      ?? null,
            ],
            'semestre'         => [
                'id'            => $r->semestre_id,
                'code'          => $r->semestre_code     ?? null,
                'label'         => $r->semestre_label    ?? null,
                'academic_year' => $r->semestre_year     ?? null,
                'is_open'       => (bool) ($r->semestre_is_open ?? false),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER – charger les pièces jointes (colonnes réelles)
    // ══════════════════════════════════════════════════════════════════
   // ══════════════════════════════════════════════════════════════════
// HELPER – charger les pièces jointes (auto-détection des colonnes)
// ══════════════════════════════════════════════════════════════════
private function loadAttachments(int $reclamationId): array
{
    // SELECT * pour éviter toute erreur de colonne inconnue
    $rows = DB::table('reclamation_attachments')
        ->where('reclamation_id', $reclamationId)
        ->get();

    return $rows->map(function ($a) {
        $row = (array) $a;

        // ── Nom du fichier (tester toutes les variantes possibles) ──
        $fileName = $row['original_name']
            ?? $row['file_name']
            ?? $row['filename']
            ?? $row['name']
            ?? $row['stored_name']
            ?? 'Fichier joint';

        // ── Chemin de stockage (tester toutes les variantes) ────────
        $filePath = $row['storage_path']
            ?? $row['file_path']
            ?? $row['path']
            ?? $row['filepath']
            ?? $row['stored_path']
            ?? $row['disk_path']
            ?? null;

        // ── Taille ──────────────────────────────────────────────────
        $fileSize = $row['file_size']
            ?? $row['size']
            ?? $row['filesize']
            ?? 0;

        // ── Type MIME ───────────────────────────────────────────────
        $mimeType = $row['mime_type']
            ?? $row['mimetype']
            ?? $row['content_type']
            ?? $row['type']
            ?? null;

        // ── URL publique ────────────────────────────────────────────
        $url = $row['url'] ?? null;
        if (! $url && $filePath) {
            $disk = $row['disk'] ?? 'public';
            try {
                $url = Storage::disk($disk)->url($filePath);
            } catch (\Throwable $e) {
                $url = '/storage/' . ltrim($filePath, '/');
            }
        }

        return [
            'id'         => $row['id'],
            'file_name'  => $fileName,
            'file_path'  => $filePath,
            'file_size'  => $fileSize,
            'mime_type'  => $mimeType,
            'url'        => $url,
            'created_at' => $row['created_at'] ?? null,
        ];
    })->toArray();
}


    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/student/reclamations
    // ══════════════════════════════════════════════════════════════════
    public function index(Request $request): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        $query = DB::table('reclamations')
            ->join('modules',   'reclamations.module_id',   '=', 'modules.id')
            ->join('semestres', 'reclamations.semestre_id', '=', 'semestres.id')
            ->where('reclamations.student_id', $student->id)
            ->whereNull('reclamations.deleted_at')
            ->select([
                'reclamations.*',
                'modules.name             as module_name',
                'modules.code             as module_code',
                'modules.coefficient      as module_coefficient',
                'modules.credits          as module_credits',
                'semestres.code           as semestre_code',
                'semestres.label          as semestre_label',
                'semestres.academic_year  as semestre_year',
                'semestres.is_open        as semestre_is_open',
            ]);

        // ── Filtre par statut ──────────────────────────────────────────
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('reclamations.status', $request->status);
        }

        // ── Filtre par type ────────────────────────────────────────────
        if ($request->filled('type')) {
            $query->where('reclamations.type', $this->mapTypeToDb($request->type));
        }

        // ── Pagination ─────────────────────────────────────────────────
        $perPage = max(1, (int) $request->get('per_page', 10));
        $page    = max(1, (int) $request->get('page', 1));
        $total   = $query->count();

        $items = $query
            ->orderByDesc('reclamations.created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($r) => $this->formatReclamation($r));

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $perPage)),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/student/reclamations/counts
    // ══════════════════════════════════════════════════════════════════
    public function counts(): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json(['success' => true, 'data' => ['all' => 0]]);
        }

        $rows = DB::table('reclamations')
            ->where('student_id', $student->id)
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $counts = ['all' => 0];
        foreach ($rows as $row) {
            $counts[$row->status]  = (int) $row->total;
            $counts['all']        += (int) $row->total;
        }

        return response()->json(['success' => true, 'data' => $counts]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/student/reclamations/{id}
    // ══════════════════════════════════════════════════════════════════
    public function show($id): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        // ── Réclamation avec jointures ─────────────────────────────────
        $r = DB::table('reclamations')
            ->join('modules',   'reclamations.module_id',   '=', 'modules.id')
            ->join('semestres', 'reclamations.semestre_id', '=', 'semestres.id')
            ->where('reclamations.id',         $id)
            ->where('reclamations.student_id', $student->id)
            ->whereNull('reclamations.deleted_at')
            ->select([
                'reclamations.*',
                'modules.name             as module_name',
                'modules.code             as module_code',
                'modules.coefficient      as module_coefficient',
                'modules.credits          as module_credits',
                'semestres.code           as semestre_code',
                'semestres.label          as semestre_label',
                'semestres.academic_year  as semestre_year',
                'semestres.is_open        as semestre_is_open',
            ])
            ->first();

        if (! $r) {
            Log::warning('[ReclamationShow] introuvable', [
                'id'         => $id,
                'student_id' => $student->id,
                'user_id'    => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Réclamation introuvable.',
            ], 404);
        }

        // ── Historique ─────────────────────────────────────────────────
        $history = DB::table('reclamation_history as h')
            ->where('h.reclamation_id', $id)
            ->leftJoin('users as u', 'h.changed_by', '=', 'u.id')
            ->select([
                'h.id',
                'h.old_status',
                'h.new_status',
                'h.comment',
                'h.created_at',
                DB::raw("
                    CASE
                        WHEN u.role IN ('admin','super_admin') THEN 'Administration'
                        WHEN u.role = 'student'               THEN 'Étudiant'
                        ELSE 'Système'
                    END as changed_by_label
                "),
            ])
            ->orderBy('h.created_at', 'asc')
            ->get()
            ->map(fn ($h) => [
                'id'              => $h->id,
                'old_status'      => $h->old_status,
                'new_status'      => $h->new_status,
                'comment'         => $h->comment,
                'created_at'      => $h->created_at,
                'changed_by_label'=> $h->changed_by_label ?? 'Système',
            ]);

        // ── Pièces jointes ─────────────────────────────────────────────
        $attachments = $this->loadAttachments((int) $id);

        // ── Réponse finale ─────────────────────────────────────────────
        $formatted                 = $this->formatReclamation($r);
        $formatted['history']      = $history;
        $formatted['attachments']  = $attachments;

        return response()->json([
            'success' => true,
            'data'    => $formatted,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/v1/student/reclamations
    // ══════════════════════════════════════════════════════════════════
    public function store(Request $request): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        // ── Validation ─────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'semestre_id'   => 'required|integer|exists:semestres,id',
            'module_id'     => 'required|integer|exists:modules,id',
            'type'          => 'required|in:cc,examen,rattrapage',
            'note_actuelle' => 'required|numeric|min:0|max:20',
            'note_reclamee' => 'nullable|numeric|min:0|max:20',
            'justification' => 'required|string|min:10|max:2000',
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ── Note réclamée obligatoire pour CC ──────────────────────────
        if ($request->type === 'cc' && is_null($request->note_reclamee)) {
            return response()->json([
                'success' => false,
                'message' => 'La note réclamée est obligatoire pour un Contrôle Continu.',
                'errors'  => ['note_reclamee' => ['Champ obligatoire pour un CC.']],
            ], 422);
        }

        // ── Vérifier que le semestre est ouvert ────────────────────────
        $semestre = DB::table('semestres')
            ->where('id', $request->semestre_id)
            ->whereNull('deleted_at')
            ->first();

        if (! $semestre) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable.',
            ], 404);
        }

        $openFlag = match ($request->type) {
            'cc'         => (bool) $semestre->is_open,
            'examen'     => (bool) $semestre->is_exam_open,
            'rattrapage' => (bool) $semestre->is_rattrapage_open,
            default      => false,
        };

        if (! $openFlag) {
            $typeLabel = match ($request->type) {
                'cc'         => 'Contrôle Continu',
                'examen'     => 'Examen',
                'rattrapage' => 'Rattrapage',
                default      => $request->type,
            };
            return response()->json([
                'success' => false,
                'message' => "Les réclamations de type « {$typeLabel} » ne sont pas ouvertes pour ce semestre.",
            ], 422);
        }

        // ── Vérifier doublon actif ─────────────────────────────────────
        $dbType   = $this->mapTypeToDb($request->type);
        $existing = DB::table('reclamations')
            ->where('student_id',  $student->id)
            ->where('module_id',   $request->module_id)
            ->where('semestre_id', $request->semestre_id)
            ->where('type',        $dbType)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->whereNull('deleted_at')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une réclamation active pour ce module et ce type.',
            ], 409);
        }

        // ── Référence unique ───────────────────────────────────────────
        $year      = date('Y');
        $count     = DB::table('reclamations')->whereYear('created_at', $year)->count() + 1;
        $reference = 'RECL-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

        // ── Transaction ────────────────────────────────────────────────
        DB::beginTransaction();
        try {
            // 1. Insérer la réclamation
            $reclamationId = DB::table('reclamations')->insertGetId([
                'reference_number' => $reference,
                'student_id'       => $student->id,
                'module_id'        => $request->module_id,
                'semestre_id'      => $request->semestre_id,
                'academic_year'    => $student->academic_year
                                        ?? ($year . '-' . ($year + 1)),
                'type'             => $dbType,
                'note_actuelle'    => $request->note_actuelle,
                'note_reclamee'    => $request->type === 'cc'
                                        ? $request->note_reclamee
                                        : null,
                'justification'    => $request->justification,
                'status'           => 'submitted',
                'is_escalated'     => 0,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now(),
            ]);

            // 2. Historique initial
            DB::table('reclamation_history')->insert([
                'reclamation_id' => $reclamationId,
                'old_status'     => null,
                'new_status'     => 'submitted',
                'comment'        => "Réclamation soumise par l'étudiant.",
                'changed_by'     => Auth::id(),   // ← user_id (pas student_id)
                'ip_address'     => $request->ip(),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);

            // 3. Pièce jointe optionnelle
            if ($request->hasFile('document') && $request->file('document')->isValid()) {
                $file        = $request->file('document');
                $storedName  = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $storagePath = $file->storeAs('reclamations/attachments', $storedName, 'public');

                DB::table('reclamation_attachments')->insert([
                    'reclamation_id' => $reclamationId,
                    'original_name'  => $file->getClientOriginalName(),
                    'stored_name'    => $storedName,
                    'storage_path'   => $storagePath,
                    'mime_type'      => $file->getMimeType(),
                    'file_size'      => $file->getSize(),
                    'is_scanned'     => 0,
                    'is_safe'        => 1,
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation soumise avec succès.',
                'data'    => [
                    'id'               => $reclamationId,
                    'reference_number' => $reference,
                    'reference'        => $reference,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[ReclamationStore] Erreur: ' . $e->getMessage(), [
                'student_id'  => $student->id,
                'module_id'   => $request->module_id,
                'semestre_id' => $request->semestre_id,
                'type'        => $request->type,
                'trace'       => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => app()->isLocal()
                    ? 'Erreur : ' . $e->getMessage()
                    : 'Une erreur est survenue lors de la soumission.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // PUT /api/v1/student/reclamations/{id}
    // ══════════════════════════════════════════════════════════════════
    public function update(Request $request, $id): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        $reclamation = DB::table('reclamations')
            ->where('id',         $id)
            ->where('student_id', $student->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $reclamation) {
            return response()->json([
                'success' => false,
                'message' => 'Réclamation introuvable.',
            ], 404);
        }

        if ($reclamation->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réclamations au statut « soumis » peuvent être modifiées.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'note_reclamee' => 'nullable|numeric|min:0|max:20',
            'justification' => 'nullable|string|min:10|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updateData = ['updated_at' => Carbon::now()];
        if ($request->filled('note_reclamee')) {
            $updateData['note_reclamee'] = $request->note_reclamee;
        }
        if ($request->filled('justification')) {
            $updateData['justification'] = $request->justification;
        }

        DB::table('reclamations')->where('id', $id)->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Réclamation mise à jour avec succès.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // DELETE /api/v1/student/reclamations/{id}  (annulation)
    // ══════════════════════════════════════════════════════════════════
    public function destroy($id): JsonResponse
    {
        $student = $this->getStudent();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        $reclamation = DB::table('reclamations')
            ->where('id',         $id)
            ->where('student_id', $student->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $reclamation) {
            return response()->json([
                'success' => false,
                'message' => 'Réclamation introuvable.',
            ], 404);
        }

        if (! in_array($reclamation->status, ['submitted', 'received'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une réclamation déjà en cours de traitement.',
            ], 422);
        }

        DB::table('reclamations')->where('id', $id)->update([
            'deleted_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réclamation annulée avec succès.',
        ]);
    }
}
