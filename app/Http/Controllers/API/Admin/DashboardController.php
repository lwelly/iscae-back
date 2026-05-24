<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Models\Student;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Stats réclamations
        $reclamationsStats = [
            'total'           => Reclamation::count(),
            'pending'         => Reclamation::where('status', 'pending')->count(),
            'in_progress'     => Reclamation::where('status', 'in_progress')->count(),
            'resolved'        => Reclamation::where('status', 'resolved')->count(),
            'rejected'        => Reclamation::where('status', 'rejected')->count(),
            'escalated'       => Reclamation::where('is_escalated', true)->count(),
            'resolution_rate' => $this->getResolutionRate(),
        ];

        // Stats étudiants — sans scope active()
        $studentsStats = [
            'total'                 => Student::count(),
            'total_active'          => Student::whereHas('user', fn($q) => $q->where('is_active', true))->count(),
            'registered_this_month' => Student::whereMonth('created_at', now()->month)
                                               ->whereYear('created_at', now()->year)
                                               ->count(),
        ];

        // Réclamations par type
        $byType = Reclamation::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Activité 30 derniers jours
        $recentActivity = Reclamation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Top modules avec réclamations
        $topModules = Reclamation::selectRaw('module_id, COUNT(*) as count')
            ->with('module:id,name,code')
            ->groupBy('module_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'module' => $r->module?->nom ?? $r->module?->name,
                'code'   => $r->module?->code,
                'count'  => $r->count,
            ]);

        // Réclamations récentes
        $recentReclamations = Reclamation::with(['student', 'module'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'reference'  => $r->reference_number,
                'student'    => $r->student?->full_name,
                'module'     => $r->module?->nom ?? $r->module?->name,
                'status'     => $r->status,
                'created_at' => $r->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'               => $reclamationsStats,
                'students_stats'      => $studentsStats,
                'by_type'             => $byType,
                'recent_activity'     => $recentActivity,
                'top_modules'         => $topModules,
                'recent_reclamations' => $recentReclamations,
                'resolution_rate'     => $reclamationsStats['resolution_rate'],
            ],
        ]);
    }

    private function getResolutionRate(): float
    {
        $total    = Reclamation::whereIn('status', ['resolved', 'rejected'])->count();
        $resolved = Reclamation::where('status', 'resolved')->count();
        return $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
    }
}
