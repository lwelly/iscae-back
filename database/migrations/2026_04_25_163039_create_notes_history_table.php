<?php
// database/migrations/xxxx_create_notes_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')
                  ->constrained('notes')
                  ->cascadeOnDelete();
            $table->foreignId('changed_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->json('old_values');
            $table->json('new_values');
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('note_id', 'idx_notes_history_note');
            $table->index('changed_at', 'idx_notes_history_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes_history');
    }
};
