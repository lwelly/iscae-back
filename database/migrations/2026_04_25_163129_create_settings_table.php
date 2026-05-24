<?php
// database/migrations/xxxx_create_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])
                  ->default('string');
            $table->string('group', 50)->default('general');
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index('group', 'idx_settings_group');
            $table->index('is_public', 'idx_settings_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
