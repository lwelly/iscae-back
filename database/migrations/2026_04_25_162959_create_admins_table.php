<?php
// database/migrations/xxxx_create_admins_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->nullOnDelete();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->enum('role_label', [
                'super_admin',
                'admin',
                'department_head',
                'staff'
            ])->default('admin');
            $table->boolean('two_fa_enabled')->default(true);
            $table->unsignedTinyInteger('two_fa_reask_days')->default(30);
            $table->timestamp('last_two_fa_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role_label', 'department_id'], 'idx_admins_role_dept');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
