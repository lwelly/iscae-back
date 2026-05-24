<?php
// app/Http/Controllers/API/Admin/NoteController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use App\Models\Module;
use App\Models\Semestre;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private AuditService        $auditService,
        private NotificationService $notificationService
    ) {}

    // GET /api/v1/admin/notes
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['student', 'module', 'semestre']);

        if ($request->has('semestre_id')) {
            $query->where('semestre_id', $request->query('semestre_id'));
        }
        if ($request->has('module_id')) {
            $query->where('module_id', $request->query('module_id'));
        }
        if ($request->has('is_published')) {
            $query->where('is_published', (bool) $request->query('is_published'));
        }

        $notes = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $notes->items(),
            'meta'    => ['total' => $notes->total()],
        ]);
    }

    // POST /api/v1/admin/notes/upsert
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'notes'                   => ['required', 'array'],
            'notes.*.student_id'      => ['required', 'integer', 'exists:students,id'],
            'notes.*.module_id'       => ['required', 'integer', 'exists:modules,id'],
            'notes.*.semestre_id'     => ['required', 'integer', 'exists:semestres,id'],
            'notes.*.note_controle'   => ['nullable', 'numeric', 'min:0', 'max:20'],
            'notes.*.note_examen'     => ['nullable', 'numeric', 'min:0', 'max:20'],
            'notes.*.note_rattrapage' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        $academicYear = \App\Models\Setting::getValue('current_academic_year', '2024-2025');
        $count        = 0;

        foreach ($request->input('notes') as $noteData) {
            $note = Note::updateOrCreate(
                [
                    'student_id'    => $noteData['student_id'],
                    'module_id'     => $noteData['module_id'],
                    'academic_year' => $academicYear,
                ],
                [
                    'semestre_id'     => $noteData['semestre_id'],
                    'note_controle'   => $noteData['note_controle'] ?? null,
                    'note_examen'     => $noteData['note_examen'] ?? null,
                    'note_rattrapage' => $noteData['note_rattrapage'] ?? null,
                ]
            );

            // Calculer la note finale
            $note->update(['note_finale' => $note->calculateFinale()]);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} note(s) enregistrée(s) avec succès.",
        ]);
    }

    // POST /api/v1/admin/notes/publish
    public function publish(Request $request): JsonResponse
    {
        $request->validate([
            'semestre_id' => ['required', 'integer', 'exists:semestres,id'],
            'module_id'   => ['nullable', 'integer', 'exists:modules,id'],
        ]);

        $query = Note::where('semestre_id', $request->input('semestre_id'))
                     ->where('is_published', false);

        if ($request->has('module_id')) {
            $query->where('module_id', $request->input('module_id'));
        }

        $notes     = $query->with(['student.user', 'semestre'])->get();
        $published = 0;

        foreach ($notes as $note) {
            $note->updateAndPublish($request->user()->id);
            $published++;
        }

        // Notifier les étudiants concernés
        $semestre     = Semestre::find($request->input('semestre_id'));
        $studentIds   = $notes->pluck('student.user_id')->unique()->toArray();

        foreach ($studentIds as $userId) {
            $student = \App\Models\User::find($userId);
            if ($student) {
                $this->notificationService->notifyNotesPublished(
                    $student,
                    $semestre?->label ?? '',
                    \App\Models\Setting::getValue('current_academic_year', '2024-2025')
                );
            }
        }

        $this->auditService->logNotePublished(
            $request->user()->id,
            $request->input('module_id') ?? 0
        );

        return response()->json([
            'success' => true,
            'message' => "{$published} note(s) publiée(s) et étudiants notifiés.",
        ]);
    }
}
