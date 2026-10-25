<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('proyek_denah', 'klien_id')) {
            Schema::table('proyek_denah', function (Blueprint $table) {
                $table->foreignId('klien_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('klien')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('proyek_denah', function (Blueprint $table) {
            $table->dropForeign(['klien_id']);
            $table->dropColumn('klien_id');
        });
    }
};