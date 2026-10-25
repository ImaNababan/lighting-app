@extends('layout.master')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="fw-bold mb-0">Katalog Lampu</h3>
            <a href="{{ route('katalog-lampu.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Tambah Kode Lampu
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Foto</th>
                            <th>Deskripsi</th>
                            <th>Harga Satuan</th>
                            <th>Satuan</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($katalog as $item)
                            <tr>
                                <td>{{ $item->kode }}</td>
                                <td>
                                    @if ($item->foto)
                                        <img src="{{ asset('storage/' . $item->foto) }}" width="50">
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item->deskripsi }}</td>
                                <td>Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td>{{ $item->satuan }}</td>
                                <td>
                                    <a href="{{ route('katalog-lampu.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('katalog-lampu.destroy', $item->id) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Yakin hapus kode lampu ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data kode lampu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $katalog->links() }}
            </div>
        </div>
    </div>
@endsection