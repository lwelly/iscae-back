<?php
// database/migrations/xxxx_create_reclamations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30)->unique(); // RECL-2024-000001
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->restrictOnDelete();
            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->restrictOnDelete();
            $table->foreignId('semestre_id')
                  ->constrained('semestres')
                  ->restrictOnDelete();
            $table->foreignId('note_id')
                  ->nullable()
                  ->constrained('notes')
                  ->nullOnDelete();
            $table->string('academic_year', 9);
            $table->enum('type', ['controle', 'examen', 'rattrapage']);
            $table->decimal('note_actuelle', 5, 2)->nullable();
            $table->decimal('note_reclamee', 5, 2)->nullable(); // uniquement controle
            $table->text('justification');
            $table->enum('status', [
                'submitted',
                'received',
                'in_review',
                'resolved',
                'rejected',
                'escalated'
            ])->default('submitted');
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to')
                  ->nullable()
                  ->constrained('admins')
                  ->nullOnDelete();
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('admins')
                  ->nullOnDelete();
            $table->text('admin_response')->nullable();
            $table->foreignId('responded_by')
                  ->nullable()
                  ->constrained('admins')
                  ->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('meeting_scheduled_at')->nullable();
            $table->string('meeting_location', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Règle métier : pas de doublon par étudiant/module/type/année
            $table->unique(
                ['student_id', 'module_id', 'type', 'academic_year'],
                'uq_reclamation_unique'
            );
            $table->index(
                ['status', 'academic_year'],
                'idx_reclam_status_year'
            );
            $table->index(
                ['student_id', 'status'],
                'idx_reclam_student_status'
            );
            $table->index(
                ['module_id', 'status'],
                'idx_reclam_module_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
