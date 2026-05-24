<?php
// database/migrations/xxxx_create_reclamation_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')
                  ->constrained('reclamations')
                  ->cascadeOnDelete();
            $table->foreignId('changed_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('reclamation_id', 'idx_reclam_history_reclam');
            $table->index('changed_at', 'idx_reclam_history_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_history');
    }
};
