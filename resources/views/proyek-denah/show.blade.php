@extends('layout.master')

@section('content')
    @php
        $user = auth()->user();
        $adalahPemilik = $user->isDrafter() && $proyekDenah->user_id === $user->id;
        $urlFile = asset('storage/' . $proyekDenah->file_asli_path);
        // Manajer tidak melihat penawaran yang masih draft.
        $daftarPenawaran = $proyekDenah->penawaran->when(
            $user->isManajer(),
            fn ($c) => $c->where('status', '!=', 'draft')
        );
    @endphp

    <div class="page-inner">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="fw-bold mb-0">{{ $proyekDenah->nama_proyek }}</h3>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('proyek-denah.index') }}"
                class="btn btn-secondary">Kembali</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $pesan)
                    <div>{{ $pesan }}</div>
                @endforeach
            </div>
        @endif

        {{-- Ringkasan proyek + status --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge badge-{{ $proyekDenah->statusBadge() }}">{{ $proyekDenah->statusLabel() }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Klien</small>
                        {{ $proyekDenah->klien ? $proyekDenah->klien->nama : '-' }}
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Diunggah oleh</small>
                        {{ $proyekDenah->user ? $proyekDenah->user->name : '-' }}
                        ({{ $proyekDenah->created_at->format('d M Y H:i') }})
                    </div>
                </div>

                @if ($proyekDenah->status === 'failed' && $proyekDenah->catatan && $user->isAdmin())
                    <div class="alert alert-danger mt-3 mb-0">{{ $proyekDenah->catatan }}</div>
                @endif
            </div>
        </div>
        {{-- Permintaan upload ulang dari Admin: dilihat Admin dan Drafter --}}
        @if ($proyekDenah->status === 'revisi' && ! $user->isManajer())
            <div class="alert alert-warning">
                <strong>Admin meminta denah ini diunggah ulang</strong>
                {{ $proyekDenah->direview_at ? '(' . $proyekDenah->direview_at->format('d M Y H:i') . ')' : '' }}.
                <div class="mt-1">
                    Alasan: {{ $proyekDenah->catatan_review ?: '(tidak ada alasan tertulis)' }}
                </div>
                <div class="mt-1 text-muted">
                    @if ($user->isDrafter())
                        Unggah file revisi pada bagian Aksi Drafter di bawah. Setelah diunggah,
                        denah otomatis kembali ke Admin untuk dihitung ulang.
                    @else
                        Menunggu Drafter mengunggah file revisi.
                    @endif
                </div>
            </div>
        @endif

        {{-- Alasan penolakan Manajer: Admin dan Drafter sama-sama melihat --}}
        @unless ($user->isManajer())
            @foreach ($proyekDenah->penawaranDitolak() as $ditolak)
                <div class="alert alert-danger">
                    <strong>Penawaran {{ $ditolak->nomor_penawaran }} ditolak oleh
                        {{ $ditolak->reviewer?->name ?? 'Manajer' }}</strong>
                    {{ $ditolak->direview_at ? 'pada ' . $ditolak->direview_at->format('d M Y H:i') : '' }}.
                    <div class="mt-1">
                        Alasan: {{ $ditolak->catatan_review ?: '(tidak ada alasan tertulis)' }}
                    </div>
                    <div class="mt-1 text-muted">
                        @if ($user->isDrafter())
                            @if ($proyekDenah->bisaUploadUlang())
                                Jika alasannya menyangkut denah, unggah file revisi pada bagian
                                Aksi Drafter di bawah.
                            @else
                                Denah ini tidak bisa diunggah ulang karena ada penawaran yang
                                sedang direview atau sudah disetujui.
                            @endif
                        @else
                            Revisi penawaran ini, atau minta Drafter mengunggah ulang denah
                            bila alasannya menyangkut denah.
                        @endif
                    </div>
                </div>
            @endforeach
        @endunless

        {{-- Overview / pratinjau PDF denah --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">Overview Denah (PDF)</h5>
                <a href="{{ $urlFile }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    Buka di tab baru
                </a>
            </div>
            <div class="card-body p-0">
                <iframe src="{{ $urlFile }}#view=FitH" title="Pratinjau denah" width="100%" height="650"
                    style="border: 0;"></iframe>
            </div>
        </div>

        {{-- Aksi Drafter: kirim ke Admin / ganti file --}}
        @if ($adalahPemilik)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Aksi Drafter</h5>

                    @if ($proyekDenah->bisaDikirim())
                        <form action="{{ route('proyek-denah.kirim', $proyekDenah->id) }}" method="POST"
                            class="d-inline"
                            onsubmit="return confirm('Kirim denah ini ke Admin?')">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-paper-plane"></i> Kirim ke Admin
                            </button>
                        </form>
                    @else
                        <p class="text-muted mb-0">
                            Denah sudah dikirim ke Admin
                            {{ $proyekDenah->diajukan_at ? '(' . $proyekDenah->diajukan_at->format('d M Y H:i') . ')' : '' }}.
                        </p>
                    @endif

                    @if ($proyekDenah->bisaUploadUlang())
                        <hr>
                        <form action="{{ route('proyek-denah.upload-ulang', $proyekDenah->id) }}" method="POST"
                            enctype="multipart/form-data" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-8">
                                <label class="form-label">
                                    {{ $proyekDenah->status === 'uploaded' ? 'Ganti file denah (PDF)' : 'File denah revisi (PDF)' }}
                                </label>
                                <input type="file" name="file_denah" accept=".pdf" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning">Upload Ulang</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Aksi Admin: hitung titik lampu / minta upload ulang --}}
        @if ($user->isAdmin())
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Aksi Admin</h5>

                    @if ($proyekDenah->bisaDihitung())
                        <form action="{{ route('proyek-denah.process', $proyekDenah->id) }}" method="POST"
                            class="d-inline"
                            @if ($proyekDenah->status === 'processed')
                                onsubmit="return confirm('Hitung ulang titik lampu? Hasil sebelumnya akan diganti.')"
                            @endif>
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-cogs"></i>
                                {{ $proyekDenah->status === 'processed' ? 'Hitung Ulang Titik Lampu' : 'Hitung Titik Lampu' }}
                            </button>
                        </form>
                    @elseif ($proyekDenah->status === 'revisi')
                        <p class="text-muted mb-0">
                            Menunggu Drafter mengunggah file revisi. Setelah diunggah, titik lampu
                            bisa dihitung ulang di sini.
                        </p>
                    @else
                        <p class="text-muted mb-0">
                            Denah ini belum dikirim Drafter, jadi titik lampu belum bisa dihitung.
                        </p>
                    @endif

                    @if ($proyekDenah->bisaMintaRevisi())
                        <hr>
                        <form action="{{ route('proyek-denah.minta-revisi', $proyekDenah->id) }}" method="POST"
                            onsubmit="return confirm('Minta Drafter mengunggah ulang denah ini?')">
                            @csrf
                            <label class="form-label fw-bold">Minta Drafter upload ulang denah</label>
                            <textarea name="catatan_review" rows="2" class="form-control mb-2" required minlength="5"
                                maxlength="1000"
                                placeholder="Alasan wajib diisi, mis. denah belum lengkap atau kode lampu tidak terbaca"></textarea>
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-redo"></i> Minta Upload Ulang
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Hasil hitung titik: Admin dan Manajer, setelah Admin menghitung --}}
        @if ($proyekDenah->status === 'processed' && ! $user->isDrafter())
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Hasil Hitung Titik</h5>

                    @if ($rekap->count() > 0)
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kode Lampu</th>
                                    <th>Jumlah Titik</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rekap as $item)
                                    <tr>
                                        <td>{{ $item['kode'] ?? '(tidak dikenali)' }}</td>
                                        <td>{{ $item['jumlah'] }}</td>
                                        <td>
                                            @if ($item['tersedia'])
                                                <span class="badge bg-success">Tersedia di pricelist</span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    Ditemukan, tapi belum ada di pricelist lampu
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($user->isAdmin() && $rekap->contains(fn($item) => !$item['tersedia']))
                            <div class="alert alert-warning">
                                Beberapa kode di atas terdeteksi di gambar denah tapi belum terdaftar
                                di Katalog Lampu, jadi belum punya harga. Tambahkan dulu di menu
                                <a href="{{ route('katalog-lampu.create') }}">Katalog Lampu</a> kalau
                                kode itu memang valid, lalu klik "Hitung Ulang Titik Lampu",
                                atau cek ulang penulisannya di gambar.
                            </div>
                        @endif

                        @if ($user->isAdmin())
                            <a href="{{ route('penawaran.create', $proyekDenah->id) }}" class="btn btn-success">
                                <i class="fa fa-file-invoice-dollar"></i> Buat Penawaran
                            </a>
                        @endif
                    @else
                        <p class="text-muted mb-0">Belum ada kode lampu yang terdeteksi.</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Penawaran untuk proyek ini --}}
        @if (! $user->isDrafter() && $daftarPenawaran->count() > 0)
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Penawaran Proyek Ini</h5>
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>No Ref.</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th width="100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarPenawaran as $p)
                                <tr>
                                    <td>{{ $p->nomor_penawaran }}</td>
                                    <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $p->statusBadge() }}">{{ $p->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('penawaran.show', $p->id) }}"
                                            class="btn btn-sm btn-primary">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        
    </div>
@endsection