<?php

namespace App\Notifications;

use App\Models\ProyekDenah;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Dikirim ke Drafter saat Admin meminta denah diunggah ulang.
 * Disimpan di tabel notifications (channel database), tampil di lonceng header.
 */
class DenahRevisi extends Notification
{
    public function __construct(
        private ProyekDenah $proyekDenah,
        private string $alasan,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'proyek_denah_id' => $this->proyekDenah->id,
            'judul' => 'Denah perlu diunggah ulang',
            'pesan' => $this->proyekDenah->nama_proyek . ': ' . Str::limit($this->alasan, 70),
            'ikon' => 'fa-redo',
            'warna' => 'warning',
        ];
    }
}