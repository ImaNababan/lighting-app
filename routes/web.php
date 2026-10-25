<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogLampuController;
use App\Http\Controllers\KlienController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProyekDenahController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifikasi (lonceng di header): buka satu notifikasi / tandai semua terbaca.
    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('notifikasi/{id}', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    /*
    |----------------------------------------------------------------------
    | DRAFTER - unggah denah PDF lalu kirim ke Admin
    |----------------------------------------------------------------------
    | Rute 'create' harus didaftarkan sebelum 'proyek-denah/{proyekDenah}'.
    */
    Route::middleware('role:drafter')->group(function () {
        Route::get('proyek-denah/create', [ProyekDenahController::class, 'create'])->name('proyek-denah.create');
        Route::post('proyek-denah', [ProyekDenahController::class, 'store'])->name('proyek-denah.store');
        Route::post('proyek-denah/{proyekDenah}/kirim', [ProyekDenahController::class, 'kirim'])->name('proyek-denah.kirim');
        Route::post('proyek-denah/{proyekDenah}/upload-ulang', [ProyekDenahController::class, 'uploadUlang'])->name('proyek-denah.upload-ulang');
    });

    // Daftar dan detail denah (isi daftar disaring per level di controller).
    Route::get('proyek-denah', [ProyekDenahController::class, 'index'])->name('proyek-denah.index');
    Route::get('proyek-denah/{proyekDenah}', [ProyekDenahController::class, 'show'])->name('proyek-denah.show');

    // Hapus: Drafter (denah miliknya, selama belum dikirim) dan Admin.
    Route::delete('proyek-denah/{proyekDenah}', [ProyekDenahController::class, 'destroy'])
        ->middleware('role:drafter,admin')
        ->name('proyek-denah.destroy');

    // Klien dipakai Drafter saat mengunggah denah, dikelola Admin.
    Route::middleware('role:drafter,admin')->group(function () {
        Route::get('klien/search/ajax', [KlienController::class, 'search'])->name('klien.search');
        Route::resource('klien', KlienController::class);
    });

    /*
    |----------------------------------------------------------------------
    | ADMIN - hitung titik, buat/revisi penawaran, kirim ke Manajer
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::resource('katalog-lampu', KatalogLampuController::class);

        Route::post('proyek-denah/{proyekDenah}/process', [ProyekDenahController::class, 'process'])->name('proyek-denah.process');
        Route::post('proyek-denah/{proyekDenah}/minta-revisi', [ProyekDenahController::class, 'mintaRevisi'])->name('proyek-denah.minta-revisi');

        Route::get('proyek-denah/{proyekDenah}/penawaran/create', [PenawaranController::class, 'create'])
            ->name('penawaran.create');
        Route::post('proyek-denah/{proyekDenah}/penawaran', [PenawaranController::class, 'store'])
            ->name('penawaran.store');

        Route::post('penawaran/{penawaran}/ajukan', [PenawaranController::class, 'ajukan'])
            ->name('penawaran.ajukan');
        Route::resource('penawaran', PenawaranController::class)
            ->only(['edit', 'update', 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | MANAJER - menyetujui atau menolak penawaran dari Admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:manajer')->group(function () {
        Route::post('penawaran/{penawaran}/approve', [PenawaranController::class, 'approve'])
            ->name('penawaran.approve');
        Route::post('penawaran/{penawaran}/reject', [PenawaranController::class, 'reject'])
            ->name('penawaran.reject');
    });

    // Daftar dan detail penawaran: Admin dan Manajer.
    Route::middleware('role:admin,manajer')->group(function () {
        Route::resource('penawaran', PenawaranController::class)
            ->only(['index', 'show']);
    });
});

require __DIR__ . '/auth.php';