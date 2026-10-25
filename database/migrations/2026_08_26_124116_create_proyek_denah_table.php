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
        Schema::create('proyek_denah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('klien_id')
                ->nullable()
                ->after('user_id')
                ->constrained('klien')
                ->nullOnDelete();
            $table->string('nama_proyek');
            $table->string('file_asli_path');
            $table->string('file_render_path')->nullable();
            $table->enum('status', ['uploaded', 'processing', 'processed', 'failed', 'approved'])
                ->default('uploaded');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyek_denah');
        Schema::table('proyek_denah', function (Blueprint $table) {
            $table->dropForeign(['klien_id']);
            $table->dropColumn('klien_id');
        });
    }
};
