@extends('layout.master')

@section('content')
    <div class="page-inner">
        <h3 class="fw-bold mb-3">Tambah Kode Lampu</h3>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('katalog-lampu.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Kode Lampu</label>
                        <input type="text" name="kode" value="{{ old('kode') }}"
                            class="form-control @error('kode') is-invalid @enderror">
                        @error('kode')
                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" rows="3"
                            class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <input type="file" name="foto" accept="image/*"
                            class="form-control @error('foto') is-invalid @enderror">
                        @error('foto')
                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Satuan</label>
                            <input type="number" step="0.01" name="harga_satuan" value="{{ old('harga_satuan') }}"
                                class="form-control @error('harga_satuan') is-invalid @enderror">
                            @error('harga_satuan')
                            <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Satuan</label>
                            <select name="satuan" class="form-control @error('satuan') is-invalid @enderror">
                                <option value="">-- Pilih --</option>
                                <option value="pcs" {{ old('satuan') == 'pcs' ? 'selected' : '' }}>pcs</option>
                                <option value="set" {{ old('satuan') == 'set' ? 'selected' : '' }}>set</option>
                                <option value="mtr" {{ old('satuan') == 'mtr' ? 'selected' : '' }}>mtr</option>
                            </select>
                            @error('satuan')
                            <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('katalog-lampu.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection