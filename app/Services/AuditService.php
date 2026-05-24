<?php
// app/Services/AuditService.php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    private ?Request $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * Enregistre une action dans le journal d'audit
     */
    public function log(
        string  $action,
        ?int    $userId     = null,
        ?string $userRole   = null,
        ?string $entityType = null,
        ?int    $entityId   = null,
        ?array  $oldValues  = null,
        ?array  $newValues  = null,
        string  $status     = 'success'
    ): void {
        // Utiliser l'utilisateur connecté si non fourni
        if ($userId === null && $this->request->user()) {
            $userId   = $this->request->user()->id;
            $userRole = $this->request->user()->role;
        }

        AuditLog::log(
            action:     $action,
            userId:     $userId,
            userRole:   $userRole,
            entityType: $entityType,
            entityId:   $entityId,
            oldValues:  $oldValues,
            newValues:  $newValues,
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
            status:     $status
        );
    }

    // ==========================================
    // Actions prédéfinies
    // ==========================================

    public function logLogin(int $userId, string $role, bool $success): void
    {
        $this->log(
            action:   $success ? 'auth.login.success' : 'auth.login.failed',
            userId:   $userId,
            userRole: $role,
            status:   $success ? 'success' : 'failure'
        );
    }

    public function logLogout(int $userId, string $role): void
    {
        $this->log(
            action:   'auth.logout',
            userId:   $userId,
            userRole: $role
        );
    }

    public function logRegistration(int $userId): void
    {
        $this->log(
            action:     'auth.register',
            userId:     $userId,
            userRole:   'student',
            entityType: 'User',
            entityId:   $userId
        );
    }

    public function logOtpVerified(int $userId, string $type): void
    {
        $this->log(
            action:   "otp.verified.{$type}",
            userId:   $userId,
            userRole: 'student'
        );
    }

    public function logReclamationCreated(int $userId, int $reclamationId): void
    {
        $this->log(
            action:     'reclamation.created',
            userId:     $userId,
            userRole:   'student',
            entityType: 'Reclamation',
            entityId:   $reclamationId
        );
    }

    public function logReclamationStatusChanged(
        int    $userId,
        string $userRole,
        int    $reclamationId,
        string $oldStatus,
        string $newStatus
    ): void {
        $this->log(
            action:     'reclamation.status_changed',
            userId:     $userId,
            userRole:   $userRole,
            entityType: 'Reclamation',
            entityId:   $reclamationId,
            oldValues:  ['status' => $oldStatus],
            newValues:  ['status' => $newStatus]
        );
    }

    public function logNotePublished(int $userId, int $moduleId): void
    {
        $this->log(
            action:     'note.published',
            userId:     $userId,
            userRole:   'admin',
            entityType: 'Module',
            entityId:   $moduleId
        );
    }

    public function logPasswordChanged(int $userId, string $role): void
    {
        $this->log(
            action:   'user.password_changed',
            userId:   $userId,
            userRole: $role
        );
    }

    public function logProfileUpdated(int $userId, string $role, array $changes): void
    {
        $this->log(
            action:     'user.profile_updated',
            userId:     $userId,
            userRole:   $role,
            entityType: 'User',
            entityId:   $userId,
            newValues:  $changes
        );
    }

    public function logStudentImported(int $adminId, int $count, string $fileName): void
    {
        $this->log(
            action:    'student.bulk_imported',
            userId:    $adminId,
            userRole:  'admin',
            newValues: ['count' => $count, 'file' => $fileName]
        );
    }

    public function logFileUploaded(
        int    $userId,
        string $role,
        string $fileType,
        string $fileName
    ): void {
        $this->log(
            action:    'file.uploaded',
            userId:    $userId,
            userRole:  $role,
            newValues: ['type' => $fileType, 'file' => $fileName]
        );
    }

    public function logSuspiciousActivity(
        string $detail,
        ?int   $userId = null
    ): void {
        $this->log(
            action:    'security.suspicious',
            userId:    $userId,
            newValues: ['detail' => $detail],
            status:    'failure'
        );
    }
}
