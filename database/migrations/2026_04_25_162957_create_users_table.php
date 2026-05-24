<?php
// database/migrations/xxxx_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['student', 'admin'])->default('student');
            $table->string('login_identifier', 50)->unique(); // matricule ou email
            $table->string('email', 150)->unique();
            $table->string('password');                       // bcrypt hash
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedTinyInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role', 'idx_users_role');
            $table->index(['is_active', 'is_verified'], 'idx_users_active_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
