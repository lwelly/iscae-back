<?php
// database/migrations/xxxx_create_login_attempts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('login_identifier', 150); // pas de FK, sécurité
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_successful')->default(false);
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('attempted_at')->useCurrent();

            $table->index(
                ['login_identifier', 'ip_address', 'attempted_at'],
                'idx_attempts_identifier_ip'
            );
            $table->index('attempted_at', 'idx_attempts_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
