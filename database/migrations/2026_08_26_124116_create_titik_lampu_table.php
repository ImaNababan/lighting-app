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
        Schema::create('titik_lampu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_denah_id')->constrained('proyek_denah')->cascadeOnDelete();
            $table->string('kode_terdeteksi', 50)->nullable();
            $table->string('kode_final', 50)->nullable();
            $table->integer('x')->nullable();
            $table->integer('y')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->enum('status', ['cocok', 'ambigu', 'tidak_cocok', 'dikoreksi_manual'])
                ->default('tidak_cocok');
            $table->string('crop_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titik_lampu');
    }
};
