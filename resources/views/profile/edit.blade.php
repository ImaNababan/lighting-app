@extends('layout.master')

@section('title', 'Profil Saya')

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="page-inner">
        <h3 class="fw-bold mb-3">Profil Saya</h3>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success">Profil berhasil diperbarui.</div>
        @endif
        @if (session('status') === 'password-updated')
            <div class="alert alert-success">Password berhasil diubah.</div>
        @endif

        <div class="row">
            {{-- Kartu ringkasan --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <x-avatar :user="$user" :size="96" />
                        </div>
                        <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                        <p class="text-muted mb-2">{{ $user->email }}</p>
                        <span class="badge badge-{{ $user->roleWarna() }}">{{ $user->roleLabel() }}</span>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted d-block">Peran</small>
                        <p class="mb-3">{{ $user->roleDeskripsi() }}</p>

                        <small class="text-muted d-block">Bergabung sejak</small>
                        <span>{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                {{-- Informasi akun --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0">Informasi Akun</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text" id="name" name="name"
                                    value="{{ old('name', $user->name) }}" required autocomplete="name"
                                    class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email"
                                    value="{{ old('email', $user->email) }}" required autocomplete="username"
                                    class="form-control @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                {{-- Ubah password --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0">Ubah Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password saat ini</label>
                                <input type="password" id="current_password" name="current_password"
                                    autocomplete="current-password"
                                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password baru</label>
                                    <input type="password" id="password" name="password"
                                        autocomplete="new-password"
                                        class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                                    @error('password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Ulangi password baru</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        autocomplete="new-password" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Ubah Password</button>
                        </form>
                    </div>
                </div>

                {{-- Hapus akun --}}
                <div class="card border border-danger">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0 text-danger">Hapus Akun</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Setelah akun dihapus, semua data akun ini akan hilang permanen dan tidak bisa dikembalikan.
                        </p>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                            data-bs-target="#modalHapusAkun">
                            Hapus Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus akun --}}
    <div class="modal fade" id="modalHapusAkun" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yakin hapus akun?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Masukkan password untuk mengonfirmasi penghapusan akun.</p>
                    <input type="password" name="password" placeholder="Password"
                        class="form-control @error('password', 'userDeletion') is-invalid @enderror">
                    @error('password', 'userDeletion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus Akun</button>
                </div>
            </form>
        </div>
    </div>
@endsection

{{-- Buka lagi modal kalau password konfirmasi salah --}}
@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            new bootstrap.Modal(document.getElementById('modalHapusAkun')).show();
        </script>
    @endpush
@endif