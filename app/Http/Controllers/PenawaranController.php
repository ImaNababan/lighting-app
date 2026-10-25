<?php

namespace App\Http\Controllers;

use App\Helpers\Terbilang;
use App\Models\KatalogLampu;
use App\Models\Penawaran;
use App\Models\ProyekDenah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenawaranController extends Controller
{
    /**
     * Bulan dalam romawi, dipakai untuk format nomor penawaran
     * (IL/SP-GBL/{tahun}/{bulan romawi}/{nomor urut}).
     */
    private const ROMAWI_BULAN = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Daftar penawaran. Filter lewat ?status=...
     * Admin default "semua"; Manajer default "diajukan" dan tidak melihat draft.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $default = $user->isManajer() ? Penawaran::STATUS_DIAJUKAN : 'semua';

        $status = $request->query('status');
        if (! is_string($status)
            || ($status !== 'semua' && ! array_key_exists($status, Penawaran::STATUS_LABEL))
            || ($user->isManajer() && $status === Penawaran::STATUS_DRAFT)) {
            $status = $default;
        }

        $query = Penawaran::with('proyekDenah.klien')->latest();
        if ($user->isManajer()) {
            $query->where('status', '!=', Penawaran::STATUS_DRAFT);
        }
        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $penawaran = $query->paginate(10)->withQueryString();

        $jumlah = Penawaran::query()
            ->when($user->isManajer(), fn ($q) => $q->where('status', '!=', Penawaran::STATUS_DRAFT))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('penawaran.index', compact('penawaran', 'status', 'jumlah'));
    }

    /**
     * Tampilkan form draft penawaran, sudah diisi otomatis dari kode lampu
     * yang terdeteksi & cocok di katalog untuk proyek denah ini. Qty tiap
     * baris masih bisa diubah manual, dan boleh tambah baris lain di form.
     */
    public function create(ProyekDenah $proyekDenah)
    {
        if ($tolak = $this->tolakJikaTitikBelumDihitung($proyekDenah)) {
            return $tolak;
        }

        $proyekDenah->load('klien');

        $items = $proyekDenah->titikLampu()
            ->whereNotNull('kode_final')
            ->get()
            ->groupBy('kode_final')
            ->map(function ($group) {
                $katalog = KatalogLampu::where('kode', $group->first()->kode_final)->first();

                return [
                    'katalog_lampu_id' => $katalog?->id,
                    'kode_lampu' => $group->first()->kode_final,
                    'deskripsi' => $katalog?->deskripsi ?? '',
                    'foto' => $katalog?->foto,
                    'qty' => $group->count(),
                    'satuan' => $katalog?->satuan ?? 'pcs',
                    'harga_satuan' => $katalog ? (float) $katalog->harga_satuan : 0,
                ];
            })
            ->values();

        return view('penawaran.create', [
            'proyekDenah' => $proyekDenah,
            'items' => $items,
            'nomorPenawaran' => $this->generateNomorPenawaran(),
            'tanggalHariIni' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request, ProyekDenah $proyekDenah)
    {
        if ($tolak = $this->tolakJikaTitikBelumDihitung($proyekDenah)) {
            return $tolak;
        }

        $validated = $request->validate($this->aturanValidasi());

        [$subtotal, $ppnPersen, $total] = $this->hitungTotal($validated['items']);

        $penawaran = Penawaran::create([
            'proyek_denah_id' => $proyekDenah->id,
            'nomor_penawaran' => $validated['nomor_penawaran'],
            'tanggal_penawaran' => $validated['tanggal_penawaran'],
            'atas_nama' => $validated['atas_nama'],
            'nomor_telepon' => $validated['nomor_telepon'] ?? null,
            'subtotal' => $subtotal,
            'ppn_persen' => $ppnPersen,
            'total' => $total,
            'status' => Penawaran::STATUS_DRAFT,
        ]);

        $this->simpanItem($penawaran, $validated['items']);

        return redirect()->route('penawaran.show', $penawaran->id)
            ->with('success', 'Draft penawaran berhasil dibuat. Cek angkanya, lalu kirim ke Manajer.');
    }

    public function show(Penawaran $penawaran)
    {
        // Manajer hanya melihat penawaran yang sudah dikirim ke dia.
        abort_if(
            auth()->user()->isManajer() && $penawaran->status === Penawaran::STATUS_DRAFT,
            403,
            'Penawaran ini belum dikirim ke Manajer.'
        );

        $penawaran->load(['items', 'proyekDenah.klien', 'reviewer']);

        $ppnNominal = $penawaran->total - $penawaran->subtotal;
        $terbilang = Terbilang::make((int) round($penawaran->total));
        $tanggalIndo = $penawaran->tanggal_penawaran->day . ' '
            . self::NAMA_BULAN[(int) $penawaran->tanggal_penawaran->format('n')] . ' '
            . $penawaran->tanggal_penawaran->year;

        return view('penawaran.show', compact('penawaran', 'ppnNominal', 'terbilang', 'tanggalIndo'));
    }

    /**
     * Form revisi: dipakai saat penawaran masih draft atau setelah ditolak Manajer.
     */
    public function edit(Penawaran $penawaran)
    {
        if (! $penawaran->bisaDiedit()) {
            return redirect()->route('penawaran.show', $penawaran->id)
                ->with('error', 'Penawaran yang sedang direview atau sudah disetujui tidak bisa diedit.');
        }

        $penawaran->load('proyekDenah.klien');

        $items = $penawaran->items()->with('katalogLampu')->get()
            ->map(function ($item) {
                return [
                    'katalog_lampu_id' => $item->katalog_lampu_id,
                    'kode_lampu' => $item->kode_lampu,
                    'deskripsi' => $item->deskripsi,
                    'foto' => $item->katalogLampu?->foto,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan ?? 'pcs',
                    'harga_satuan' => (float) $item->harga_satuan,
                ];
            })
            ->values();

        return view('penawaran.create', [
            'penawaran' => $penawaran,
            'proyekDenah' => $penawaran->proyekDenah,
            'items' => $items,
            'nomorPenawaran' => $penawaran->nomor_penawaran,
            'tanggalHariIni' => $penawaran->tanggal_penawaran->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, Penawaran $penawaran)
    {
        if (! $penawaran->bisaDiedit()) {
            return redirect()->route('penawaran.show', $penawaran->id)
                ->with('error', 'Penawaran yang sedang direview atau sudah disetujui tidak bisa diedit.');
        }

        $validated = $request->validate($this->aturanValidasi($penawaran->id));

        [$subtotal, $ppnPersen, $total] = $this->hitungTotal($validated['items']);

        $penawaran->update([
            'nomor_penawaran' => $validated['nomor_penawaran'],
            'tanggal_penawaran' => $validated['tanggal_penawaran'],
            'atas_nama' => $validated['atas_nama'],
            'nomor_telepon' => $validated['nomor_telepon'] ?? null,
            'subtotal' => $subtotal,
            'ppn_persen' => $ppnPersen,
            'total' => $total,
        ]);

        $penawaran->items()->delete();
        $this->simpanItem($penawaran, $validated['items']);

        return redirect()->route('penawaran.show', $penawaran->id)
            ->with('success', 'Penawaran berhasil diperbarui. Klik "Kirim ke Manajer" kalau sudah siap.');
    }

    /**
     * Admin mengirim penawaran ke Manajer (pertama kali, atau setelah direvisi).
     */
    public function ajukan(Penawaran $penawaran)
    {
        if (! $penawaran->bisaDiajukan()) {
            return back()->with('error', 'Penawaran ini tidak bisa dikirim pada status sekarang.');
        }

        $penawaran->update([
            'status' => Penawaran::STATUS_DIAJUKAN,
            'diajukan_at' => now(),
            'reviewer_id' => null,
            'direview_at' => null,
            'catatan_review' => null,
        ]);

        return redirect()->route('penawaran.show', $penawaran->id)
            ->with('success', 'Penawaran berhasil dikirim ke Manajer.');
    }

    /**
     * Manajer menyetujui penawaran.
     */
    public function approve(Request $request, Penawaran $penawaran)
    {
        if (! $penawaran->bisaDireview()) {
            return back()->with('error', 'Hanya penawaran yang menunggu review yang bisa disetujui.');
        }

        $validated = $request->validate([
            'catatan_review' => 'nullable|string|max:1000',
        ]);

        $penawaran->update([
            'status' => Penawaran::STATUS_FINAL,
            'reviewer_id' => auth()->id(),
            'direview_at' => now(),
            'catatan_review' => $validated['catatan_review'] ?? null,
        ]);

        return redirect()->route('penawaran.show', $penawaran->id)
            ->with('success', 'Penawaran disetujui.');
    }

    /**
     * Manajer menolak penawaran. Alasan wajib diisi supaya Admin tahu apa yang direvisi.
     */
    public function reject(Request $request, Penawaran $penawaran)
    {
        if (! $penawaran->bisaDireview()) {
            return back()->with('error', 'Hanya penawaran yang menunggu review yang bisa ditolak.');
        }

        $validated = $request->validate([
            'catatan_review' => 'required|string|min:5|max:1000',
        ]);

        $penawaran->update([
            'status' => Penawaran::STATUS_DITOLAK,
            'reviewer_id' => auth()->id(),
            'direview_at' => now(),
            'catatan_review' => $validated['catatan_review'],
        ]);

        return redirect()->route('penawaran.show', $penawaran->id)
            ->with('success', 'Penawaran ditolak dan dikembalikan ke Admin.');
    }

    public function destroy(Penawaran $penawaran)
    {
        $penawaran->delete();

        return redirect()->route('penawaran.index')
            ->with('success', 'Penawaran berhasil dihapus.');
    }

    /**
     * Penawaran hanya boleh dibuat dari denah yang titik lampunya sudah dihitung Admin.
     */
    private function tolakJikaTitikBelumDihitung(ProyekDenah $proyekDenah)
    {
        if ($proyekDenah->bolehDibuatPenawaran()) {
            return null;
        }

        return redirect()->route('proyek-denah.show', $proyekDenah->id)
            ->with('error', 'Penawaran hanya bisa dibuat setelah titik lampu dihitung.');
    }

    private function aturanValidasi(?int $ignoreId = null): array
    {
        return [
            'nomor_penawaran' => [
                'required', 'string', 'max:100',
                Rule::unique('penawaran', 'nomor_penawaran')->ignore($ignoreId),
            ],
            'tanggal_penawaran' => 'required|date',
            'atas_nama' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.katalog_lampu_id' => 'nullable|exists:katalog_lampu,id',
            'items.*.kode_lampu' => 'required|string|max:50',
            'items.*.deskripsi' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ];
    }

    /** @return array{0: float|int, 1: float, 2: float|int} subtotal, persen PPN, total */
    private function hitungTotal(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['harga_satuan'];
        }

        $ppnPersen = 11.00;
        $ppn = round($subtotal * $ppnPersen / 100);

        return [$subtotal, $ppnPersen, $subtotal + $ppn];
    }

    private function simpanItem(Penawaran $penawaran, array $items): void
    {
        foreach ($items as $item) {
            $penawaran->items()->create([
                'katalog_lampu_id' => $item['katalog_lampu_id'] ?? null,
                'kode_lampu' => $item['kode_lampu'],
                'deskripsi' => $item['deskripsi'],
                'qty' => $item['qty'],
                'satuan' => $item['satuan'] ?? 'pcs',
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $item['qty'] * $item['harga_satuan'],
            ]);
        }
    }

    /**
     * Format: IL/SP-GBL/{tahun 2 digit}/{bulan romawi}/{nomor urut 3 digit}.
     * Nomor urut melanjutkan dari nomor urut tertinggi yang pernah dipakai
     * di seluruh penawaran (bukan per klien/proyek), mulai dari 001.
     */
    private function generateNomorPenawaran(): string
    {
        $tahun = now()->format('y');
        $romawiBulan = self::ROMAWI_BULAN[(int) now()->format('n')];

        $urutTerakhir = Penawaran::query()
            ->pluck('nomor_penawaran')
            ->map(function ($nomor) {
                $bagian = explode('/', $nomor);
                return (int) end($bagian);
            })
            ->max();

        $urutBaru = ($urutTerakhir ?? 0) + 1;

        return sprintf('IL/SP-GBL/%s/%s/%03d', $tahun, $romawiBulan, $urutBaru);
    }
}