@extends('layout.master')

@section('content')
    @php
        $user = auth()->user();
        // Tab proses hitung titik hanya relevan untuk Admin.
        $sembunyi = $user->isAdmin() ? [] : ['processing', 'failed'];
        $tab = ['semua' => 'Semua'] + array_diff_key(
            \App\Models\ProyekDenah::STATUS_LABEL,
            array_flip($sembunyi)
        );
        $totalSemua = $hitung->sum();
    @endphp

    <div class="page-inner">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="fw-bold mb-0">{{ $user->isDrafter() ? 'Proyek Denah' : 'Denah Proyek' }}</h3>
            @if ($user->isDrafter())
                <a href="{{ route('proyek-denah.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Upload Denah Baru
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif


        <ul class="nav nav-pills mb-3">
            @foreach ($tab as $kode => $label)
                @php $jumlah = $kode === 'semua' ? $totalSemua : ($hitung[$kode] ?? 0); @endphp
                <li class="nav-item">
                    <a class="nav-link {{ $status === $kode ? 'active' : '' }}"
                        href="{{ route('proyek-denah.index', ['status' => $kode]) }}">
                        {{ $label }} <span class="badge bg-light text-dark">{{ $jumlah }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama Proyek</th>
                            <th>Klien</th>
                            @unless ($user->isDrafter())
                                <th>Drafter</th>
                            @endunless
                            <th>Status</th>
                            <th>Diupload</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proyek as $item)
                            <tr>
                                <td>{{ $item->nama_proyek }}</td>
                                <td>{{ $item->klien ? $item->klien->nama : '-' }}</td>
                                @unless ($user->isDrafter())
                                    <td>{{ $item->user ? $item->user->name : '-' }}</td>
                                @endunless
                                <td>
                                    <span class="badge badge-{{ $item->statusBadge() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->status === 'revisi' && ! $user->isManajer())
                                        <small class="text-warning d-block mt-1">
                                            {{ \Illuminate\Support\Str::limit($item->catatan_review ?: '-', 80) }}
                                        </small>
                                    @endif
                                    @unless ($user->isManajer())
                                        @foreach ($item->penawaranDitolak() as $ditolak)
                                            <div class="mt-1">
                                                <span class="badge badge-danger">Penawaran ditolak</span>
                                                <small class="text-danger d-block">
                                                    {{ \Illuminate\Support\Str::limit($ditolak->catatan_review ?: '-', 80) }}
                                                </small>
                                            </div>
                                        @endforeach
                                    @endunless
                                </td>
                                <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('proyek-denah.show', $item->id) }}" class="btn btn-sm btn-info">
                                        Detail
                                    </a>
                                    @if ($user->isAdmin() || ($user->isDrafter() && $item->bisaDihapusDrafter()))
                                        <form action="{{ route('proyek-denah.destroy', $item->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Yakin hapus proyek ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $user->isDrafter() ? 5 : 6 }}" class="text-center">
                                    Tidak ada denah pada tab ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $proyek->links() }}
            </div>
        </div>
    </div>
@endsection