@extends('layout.master')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="fw-bold mb-0">Klien</h3>
            <a href="{{ route('klien.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Tambah Klien
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
                            <th>Nama</th>
                            <th>Nomor Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($klien as $item)
                            <tr>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->nomor_telepon ?? '-' }}</td>
                                <td>{{ $item->email ?? '-' }}</td>
                                <td>{{ $item->alamat ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('klien.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('klien.destroy', $item->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Yakin hapus klien ini? Proyek yang terhubung tidak akan ikut terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data klien.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $klien->links() }}
            </div>
        </div>
    </div>
@endsection