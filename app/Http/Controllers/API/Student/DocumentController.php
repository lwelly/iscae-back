<?php
// app/Http/Controllers/API/Student/DocumentController.php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    // ==========================================
    // GET /api/v1/student/documents
    // ==========================================
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $documents = Document::where('student_id', $student->id)
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->get();

        // Grouper par type
        $grouped = $documents->groupBy('type')
            ->map(fn($docs, $type) => [
                'type'      => $type,
                'count'     => $docs->count(),
                'documents' => DocumentResource::collection($docs),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'total'   => $documents->count(),
                'grouped' => $grouped,
            ],
        ]);
    }

    // ==========================================
    // GET /api/v1/student/documents/{id}
    // ==========================================
    public function show(Request $request, int $id): JsonResponse
    {
        $student  = $request->user()->student;
        $document = Document::where('student_id', $student->id)
            ->where('is_published', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new DocumentResource($document),
        ]);
    }

    // ==========================================
    // GET /api/v1/student/documents/{id}/download
    // ==========================================
    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $student  = $request->user()->student;
        $document = Document::where('student_id', $student->id)
            ->where('is_published', true)
            ->findOrFail($id);

        // Vérifier que le fichier existe
        if (!Storage::disk('private')->exists($document->storage_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable sur le serveur.',
                'code'    => 'FILE_NOT_FOUND',
            ], 404);
        }

        // Audit téléchargement
        $this->auditService->log(
            action:     'document.downloaded',
            userId:     $request->user()->id,
            userRole:   'student',
            entityType: 'Document',
            entityId:   $document->id
        );

        return Storage::disk('private')->download(
            $document->storage_path,
            $document->title . '.' . pathinfo($document->stored_name, PATHINFO_EXTENSION)
        );
    }
}
