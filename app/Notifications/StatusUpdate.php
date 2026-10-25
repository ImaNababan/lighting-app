<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class StatusUpdate extends Notification
{
    public function __construct(
        private string $judul,
        private string $pesan,
        private string $url,
        private string $ikon = 'fa-bell',
        private string $warna = 'primary',
        private ?int $proyekDenahId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'proyek_denah_id' => $this->proyekDenahId,
            'url' => $this->url,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'ikon' => $this->ikon,
            'warna' => $this->warna,
        ];
    }
}