<?php
// database/migrations/xxxx_create_otp_codes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('preloaded_id')->nullable(); // avant création compte
            $table->enum('type', [
                'registration',
                'password_reset',
                'admin_2fa',
                'email_change'
            ]);
            $table->string('code_hash');           // bcrypt du OTP
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(
                ['user_id', 'type', 'is_used', 'expires_at'],
                'idx_otp_lookup'
            );
            $table->index(
                ['preloaded_id', 'type', 'is_used'],
                'idx_otp_preloaded'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
