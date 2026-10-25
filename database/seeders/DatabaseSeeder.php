<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun contoh untuk tiga level pengguna (HANYA untuk pengembangan/demo:
     * password-nya 'password', ganti sebelum dipakai sungguhan).
     * firstOrCreate: akun dengan email yang sama tidak ditimpa.
     */
    public function run(): void
    {
        $akun = [
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => User::ROLE_ADMIN],
            ['name' => 'Manajer', 'email' => 'manajer@example.com', 'role' => User::ROLE_MANAJER],
            ['name' => 'Drafter', 'email' => 'drafter@example.com', 'role' => User::ROLE_DRAFTER],
        ];

        // 'role' tidak ada di $fillable (supaya tidak bisa diisi dari form register),
        // jadi diisi lewat unguarded.
        User::unguarded(function () use ($akun) {
            foreach ($akun as $a) {
                User::firstOrCreate(
                    ['email' => $a['email']],
                    [
                        'name' => $a['name'],
                        'role' => $a['role'],
                        'password' => 'password',
                        'email_verified_at' => now(),
                    ]
                );
            }
        });
    }
}
