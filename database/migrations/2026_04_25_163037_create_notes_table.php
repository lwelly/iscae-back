<?php
// database/migrations/xxxx_create_notes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->restrictOnDelete();
            $table->foreignId('semestre_id')
                  ->constrained('semestres')
                  ->restrictOnDelete();
            $table->string('academic_year', 9);
            $table->decimal('note_controle', 5, 2)->nullable();
            $table->decimal('note_examen', 5, 2)->nullable();
            $table->decimal('note_rattrapage', 5, 2)->nullable();
            $table->decimal('note_finale', 5, 2)->nullable();  // calculée
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            // Un étudiant ne peut avoir qu'une note par module par année
            $table->unique(
                ['student_id', 'module_id', 'academic_year'],
                'uq_notes_student_module_year'
            );
            $table->index(
                ['student_id', 'semestre_id', 'is_published'],
                'idx_notes_student_sem'
            );
            $table->index(
                ['module_id', 'is_published'],
                'idx_notes_module_published'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
