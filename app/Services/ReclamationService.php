<?php

namespace App\Services;

use App\Models\Reclamation;
use App\Models\Student;
use App\Models\Module;
use App\Models\Semestre;
use App\Models\Note;
use App\Models\Admin;
use App\Models\User;
use App\Models\Setting;
use App\Exceptions\ReclamationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReclamationService
{
    public function __construct(
        private AuditService        $auditService,
        private NotificationService $notificationService
    ) {}

    // ── Soumettre ─────────────────────────────────────────────────────────────
    public function submit(
        Student       $student,
        array         $data,
        ?UploadedFile $attachment = null,
        ?string       $ip = null
    ): Reclamation {
        $this->validateBusinessRules($student, $data);

        return DB::transaction(function () use ($student, $data, $attachment, $ip) {

            $reclamation = Reclamation::create([
                'reference_number' => Reclamation::generateReference(),
                'student_id'       => $student->id,
                'module_id'        => $data['module_id'],
                'semestre_id'      => $data['semestre_id'],
                'note_id'          => $data['note_id'] ?? null,
                'academic_year'    => Setting::getValue('current_academic_year', date('Y') . '-' . (date('Y') + 1)),
                'type'             => $data['type'],
                'note_actuelle'    => $data['note_actuelle'] ?? null,
                'note_reclamee'    => $data['type'] === 'controle' ? ($data['note_reclamee'] ?? null) : null,
                'justification'    => $data['justification'],
                'status'           => Reclamation::STATUS_SUBMITTED,
            ]);

            // ✅ CORRIGÉ : created_at (plus changed_at)
            \App\Models\ReclamationHistory::create([
                'reclamation_id' => $reclamation->id,
                'changed_by'     => $student->user_id,
                'old_status'     => null,
                'new_status'     => Reclamation::STATUS_SUBMITTED,
                'comment'        => "Réclamation soumise par l'étudiant.",
                'ip_address'     => $ip,
                'created_at'     => now(),   // ✅
            ]);

            if ($attachment) {
                $this->storeAttachment($reclamation, $attachment);
            }

            try {
                $this->notificationService->notifyReclamationSubmitted(
                    $student->user,
                    $reclamation->reference_number,
                    $reclamation->module->name
                );
                $this->notifyAdmins($reclamation, $student);
                $this->auditService->logReclamationCreated($student->user_id, $reclamation->id);
            } catch (\Exception $e) {
                \Log::warning('[ReclamationService] Notification/Audit error: ' . $e->getMessage());
            }

            return $reclamation->load(['module', 'semestre', 'attachments']);
        });
    }

    // ── Changer le statut (admin) ─────────────────────────────────────────────
    public function changeStatus(
    Reclamation $reclamation,
    string $newStatus,
    $admin,
    ?string $comment = null,
    ?string $adminResponse = null,
    ?string $ip = null
): Reclamation {
    $this->validateStatusTransition($reclamation->status, $newStatus);

    $oldStatus = $reclamation->status;

    // ✅ DEBUG TEMPORAIRE
    \Log::info('[changeStatus DEBUG]', [
        'admin_type'  => gettype($admin),
        'admin_class' => is_object($admin) ? get_class($admin) : 'not_object',
        'admin_id'    => is_object($admin) ? ($admin->id ?? 'NO_ID_PROPERTY') : 'not_object',
        'auth_id'     => auth()->id(),
    ]);

    $adminId = is_object($admin) && isset($admin->id) ? $admin->id : auth()->id();

    \Log::info('[changeStatus] adminId final = ' . ($adminId ?? 'NULL'));

    if (!$adminId) {
        throw new \Exception("Impossible de déterminer l'identifiant de l'administrateur.");
    }

    DB::beginTransaction();
    try {
        $updateData = [
            'status'     => $newStatus,
            'updated_at' => now(),
        ];

        if ($adminResponse && trim($adminResponse) !== '') {
            $updateData['admin_response'] = $adminResponse;
            $updateData['responded_by']   = $adminId;
            $updateData['responded_at']   = now();
        }

        if (in_array($newStatus, ['resolved', 'rejected'])) {
            $updateData['resolved_at'] = now();
        }

        DB::table('reclamations')
            ->where('id', $reclamation->id)
            ->update($updateData);

        DB::table('reclamation_history')->insert([
            'reclamation_id' => $reclamation->id,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
            'comment'        => $comment ?? $adminResponse ?? "Statut changé en « {$newStatus} » par l'administrateur.",
            'changed_by'     => $adminId,
            'ip_address'     => $ip ?? request()->ip(),
            'created_at'     => now(),
        ]);

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }

    // Notifications silencieuses
    // Notifications silencieuses — ne bloque jamais
// Notifications silencieuses
try {
    if ($this->notificationService && method_exists($this->notificationService, 'notifyReclamationStatusChanged')) {
        $fresh = $reclamation->fresh()->load('student');
        $this->notificationService->notifyReclamationStatusChanged(
            $fresh->student?->user,
            $fresh->reference_number,
            $oldStatus,
            $newStatus
        );
    }
} catch (\Throwable $e) {
    \Log::warning('[changeStatus] Notification échouée : ' . $e->getMessage());
}



    // Audit silencieux
   // Audit silencieux
// Audit silencieux
try {
    if ($this->auditService) {
        $this->auditService->log(
            'reclamation.status_changed',  // action
            $adminId,                       // userId (int)
            'admin',                        // userRole
            'reclamation',                  // entityType
            $reclamation->id,              // entityId
            ['status' => $oldStatus],      // oldValues
            ['status' => $newStatus]       // newValues
        );
    }
} catch (\Throwable $e) {
    \Log::warning('[changeStatus] Audit échoué : ' . $e->getMessage());
}


    return $reclamation->fresh()->load([
        'student.filiere',
        'student.niveau',
        'module',
        'semestre',
        'history',
        'attachments',
    ]);
}



    // ── Escalader ─────────────────────────────────────────────────────────────
    public function escalate(
        Reclamation $reclamation,
        Admin       $escalatedTo,
        User        $escalatedBy,
        ?string     $reason = null,
        ?string     $ip     = null
    ): Reclamation {

        if ($reclamation->is_escalated) {
            throw new ReclamationException(
                'Cette réclamation est déjà escaladée.',
                'ALREADY_ESCALATED'
            );
        }

        DB::transaction(function () use ($reclamation, $escalatedTo, $escalatedBy, $reason, $ip) {

            $reclamation->update([
                'status'           => Reclamation::STATUS_ESCALATED,
                'is_escalated'     => true,
                'escalated_at'     => now(),
                'escalated_to'     => $escalatedTo->id,
                'escalation_reason'=> $reason,
            ]);

            // ✅ CORRIGÉ : created_at
            \App\Models\ReclamationHistory::create([
                'reclamation_id' => $reclamation->id,
                'changed_by'     => $escalatedBy->id,
                'old_status'     => Reclamation::STATUS_IN_REVIEW,
                'new_status'     => Reclamation::STATUS_ESCALATED,
                'comment'        => $reason ?? 'Réclamation escaladée au chef de département.',
                'ip_address'     => $ip,
                'created_at'     => now(),   // ✅
            ]);

            try {
                $this->notificationService->notifyEscalated(
                    $escalatedTo->user,
                    $reclamation->reference_number,
                    $reclamation->student->full_name,
                    $reclamation->module->name
                );
                $this->notificationService->notifyReclamationStatusChanged(
                    $reclamation->student->user,
                    $reclamation->reference_number,
                    Reclamation::STATUS_IN_REVIEW,
                    Reclamation::STATUS_ESCALATED
                );
            } catch (\Exception $e) {
                \Log::warning('[ReclamationService] Escalate notification error: ' . $e->getMessage());
            }
        });

        return $reclamation->fresh();
    }

    // ── Programmer une réunion ────────────────────────────────────────────────
    public function scheduleMeeting(
        Reclamation $reclamation,
        string      $meetingAt,
        string      $location,
        User        $admin
    ): Reclamation {

        $reclamation->update([
            'meeting_scheduled_at' => $meetingAt,
            'meeting_location'     => $location,
        ]);

        try {
            $this->notificationService->notifyMeetingScheduled(
                $reclamation->student->user,
                $reclamation->reference_number,
                $meetingAt,
                $location
            );
        } catch (\Exception $e) {
            \Log::warning('[ReclamationService] Meeting notification error: ' . $e->getMessage());
        }

        return $reclamation->fresh();
    }

    // ── Validation règles métier ──────────────────────────────────────────────
    private function validateBusinessRules(Student $student, array $data): void
    {
        $module   = Module::findOrFail($data['module_id']);
        $semestre = Semestre::findOrFail($data['semestre_id']);

        if (!$semestre->isAcceptingReclamations()) {
            throw new ReclamationException(
                'Les réclamations ne sont pas ouvertes pour ce semestre.',
                'SEMESTRE_CLOSED'
            );
        }

        if (!$student->canAccessModule($module)) {
            throw new ReclamationException(
                "Ce module n'appartient pas à votre filière.",
                'MODULE_NOT_IN_FILIERE'
            );
        }

        $allowedCodes = $student->getAllowedSemestreCodes();
        if (!in_array($semestre->code, $allowedCodes)) {
            throw new ReclamationException(
                'Ce semestre ne correspond pas à votre niveau.',
                'SEMESTRE_LEVEL_MISMATCH'
            );
        }

        $currentYear = Setting::getValue('current_academic_year', date('Y') . '-' . (date('Y') + 1));
        $exists = Reclamation::where('student_id', $student->id)
                             ->where('module_id', $data['module_id'])
                             ->where('type', $data['type'])
                             ->where('academic_year', $currentYear)
                             ->exists();

        if ($exists) {
            throw new ReclamationException(
                'Vous avez déjà soumis une réclamation de ce type pour ce module.',
                'DUPLICATE_RECLAMATION'
            );
        }

        $max         = (int) Setting::getValue('reclamation_max_active', 3);
        $activeCount = Reclamation::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'received', 'in_review', 'escalated'])
            ->count();

        if ($activeCount >= $max) {
            throw new ReclamationException(
                "Vous avez atteint le nombre maximum de réclamations actives ({$max}).",
                'MAX_RECLAMATIONS_REACHED'
            );
        }

        if ($data['type'] === 'controle' && empty($data['note_reclamee'])) {
            throw new ReclamationException(
                'La note réclamée est obligatoire pour une réclamation de type contrôle.',
                'NOTE_RECLAMEE_REQUIRED'
            );
        }
    }

    // ── Validation transitions statut ─────────────────────────────────────────
    private function validateStatusTransition(string $from, string $to): void
    {
        $allowed = [
            'submitted' => ['received', 'in_review', 'resolved', 'rejected'],
            'received'  => ['in_review', 'resolved', 'rejected'],
            'in_review' => ['resolved', 'rejected', 'escalated'],
            'escalated' => ['resolved', 'rejected', 'in_review'],
            'resolved'  => [],
            'rejected'  => [],
        ];

        if (!in_array($to, $allowed[$from] ?? [])) {
            throw new \Exception("Transition de statut invalide : {$from} → {$to}.");
        }
    }

    // ── Stocker pièce jointe ──────────────────────────────────────────────────
    private function storeAttachment(Reclamation $reclamation, UploadedFile $file): void
    {
        $maxSizeMb = (int) Setting::getValue('max_upload_size_mb', 10);
        $maxSizeB  = $maxSizeMb * 1024 * 1024;

        if ($file->getSize() > $maxSizeB) {
            throw new ReclamationException(
                "Le fichier dépasse la taille maximale autorisée ({$maxSizeMb} MB).",
                'FILE_TOO_LARGE'
            );
        }

        $allowedTypes = explode(',', Setting::getValue('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx'));
        $extension    = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            throw new ReclamationException(
                'Type de fichier non autorisé.',
                'FILE_TYPE_NOT_ALLOWED'
            );
        }

        $storedName = Str::uuid() . '.' . $extension;
        $path       = $file->storeAs('reclamations/' . $reclamation->id, $storedName, 'private');

        \App\Models\ReclamationAttachment::create([
            'reclamation_id' => $reclamation->id,
            'original_name'  => $file->getClientOriginalName(),
            'stored_name'    => $storedName,
            'storage_path'   => $path,
            'mime_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
            'is_scanned'     => false,
            'is_safe'        => false,
        ]);
    }

    // ── Notifier les admins ───────────────────────────────────────────────────
    private function notifyAdmins(Reclamation $reclamation, Student $student): void
    {
        $deptId = $student->filiere?->department_id;

        $admins = Admin::whereHas('user', fn($q) => $q->where('is_active', true))
                       ->where(function ($q) use ($deptId) {
                           $q->where('role_label', 'super_admin')
                             ->orWhere(function ($q2) use ($deptId) {
                                 $q2->where('department_id', $deptId)
                                    ->whereIn('role_label', ['admin', 'department_head', 'staff']);
                             });
                       })
                       ->with('user')
                       ->get();

        foreach ($admins as $admin) {
            try {
                $this->notificationService->notifyAdminNewReclamation(
                    $admin->user,
                    $reclamation->reference_number,
                    $student->full_name,
                    $reclamation->module->name
                );
            } catch (\Exception $e) {
                \Log::warning('[ReclamationService] Admin notification error: ' . $e->getMessage());
            }
        }
    }
}
