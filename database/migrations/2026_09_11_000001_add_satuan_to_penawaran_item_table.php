<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabel penawaran_item belum punya kolom satuan (pcs/set/mtr), padahal
     * dibutuhkan untuk ditampilkan di tabel penawaran (kolom "Qty" pada
     * dokumen penawaran perlu satuan seperti "50 mtr", "10 pcs", dst).
     * Ditambahkan lewat migration baru, bukan mengubah migration lama.
     */
    public function up(): void
    {
        Schema::table('penawaran_item', function (Blueprint $table) {
            $table->string('satuan', 20)->default('pcs')->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_item', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }
};