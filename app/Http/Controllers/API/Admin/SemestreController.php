<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class SemestreController extends Controller
{
    // ── Mapping niveau → semestres autorisés ─────────────────────────────────
    private const NIVEAU_SEMESTRES = [
        'L1' => ['S1', 'S2'],
        'L2' => ['S1', 'S2', 'S3', 'S4'],
        'L3' => ['S3', 'S4', 'S5', 'S6'],
    ];

    // ── Admin : tous les semestres ────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $semestres = DB::table('semestres')
            ->orderBy('academic_year', 'desc')
            ->orderBy('order_index', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $semestres]);
    }

    // ── Étudiant : semestres filtrés selon son niveau ─────────────────────────
    public function indexForStudent(Request $request): JsonResponse
    {
        $user = $request->user();

        // Récupérer le niveau de l'étudiant
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        // Récupérer le code du niveau (ex: "L1", "L2", "L3")
        $niveau = DB::table('niveaux')->where('id', $student->niveau_id)->first();

        if (!$niveau) {
            return response()->json([
                'success' => false,
                'message' => 'Niveau introuvable.',
            ], 404);
        }

        $niveauCode = strtoupper(trim($niveau->code));

        // Codes de semestres autorisés pour ce niveau
        $allowedCodes = self::NIVEAU_SEMESTRES[$niveauCode] ?? [];

        if (empty($allowedCodes)) {
            return response()->json([
                'success' => true,
                'data'    => [],
                'message' => "Aucun semestre configuré pour le niveau {$niveauCode}.",
            ]);
        }

        // Récupérer les semestres ouverts correspondant au niveau
        $semestres = DB::table('semestres')
            ->whereIn('code', $allowedCodes)
            ->where(function ($query) {
                $query->where('is_open', true) // Représente le CC
                      ->orWhere('is_exam_open', true)
                      ->orWhere('is_rattrapage_open', true);
            })
            ->orderBy('order_index', 'asc')
            ->get()
            ->map(function ($s) {
                // Calculer les types disponibles selon les ouvertures
                $types = [];
                if ($s->is_open)            $types[] = 'cc';
                if ($s->is_exam_open)       $types[] = 'examen';
                if ($s->is_rattrapage_open) $types[] = 'rattrapage';

                $s->available_types = $types;
                return $s;
            });

        return response()->json([
            'success' => true,
            'data'    => $semestres,
            'niveau'  => $niveauCode,
        ]);
    }

    // ── Créer un semestre ─────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'niveau_id'     => 'required|exists:niveaux,id',
            'code'          => 'required|string|max:20',
            'label'         => 'required|string|max:100',
            'order_index'   => 'required|integer',
            'academic_year' => 'required|string|max:20',
        ]);

        $id = DB::table('semestres')->insertGetId(array_merge($data, [
            'is_open'            => false, // CC fermé par défaut
            'is_exam_open'       => false,
            'is_rattrapage_open' => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]));

        $semestre = DB::table('semestres')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Semestre créé avec succès.',
            'data'    => $semestre,
        ], 201);
    }

    // ── Mettre à jour un semestre ─────────────────────────────────────────────
    public function update(Request $request, $id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable.',
            ], 404);
        }

        $data = $request->validate([
            'niveau_id'     => 'sometimes|exists:niveaux,id',
            'code'          => 'sometimes|string|max:20',
            'label'         => 'sometimes|string|max:100',
            'order_index'   => 'sometimes|integer',
            'academic_year' => 'sometimes|string|max:20',
        ]);

        DB::table('semestres')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );

        $semestre = DB::table('semestres')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Semestre mis à jour.',
            'data'    => $semestre,
        ]);
    }

    // ── Ouvrir / Fermer Contrôle Continu (CC) ───────────────────────────────
    public function toggle($id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();

        if (!$semestre) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable.',
            ], 404);
        }

        $newState = !(bool) $semestre->is_open;

        DB::table('semestres')->where('id', $id)->update([
            'is_open'    => $newState,
            'updated_at' => now(),
        ]);

        $semestre = DB::table('semestres')->where('id', $id)->first();
        $msg = $newState
            ? "Contrôle Continu du semestre {$semestre->code} ouvert."
            : "Contrôle Continu du semestre {$semestre->code} fermé.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $semestre,
        ]);
    }

    // ── Ouvrir / Fermer Examens ───────────────────────────────────────────────
    public function toggleExam($id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable.',
            ], 404);
        }

        $newState = !(bool) $semestre->is_exam_open;

        DB::table('semestres')->where('id', $id)->update([
            'is_exam_open'  => $newState,
            'exam_open_at'  => $newState ? now() : ($semestre->exam_open_at ?? null),
            'exam_close_at' => !$newState ? now() : null,
            'updated_at'    => now(),
        ]);

        $semestre = DB::table('semestres')->where('id', $id)->first();
        $msg = $newState
            ? "Examens du semestre {$semestre->code} ouverts."
            : "Examens du semestre {$semestre->code} fermés.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $semestre,
        ]);
    }

    // ── Ouvrir / Fermer Rattrapage ────────────────────────────────────────────
    public function toggleRattrapage($id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable.',
            ], 404);
        }

        $newState = !(bool) $semestre->is_rattrapage_open;

        DB::table('semestres')->where('id', $id)->update([
            'is_rattrapage_open'  => $newState,
            'rattrapage_open_at'  => $newState ? now() : ($semestre->rattrapage_open_at ?? null),
            'rattrapage_close_at' => !$newState ? now() : null,
            'updated_at'          => now(),
        ]);

        $semestre = DB::table('semestres')->where('id', $id)->first();
        $msg = $newState
            ? "Rattrapage du semestre {$semestre->code} ouvert."
            : "Rattrapage du semestre {$semestre->code} fermé.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $semestre,
        ]);
    }
}