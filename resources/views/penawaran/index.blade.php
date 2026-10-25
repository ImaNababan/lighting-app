@extends('layout.master')

@section('content')
    @php
        $user = auth()->user();
        $tab = ['semua' => 'Semua'] + \App\Models\Penawaran::STATUS_LABEL;
        if ($user->isManajer()) {
            unset($tab['draft']); // draft masih dikerjakan Admin
        }
        $totalSemua = $jumlah->sum();
    @endphp

    <div class="page-inner">
        <h3 class="fw-bold mb-3">{{ $user->isManajer() ? 'Review Penawaran' : 'Daftar Penawaran' }}</h3>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <ul class="nav nav-pills mb-3">
            @foreach ($tab as $kode => $label)
                @php $n = $kode === 'semua' ? $totalSemua : ($jumlah[$kode] ?? 0); @endphp
                <li class="nav-item">
                    <a class="nav-link {{ $status === $kode ? 'active' : '' }}"
                        href="{{ route('penawaran.index', ['status' => $kode]) }}">
                        {{ $label }} ({{ $n }})
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No Ref.</th>
                                <th>Tanggal</th>
                                <th>Project</th>
                                <th>Kepada</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th width="120"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($penawaran as $p)
                                <tr>
                                    <td>{{ $p->nomor_penawaran }}</td>
                                    <td>{{ $p->tanggal_penawaran->format('d-m-Y') }}</td>
                                    <td>{{ $p->proyekDenah->nama_proyek ?? '-' }}</td>
                                    <td>{{ $p->atas_nama }}</td>
                                    <td class="text-end">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $p->statusBadge() }}">{{ $p->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('penawaran.show', $p->id) }}"
                                            class="btn btn-sm btn-primary">
                                            {{ $user->isManajer() && $p->bisaDireview() ? 'Review' : 'Lihat' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Tidak ada penawaran pada tab ini.
                                        @if ($user->isAdmin() && $status === 'semua')
                                            Buat dari halaman detail proyek denah.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $penawaran->links() }}
            </div>
        </div>
    </div>
@endsection