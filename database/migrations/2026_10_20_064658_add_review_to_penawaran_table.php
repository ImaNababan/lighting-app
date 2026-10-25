<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Enum lama (draft, final) diganti string supaya bisa menampung
        // status baru: diajukan dan ditolak. Nilai lama tetap aman.
        Schema::table('penawaran', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->change();
        });

        Schema::table('penawaran', function (Blueprint $table) {
            $table->timestamp('diajukan_at')->nullable()->after('status');
            $table->foreignId('reviewer_id')->nullable()->after('diajukan_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('direview_at')->nullable()->after('reviewer_id');
            $table->text('catatan_review')->nullable()->after('direview_at');
        });

        // Petakan status denah lama ke alur baru
        // (review Manajer sekarang ada di penawaran, bukan di denah).
        DB::table('proyek_denah')->where('status', 'diajukan')->update(['status' => 'dikirim']);
        DB::table('proyek_denah')->where('status', 'approved')->update(['status' => 'processed']);
        DB::table('proyek_denah')->where('status', 'rejected')->update(['status' => 'uploaded']);
    }

    public function down(): void
    {
        Schema::table('penawaran', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
            $table->dropColumn(['diajukan_at', 'reviewer_id', 'direview_at', 'catatan_review']);
        });
    }
};