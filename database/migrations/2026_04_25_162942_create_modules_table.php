<?php
// database/migrations/xxxx_create_modules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')
                  ->constrained('filieres')
                  ->restrictOnDelete();
            $table->foreignId('semestre_id')
                  ->constrained('semestres')
                  ->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->unsignedTinyInteger('coefficient')->default(1);
            $table->unsignedTinyInteger('credits')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['filiere_id', 'semestre_id'], 'idx_modules_filiere_semestre');
            $table->index('is_active', 'idx_modules_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
