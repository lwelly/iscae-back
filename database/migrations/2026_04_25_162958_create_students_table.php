<?php
// database/migrations/xxxx_create_students_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('preloaded_id')
                  ->unique()
                  ->constrained('students_preloaded')
                  ->restrictOnDelete();
            $table->foreignId('filiere_id')
                  ->constrained('filieres')
                  ->restrictOnDelete();
            $table->foreignId('niveau_id')
                  ->constrained('niveaux')
                  ->restrictOnDelete();
            $table->string('matricule', 20)->unique();
            $table->string('nni', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 150)->unique();
            $table->string('telephone', 20)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('academic_year', 9)->default('2024-2025');
            $table->enum('status', ['active', 'suspended', 'graduated', 'expelled'])
                  ->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['filiere_id', 'niveau_id'], 'idx_students_filiere_niveau');
            $table->index('status', 'idx_students_status');
            $table->index('matricule', 'idx_students_matricule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
