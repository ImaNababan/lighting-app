@php $pengguna = auth()->user(); @endphp
<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <a href="{{ url('/dashboard') }}" class="logo">
        <img src="{{ asset('assets/img/kaiadmin/logo_light.svg') }}" alt="navbar brand" class="navbar-brand"
          height="20" />
      </a>
      <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
          <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
          <i class="gg-menu-left"></i>
        </button>
      </div>
      <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
      </button>
    </div>
    <!-- End Logo Header -->
  </div>
  <div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
      <ul class="nav nav-secondary">

        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <a href="{{ route('dashboard') }}">
            <i class="fas fa-home"></i>
            <p>Dashboard</p>
          </a>
        </li>

        @if ($pengguna?->isAdmin())
        <li class="nav-section">
          <span class="sidebar-mini-icon">
            <i class="fa fa-ellipsis-h"></i>
          </span>
          <h4 class="text-section">Data Master</h4>
        </li>

        <li class="nav-item {{ request()->routeIs('katalog-lampu.*') ? 'active' : '' }}">
          <a href="{{ route('katalog-lampu.index') }}">
            <i class="fas fa-lightbulb"></i>
            <p>Katalog Lampu</p>
          </a>
        </li>
        @endif

         @if ($pengguna?->isDrafter() || $pengguna?->isAdmin())
        <li class="nav-item {{ request()->routeIs('proyek-denah.*') ? 'active' : '' }}">
          <a href="{{ route('proyek-denah.index') }}">
            <i class="fas fa-file-upload"></i>
            <p>{{ $pengguna->isDrafter() ? 'Upload Denah' : 'Denah' }}</p>
          </a>
        </li>

        <li class="nav-item {{ request()->routeIs('klien.*') ? 'active' : '' }}">
          <a href="{{ route('klien.index') }}">
            <i class="fas fa-users"></i>
            <p>Data Klien</p>
          </a>
        </li>
        @endif

        <li class="nav-section">
          <span class="sidebar-mini-icon">
            <i class="fa fa-ellipsis-h"></i>
          </span>
          <h4 class="text-section">Proyek</h4>
        </li>

        @if ($pengguna?->isAdmin() || $pengguna?->isManajer())
        <li class="nav-item {{ request()->routeIs('penawaran.*') ? 'active' : '' }}">
          <a href="{{ route('penawaran.index') }}">
            <i class="fas fa-file-invoice-dollar"></i>
            <p>{{ $pengguna->isManajer() ? 'Review Penawaran' : 'Penawaran' }}</p>
          </a>
        </li>
        @endif

        <li class="nav-section">
          <span class="sidebar-mini-icon">
            <i class="fa fa-ellipsis-h"></i>
          </span>
          <h4 class="text-section">Akun</h4>
        </li>

        <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
          <a href="{{ route('profile.edit') }}">
            <i class="fas fa-user"></i>
            <p>Profil</p>
          </a>
        </li>

      </ul>
    </div>
  </div>
</div>