{{--
    Sidebar admin.
    $active : penanda menu aktif — 'dashboard' | 'archive' | 'log' | 'user' | 'master'
    Catatan: ganti href="#" dengan route() / url() saat routing sudah dibuat.
--}}
@php($active = $active ?? 'dashboard')

<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar__main">
        <div class="sidebar__logo">
            <img src="{{ asset('assets/Logo.png') }}" alt="KSS" style="width: 32px;">
            <img src="{{ asset('assets/KSS-text.png') }}" alt="KSS" style="width: 56px;">
        </div>

        <!-- Nav -->
        <nav class="sidebar__nav">

            <!-- MENU UTAMA -->
            <div class="sidebar__section">
                <span class="sidebar__section-label">Menu Utama</span>

                <a href="{{ route('admin.index') }}" class="sidebar__nav-item {{ $active === 'dashboard' ? 'active' : '' }}" data-tooltip="Dashboard Sistem">
                    <span class="nav-icon"><i class="fi fi-sr-apps"></i></span>
                    <span class="nav-label">Dashboard Sistem</span>
                </a>

                <a href="{{ route('admin.archive') }}" class="sidebar__nav-item {{ $active === 'archive' ? 'active' : '' }}" data-tooltip="Arsip Laporan">
                    <span class="nav-icon"><i class="fi fi-sr-folder"></i></span>
                    <span class="nav-label">Arsip Laporan</span>
                </a>

                <a href="{{ route('admin.log') }}" class="sidebar__nav-item {{ $active === 'log' ? 'active' : '' }}" data-tooltip="Log Aktivitas">
                    <span class="nav-icon"><i class="fi fi-sr-document"></i></span>
                    <span class="nav-label">Log Aktivitas</span>
                </a>
            </div>

            <!-- ADMINISTRASI -->
            <div class="sidebar__section">
                <span class="sidebar__section-label">Administrasi</span>

                <a href="{{ route('admin.user-manage') }}" class="sidebar__nav-item {{ $active === 'user' ? 'active' : '' }}" data-tooltip="Kelola Pengguna">
                    <span class="nav-icon"><i class="fi fi-sr-user"></i></span>
                    <span class="nav-label">Kelola Pengguna</span>
                </a>

                <a href="{{ route('admin.datamaster') }}" class="sidebar__nav-item js-submenu-toggle {{ $active === 'master' ? 'submenu-open' : '' }}" data-tooltip="Data Master">
                    <span class="nav-icon"><i class="fi fi-sr-database"></i></span>
                    <span class="nav-label">Data Master</span>
                    <span class="nav-chevron"><i class="fi fi-rr-angle-small-down"></i></span>
                </a>
                <div class="sidebar__submenu-wrapper {{ $active === 'master' ? 'open' : '' }}">
                    <div class="sidebar__submenu">
                        <div class="sidebar__submenu-line"></div>
                        <div class="sidebar__submenu-items">
                            <a href="{{ route('admin.datamaster', ['pane' => 'karyawan']) }}" class="sidebar__submenu-item {{ $active === 'master' ? 'active' : '' }}" data-pane="karyawan">Data Karyawan</a>
                            <a href="{{ route('admin.datamaster', ['pane' => 'unit']) }}" class="sidebar__submenu-item" data-pane="unit">Data Unit</a>
                            <a href="{{ route('admin.datamaster', ['pane' => 'truck']) }}" class="sidebar__submenu-item" data-pane="truck">Data Truck</a>
                            <a href="{{ route('admin.datamaster', ['pane' => 'inventaris']) }}" class="sidebar__submenu-item" data-pane="inventaris">Data Inventaris</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.backup') }}"
                   class="sidebar__nav-item {{ $active === 'backup' ? 'active' : '' }}"
                   data-tooltip="Manajemen Backup">
                    <span class="nav-icon"><i class="fi fi-sr-cloud-upload"></i></span>
                    <span class="nav-label">Manajemen Backup</span>
                </a>
            </div>

            <!-- SISTEM -->
            <div class="sidebar__section">
                <span class="sidebar__section-label">Sistem</span>

                <a href="{{ route('admin.help') }}" class="sidebar__nav-item {{ $active === 'help' ? 'active' : '' }}" data-tooltip="Pusat Bantuan">
                    <span class="nav-icon"><i class="fi fi-sr-interrogation"></i></span>
                    <span class="nav-label">Pusat Bantuan</span>
                </a>
            </div>

        </nav>
    </div>

    <!-- Logout -->
    <div class="sidebar__footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="sidebar__logout w-100 border-0"
                    data-tooltip="Logout">
                <span class="nav-icon"><i class="fi fi-br-sign-out-alt"></i></span>
                <span class="nav-label">Logout</span>
            </button>
        </form>
    </div>

</aside>
