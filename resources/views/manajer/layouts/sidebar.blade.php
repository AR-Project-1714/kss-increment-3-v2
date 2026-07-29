    @php
        // Kinerja Operasi dan Rincian Kegiatan memakai filter periode yang
        // sama, jadi filter aktif ikut dibawa saat berpindah di antara
        // keduanya — pindah menu tidak berarti menyaring ulang dari awal.
        $performanceQuery = request()->routeIs('manajer.performa', 'manajer.kegiatan')
            ? request()->only(['periode', 'dari', 'sampai', 'regu', 'shift'])
            : [];
    @endphp

    <aside class="sidebar" id="sidebar">

        <!-- Logo -->
        <div>
            <div class="sidebar__logo">
                <img src="{{ asset('assets/Logo.png') }}" alt="" style="width: 32px;">
                <img src="{{ asset('assets/KSS-text.png') }}" alt="" style="width: 56px;">
            </div>

            <!-- Nav -->
            <nav class="sidebar__nav">

                <!-- MENU UTAMA -->
                <div class="sidebar__section" data-sidebar-section="main">
                    <span class="sidebar__section-label">Menu Utama</span>

                    <a href="{{ route('manajer.index') }}" class="sidebar__nav-item {{ request()->routeIs('manajer.index') ? 'active' : '' }}" data-tooltip="Dashboard">
                        <span class="nav-icon"><i class="fi fi-sr-apps"></i></span>
                        <span class="nav-label">Dashboard</span>
                    </a>

                    <a href="{{ route('manajer.archive') }}" class="sidebar__nav-item {{ request()->routeIs('manajer.archive') ? 'active' : '' }}" data-tooltip="Arsip Laporan">
                        <span class="nav-icon"><i class="fi fi-sr-folder"></i></span>
                        <span class="nav-label">Arsip Laporan</span>
                    </a>
                </div>

                <!-- ANALITIK OPERASI -->
                <div class="sidebar__section" data-sidebar-section="operational-analytics">
                    <span class="sidebar__section-label">Analitik Operasi</span>

                    <a href="{{ route('manajer.performa', $performanceQuery) }}" class="sidebar__nav-item {{ request()->routeIs('manajer.performa') ? 'active' : '' }}" data-tooltip="Kinerja Divisi Operasi">
                        <span class="nav-icon"><i class="fi fi-sr-chart-histogram"></i></span>
                        <span class="nav-label">Kinerja Operasi</span>
                    </a>

                    <a href="{{ route('manajer.kegiatan', $performanceQuery) }}" class="sidebar__nav-item {{ request()->routeIs('manajer.kegiatan*') ? 'active' : '' }}" data-tooltip="Rincian per Jenis Kegiatan">
                        <span class="nav-icon"><i class="fi fi-sr-boxes"></i></span>
                        <span class="nav-label">Rincian Kegiatan</span>
                    </a>
                </div>

                <!-- SISTEM -->
                <div class="sidebar__section" data-sidebar-section="system">
                    <span class="sidebar__section-label">Sistem</span>

                    <a href="{{ route('manajer.bantuan') }}" class="sidebar__nav-item {{ request()->routeIs('manajer.bantuan') ? 'active' : '' }}" data-tooltip="Pusat Bantuan">
                        <span class="nav-icon"><i class="fi fi-sr-interrogation"></i></span>
                        <span class="nav-label">Pusat Bantuan</span>
                    </a>
                </div>

            </nav>
        </div>

        <!-- Logout -->
        <div class="sidebar__footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="sidebar__logout border-0 w-100" data-tooltip="Logout">
                    <span class="nav-icon"><i class="fi fi-br-sign-out-alt"></i></span>
                    <span class="nav-label">Logout</span>
                </button>
            </form>
        </div>

    </aside>
