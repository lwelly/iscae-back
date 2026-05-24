<?php
// database/migrations/xxxx_create_semestres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('niveau_id')
                  ->constrained('niveaux')
                  ->restrictOnDelete();
            $table->string('code', 10);         // S1, S2 ...
            $table->string('label', 50);
            $table->unsignedTinyInteger('order_index');
            $table->string('academic_year', 9); // 2024-2025
            $table->boolean('is_open')->default(false);
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->timestamps();

            // Un semestre par code + année académique
            $table->unique(['code', 'academic_year'], 'uq_semestres_code_year');
            $table->index(['is_open', 'academic_year'], 'idx_semestres_open_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semestres');
    }
};
