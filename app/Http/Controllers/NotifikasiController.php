<?php

namespace App\Http\Controllers;

use App\Models\ProyekDenah;

class NotifikasiController extends Controller
{
    /** Tandai satu notifikasi terbaca lalu buka denah yang dimaksud. */
    public function baca(string $id)
    {
        $notif = auth()->user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        $denahId = $notif->data['proyek_denah_id'] ?? null;

        if ($denahId && ProyekDenah::whereKey($denahId)->exists()) {
            return redirect()->route('proyek-denah.show', $denahId);
        }

        return redirect()->route('dashboard');
    }

    public function bacaSemua()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}