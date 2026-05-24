<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    // GET /api/v1/student/notes
    public function index(Request $request)
    {
        $user    = Auth::user();
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        $semestres = DB::table('semestres')
            ->join('niveaux', 'semestres.niveau_id', '=', 'niveaux.id')
            ->select('semestres.*', 'niveaux.code as niveau_code', 'niveaux.label as niveau_label')
            ->orderBy('semestres.order_index')
            ->get();

        $notes = DB::table('notes')
            ->join('modules',   'notes.module_id',   '=', 'modules.id')
            ->join('semestres', 'notes.semestre_id',  '=', 'semestres.id')
            ->where('notes.student_id', $student->id)
            ->where('notes.is_published', 1)
            ->select(
                'notes.*',
                'modules.name  as module_name',
                'modules.code  as module_code',
                'modules.coefficient',
                'modules.credits',
                'semestres.code  as semestre_code',
                'semestres.label as semestre_label',
                'semestres.is_open as semestre_is_open'
            )
            ->get();

        $activeReclamations = DB::table('reclamations')
            ->where('student_id', $student->id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->pluck('module_id')
            ->toArray();

        $semestresData = [];
        foreach ($semestres as $sem) {
            $semNotes = $notes->filter(fn($n) => $n->semestre_id == $sem->id)->values();

            $notesWithValues = $semNotes->filter(fn($n) => $n->note_finale !== null && $n->note_finale > 0);
            $moyenne = 0;
            if ($notesWithValues->count() > 0) {
                $totalCoeff  = $notesWithValues->sum('coefficient');
                $totalPoints = $notesWithValues->sum(fn($n) => $n->note_finale * $n->coefficient);
                $moyenne     = $totalCoeff > 0
                    ? round($totalPoints / $totalCoeff, 2)
                    : round($notesWithValues->avg('note_finale'), 2);
            }

            $semestresData[] = [
                'id'           => $sem->id,
                'semestre_id'  => $sem->id,
                'code'         => $sem->code,
                'label'        => $sem->label,
                'semestre'     => $sem->code,
                'academic_year'=> $sem->academic_year ?? '2024-2025',
                'is_open'      => (bool) $sem->is_open,
                'moyenne'      => $moyenne,
                'notes'        => $semNotes->map(fn($n) => [
                    'id'                     => $n->id,
                    'student_id'             => $n->student_id,
                    'module_id'              => $n->module_id,
                    'module_name'            => $n->module_name,
                    'module_code'            => $n->module_code,
                    'name'                   => $n->module_name,
                    'code'                   => $n->module_code,
                    'coefficient'            => $n->coefficient,
                    'credits'                => $n->credits,
                    'note_cc'                => $n->note_controle,
                    'note_exam'              => $n->note_examen,
                    'note_rattrapage'        => $n->note_rattrapage,
                    'note_finale'            => $n->note_finale,
                    'is_published'           => (bool) $n->is_published,
                    'semestre_id'            => $n->semestre_id,
                    'semestre_is_open'       => (bool) $n->semestre_is_open,
                    'has_active_reclamation' => in_array($n->module_id, $activeReclamations),
                ])->values(),
            ];
        }

        $semestresWithNotes = array_values(
            array_filter($semestresData, fn($s) => count($s['notes']) > 0)
        );

        $allNotes  = $notes->filter(fn($n) => $n->note_finale > 0);
        $globalAvg = 0;
        if ($allNotes->count() > 0) {
            $totalCoeff  = $allNotes->sum('coefficient');
            $totalPoints = $allNotes->sum(fn($n) => $n->note_finale * $n->coefficient);
            $globalAvg   = $totalCoeff > 0
                ? round($totalPoints / $totalCoeff, 2)
                : round($allNotes->avg('note_finale'), 2);
        }

        return response()->json([
            'data' => [
                'academic_year'  => $student->academic_year ?? '2024-2025',
                'global_average' => $globalAvg,
                'semestres'      => $semestresWithNotes,
            ]
        ]);
    }

    // GET /api/v1/student/modules
    // Retourne les modules des semestres OUVERTS de la filière de l'étudiant
    // utilisé par le formulaire de nouvelle réclamation
   // GET /api/v1/student/modules
public function modules(Request $request)
{
    $user    = Auth::user();
    $student = DB::table('students')->where('user_id', $user->id)->first();

    if (!$student) {
        return response()->json(['message' => 'Étudiant introuvable.'], 404);
    }

    // Semestres ouverts
    $semestresOuverts = DB::table('semestres')
        ->where('is_open', true)
        ->pluck('id')
        ->toArray();

    if (empty($semestresOuverts)) {
        return response()->json([
            'data'    => [],
            'message' => 'Aucun semestre ouvert aux réclamations.',
        ]);
    }

    // Modules de la filière ET du semestre ouvert
    $modules = DB::table('modules')
        ->join('semestres', 'modules.semestre_id', '=', 'semestres.id')
        ->where('modules.filiere_id',  $student->filiere_id)
        ->whereIn('modules.semestre_id', $semestresOuverts)
        ->where('modules.is_active', true)
        ->select(
            'modules.id',
            'modules.name',
            'modules.code',
            'modules.coefficient',
            'modules.credits',
            'modules.semestre_id',
            'semestres.code  as semestre_code',
            'semestres.label as semestre_label',
            'semestres.is_open as semestre_is_open'
        )
        ->orderBy('semestres.order_index')
        ->orderBy('modules.name')
        ->get();

    // Enrichir avec la note publiée de l'étudiant
    $modules = $modules->map(function ($m) use ($student) {
        $note = DB::table('notes')
            ->where('student_id',   $student->id)
            ->where('module_id',    $m->id)
            ->where('is_published', 1)
            ->first();

        return [
            'id'               => $m->id,
            'name'             => $m->name,
            'code'             => $m->code,
            'coefficient'      => $m->coefficient,
            'credits'          => $m->credits,
            'semestre_id'      => $m->semestre_id,
            'semestre_code'    => $m->semestre_code,
            'semestre_label'   => $m->semestre_label,
            'semestre_is_open' => (bool) $m->semestre_is_open,
            'note_actuelle'    => $note?->note_finale,
            'note_cc'          => $note?->note_controle,
            'note_exam'        => $note?->note_examen,
        ];
    });

    return response()->json([
        'success' => true,
        'data'    => $modules,
    ]);
}
}
