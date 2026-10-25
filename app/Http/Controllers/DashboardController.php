<?php

namespace App\Http\Controllers;

use App\Models\KatalogLampu;
use App\Models\Klien;
use App\Models\Penawaran;
use App\Models\PenawaranItem;
use App\Models\ProyekDenah;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    private const BULAN_PENDEK = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    private const WARNA_DENAH = [
        ProyekDenah::STATUS_UPLOADED => '#adb5bd',
        ProyekDenah::STATUS_DIKIRIM => '#1d7af3',
        ProyekDenah::STATUS_PROCESSING => '#ffad46',
        ProyekDenah::STATUS_PROCESSED => '#31ce36',
        ProyekDenah::STATUS_FAILED => '#f25961',
    ];

    private const WARNA_PENAWARAN = [
        Penawaran::STATUS_DRAFT => '#ffad46',
        Penawaran::STATUS_DIAJUKAN => '#1d7af3',
        Penawaran::STATUS_DITOLAK => '#f25961',
        Penawaran::STATUS_FINAL => '#31ce36',
    ];

    /**
     * Dashboard menyesuaikan level: tiap level melihat angka, grafik,
     * dan daftar tugas yang relevan untuk perannya.
     */
    public function index()
    {
        $user = auth()->user();

        $data = match (true) {
            $user->isDrafter() => $this->dataDrafter($user->id),
            $user->isManajer() => $this->dataManajer(),
            default => $this->dataAdmin(),
        };

        return view('dashboard', $data);
    }

    private function dataDrafter(int $userId): array
    {
        $hitung = ProyekDenah::where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bulan = $this->enamBulan();
        $bulanan = ProyekDenah::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['created_at']);

        $tugas = ProyekDenah::with('klien')
            ->where('user_id', $userId)
            ->where('status', ProyekDenah::STATUS_UPLOADED)
            ->latest()->limit(6)->get()
            ->map(fn ($d) => [
                'ikon' => 'fa-paper-plane',
                'warna' => 'secondary',
                'judul' => $d->nama_proyek,
                'sub' => ($d->klien->nama ?? 'Tanpa klien') . ' · belum dikirim ke Admin',
                'aksi' => 'Kirim ke Admin',
                'url' => route('proyek-denah.show', $d->id),
                'waktu' => $d->created_at,
            ]);

        // Penawaran dari denah milik Drafter yang ditolak Manajer: tampilkan alasannya.
$ditolak = Penawaran::with('proyekDenah')
    ->where('status', Penawaran::STATUS_DITOLAK)
    ->whereHas('proyekDenah', fn ($q) => $q->where('user_id', $userId))
    ->orderByDesc('direview_at')->limit(6)->get()
    ->map(fn ($p) => [
        'ikon' => 'fa-undo',
        'warna' => 'danger',
        'judul' => $p->proyekDenah->nama_proyek ?? $p->nomor_penawaran,
        'sub' => 'Penawaran ditolak: ' . Str::limit($p->catatan_review ?? '-', 50),
        'aksi' => 'Lihat alasan',
        'url' => route('proyek-denah.show', $p->proyek_denah_id),
        'waktu' => $p->direview_at ?? $p->created_at,
    ]);

$tugas = $ditolak->concat($tugas)->sortByDesc('waktu')->take(6)->values();

        $terbaru = ProyekDenah::with('klien')
            ->where('user_id', $userId)
            ->latest()->limit(5)->get()
            ->map(fn ($d) => [
                'ikon' => 'fa-file-pdf',
                'warna' => $d->statusBadge(),
                'judul' => $d->nama_proyek,
                'sub' => $d->klien->nama ?? 'Tanpa klien',
                'badge' => $d->statusBadge(),
                'status' => $d->statusLabel(),
                'url' => route('proyek-denah.show', $d->id),
                'waktu' => $d->created_at,
            ]);

        return [
            'aksi' => [
                ['label' => 'Upload Denah Baru', 'url' => route('proyek-denah.create'), 'kelas' => 'btn-primary', 'ikon' => 'fa-plus'],
            ],
            'kartu' => [
                $this->kartu('Denah saya', $hitung->sum(), 'fa-folder-open', 'primary', route('proyek-denah.index', ['status' => 'semua'])),
                $this->kartu('Belum dikirim', $hitung[ProyekDenah::STATUS_UPLOADED] ?? 0, 'fa-file-upload', 'secondary', route('proyek-denah.index', ['status' => ProyekDenah::STATUS_UPLOADED])),
                $this->kartu('Dikirim ke Admin', $hitung[ProyekDenah::STATUS_DIKIRIM] ?? 0, 'fa-paper-plane', 'info', route('proyek-denah.index', ['status' => ProyekDenah::STATUS_DIKIRIM])),
                $this->kartu('Titik terhitung', $hitung[ProyekDenah::STATUS_PROCESSED] ?? 0, 'fa-check-circle', 'success', route('proyek-denah.index', ['status' => ProyekDenah::STATUS_PROCESSED])),
            ],
            'kartu2' => null,
            'tren' => [
                'judul' => 'Denah diunggah (6 bulan terakhir)',
                'label' => array_values($bulan),
                'data' => $this->perBulan($bulanan, 'created_at', fn () => 1, $bulan),
                'satuan' => 'denah',
                'rupiah' => false,
                'warna' => 'rgba(29, 122, 243, 0.85)',
            ],
            'donat' => $this->donat('Status denah saya', $hitung, ProyekDenah::STATUS_LABEL, self::WARNA_DENAH),
            'topKode' => null,
            'judulTugas' => 'Perlu kamu kirim',
            'tugas' => $tugas,
            'judulTerbaru' => 'Denah terbaru',
            'terbaru' => $terbaru,
        ];
    }

    private function dataManajer(): array
    {
        $hitung = Penawaran::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $nilaiFinal = (float) Penawaran::where('status', Penawaran::STATUS_FINAL)->sum('total');

        $bulan = $this->enamBulan();
        $bulanan = Penawaran::where('status', Penawaran::STATUS_FINAL)
            ->whereNotNull('direview_at')
            ->where('direview_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['direview_at', 'total']);

        $tugas = Penawaran::with('proyekDenah')
            ->where('status', Penawaran::STATUS_DIAJUKAN)
            ->orderByDesc('diajukan_at')->limit(6)->get()
            ->map(fn ($p) => [
                'ikon' => 'fa-file-signature',
                'warna' => 'primary',
                'judul' => $p->nomor_penawaran,
                'sub' => ($p->proyekDenah->nama_proyek ?? '-') . ' · ' . $this->rp((float) $p->total),
                'aksi' => 'Review',
                'url' => route('penawaran.show', $p->id),
                'waktu' => $p->diajukan_at ?? $p->created_at,
            ]);

        $terbaru = Penawaran::with('proyekDenah')
            ->whereIn('status', [Penawaran::STATUS_FINAL, Penawaran::STATUS_DITOLAK])
            ->orderByDesc('direview_at')->limit(5)->get()
            ->map(fn ($p) => [
                'ikon' => $p->status === Penawaran::STATUS_FINAL ? 'fa-check' : 'fa-times',
                'warna' => $p->statusBadge(),
                'judul' => $p->nomor_penawaran,
                'sub' => ($p->proyekDenah->nama_proyek ?? '-') . ' · ' . $this->rp((float) $p->total),
                'badge' => $p->statusBadge(),
                'status' => $p->statusLabel(),
                'url' => route('penawaran.show', $p->id),
                'waktu' => $p->direview_at ?? $p->created_at,
            ]);

        return [
            'aksi' => [
                ['label' => 'Review Penawaran', 'url' => route('penawaran.index'), 'kelas' => 'btn-primary', 'ikon' => 'fa-file-signature'],
            ],
            'kartu' => [
                $this->kartu('Menunggu persetujuan', $hitung[Penawaran::STATUS_DIAJUKAN] ?? 0, 'fa-hourglass-half', 'primary', route('penawaran.index', ['status' => Penawaran::STATUS_DIAJUKAN])),
                $this->kartu('Disetujui', $hitung[Penawaran::STATUS_FINAL] ?? 0, 'fa-check-circle', 'success', route('penawaran.index', ['status' => Penawaran::STATUS_FINAL])),
                $this->kartu('Ditolak', $hitung[Penawaran::STATUS_DITOLAK] ?? 0, 'fa-times-circle', 'danger', route('penawaran.index', ['status' => Penawaran::STATUS_DITOLAK])),
                $this->kartu('Nilai disetujui', $this->rp($nilaiFinal), 'fa-wallet', 'info', route('penawaran.index', ['status' => Penawaran::STATUS_FINAL])),
            ],
            'kartu2' => null,
            'tren' => [
                'judul' => 'Nilai penawaran disetujui per bulan (juta Rp)',
                'label' => array_values($bulan),
                'data' => array_map(
                    fn ($v) => round($v / 1_000_000, 1),
                    $this->perBulan($bulanan, 'direview_at', fn ($r) => (float) $r->total, $bulan)
                ),
                'satuan' => 'juta',
                'rupiah' => true,
                'warna' => 'rgba(49, 206, 54, 0.85)',
            ],
            'donat' => $this->donat(
                'Status penawaran',
                $hitung->except(Penawaran::STATUS_DRAFT),
                Penawaran::STATUS_LABEL,
                self::WARNA_PENAWARAN
            ),
            'topKode' => null,
            'judulTugas' => 'Menunggu keputusanmu',
            'tugas' => $tugas,
            'judulTerbaru' => 'Keputusan terakhir',
            'terbaru' => $terbaru,
        ];
    }

    private function dataAdmin(): array
    {
        $denah = ProyekDenah::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tawar = Penawaran::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $nilaiFinal = (float) Penawaran::where('status', Penawaran::STATUS_FINAL)->sum('total');

        $bulan = $this->enamBulan();
        $bulanan = Penawaran::where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['created_at', 'total']);

        $top = PenawaranItem::selectRaw('kode_lampu, sum(qty) as total')
            ->groupBy('kode_lampu')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Pekerjaan Admin: denah yang perlu dihitung, penawaran ditolak, penawaran draft.
        $tugas = collect();

        ProyekDenah::with(['klien', 'user'])
            ->where('status', ProyekDenah::STATUS_DIKIRIM)
            ->limit(6)->get()
            ->each(fn ($d) => $tugas->push([
                'ikon' => 'fa-cogs',
                'warna' => 'primary',
                'judul' => $d->nama_proyek,
                'sub' => ($d->klien->nama ?? 'Tanpa klien') . ' · dari ' . ($d->user->name ?? 'Drafter'),
                'aksi' => 'Hitung titik',
                'url' => route('proyek-denah.show', $d->id),
                'waktu' => $d->diajukan_at ?? $d->created_at,
            ]));

        Penawaran::with('proyekDenah')
            ->where('status', Penawaran::STATUS_DITOLAK)
            ->limit(6)->get()
            ->each(fn ($p) => $tugas->push([
                'ikon' => 'fa-undo',
                'warna' => 'danger',
                'judul' => $p->nomor_penawaran,
                'sub' => 'Ditolak: ' . Str::limit($p->catatan_review ?? '-', 50),
                'aksi' => 'Revisi',
                'url' => route('penawaran.show', $p->id),
                'waktu' => $p->direview_at ?? $p->created_at,
            ]));

        Penawaran::with('proyekDenah')
            ->where('status', Penawaran::STATUS_DRAFT)
            ->limit(6)->get()
            ->each(fn ($p) => $tugas->push([
                'ikon' => 'fa-edit',
                'warna' => 'warning',
                'judul' => $p->nomor_penawaran,
                'sub' => ($p->proyekDenah->nama_proyek ?? '-') . ' · draft, belum dikirim ke Manajer',
                'aksi' => 'Lanjutkan',
                'url' => route('penawaran.show', $p->id),
                'waktu' => $p->created_at,
            ]));

        $tugas = $tugas->sortByDesc('waktu')->take(6)->values();

        $terbaru = Penawaran::with('proyekDenah')
            ->latest()->limit(5)->get()
            ->map(fn ($p) => [
                'ikon' => 'fa-file-invoice-dollar',
                'warna' => $p->statusBadge(),
                'judul' => $p->nomor_penawaran,
                'sub' => ($p->proyekDenah->nama_proyek ?? '-') . ' · ' . $this->rp((float) $p->total),
                'badge' => $p->statusBadge(),
                'status' => $p->statusLabel(),
                'url' => route('penawaran.show', $p->id),
                'waktu' => $p->created_at,
            ]);

        return [
            'aksi' => [
                ['label' => 'Penawaran', 'url' => route('penawaran.index'), 'kelas' => 'btn-label-info', 'ikon' => 'fa-file-invoice-dollar'],
                ['label' => 'Katalog Lampu', 'url' => route('katalog-lampu.index'), 'kelas' => 'btn-primary', 'ikon' => 'fa-lightbulb'],
            ],
            'kartu' => [
                $this->kartu('Denah perlu dihitung', $denah[ProyekDenah::STATUS_DIKIRIM] ?? 0, 'fa-cogs', 'primary', route('proyek-denah.index', ['status' => ProyekDenah::STATUS_DIKIRIM])),
                $this->kartu('Siap dibuat penawaran', $denah[ProyekDenah::STATUS_PROCESSED] ?? 0, 'fa-lightbulb', 'info', route('proyek-denah.index', ['status' => ProyekDenah::STATUS_PROCESSED])),
                $this->kartu('Penawaran draft', $tawar[Penawaran::STATUS_DRAFT] ?? 0, 'fa-edit', 'warning', route('penawaran.index', ['status' => Penawaran::STATUS_DRAFT])),
                $this->kartu('Ditolak Manajer', $tawar[Penawaran::STATUS_DITOLAK] ?? 0, 'fa-undo', 'danger', route('penawaran.index', ['status' => Penawaran::STATUS_DITOLAK])),
            ],
            'kartu2' => [
                $this->kartu('Nilai disetujui', $this->rp($nilaiFinal), 'fa-wallet', 'success', route('penawaran.index', ['status' => Penawaran::STATUS_FINAL])),
                $this->kartu('Total penawaran', $tawar->sum(), 'fa-file-invoice-dollar', 'primary', route('penawaran.index', ['status' => 'semua'])),
                $this->kartu('Klien', Klien::count(), 'fa-users', 'info', route('klien.index')),
                $this->kartu('Katalog lampu', KatalogLampu::count(), 'fa-box-open', 'secondary', route('katalog-lampu.index')),
            ],
            'tren' => [
                'judul' => 'Nilai penawaran dibuat per bulan (juta Rp)',
                'label' => array_values($bulan),
                'data' => array_map(
                    fn ($v) => round($v / 1_000_000, 1),
                    $this->perBulan($bulanan, 'created_at', fn ($r) => (float) $r->total, $bulan)
                ),
                'satuan' => 'juta',
                'rupiah' => true,
                'warna' => 'rgba(29, 122, 243, 0.85)',
            ],
            'donat' => $this->donat('Status penawaran', $tawar, Penawaran::STATUS_LABEL, self::WARNA_PENAWARAN),
            'topKode' => [
                'judul' => '5 kode lampu paling banyak ditawarkan (total qty)',
                'label' => $top->pluck('kode_lampu')->all(),
                'data' => $top->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
            'judulTugas' => 'Perlu dikerjakan',
            'tugas' => $tugas,
            'judulTerbaru' => 'Penawaran terbaru',
            'terbaru' => $terbaru,
        ];
    }

    private function kartu(string $judul, int|float|string $nilai, string $ikon, string $warna, string $url): array
    {
        return compact('judul', 'nilai', 'ikon', 'warna', 'url');
    }

    /** 6 bulan terakhir: ['2026-05' => 'Mei 26', ...] berurutan dari yang terlama. */
    private function enamBulan(): array
    {
        $bulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->startOfMonth()->subMonths($i);
            $bulan[$d->format('Y-m')] = self::BULAN_PENDEK[$d->month] . ' ' . $d->format('y');
        }

        return $bulan;
    }

    /** Jumlahkan nilai per bulan berdasarkan kolom tanggal. */
    private function perBulan(Collection $baris, string $kolomTanggal, callable $nilai, array $bulan): array
    {
        $hasil = array_fill_keys(array_keys($bulan), 0);

        foreach ($baris as $row) {
            $kunci = $row->{$kolomTanggal}->format('Y-m');
            if (isset($hasil[$kunci])) {
                $hasil[$kunci] += $nilai($row);
            }
        }

        return array_values($hasil);
    }

    /** Data grafik donat: hanya status yang jumlahnya > 0. */
    private function donat(string $judul, Collection $hitung, array $label, array $warna): array
    {
        $l = [];
        $d = [];
        $w = [];

        foreach ($label as $status => $nama) {
            $n = (int) ($hitung[$status] ?? 0);
            if ($n > 0) {
                $l[] = $nama;
                $d[] = $n;
                $w[] = $warna[$status] ?? '#adb5bd';
            }
        }

        return ['judul' => $judul, 'label' => $l, 'data' => $d, 'warna' => $w];
    }

    /** Rupiah ringkas: Rp 12,5 jt / Rp 1,2 M / Rp 850.000 */
    private function rp(float $n): string
    {
        if ($n >= 1_000_000_000) {
            return 'Rp ' . rtrim(rtrim(number_format($n / 1_000_000_000, 1, ',', '.'), '0'), ',') . ' M';
        }
        if ($n >= 1_000_000) {
            return 'Rp ' . rtrim(rtrim(number_format($n / 1_000_000, 1, ',', '.'), '0'), ',') . ' jt';
        }

        return 'Rp ' . number_format($n, 0, ',', '.');
    }
}