<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penawaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_denah_id')->constrained('proyek_denah')->cascadeOnDelete();
            $table->string('nomor_penawaran', 100)->unique();
            $table->date('tanggal_penawaran');
            $table->string('atas_nama');
            $table->string('nomor_telepon', 30)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('ppn_persen', 5, 2)->default(11.00);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran');
    }
};
