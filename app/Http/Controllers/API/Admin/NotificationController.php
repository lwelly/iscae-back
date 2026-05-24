<?php
// app/Http/Controllers/API/Admin/NotificationController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private AuditService        $auditService
    ) {}

    // ==========================================
    // GET /api/v1/admin/notifications
    // ==========================================
    public function index(Request $request): JsonResponse
    {
        // Notifications reçues par cet admin
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => NotificationResource::collection($notifications->items()),
            'meta'    => [
                'total'        => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'unread_count' => Notification::forUser($request->user()->id)
                                              ->unread()
                                              ->count(),
            ],
        ]);
    }

    // ==========================================
    // PUT /api/v1/admin/notifications/{id}/read
    // ==========================================
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue.',
        ]);
    }

    // ==========================================
    // PUT /api/v1/admin/notifications/read-all
    // ==========================================
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues.',
        ]);
    }

    // ==========================================
    // POST /api/v1/admin/notifications/bulk
    // ==========================================
    public function sendBulk(BulkNotificationRequest $request): JsonResponse
    {
        $target  = $request->input('target');
        $userIds = $this->resolveTargetUserIds($request);

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun destinataire trouvé pour cette cible.',
                'code'    => 'NO_RECIPIENTS',
            ], 422);
        }

        $count = $this->notificationService->sendBulk(
            userIds: $userIds,
            type:    $request->input('type', 'admin.broadcast'),
            title:   $request->input('title'),
            body:    $request->input('body'),
            data:    ['sent_by' => $request->user()->admin?->full_name],
            channel: $request->input('channel', 'in_app')
        );

        $this->auditService->log(
            action:    'notification.bulk_sent',
            userId:    $request->user()->id,
            userRole:  'admin',
            newValues: [
                'target'     => $target,
                'recipients' => $count,
                'title'      => $request->input('title'),
                'channel'    => $request->input('channel'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) envoyée(s) avec succès.",
            'data'    => [
                'sent_count' => $count,
                'target'     => $target,
                'channel'    => $request->input('channel'),
            ],
        ]);
    }

    // ==========================================
    // Résoudre les IDs utilisateurs selon la cible
    // ==========================================
    private function resolveTargetUserIds(BulkNotificationRequest $request): array
    {
        return match ($request->input('target')) {

            // Tous les étudiants actifs
            'all_students' => User::where('role', 'student')
                ->where('is_active', true)
                ->pluck('id')
                ->toArray(),

            // Étudiants d'une filière spécifique
            'filiere' => Student::where('filiere_id', $request->input('filiere_id'))
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->pluck('user_id')
                ->toArray(),

            // Étudiants d'un niveau spécifique
            'niveau' => Student::where('niveau_id', $request->input('niveau_id'))
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray(),

            // Liste spécifique d'utilisateurs
            'specific' => collect($request->input('user_ids', []))
                ->map(fn($id) => (int) $id)
                ->toArray(),

            default => [],
        };
    }
}
