<?php
// database/migrations/xxxx_create_students_preloaded_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students_preloaded', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 20)->unique();
            $table->string('nni', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 150)->unique();
            $table->string('filiere_code', 20);
            $table->string('niveau_code', 10);
            $table->string('academic_year', 9)->default('2024-2025');
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
            $table->string('import_batch', 50)->nullable();
            $table->string('import_file', 255)->nullable();
            $table->timestamps();

            $table->unique(['matricule', 'nni'], 'uq_preloaded_matricule_nni');
            $table->index(['filiere_code', 'niveau_code'], 'idx_preloaded_filiere_niveau');
            $table->index('is_registered', 'idx_preloaded_registered');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students_preloaded');
    }
};
