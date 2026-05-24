<?php
// database/migrations/xxxx_create_reclamation_attachments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamation_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')
                  ->constrained('reclamations')
                  ->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_name', 255)->unique(); // UUID filename
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');          // en octets, max 10MB
            $table->boolean('is_scanned')->default(false);
            $table->boolean('is_safe')->default(false);
            $table->timestamps();

            $table->index('reclamation_id', 'idx_attach_reclamation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_attachments');
    }
};
