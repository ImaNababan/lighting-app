@extends('layout.master')

@section('content')
    <div class="page-inner">
        <h3 class="fw-bold mb-3">Upload Denah Baru</h3>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('proyek-denah.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Klien</label>
                        <select id="klien_id" name="klien_id" class="form-control @error('klien_id') is-invalid @enderror"
                            style="width: 100%">
                            @if (old('klien_id'))
                                <option value="{{ old('klien_id') }}" selected>{{ old('klien_nama') }}</option>
                            @endif
                        </select>
                        @error('klien_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Ketik nama, nomor telepon, atau email klien untuk mencari.
                            Belum ada kliennya? <a href="{{ route('klien.create') }}" target="_blank">Tambah klien baru</a>.
                        </div>
                    </div>

                    <div id="klien-info" class="alert alert-light border mb-3" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nama Klien</small>
                                <span id="klien-info-nama" class="fw-bold"></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nomor Telepon</small>
                                <span id="klien-info-telepon">-</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Alamat</small>
                                <span id="klien-info-alamat">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Proyek</label>
                        <input type="text" name="nama_proyek" value="{{ old('nama_proyek') }}"
                            class="form-control @error('nama_proyek') is-invalid @enderror"
                            placeholder="Contoh: Hotel XYZ - Lantai 5">
                        @error('nama_proyek')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Satu klien bisa punya beberapa proyek dengan nama berbeda-beda.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Denah (PDF, maks 20MB)</label>
                        <input type="file" name="file_denah" accept=".pdf" data-max-mb="20"
                            class="form-control @error('file_denah') is-invalid @enderror">
                        @error('file_denah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Upload</button>
                    <a href="{{ route('proyek-denah.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/plugin/select2/select2.full.min.js') }}"></script>
    <script>
        $(function () {
            function renderKlienInfo(data) {
                if (!data || !data.id) {
                    $('#klien-info').slideUp(150);
                    return;
                }
                $('#klien-info-nama').text(data.nama || data.text || '-');
                $('#klien-info-telepon').text(data.nomor_telepon || '-');
                $('#klien-info-alamat').text(data.alamat || '-');
                $('#klien-info').slideDown(150);
            }

            $('#klien_id').select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: 'Ketik untuk mencari klien...',
                minimumInputLength: 1,
                language: {
                    inputTooShort: function () { return 'Ketik minimal 1 huruf...'; },
                    searching: function () { return 'Mencari...'; },
                    noResults: function () { return 'Klien tidak ditemukan.'; },
                },
                ajax: {
                    url: '{{ route('klien.search') }}',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.results };
                    },
                    cache: true,
                },
            });

            $('#klien_id').on('select2:select', function (e) {
                renderKlienInfo(e.params.data);
            });

            $('#klien_id').on('select2:clear', function () {
                renderKlienInfo(null);
            });
        });
    </script>
@endpush