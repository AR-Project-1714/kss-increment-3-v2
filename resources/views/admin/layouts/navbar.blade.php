{{--
    Top navbar admin.
    $greeting & $role bisa dioper dari controller; default statis di bawah.
--}}
<nav class="navbar-top">
    <div class="navbar-top__left">
        <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Toggle Sidebar">
            <i class="fi fi-sr-angle-double-small-left" id="sidebarToggleIcon"></i>
        </button>
        <div class="greeting">
            <div class="navbar-top__user-name">{{ $greeting ?? 'Selamat Pagi, Pak Mustari' }}</div>
            <div class="navbar-top__user-role">{{ $role ?? 'Administrator Sistem' }}</div>
        </div>
    </div>
    <div class="navbar-top__right">
        <button class="btn-theme" id="btnTheme" title="Ganti Tema">
            <i class="fi fi-rr-sun" id="themeIcon"></i>
        </button>
    </div>
</nav>
