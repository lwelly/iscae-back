<?php
// database/migrations/xxxx_create_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();  // UUID, généré par Laravel
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('type', 100);    // ex: ReclamationStatusChanged
            $table->string('title', 255);
            $table->text('body');
            $table->json('data')->nullable();
            $table->enum('channel', ['in_app', 'email', 'both'])->default('both');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('notifiable_type', 100)->nullable(); // morphable
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->foreignId('sent_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['user_id', 'is_read', 'created_at'],
                'idx_notif_user_read'
            );
            $table->index(
                ['notifiable_type', 'notifiable_id'],
                'idx_notif_morphable'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
