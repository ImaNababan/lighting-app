@extends('layout.master')

@section('content')
    <div class="page-inner">
        {{-- Header sapaan + aksi cepat --}}
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Dashboard</h3>
                <h6 class="op-7 mb-0">
                    Halo, {{ auth()->user()->name }} · {{ auth()->user()->roleLabel() }} ·
                    {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                </h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                @foreach ($aksi as $a)
                    <a href="{{ $a['url'] }}" class="btn {{ $a['kelas'] }} btn-round {{ ! $loop->last ? 'me-2' : '' }}">
                        <i class="fa {{ $a['ikon'] }}"></i> {{ $a['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Kartu statistik --}}
        @foreach ([$kartu, $kartu2] as $baris)
            @if (! empty($baris))
                <div class="row">
                    @foreach ($baris as $k)
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ $k['url'] }}" class="text-decoration-none text-reset">
                                <div class="card card-stats card-round">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-icon">
                                                <div class="icon-big text-center icon-{{ $k['warna'] }} bubble-shadow-small">
                                                    <i class="fas {{ $k['ikon'] }}"></i>
                                                </div>
                                            </div>
                                            <div class="col col-stats ms-3 ms-sm-0">
                                                <div class="numbers">
                                                    <p class="card-category">{{ $k['judul'] }}</p>
                                                    <h4 class="card-title">{{ $k['nilai'] }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach

        {{-- Grafik tren + donat status --}}
        <div class="row">
            <div class="col-md-8">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">{{ $tren['judul'] }}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="chartTren"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-title">{{ $donat['judul'] }}</div>
                    </div>
                    <div class="card-body">
                        @if (array_sum($donat['data']) > 0)
                            <div style="height: 300px;">
                                <canvas id="chartDonat"></canvas>
                            </div>
                        @else
                            <p class="text-muted text-center my-5">Belum ada data.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Perlu tindakan + aktivitas terbaru --}}
        <div class="row">
            <div class="col-md-7">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">{{ $judulTugas }}</div>
                            <span class="badge badge-count ms-2">{{ $tugas->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse ($tugas as $t)
                            <div class="d-flex align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-title rounded-circle border border-white bg-{{ $t['warna'] }}">
                                        <i class="fa {{ $t['ikon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ $t['judul'] }}</div>
                                    <small class="text-muted">
                                        {{ $t['sub'] }} · {{ $t['waktu']->locale('id')->diffForHumans() }}
                                    </small>
                                </div>
                                <a href="{{ $t['url'] }}" class="btn btn-sm btn-{{ $t['warna'] }} btn-round ms-2">
                                    {{ $t['aksi'] }}
                                </a>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="fa fa-check-circle fa-2x d-block mb-2"></i>
                                Tidak ada yang perlu dikerjakan sekarang.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-title">{{ $judulTerbaru }}</div>
                    </div>
                    <div class="card-body">
                        @forelse ($terbaru as $r)
                            <div class="d-flex align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-title rounded-circle border border-white bg-{{ $r['warna'] }}">
                                        <i class="fa {{ $r['ikon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ $r['url'] }}" class="fw-bold text-reset">{{ $r['judul'] }}</a>
                                    <div>
                                        <small class="text-muted">
                                            {{ $r['sub'] }} · {{ $r['waktu']->locale('id')->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                                <span class="badge badge-{{ $r['badge'] }} ms-2">{{ $r['status'] }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center my-5">Belum ada aktivitas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Khusus Admin: kode lampu terlaris --}}
        @if ($topKode !== null)
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-round">
                        <div class="card-header">
                            <div class="card-title">{{ $topKode['judul'] }}</div>
                        </div>
                        <div class="card-body">
                            @if (count($topKode['data']) > 0)
                                <div style="height: 260px;">
                                    <canvas id="chartTopKode"></canvas>
                                </div>
                            @else
                                <p class="text-muted text-center my-4">Belum ada item penawaran.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            (function () {
                var tren = @json($tren);
                var donat = @json($donat);

                // Grafik tren per bulan
                var elTren = document.getElementById('chartTren');
                if (elTren) {
                    new Chart(elTren.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: tren.label,
                            datasets: [{
                                label: tren.judul,
                                data: tren.data,
                                backgroundColor: tren.warna
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        callback: function (v) {
                                            if (tren.rupiah) { return v; }
                                            return Math.floor(v) === v ? v : null;
                                        }
                                    }
                                }],
                                xAxes: [{ gridLines: { display: false } }]
                            },
                            tooltips: {
                                callbacks: {
                                    label: function (item) {
                                        if (tren.rupiah) {
                                            return 'Rp ' + item.yLabel.toLocaleString('id-ID') + ' juta';
                                        }
                                        return item.yLabel + ' ' + tren.satuan;
                                    }
                                }
                            }
                        }
                    });
                }

                // Grafik donat status
                var elDonat = document.getElementById('chartDonat');
                if (elDonat) {
                    new Chart(elDonat.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: donat.label,
                            datasets: [{
                                data: donat.data,
                                backgroundColor: donat.warna,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: 'bottom' },
                            cutoutPercentage: 65
                        }
                    });
                }

                @if ($topKode !== null && count($topKode['data']) > 0)
                // Grafik kode lampu terlaris (khusus Admin)
                var topKode = @json($topKode);
                var elTop = document.getElementById('chartTopKode');
                if (elTop) {
                    new Chart(elTop.getContext('2d'), {
                        type: 'horizontalBar',
                        data: {
                            labels: topKode.label,
                            datasets: [{
                                label: 'Total qty',
                                data: topKode.data,
                                backgroundColor: 'rgba(104, 97, 206, 0.85)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            scales: {
                                xAxes: [{ ticks: { beginAtZero: true } }],
                                yAxes: [{ gridLines: { display: false } }]
                            }
                        }
                    });
                }
                @endif
            })();
        </script>
    @endpush
@endsection