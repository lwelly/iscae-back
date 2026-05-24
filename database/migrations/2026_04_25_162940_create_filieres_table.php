<?php
// database/migrations/xxxx_create_filieres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active'], 'idx_filieres_dept_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filieres');
    }
};
