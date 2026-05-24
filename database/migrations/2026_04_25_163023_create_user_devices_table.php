<?php
// database/migrations/xxxx_create_user_devices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('device_fingerprint', 64); // SHA-256 hash
            $table->string('device_name', 150)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->string('trust_token_hash', 64)->nullable(); // SHA-256
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('trusted_until')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'device_fingerprint'],
                'uq_user_device'
            );
            $table->index(
                ['user_id', 'is_trusted', 'trusted_until'],
                'idx_devices_trust'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
