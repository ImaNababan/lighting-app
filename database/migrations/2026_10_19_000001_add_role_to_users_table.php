<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default 'drafter' = level paling rendah, supaya akun baru dari
            // halaman register tidak otomatis menjadi admin.
            $table->string('role', 20)->default('drafter')->after('password')->index();
        });

        // Akun yang sudah ada sebelum migrasi ini = kondisi saat ini (satu level), jadi admin.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
