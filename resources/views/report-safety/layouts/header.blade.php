<header class="header d-flex justify-content-center align-items-center align-self-stretch p-navbar white-bg">
    <div class="header-left d-flex align-items-center gap-15 flexible">
        <a href="{{ route('safety.index') }}">
            <img class="logo size-logo" src="{{ asset('assets/KSS-full.png') }}" alt="Logo KSS" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<strong style=\'color: var(--blue-main);\'>KSS LOGO</strong>');">
        </a>
        <div class="divider-vertical"></div>
        <div class="info-officer d-flex flex-column align-items-start flexible">
            <span class="nama align-self-stretch fsize-12 fw-600">Selamat Datang, {{ auth()->user()->name ?? 'Safety' }}</span>
            <span class="role align-self-stretch fsize-9 text-secondary fw-300">Karu Safety / K3</span>
        </div>
    </div>
    <div class="header-right d-flex justify-content-center align-items-center gap-20">
        <button class="btn-theme br-10" id="themeToggle" title="Ganti Tema">
            <div class="icon-container">
                <i class="fi fi-rr-sun" id="themeIcon"></i>
            </div>
        </button>
        <div class="divider-vertical"></div>
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn-logout d-flex align-items-center br-6">
                <div class="icon-logout fsize-10">
                    <i class="fi fi-br-exit"></i>
                </div>
                <span class="text fw-500 fsize-12">Logout</span>
            </button>
        </form>
    </div>
</header>
