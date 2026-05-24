<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('semestres', function (Blueprint $table) {
            $table->boolean('is_exam_open')->default(false)->after('is_open');
            $table->boolean('is_rattrapage_open')->default(false)->after('is_exam_open');
            $table->timestamp('exam_open_at')->nullable()->after('is_rattrapage_open');
            $table->timestamp('exam_close_at')->nullable()->after('exam_open_at');
            $table->timestamp('rattrapage_open_at')->nullable()->after('exam_close_at');
            $table->timestamp('rattrapage_close_at')->nullable()->after('rattrapage_open_at');
        });
    }
    public function down(): void {
        Schema::table('semestres', function (Blueprint $table) {
            $table->dropColumn([
                'is_exam_open','is_rattrapage_open',
                'exam_open_at','exam_close_at',
                'rattrapage_open_at','rattrapage_close_at',
            ]);
        });
    }
};
