<?php

namespace App\Http\Controllers;

use App\Models\ProyekDenah;
use App\Models\TitikLampu;
use App\Models\KatalogLampu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use App\Models\User;
use App\Notifications\StatusUpdate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ProyekDenahController extends Controller
{
    /**
     * Daftar denah, isinya menyesuaikan level pengguna:
     * - Drafter : hanya denah miliknya sendiri
     * - Admin   : semua denah, default tab "dikirim" (siap dihitung titiknya)
     * - Manajer : semua denah (biasanya Manajer membuka denah dari halaman penawaran)
     * Tab lain bisa dipilih lewat ?status=... ; ?status=semua menampilkan semuanya.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $default = $user->isAdmin() ? ProyekDenah::STATUS_DIKIRIM : 'semua';

        $status = $request->query('status');
        if (
            !is_string($status)
            || ($status !== 'semua' && !array_key_exists($status, ProyekDenah::STATUS_LABEL))
        ) {
            $status = $default;
        }

        $query = ProyekDenah::with(['klien', 'user', 'penawaran'])->latest();
        if ($user->isDrafter()) {
            $query->where('user_id', $user->id);
        }
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $proyek = $query->paginate(10)->withQueryString();

        $hitung = ProyekDenah::query()
            ->when($user->isDrafter(), fn($q) => $q->where('user_id', $user->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('proyek-denah.index', compact('proyek', 'status', 'hitung'));
    }

    public function create()
    {
        return view('proyek-denah.create');
    }

    /**
     * Drafter hanya mengunggah PDF. Penghitungan titik dilakukan Admin
     * setelah denah dikirim.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'klien_id' => 'required|exists:klien,id',
            'nama_proyek' => 'required|string|max:255',
            'file_denah' => 'required|file|mimes:pdf|max:20480',
        ]);

        $path = $request->file('file_denah')->store('proyek-denah/asli', 'public');

        $proyek = ProyekDenah::create([
            'user_id' => auth()->id(),
            'klien_id' => $validated['klien_id'],
            'nama_proyek' => $validated['nama_proyek'],
            'file_asli_path' => $path,
            'status' => ProyekDenah::STATUS_UPLOADED,
        ]);

        return redirect()->route('proyek-denah.show', $proyek->id)
            ->with('success', 'Denah berhasil diunggah. Klik "Kirim ke Admin" untuk meneruskannya.');
    }

    public function show(ProyekDenah $proyekDenah)
    {
        $this->pastikanBolehMelihat($proyekDenah);

        $proyekDenah->load(['klien', 'user', 'penawaran.reviewer']);
        $titikLampu = $proyekDenah->titikLampu()->orderBy('id')->get();

        // Rekap SEMUA kode yang terdeteksi di PDF, bukan cuma yang cocok
        // dengan katalog_lampu, supaya kode yang belum ada di pricelist
        // tetap kelihatan (dengan status "tidak tersedia").
        $rekap = $titikLampu
            ->groupBy('kode_terdeteksi')
            ->map(function ($group) {
                return [
                    'kode' => $group->first()->kode_terdeteksi,
                    'jumlah' => $group->count(),
                    'tersedia' => $group->first()->kode_final !== null,
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        return view('proyek-denah.show', compact('proyekDenah', 'titikLampu', 'rekap'));
    }

    public function destroy(ProyekDenah $proyekDenah)
    {
        $user = auth()->user();

        if ($user->isDrafter()) {
            $this->pastikanPemilik($proyekDenah);

            if (!$proyekDenah->bisaDihapusDrafter()) {
                return back()->with('error', 'Denah yang sudah dikirim ke Admin tidak bisa dihapus.');
            }
        }

        if ($proyekDenah->file_asli_path) {
            Storage::disk('public')->delete($proyekDenah->file_asli_path);
        }
        $proyekDenah->delete();

        return redirect()->route('proyek-denah.index')
            ->with('success', 'Proyek denah berhasil dihapus.');
    }

    /**
     * Admin menghitung titik lampu (menjalankan deteksi Python)
     * setelah denah dikirim Drafter.
     */
    public function process(ProyekDenah $proyekDenah)
    {
        if (!$proyekDenah->bisaDihitung()) {
            return back()->with('error', 'Titik lampu baru bisa dihitung setelah denah dikirim Drafter.');
        }

        $this->jalankanDeteksi($proyekDenah);

        if ($proyekDenah->status === ProyekDenah::STATUS_FAILED) {
            return back()->with('error', 'Hitung titik gagal: ' . $proyekDenah->catatan);
        }

        $jumlah = $proyekDenah->titikLampu()->count();
        return back()->with('success', "Hitung titik selesai. Terdeteksi {$jumlah} titik.");
    }

    /**
     * Drafter mengirim denah ke Admin.
     */
    public function kirim(ProyekDenah $proyekDenah)
    {
        $this->pastikanPemilik($proyekDenah);

        if (!$proyekDenah->bisaDikirim()) {
            return back()->with('error', 'Denah ini tidak bisa dikirim pada status sekarang.');
        }

        $proyekDenah->update([
            'status' => ProyekDenah::STATUS_DIKIRIM,
            'diajukan_at' => now(),
        ]);

        $this->notifikasiKeAdmin($proyekDenah, 'Denah baru masuk');

        return redirect()->route('proyek-denah.show', $proyekDenah->id)
            ->with('success', 'Denah berhasil dikirim ke Admin.');
    }

    /**
     * Admin meminta Drafter mengunggah ulang denah (mis. denah salah, kurang
     * lengkap, atau hitung titik gagal). Status denah berubah jadi "Perlu revisi"
     * dan Drafter menerima notifikasi, jadi ada dua pengingat: status dan notif.
     */
    public function mintaRevisi(Request $request, ProyekDenah $proyekDenah)
    {
        if (!$proyekDenah->bisaMintaRevisi()) {
            return back()->with('error', 'Upload ulang tidak bisa diminta pada status denah sekarang.');
        }

        $validated = $request->validate([
            'catatan_review' => 'required|string|min:5|max:1000',
        ]);

        $proyekDenah->update([
            'status' => ProyekDenah::STATUS_REVISI,
            'reviewer_id' => auth()->id(),
            'direview_at' => now(),
            'catatan_review' => $validated['catatan_review'],
        ]);

        $proyekDenah->user?->notify(new StatusUpdate(
            judul: 'Denah perlu diunggah ulang',
            pesan: $proyekDenah->nama_proyek . ': ' . Str::limit($validated['catatan_review'], 70),
            url: route('proyek-denah.show', $proyekDenah->id),
            ikon: 'fa-redo',
            warna: 'warning',
            proyekDenahId: $proyekDenah->id,
        ));

        return redirect()->route('proyek-denah.show', $proyekDenah->id)
            ->with('success', 'Permintaan upload ulang sudah dikirim ke Drafter.');
    }

    /**
     * Drafter mengganti file denah.
     * - Sebelum dikirim: file diganti saja, status tetap Diunggah.
     * - Saat revisi (diminta Admin / penawaran ditolak): file diganti, hasil hitung
     *   lama dibuang, dan denah langsung kembali ke antrean Admin supaya bisa
     *   dihitung ulang.
     */
    public function uploadUlang(Request $request, ProyekDenah $proyekDenah)
    {
        $this->pastikanPemilik($proyekDenah);

        if (!$proyekDenah->bisaUploadUlang()) {
            return back()->with('error', 'File denah tidak bisa diganti pada status ini.');
        }

        $request->validate([
            'file_denah' => 'required|file|mimes:pdf|max:20480',
        ]);

        $sudahDikirim = $proyekDenah->status !== ProyekDenah::STATUS_UPLOADED;

        $pathLama = $proyekDenah->file_asli_path;
        $pathBaru = $request->file('file_denah')->store('proyek-denah/asli', 'public');

        $data = [
            'file_asli_path' => $pathBaru,
            'catatan' => null,
        ];

        if ($sudahDikirim) {
            // Titik dari file lama tidak berlaku lagi untuk file baru.
            $proyekDenah->titikLampu()->delete();

            $data += [
                'status' => ProyekDenah::STATUS_DIKIRIM,
                'diajukan_at' => now(),
                'reviewer_id' => null,
                'direview_at' => null,
                'catatan_review' => null,
            ];
        }

        $proyekDenah->update($data);

        if ($pathLama) {
            Storage::disk('public')->delete($pathLama);
        }

        if ($sudahDikirim) {
            $this->notifikasiKeAdmin($proyekDenah, 'Denah revisi diunggah ulang');
        }
        // Permintaan upload ulang sudah dipenuhi: tandai notifikasinya terbaca.
        auth()->user()->unreadNotifications
            ->filter(fn($notif) => ($notif->data['proyek_denah_id'] ?? null) === $proyekDenah->id)
            ->each->markAsRead();

        return redirect()->route('proyek-denah.show', $proyekDenah->id)
            ->with('success', $sudahDikirim
                ? 'Denah revisi berhasil diunggah dan dikirim ke Admin untuk dihitung ulang.'
                : 'File denah berhasil diganti.');
    }

    /** Drafter hanya boleh membuka denah miliknya; Manajer dan Admin boleh semua. */
    private function pastikanBolehMelihat(ProyekDenah $proyekDenah): void
    {
        if (auth()->user()->isDrafter()) {
            $this->pastikanPemilik($proyekDenah);
        }
    }

    private function notifikasiKeAdmin(ProyekDenah $proyekDenah, string $judul): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->get();

        Notification::send($admin, new StatusUpdate(
            judul: $judul,
            pesan: $proyekDenah->nama_proyek . ' dari ' . (auth()->user()->name ?? 'Drafter'),
            url: route('proyek-denah.show', $proyekDenah->id),
            ikon: 'fa-file-upload',
            warna: 'info',
            proyekDenahId: $proyekDenah->id,
        ));
    }
    private function pastikanPemilik(ProyekDenah $proyekDenah): void
    {
        abort_unless(
            $proyekDenah->user_id === auth()->id(),
            403,
            'Denah ini bukan milik Anda.'
        );
    }

    /**
     * Jalankan script Python untuk mendeteksi kode lampu di file_denah,
     * lalu simpan hasilnya ke tabel titik_lampu. Kode dikenali berdasarkan
     * daftar master di script Python (format kode lampu secara umum),
     * baru kemudian dicocokkan ke katalog_lampu di sini untuk menentukan
     * apakah tersedia di pricelist saat ini atau belum.
     */
    private function jalankanDeteksi(ProyekDenah $proyekDenah): void
    {
        $proyekDenah->update(['status' => ProyekDenah::STATUS_PROCESSING]);

        $fullPdfPath = Storage::disk('public')->path($proyekDenah->file_asli_path);
        $outputJsonPath = storage_path('app/private/ocr_result_' . $proyekDenah->id . '.json');

        // Sesuaikan path ini kalau lokasi venv/script Python kamu beda
        $pythonPath = 'C:\\TA\\pymupdf-venv\\Scripts\\python.exe';
        $scriptPath = 'C:\\TA\\lighting-app\\extract_kode_lampu3.py';

        $process = new Process([$pythonPath, $scriptPath, $fullPdfPath, $outputJsonPath]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful() || !file_exists($outputJsonPath)) {
            $proyekDenah->update([
                'status' => ProyekDenah::STATUS_FAILED,
                'catatan' => $process->getErrorOutput() ?: 'Script Python gagal dijalankan.',
            ]);
            return;
        }

        $result = json_decode(file_get_contents($outputJsonPath), true);

        if (isset($result['error'])) {
            $proyekDenah->update(['status' => ProyekDenah::STATUS_FAILED, 'catatan' => $result['error']]);
            return;
        }

        $proyekDenah->titikLampu()->delete();

        foreach ($result['detail'] as $titik) {
            $kodeTerdeteksi = $titik['kode_terdeteksi'];
            $katalog = $kodeTerdeteksi
                ? KatalogLampu::where('kode', $kodeTerdeteksi)->first()
                : null;

            TitikLampu::create([
                'proyek_denah_id' => $proyekDenah->id,
                'kode_terdeteksi' => $kodeTerdeteksi,
                'kode_final' => $katalog ? $kodeTerdeteksi : null,
                'x' => $titik['x'],
                'y' => $titik['y'],
                'status' => $katalog ? 'cocok' : 'tidak_cocok',
            ]);
        }

        $proyekDenah->update([
            'status' => ProyekDenah::STATUS_PROCESSED,
            'catatan' => null,
        ]);
    }
}