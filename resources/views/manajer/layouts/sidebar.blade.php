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
                <div class="sidebar__section">
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

                <!-- SISTEM -->
                <div class="sidebar__section">
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
