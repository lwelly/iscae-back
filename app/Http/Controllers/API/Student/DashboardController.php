<?php
// app/Http/Controllers/API/Student/DashboardController.php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Models\Note;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/v1/student/dashboard
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        // Stats réclamations
        $reclamationsStats = [
            'total'      => $student->reclamations()->count(),
            'pending'    => $student->reclamations()->pending()->count(),
            'resolved'   => $student->reclamations()
                                    ->where('status', Reclamation::STATUS_RESOLVED)
                                    ->count(),
            'rejected'   => $student->reclamations()
                                    ->where('status', Reclamation::STATUS_REJECTED)
                                    ->count(),
            'escalated'  => $student->reclamations()
                                    ->where('is_escalated', true)
                                    ->count(),
        ];

        // Dernières notes publiées
        $recentNotes = Note::forStudent($student->id)
            ->published()
            ->with(['module', 'semestre'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map(fn($note) => [
                'module'      => $note->module?->name,
                'semestre'    => $note->semestre?->code,
                'note_finale' => $note->note_finale,
                'is_passed'   => $note->isPassed(),
            ]);

        // Notifications non lues
        $unreadCount = Notification::forUser($request->user()->id)
                                   ->unread()
                                   ->count();

        // Dernières réclamations
        $recentReclamations = $student->reclamations()
            ->with(['module', 'semestre'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'reference' => $r->reference_number,
                'module'    => $r->module?->name,
                'status'    => $r->status,
                'type'      => $r->type,
                'created_at'=> $r->created_at->format('Y-m-d'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'student'              => [
                    'full_name'    => $student->full_name,
                    'matricule'    => $student->matricule,
                    'filiere'      => $student->filiere?->name,
                    'niveau'       => $student->niveau?->code,
                    'photo_url'    => $student->photo_url,
                ],
                'reclamations_stats'   => $reclamationsStats,
                'recent_notes'         => $recentNotes,
                'recent_reclamations'  => $recentReclamations,
                'unread_notifications' => $unreadCount,
            ],
        ]);
    }
}
