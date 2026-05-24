<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // ================================================================
    // GET /api/v1/student/notifications
    // ================================================================
    public function index(Request $request)
    {
        // ── Identifier l'utilisateur ─────────────────────────────────
        $userId = Auth::id();

         $user          = $request->user();
    $notifications = \App\Models\Notification::where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->get()
        ->map(fn($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'message'    => $n->message,
            'type'       => $n->type,
            'data'       => is_string($n->data) ? json_decode($n->data, true) : ($n->data ?? []),
            'read_at'    => $n->read_at,
            'created_at' => $n->created_at,
        ]);

    return response()->json(['success' => true, 'data' => $notifications]);

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié — Auth::id() est null.',
            ], 401);
        }

        // ── Requête principale ───────────────────────────────────────
        $query = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        // Filtre lu/non-lu
        if ($request->filled('read')) {
            $isRead = filter_var($request->read, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead ? 1 : 0);
        }

        $total       = (clone $query)->count();
        $unreadCount = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('is_read', 0)
            ->count();

        $perPage     = min((int) $request->get('per_page', 15), 50);
        $currentPage = max((int) $request->get('page', 1), 1);
        $offset      = ($currentPage - 1) * $perPage;
        $lastPage    = $total > 0 ? (int) ceil($total / $perPage) : 1;

        $rows = (clone $query)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = $rows->map(fn($row) => $this->normalize($row))->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'        => $total,
                'unread_count' => $unreadCount,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => $lastPage,
            ],
        ]);
    }

    // ================================================================
    // GET /api/v1/student/notifications/counts
    // ================================================================
    public function counts()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $base = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        return response()->json([
            'success' => true,
            'data'    => [
                'total'  => (clone $base)->count(),
                'unread' => (clone $base)->where('is_read', 0)->count(),
                'read'   => (clone $base)->where('is_read', 1)->count(),
            ],
        ]);
    }

    // ================================================================
    // PUT /api/v1/student/notifications/read-all
    // ================================================================
    public function markAllRead()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $count = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marquée(s) comme lue(s).",
            'updated' => $count,
        ]);
    }

    // ================================================================
    // PUT /api/v1/student/notifications/{id}/read
    // ================================================================
    public function markAsRead(string $id)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->update(['is_read' => 1, 'read_at' => now()]);

        return response()->json([
            'success' => (bool) $updated,
            'message' => $updated ? 'Notification marquée comme lue.' : 'Introuvable.',
        ], $updated ? 200 : 404);
    }

    // ================================================================
    // DELETE /api/v1/student/notifications/{id}
    // ================================================================
    public function destroy(string $id)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $deleted = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'success' => (bool) $deleted,
            'message' => $deleted ? 'Notification supprimée.' : 'Introuvable.',
        ], $deleted ? 200 : 404);
    }

    // ================================================================
    // Helper privé : normaliser une ligne DB
    // ================================================================
    private function normalize(object $row): array
    {
        $arr  = (array) $row;
        $data = [];

        if (! empty($arr['data'])) {
            $decoded = json_decode($arr['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded ?? [];
            }
        }

        return [
            'id'             => $arr['id'],
            'type'           => $arr['type']    ?? null,
            'title'          => $arr['title']   ?? $data['title']   ?? 'Notification',
            'body'           => $arr['body']    ?? $data['message'] ?? $data['body'] ?? '',
            'is_read'        => (bool) ($arr['is_read'] ?? false),
            'read_at'        => $arr['read_at'] ?? null,
            'channel'        => $arr['channel'] ?? 'in_app',
            'data'           => $data,
            'reclamation_id' => $data['reclamation_id'] ?? null,
            'sent_at'        => $arr['sent_at']    ?? $arr['created_at'] ?? null,
            'created_at'     => $arr['created_at'] ?? null,
        ];
    }
}
