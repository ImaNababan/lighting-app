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
        Schema::create('katalog_lampu', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->decimal('harga_satuan', 15, 2);
            $table->enum('satuan', ['pcs', 'set', 'mtr'])->default('pcs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('katalog_lampu');
    }
};
