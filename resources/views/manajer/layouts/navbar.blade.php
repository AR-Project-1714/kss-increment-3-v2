        @php
            $user = auth()->user();
            $hour = now()->hour;
            $greeting = match (true) {
                $hour < 11 => 'Selamat Pagi',
                $hour < 15 => 'Selamat Siang',
                $hour < 18 => 'Selamat Sore',
                default => 'Selamat Malam',
            };
        @endphp
        <nav class="navbar-top">
            <div class="navbar-top__left">
                <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Toggle Sidebar" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="false">
                    <i class="fi fi-sr-angle-double-small-left" id="sidebarToggleIcon"></i>
                </button>
                <div class="greeting">
                    <div class="navbar-top__user-name">{{ $greeting }}, {{ $user->name ?? 'Manajer' }}</div>
                    <div class="navbar-top__user-role">{{ \App\Models\Role::displayName($user->role->name ?? 'manajer') }}</div>
                </div>
            </div>
            <div class="navbar-top__right">
                <button class="btn-theme" id="btnTheme" title="Ganti Tema">
                    <i class="fi fi-rr-sun" id="themeIcon"></i>
                </button>
            </div>
        </nav>
