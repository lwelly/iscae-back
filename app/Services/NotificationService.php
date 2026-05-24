<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // ================================================================
    // Méthode principale
    // ================================================================

    public function send(
        User    $recipient,
        string  $type,
        string  $title,
        string  $body,
        array   $data           = [],
        string  $channel        = 'in_app',
        ?User   $sender         = null,
        ?string $notifiableType = null,
        ?int    $notifiableId   = null
    ): ?Notification {
        try {
            $notification = Notification::create([
                'user_id'         => $recipient->id,
                'type'            => $type,
                'title'           => $title,
                'body'            => $body,
                'data'            => $data,
                'channel'         => $channel,
                'is_read'         => false,
                'sent_at'         => now(),
                'notifiable_type' => $notifiableType,
                'notifiable_id'   => $notifiableId,
                'sent_by'         => $sender?->id,
            ]);

            if (in_array($channel, ['email', 'both'])) {
                $this->sendEmail($recipient->email, $title, $body, $data);
            }

            return $notification;

        } catch (\Throwable $e) {
            Log::error('[NotificationService::send] Erreur', [
                'user_id' => $recipient->id,
                'type'    => $type,
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return null;
        }
    }

    // ================================================================
    // Notifications bulk
    // ================================================================

    public function sendBulk(
        array  $userIds,
        string $type,
        string $title,
        string $body,
        array  $data    = [],
        string $channel = 'in_app'
    ): int {
        $count = 0;
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if ($this->send($user, $type, $title, $body, $data, $channel)) {
                $count++;
            }
        }

        return $count;
    }

    // ================================================================
    // Notifications métier — Étudiant
    // ================================================================

    public function notifyReclamationSubmitted(
        User   $student,
        string $reference,
        string $moduleName
    ): ?Notification {
        return $this->send(
            $student,
            'reclamation.submitted',
            'Réclamation soumise avec succès',
            "Votre réclamation #{$reference} pour le module \"{$moduleName}\" a été reçue et sera traitée dans les meilleurs délais.",
            ['reference' => $reference, 'module' => $moduleName],
            'in_app'
        );
    }

    public function notifyReclamationStatusChanged(
        User    $student,
        string  $reference,
        string  $oldStatus,
        string  $newStatus,
        ?string $adminResponse = null
    ): ?Notification {
        $labels = [
            'submitted' => 'Soumise',
            'received'  => 'Reçue',
            'in_review' => "En cours d'examen",
            'resolved'  => 'Résolue ✅',
            'rejected'  => 'Rejetée ❌',
            'escalated' => 'Escaladée',
            'cancelled' => 'Annulée',
        ];

        $newLabel = $labels[$newStatus] ?? $newStatus;
        $body     = "Le statut de votre réclamation #{$reference} est maintenant : {$newLabel}.";

        if ($adminResponse) {
            $body .= "\n\nRéponse de l'administration : {$adminResponse}";
        }

        return $this->send(
            $student,
            'reclamation.status_changed',
            "Réclamation #{$reference} — Statut mis à jour",
            $body,
            [
                'reference'  => $reference,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            'in_app'
        );
    }

    public function notifyMeetingScheduled(
        User   $student,
        string $reference,
        string $meetingDate,
        string $location
    ): ?Notification {
        return $this->send(
            $student,
            'reclamation.meeting_scheduled',
            'Réunion programmée pour votre réclamation',
            "Une réunion concernant votre réclamation #{$reference} est programmée le {$meetingDate} à {$location}.",
            ['reference' => $reference, 'meeting_date' => $meetingDate, 'location' => $location],
            'in_app'
        );
    }

    public function notifyNotesPublished(
        User   $student,
        string $semestreLabel,
        string $academicYear
    ): ?Notification {
        return $this->send(
            $student,
            'notes.published',
            'Vos notes sont disponibles',
            "Les notes du {$semestreLabel} ({$academicYear}) ont été publiées. Connectez-vous pour les consulter.",
            ['semestre' => $semestreLabel, 'year' => $academicYear],
            'in_app'
        );
    }

    public function notifyDocumentReady(
        User   $student,
        string $docType,
        string $docTitle
    ): ?Notification {
        return $this->send(
            $student,
            'document.ready',
            'Document disponible',
            "Votre document \"{$docTitle}\" est maintenant disponible en téléchargement.",
            ['doc_type' => $docType, 'title' => $docTitle],
            'in_app'
        );
    }

    public function notifyAccountLocked(User $user, int $lockMinutes): ?Notification
    {
        return $this->send(
            $user,
            'security.account_locked',
            'Compte temporairement bloqué',
            "Votre compte a été temporairement bloqué pendant {$lockMinutes} minutes suite à plusieurs tentatives de connexion échouées.",
            ['lock_minutes' => $lockMinutes],
            'in_app'
        );
    }

    // ================================================================
    // Notifications métier — Admin
    // ================================================================

    public function notifyAdminNewReclamation(
        User   $admin,
        string $reference,
        string $studentName,
        string $moduleName
    ): ?Notification {
        return $this->send(
            $admin,
            'admin.new_reclamation',
            'Nouvelle réclamation reçue',
            "L'étudiant {$studentName} a soumis une réclamation #{$reference} pour le module \"{$moduleName}\".",
            ['reference' => $reference, 'student_name' => $studentName, 'module' => $moduleName],
            'in_app'
        );
    }

    public function notifyEscalated(
        User   $deptHead,
        string $reference,
        string $studentName,
        string $moduleName
    ): ?Notification {
        return $this->send(
            $deptHead,
            'admin.reclamation_escalated',
            'Réclamation escaladée — Action requise',
            "La réclamation #{$reference} de l'étudiant {$studentName} pour le module \"{$moduleName}\" vous a été escaladée pour décision finale.",
            ['reference' => $reference, 'student_name' => $studentName, 'module' => $moduleName],
            'in_app'
        );
    }

    // ================================================================
    // Email privé
    // ================================================================

    private function sendEmail(string $to, string $subject, string $body, array $data = []): void
    {
        try {
            Mail::send(
                'emails.notification',
                ['subject' => $subject, 'body' => $body, 'data' => $data],
                fn($msg) => $msg->to($to)->subject($subject)
            );
        } catch (\Throwable $e) {
            Log::error('[NotificationService::sendEmail] Erreur', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
