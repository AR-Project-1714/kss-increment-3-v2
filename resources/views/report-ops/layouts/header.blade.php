<header class="header d-flex justify-content-center align-items-center align-self-stretch p-navbar white-bg">
        <div class="header-left d-flex align-items-center gap-15 flexible">
            <a href="{{ route('report-ops.index') }}">
                <img class="logo size-logo" src="{{ asset('assets/KSS-full.png') }}" alt="Logo KSS" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<strong style=\'color: var(--blue-main);\'>KSS LOGO</strong>');">
            </a>
            <div class="divider-vertical"></div>
            <div class="info-officer d-flex flex-column align-items-start flexible">
                <span class="nama align-self-stretch fsize-12 fw-600">Selamat Datang, {{ auth()->user()->name ?? 'Operasional' }}</span>
                <span class="role align-self-stretch fsize-9 text-secondary fw-300">{{ auth()->user()->jobTitle() }}</span>
            </div>
        </div>
        <div class="header-right d-flex justify-content-center align-items-center gap-10">
            @include('partials.notification-center')
            <div class="kss-account-toolbar">
                @include('partials.account-trigger', [
                    'showLogout' => true,
                    'showProfileMenu' => true,
                    'showThemeToggle' => true,
                ])
            </div>
        </div>
</header>
