<?php
// database/migrations/xxxx_create_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->enum('type', [
                'attestation_inscription',
                'carte_etudiant',
                'releve_notes',
                'demande_stage',
                'autre'
            ]);
            $table->string('title', 255);
            $table->string('stored_name', 255)->unique();
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->string('academic_year', 9)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'type'], 'idx_docs_student_type');
            $table->index('is_published', 'idx_docs_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
