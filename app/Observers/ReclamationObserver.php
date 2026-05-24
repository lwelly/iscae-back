<?php

namespace App\Observers;

use App\Models\Reclamation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ReclamationObserver
{
    public function __construct(
        private NotificationService $notif
    ) {}

    public function updated(Reclamation $reclamation): void
    {
        if (! $reclamation->wasChanged('status')) return;

        $oldStatus = $reclamation->getOriginal('status');
        $newStatus = $reclamation->status;

        if ($oldStatus === $newStatus) return;

        Log::info('[ReclamationObserver::updated] DÉCLENCHÉ', [
            'id'         => $reclamation->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        try {
            $student = $reclamation->relationLoaded('student')
                ? $reclamation->student
                : $reclamation->load('student')->student;

            if (! $student) {
                Log::warning('[ReclamationObserver] student introuvable', [
                    'student_id' => $reclamation->student_id
                ]);
                return;
            }

            $studentUser = User::find($student->user_id);
            if (! $studentUser) {
                Log::warning('[ReclamationObserver] user introuvable', [
                    'user_id' => $student->user_id
                ]);
                return;
            }

            $ref = $reclamation->reference_number ?? "#{$reclamation->id}";

            $this->notif->notifyReclamationStatusChanged(
                $studentUser,
                $ref,
                $oldStatus,
                $newStatus,
                $reclamation->admin_response
            );

            Log::info('[ReclamationObserver] Notification créée', [
                'reclamation_id' => $reclamation->id,
                'user_id'        => $studentUser->id,
                'old'            => $oldStatus,
                'new'            => $newStatus,
            ]);

        } catch (\Throwable $e) {
            Log::error('[ReclamationObserver] Erreur', [
                'id'    => $reclamation->id,
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
        }
    }
}
