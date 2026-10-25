@php
    $pengguna = auth()->user();
    $jumlahNotif = $pengguna->unreadNotifications()->count();
    $daftarNotif = $pengguna->unreadNotifications()->latest()->limit(5)->get();
@endphp
<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
        <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
            <div class="input-group">
                <div class="input-group-prepend">
                    <button type="submit" class="btn btn-search pe-1">
                        <i class="fa fa-search search-icon"></i>
                    </button>
                </div>
                <input type="text" placeholder="Search ..." class="form-control" />
            </div>
        </nav>

        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
            <li class="nav-item topbar-icon dropdown hidden-caret">
                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-bell"></i>
                    @if ($jumlahNotif > 0)
                        <span class="notification">{{ $jumlahNotif }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                    <li>
                        <div class="dropdown-title">
                            {{ $jumlahNotif > 0 ? 'Kamu punya ' . $jumlahNotif . ' notifikasi baru' : 'Tidak ada notifikasi baru' }}
                        </div>
                    </li>
                    @if ($daftarNotif->isNotEmpty())
                        <li>
                            <div class="notif-scroll scrollbar-outer">
                                <div class="notif-center">
                                    @foreach ($daftarNotif as $notif)
                                        <a href="{{ route('notifikasi.baca', $notif->id) }}">
                                            <div class="notif-icon notif-{{ $notif->data['warna'] ?? 'primary' }}">
                                                <i class="fa {{ $notif->data['ikon'] ?? 'fa-bell' }}"></i>
                                            </div>
                                            <div class="notif-content">
                                                <span class="block">{{ $notif->data['judul'] ?? 'Notifikasi' }}</span>
                                                <span class="block text-muted">{{ $notif->data['pesan'] ?? '' }}</span>
                                                <span class="time">{{ $notif->created_at->format('d M Y H:i') }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                        <li>
                            <a class="see-all" href="#"
                                onclick="event.preventDefault(); document.getElementById('form-baca-semua').submit();">
                                Tandai semua dibaca <i class="fa fa-angle-right"></i>
                            </a>
                            <form id="form-baca-semua" action="{{ route('notifikasi.baca-semua') }}" method="POST"
                                class="d-none">
                                @csrf
                            </form>
                        </li>
                    @endif
                </ul>
            </li>
                        <li class="nav-item topbar-user dropdown hidden-caret">
                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div class="avatar-sm">
                        <x-avatar :user="$pengguna" :size="36" />
                    </div>
                    <span class="profile-username">
                        <span class="op-7">Hi,</span>
                        <span class="fw-bold">{{ $pengguna->name }}</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                        <li>
                            <div class="user-box">
                                <div class="avatar-lg">
                                    <x-avatar :user="$pengguna" :size="64" />
                                </div>
                                <div class="u-text">
                                    <h4>{{ $pengguna->name }}</h4>
                                    <p class="text-muted mb-1">{{ $pengguna->email }}</p>
                                    <span class="badge badge-{{ $pengguna->roleWarna() }}">{{ $pengguna->roleLabel() }}</span>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fa fa-user me-2"></i> Profil Saya
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>
    </div>
</nav>