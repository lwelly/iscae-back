<?php
// app/Http/Controllers/API/Admin/DocumentController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private AuditService        $auditService,
        private NotificationService $notificationService
    ) {}

    // ==========================================
    // GET /api/v1/admin/documents
    // ==========================================
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['student']);

        // Filtres
        if ($request->has('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->has('is_published')) {
            $query->where('is_published', (bool) $request->query('is_published'));
        }
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->query('academic_year'));
        }

        $documents = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => DocumentResource::collection($documents->items()),
            'meta'    => [
                'total'        => $documents->total(),
                'current_page' => $documents->currentPage(),
                'last_page'    => $documents->lastPage(),
            ],
        ]);
    }

    // ==========================================
    // POST /api/v1/admin/documents
    // ==========================================
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'    => ['required', 'integer', 'exists:students,id'],
            'type'          => [
                'required',
                'in:attestation_inscription,carte_etudiant,releve_notes,demande_stage,autre',
            ],
            'title'         => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'file'          => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
            ],
        ]);

        $file        = $request->file('file');
        $student     = Student::findOrFail($request->input('student_id'));
        $storedName  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path        = $file->storeAs(
            'documents/' . $student->id,
            $storedName,
            'private'
        );

        $document = Document::create([
            'student_id'    => $student->id,
            'type'          => $request->input('type'),
            'title'         => $request->input('title'),
            'stored_name'   => $storedName,
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'academic_year' => $request->input('academic_year',
                \App\Models\Setting::getValue('current_academic_year', '2024-2025')
            ),
            'is_published'  => false,
        ]);

        $this->auditService->logFileUploaded(
            $request->user()->id,
            'admin',
            $request->input('type'),
            $file->getClientOriginalName()
        );

        return response()->json([
            'success' => true,
            'message' => 'Document uploadé avec succès.',
            'data'    => new DocumentResource($document->load('student')),
        ], 201);
    }

    // ==========================================
    // PUT /api/v1/admin/documents/{id}/publish
    // ==========================================
    public function publish(Request $request, int $id): JsonResponse
    {
        $document = Document::with('student.user')->findOrFail($id);

        if ($document->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document est déjà publié.',
                'code'    => 'ALREADY_PUBLISHED',
            ], 422);
        }

        $document->update([
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        // Notifier l'étudiant
        if ($document->student?->user) {
            $this->notificationService->notifyDocumentReady(
                $document->student->user,
                $document->type,
                $document->title
            );
        }

        $this->auditService->log(
            action:     'document.published',
            userId:     $request->user()->id,
            userRole:   'admin',
            entityType: 'Document',
            entityId:   $document->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Document publié et étudiant notifié.',
            'data'    => new DocumentResource($document->fresh('student')),
        ]);
    }

    // ==========================================
    // DELETE /api/v1/admin/documents/{id}
    // ==========================================
    public function destroy(Request $request, int $id): JsonResponse
    {
        $document = Document::findOrFail($id);

        // Supprimer le fichier physique
        if (Storage::disk('private')->exists($document->storage_path)) {
            Storage::disk('private')->delete($document->storage_path);
        }

        $this->auditService->log(
            action:     'document.deleted',
            userId:     $request->user()->id,
            userRole:   'admin',
            entityType: 'Document',
            entityId:   $document->id,
            oldValues:  ['title' => $document->title, 'type' => $document->type]
        );

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès.',
        ]);
    }

    // ==========================================
    // GET /api/v1/admin/documents/{id}/download
    // ==========================================
    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (!Storage::disk('private')->exists($document->storage_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable.',
                'code'    => 'FILE_NOT_FOUND',
            ], 404);
        }

        $this->auditService->log(
            action:     'document.downloaded_by_admin',
            userId:     $request->user()->id,
            userRole:   'admin',
            entityType: 'Document',
            entityId:   $document->id
        );

        return Storage::disk('private')->download(
            $document->storage_path,
            $document->title . '.' . pathinfo($document->stored_name, PATHINFO_EXTENSION)
        );
    }
}
