@extends('layout.master')

@section('content')
    @php $modeEdit = isset($penawaran); @endphp

    <div class="page-inner">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="fw-bold mb-0">
                {{ $modeEdit ? 'Revisi Penawaran' : 'Buat Penawaran' }} — {{ $proyekDenah->nama_proyek }}
            </h3>
            <a href="{{ $modeEdit ? route('penawaran.show', $penawaran->id) : route('proyek-denah.show', $proyekDenah->id) }}"
                class="btn btn-secondary">Kembali</a>
        </div>

        @if ($modeEdit && $penawaran->status === 'ditolak' && $penawaran->catatan_review)
            <div class="alert alert-danger">
                <strong>Ditolak oleh {{ $penawaran->reviewer?->name ?? 'Manajer' }}.</strong>
                Catatan: {{ $penawaran->catatan_review }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $modeEdit ? route('penawaran.update', $penawaran->id) : route('penawaran.store', $proyekDenah->id) }}"
            method="POST" id="form-penawaran">
            @csrf
            @if ($modeEdit)
                @method('PUT')
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Kepada &amp; Nomor Penawaran</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kepada Yth. (Nama Klien)</label>
                            <input type="text" name="atas_nama" class="form-control"
                                value="{{ old('atas_nama', $penawaran->atas_nama ?? ($proyekDenah->klien->nama ?? '')) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" name="nomor_telepon" class="form-control"
                                value="{{ old('nomor_telepon', $penawaran->nomor_telepon ?? ($proyekDenah->klien->nomor_telepon ?? '')) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Klien</label>
                        <textarea class="form-control" rows="2" readonly>{{ $proyekDenah->klien->alamat ?? '-' }}</textarea>
                        <small class="text-muted">Diambil dari data klien, tampil di dokumen tapi tidak disimpan ulang di sini.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No Ref. Penawaran</label>
                            <input type="text" name="nomor_penawaran" class="form-control"
                                value="{{ old('nomor_penawaran', $nomorPenawaran) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal_penawaran" class="form-control"
                                value="{{ old('tanggal_penawaran', $tanggalHariIni) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Project</label>
                            <input type="text" class="form-control" value="{{ $proyekDenah->nama_proyek }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0">Item Penawaran</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-tambah-baris">
                            <i class="fa fa-plus"></i> Tambah Baris Manual
                        </button>
                    </div>

                    @if ($items->isEmpty())
                        <div class="alert alert-warning">
                            Belum ada kode yang cocok dengan katalog untuk proyek ini. Tambahkan baris manual
                            di bawah, atau lengkapi dulu <a href="{{ route('katalog-lampu.index') }}">Katalog Lampu</a>.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="tabel-item">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No.</th>
                                    <th style="width: 110px;">Code</th>
                                    <th>Uraian</th>
                                    <th style="width: 90px;">Photo</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 110px;">Satuan</th>
                                    <th style="width: 160px;">Harga / unit</th>
                                    <th style="width: 160px;">Total</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-item">
                                @foreach ($items as $i => $item)
                                    <tr>
                                        <td class="text-center nomor-urut">{{ $i + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $i }}][katalog_lampu_id]" value="{{ $item['katalog_lampu_id'] }}">
                                            <input type="text" name="items[{{ $i }}][kode_lampu]" class="form-control form-control-sm"
                                                value="{{ $item['kode_lampu'] }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $i }}][deskripsi]" class="form-control form-control-sm"
                                                value="{{ $item['deskripsi'] }}" required>
                                        </td>
                                        <td class="text-center">
                                            @if ($item['foto'])
                                                <img src="{{ asset('storage/' . $item['foto']) }}" alt="{{ $item['kode_lampu'] }}"
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][qty]" min="1"
                                                class="form-control form-control-sm input-qty" value="{{ $item['qty'] }}" required>
                                        </td>
                                        <td>
                                            <select name="items[{{ $i }}][satuan]" class="form-control form-control-sm">
                                                <option value="pcs" {{ $item['satuan'] == 'pcs' ? 'selected' : '' }}>pcs</option>
                                                <option value="set" {{ $item['satuan'] == 'set' ? 'selected' : '' }}>set</option>
                                                <option value="mtr" {{ $item['satuan'] == 'mtr' ? 'selected' : '' }}>mtr</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $i }}][harga_satuan]"
                                                class="form-control form-control-sm input-harga" value="{{ $item['harga_satuan'] }}" required>
                                        </td>
                                        <td class="text-end td-subtotal">Rp 0</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <table class="table" style="max-width: 350px;">
                            <tr>
                                <td>Sub Total</td>
                                <td class="text-end" id="ringkasan-subtotal">Rp 0</td>
                            </tr>
                            <tr>
                                <td>PPN 11%</td>
                                <td class="text-end" id="ringkasan-ppn">Rp 0</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end" id="ringkasan-total">Rp 0</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">
                {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Draft Penawaran' }}
            </button>
            <a href="{{ $modeEdit ? route('penawaran.show', $penawaran->id) : route('proyek-denah.show', $proyekDenah->id) }}"
                class="btn btn-secondary">Batal</a>
        </form>
    </div>

    {{-- Template baris manual, dipakai JS untuk clone baris baru --}}
    <template id="template-baris">
        <tr>
            <td class="text-center nomor-urut"></td>
            <td>
                <input type="hidden" name="items[__INDEX__][katalog_lampu_id]" value="">
                <input type="text" name="items[__INDEX__][kode_lampu]" class="form-control form-control-sm" required>
            </td>
            <td>
                <input type="text" name="items[__INDEX__][deskripsi]" class="form-control form-control-sm" required>
            </td>
            <td class="text-center"><span class="text-muted small">-</span></td>
            <td>
                <input type="number" name="items[__INDEX__][qty]" min="1" class="form-control form-control-sm input-qty" value="1" required>
            </td>
            <td>
                <select name="items[__INDEX__][satuan]" class="form-control form-control-sm">
                    <option value="pcs">pcs</option>
                    <option value="set">set</option>
                    <option value="mtr">mtr</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="items[__INDEX__][harga_satuan]" class="form-control form-control-sm input-harga" value="0" required>
            </td>
            <td class="text-end td-subtotal">Rp 0</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-baris">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    @push('scripts')
        <script>
            (function () {
                let indexBerikutnya = {{ $items->count() }};
                const tbody = document.getElementById('tbody-item');
                const template = document.getElementById('template-baris');

                function formatRupiah(angka) {
                    return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
                }

                function hitungUlang() {
                    let subtotal = 0;

                    tbody.querySelectorAll('tr').forEach(function (tr, idx) {
                        tr.querySelector('.nomor-urut').textContent = idx + 1;

                        const qty = parseFloat(tr.querySelector('.input-qty').value) || 0;
                        const harga = parseFloat(tr.querySelector('.input-harga').value) || 0;
                        const total = qty * harga;

                        tr.querySelector('.td-subtotal').textContent = formatRupiah(total);
                        subtotal += total;
                    });

                    const ppn = subtotal * 0.11;
                    const total = subtotal + ppn;

                    document.getElementById('ringkasan-subtotal').textContent = formatRupiah(subtotal);
                    document.getElementById('ringkasan-ppn').textContent = formatRupiah(ppn);
                    document.getElementById('ringkasan-total').textContent = formatRupiah(total);
                }

                function pasangEventBaris(tr) {
                    tr.querySelectorAll('.input-qty, .input-harga').forEach(function (input) {
                        input.addEventListener('input', hitungUlang);
                    });
                    tr.querySelector('.btn-hapus-baris').addEventListener('click', function () {
                        tr.remove();
                        hitungUlang();
                    });
                }

                document.getElementById('btn-tambah-baris').addEventListener('click', function () {
                    const html = template.innerHTML.replaceAll('__INDEX__', indexBerikutnya);
                    const wrapper = document.createElement('tbody');
                    wrapper.innerHTML = html;
                    const trBaru = wrapper.firstElementChild;
                    tbody.appendChild(trBaru);
                    pasangEventBaris(trBaru);
                    indexBerikutnya++;
                    hitungUlang();
                });

                tbody.querySelectorAll('tr').forEach(pasangEventBaris);
                hitungUlang();
            })();
        </script>
    @endpush
@endsection