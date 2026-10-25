@extends('layout.master')

@section('content')
<div class="page-inner">
    @php $user = auth()->user(); @endphp

    <div class="d-flex align-items-center justify-content-between mb-3 no-print">
        <h3 class="fw-bold mb-0">
            Penawaran {{ $penawaran->nomor_penawaran }}
            <span class="badge bg-{{ $penawaran->statusBadge() }} {{ $penawaran->status === 'draft' ? 'text-dark' : '' }} fs-6 align-middle">
                {{ $penawaran->statusLabel() }}
            </span>
        </h3>
        <div class="d-flex gap-2">
            @if ($user->isAdmin() && $penawaran->bisaDiedit())
            <a href="{{ route('penawaran.edit', $penawaran->id) }}" class="btn btn-warning">
                <i class="fa fa-edit"></i> {{ $penawaran->status === 'ditolak' ? 'Revisi' : 'Edit' }}
            </a>
            <form action="{{ route('penawaran.ajukan', $penawaran->id) }}" method="POST"
                onsubmit="return confirm('Kirim penawaran ini ke Manajer untuk disetujui?')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-paper-plane"></i> Kirim ke Manajer
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Cetak / Simpan
                PDF</button>
            <a href="{{ route('penawaran.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success no-print">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger no-print">{{ session('error') }}</div>
    @endif

    {{-- Info status review --}}
    @if ($penawaran->status === 'diajukan' && ! $user->isManajer())
    <div class="alert alert-info no-print">
        Penawaran sedang menunggu persetujuan Manajer
        {{ $penawaran->diajukan_at ? '(dikirim ' . $penawaran->diajukan_at->format('d M Y H:i') . ')' : '' }}.
    </div>
    @endif

    @if ($penawaran->status === 'ditolak')
    <div class="alert alert-danger no-print">
        <strong>Ditolak oleh {{ $penawaran->reviewer?->name ?? 'Manajer' }}</strong>
        {{ $penawaran->direview_at ? 'pada ' . $penawaran->direview_at->format('d M Y H:i') : '' }}.
        @if ($penawaran->catatan_review)
        <div class="mt-1">Alasan: {{ $penawaran->catatan_review }}</div>
        @endif
        @if ($user->isAdmin())
        <div class="mt-1">Klik "Revisi" untuk memperbaiki angkanya, lalu kirim ulang ke Manajer.</div>
        @endif
    </div>
    @endif

    @if ($penawaran->status === 'final')
    <div class="alert alert-success no-print">
        <strong>Disetujui oleh {{ $penawaran->reviewer?->name ?? 'Manajer' }}</strong>
        {{ $penawaran->direview_at ? 'pada ' . $penawaran->direview_at->format('d M Y H:i') : '' }}.
        @if ($penawaran->catatan_review)
        <div class="mt-1">Catatan: {{ $penawaran->catatan_review }}</div>
        @endif
    </div>
    @endif

    {{-- Review Manajer --}}
    @if ($user->isManajer() && $penawaran->bisaDireview())
    <div class="card mb-3 border-primary no-print">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Review Manajer</h5>
            <p class="text-muted">
                Periksa item, qty, dan harga di bawah. Kalau perlu melihat gambar denahnya,
                <a href="{{ route('proyek-denah.show', $penawaran->proyek_denah_id) }}">buka denah proyek ini</a>.
                Jika ditolak, alasan wajib diisi supaya Admin tahu apa yang direvisi.
            </p>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <form action="{{ route('penawaran.approve', $penawaran->id) }}" method="POST"
                        onsubmit="return confirm('Setujui penawaran ini?')">
                        @csrf
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan_review" rows="3" class="form-control mb-2"></textarea>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Setujui
                        </button>
                    </form>
                </div>
                <div class="col-md-6 mb-3">
                    <form action="{{ route('penawaran.reject', $penawaran->id) }}" method="POST">
                        @csrf
                        <label class="form-label">Alasan penolakan (wajib)</label>
                        <textarea name="catatan_review" rows="3" class="form-control mb-2" required minlength="5"></textarea>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-times"></i> Tolak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body" id="dokumen-penawaran">
            <div class="border-bottom pb-2 mb-3">
                <h4 class="fw-bold mb-0">C.V. SINAR KENCANA</h4>
                <div class="small">
                    Jl. Cibadak No.195, Telp. (022) 6011550, Fax. (022) 6034157,<br>
                    E-mail: sinarkencana_cibadak@yahoo.co.id, Bandung 40241.
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-6">
                    <div>Kepada Yth. :</div>
                    <div class="fw-bold">{{ $penawaran->atas_nama }}</div>
                    @if ($penawaran->proyekDenah->klien?->alamat)
                    <div>{{ $penawaran->proyekDenah->klien->alamat }}</div>
                    @endif
                    @if ($penawaran->nomor_telepon)
                    <div>Telp: {{ $penawaran->nomor_telepon }}</div>
                    @endif
                </div>
                <div class="col-6 text-end">
                    <h5 class="fw-bold">QUOTATION</h5>
                    <div>No Ref. : {{ $penawaran->nomor_penawaran }}</div>
                    <div>Tanggal : {{ $tanggalIndo }}</div>
                    <div>Project : <strong>{{ $penawaran->proyekDenah->nama_proyek }}</strong></div>
                </div>
            </div>

            <table class="table table-bordered tabel-penawaran">
                <thead>
                    <tr class="text-center">
                        <th style="width: 6%">No.</th>
                        <th style="width: 11%">Code</th>
                        <th>Uraian</th>
                        <th style="width: 11%">Photo</th>
                        <th style="width: 11%">Qty</th>
                        <th style="width: 16%">Harga / unit</th>
                        <th style="width: 16%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penawaran->items as $i => $item)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $item->kode_lampu }}</td>
                        <td>{{ $item->deskripsi }}</td>
                        <td class="text-center">
                            @if ($item->katalogLampu?->foto)
                            <img src="{{ asset('storage/' . $item->katalogLampu->foto) }}" alt="{{ $item->kode_lampu }}"
                                class="foto-item">
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-center">{{ $item->qty }} {{ $item->satuan }}</td>
                        <td class="text-end">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="row blok-total">
                <div class="col-7">
                    <div class="fw-bold">Terbilang :</div>
                    <div class="fst-italic">{{ $terbilang }}</div>
                </div>
                <div class="col-5">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <td>Sub Total</td>
                            <td class="text-end">Rp {{ number_format($penawaran->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>PPN {{ rtrim(rtrim(number_format($penawaran->ppn_persen, 2), '0'), '.') }}%</td>
                            <td class="text-end">Rp {{ number_format($ppnNominal, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">Rp {{ number_format($penawaran->total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-5 blok-ttd">
                <div>Hormat kami,</div>
                <div style="height: 60px;"></div>
                <div class="fw-bold">C.V. Sinar Kencana</div>
            </div>
        </div>
    </div>
</div>

<style>
    .tabel-penawaran {
        table-layout: fixed;
        width: 100%;
    }

    .tabel-penawaran td,
    .tabel-penawaran th {
        vertical-align: middle;
        word-wrap: break-word;
    }

    .foto-item {
        width: 55px;
        height: 55px;
        object-fit: cover;
    }

    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    @media print {
        html,
        body {
            background: #fff !important;
            width: 100% !important;
            height: auto !important;
            font-size: 11px;
        }

        /* sembunyikan elemen template */
        .no-print,
        .main-header,
        .sidebar,
        .main-footer,
        .nav-toggle,
        .custom-template,
        .quick-sidebar {
            display: none !important;
        }

        /* reset layout kaiadmin supaya konten penuh selebar kertas */
        .wrapper,
        .main-panel,
        .main-panel > .container,
        .content,
        .page-inner {
            width: 100% !important;
            max-width: 100% !important;
            float: none !important;
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            height: auto !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .tabel-penawaran th,
        .tabel-penawaran td {
            padding: 4px 6px !important;
            font-size: 11px;
        }

        .tabel-penawaran thead {
            display: table-header-group; /* header diulang di tiap halaman */
            background: #f1f1f1 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .tabel-penawaran tr,
        .blok-total,
        .blok-ttd {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .foto-item {
            width: 38px;
            height: 38px;
        }
    }
</style>
@endsection