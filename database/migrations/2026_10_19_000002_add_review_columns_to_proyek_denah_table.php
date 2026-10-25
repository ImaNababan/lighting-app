<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Enum lama (uploaded, processing, processed, failed, approved) diganti
        // string supaya bisa menampung status baru: diajukan dan rejected.
        // Nilai yang sudah ada tetap aman.
        Schema::table('proyek_denah', function (Blueprint $table) {
            $table->string('status', 30)->default('uploaded')->change();
        });

        Schema::table('proyek_denah', function (Blueprint $table) {
            $table->timestamp('diajukan_at')->nullable()->after('catatan');
            $table->foreignId('reviewer_id')->nullable()->after('diajukan_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('direview_at')->nullable()->after('reviewer_id');
            $table->text('catatan_review')->nullable()->after('direview_at');
        });
    }

    public function down(): void
    {
        Schema::table('proyek_denah', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
            $table->dropColumn(['diajukan_at', 'reviewer_id', 'direview_at', 'catatan_review']);
        });
    }
};
