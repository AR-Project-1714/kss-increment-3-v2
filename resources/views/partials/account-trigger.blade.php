@php
    use App\Models\Role;

    $accountUser = auth()->user();
    $accountInitials = collect(preg_split('/\s+/', trim((string) ($accountUser->name ?? 'Pengguna'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $accountPhotoUrl = $accountUser->profile_photo_path ? asset($accountUser->profile_photo_path) : '';
    $profileAccountMenu = (bool) ($showProfileMenu ?? false);
    $canChangeOwnPassword = Role::normalize($accountUser->role->name ?? null) === Role::ADMIN;
@endphp

<div class="kss-account" data-account-root>
    <button
        type="button"
        class="kss-account__trigger"
        data-account-trigger
        aria-haspopup="{{ $profileAccountMenu ? 'dialog' : 'menu' }}"
        aria-expanded="false"
        aria-label="Buka pengaturan akun {{ $accountUser->name ?? 'pengguna' }}"
    >
        <span class="kss-account__trigger-avatar" aria-hidden="true">
            <img
                src="{{ $accountPhotoUrl }}"
                alt=""
                data-account-avatar-image
                @if (! $accountPhotoUrl) hidden @endif
                onerror="this.hidden=true; this.nextElementSibling.hidden=false;"
            >
            <span data-account-avatar-fallback @if ($accountPhotoUrl) hidden @endif>{{ $accountInitials ?: 'U' }}</span>
        </span>
        <span class="kss-account__presence" aria-hidden="true"></span>
    </button>

    <div
        class="kss-account__popover @if ($profileAccountMenu) kss-account__popover--profile @endif"
        data-account-popover
        role="{{ $profileAccountMenu ? 'dialog' : 'menu' }}"
        aria-label="{{ $profileAccountMenu ? 'Menu profil pengguna' : 'Pengaturan akun' }}"
        aria-hidden="true"
    >
        @if ($profileAccountMenu)
            <span class="kss-account__menu-heading kss-account__menu-heading--profile">Profil Pengguna</span>

            <button
                type="button"
                class="kss-account__profile-card"
                data-account-photo-open
                data-account-initial-focus
                aria-label="Buka profil dan atur foto {{ $accountUser->name ?? 'pengguna' }}"
            >
                <span class="kss-account__avatar kss-account__avatar--profile" aria-hidden="true">
                    <img
                        src="{{ $accountPhotoUrl }}"
                        alt=""
                        data-account-avatar-image
                        @if (! $accountPhotoUrl) hidden @endif
                        onerror="this.hidden=true; this.nextElementSibling.hidden=false;"
                    >
                    <span data-account-avatar-fallback @if ($accountPhotoUrl) hidden @endif>{{ $accountInitials ?: 'U' }}</span>
                </span>
                <span class="kss-account__summary-copy">
                    <strong>{{ $accountUser->name ?? 'Pengguna' }}</strong>
                    <small>{{ '@'.($accountUser->username ?? 'pengguna') }}</small>
                    <span>{{ $accountUser->jobTitle() }}</span>
                </span>
                <span class="kss-account__profile-action" aria-hidden="true">
                    <i class="fi fi-rr-camera"></i>
                </span>
            </button>

            <div class="kss-account__divider"></div>

            <div class="kss-account__action-list">
                @if ($canChangeOwnPassword)
                    <button type="button" class="kss-account__menu-item" data-account-open>
                        <span class="kss-account__menu-icon" aria-hidden="true">
                            <i class="fi fi-rr-key"></i>
                        </span>
                        <span>
                            <strong>Ubah Password</strong>
                            <small>Perbarui keamanan akun</small>
                        </span>
                        <i class="fi fi-rr-angle-small-right kss-account__menu-arrow" aria-hidden="true"></i>
                    </button>
                @else
                    <button type="button" class="kss-account__menu-item" data-account-help-open>
                        <span class="kss-account__menu-icon" aria-hidden="true">
                            <i class="fi fi-rr-shield-check"></i>
                        </span>
                        <span>
                            <strong>Bantuan Akun</strong>
                            <small>Password dikelola oleh admin</small>
                        </span>
                        <i class="fi fi-rr-angle-small-right kss-account__menu-arrow" aria-hidden="true"></i>
                    </button>
                @endif

                @if ($showThemeToggle ?? false)
                    <label class="kss-account__theme-row">
                        <span class="kss-account__menu-icon" aria-hidden="true">
                            <i class="fi fi-rr-moon"></i>
                        </span>
                        <span class="kss-account__theme-copy">
                            <strong>Mode Gelap</strong>
                            <small data-account-theme-status>Nonaktif</small>
                        </span>
                        <input
                            type="checkbox"
                            class="kss-account__theme-switch"
                            data-account-theme-toggle
                            role="switch"
                            aria-label="Aktifkan mode gelap"
                        >
                    </label>
                @endif
            </div>

            @if ($showLogout ?? false)
                <div class="kss-account__divider"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="kss-account__menu-item kss-account__menu-item--danger">
                        <span class="kss-account__menu-icon" aria-hidden="true">
                            <i class="fi fi-rr-exit"></i>
                        </span>
                        <span>
                            <strong>Keluar</strong>
                            <small>Akhiri sesi pada perangkat ini</small>
                        </span>
                    </button>
                </form>
            @endif
        @else
        <div class="kss-account__summary">
            <span class="kss-account__avatar" aria-hidden="true">
                <img
                    src="{{ $accountPhotoUrl }}"
                    alt=""
                    data-account-avatar-image
                    @if (! $accountPhotoUrl) hidden @endif
                    onerror="this.hidden=true; this.nextElementSibling.hidden=false;"
                >
                <span data-account-avatar-fallback @if ($accountPhotoUrl) hidden @endif>{{ $accountInitials ?: 'U' }}</span>
            </span>
            <span class="kss-account__summary-copy">
                <strong>{{ $accountUser->name ?? 'Pengguna' }}</strong>
                <small>{{ '@'.($accountUser->username ?? 'pengguna') }}</small>
                <span>{{ $accountUser->jobTitle() }}</span>
            </span>
        </div>
        <div class="kss-account__divider"></div>
        <span class="kss-account__menu-heading">Pengaturan Akun</span>

        <button type="button" class="kss-account__menu-item" data-account-photo-open role="menuitem">
            <span class="kss-account__menu-icon" aria-hidden="true">
                <i class="fi fi-rr-camera"></i>
            </span>
            <span>
                <strong data-account-photo-label>{{ $accountPhotoUrl ? 'Ganti Foto Profil' : 'Unggah Foto Profil' }}</strong>
                <small>Atur foto dan lihat preview</small>
            </span>
            <i class="fi fi-rr-angle-small-right kss-account__menu-arrow" aria-hidden="true"></i>
        </button>

        @if ($canChangeOwnPassword)
            <button type="button" class="kss-account__menu-item" data-account-open role="menuitem">
                <span class="kss-account__menu-icon" aria-hidden="true">
                    <i class="fi fi-rr-lock"></i>
                </span>
                <span>
                    <strong>Ubah Password</strong>
                    <small>Perbarui keamanan akun</small>
                </span>
                <i class="fi fi-rr-angle-small-right kss-account__menu-arrow" aria-hidden="true"></i>
            </button>
        @else
            <button type="button" class="kss-account__menu-item" data-account-help-open role="menuitem">
                <span class="kss-account__menu-icon" aria-hidden="true">
                    <i class="fi fi-rr-shield-check"></i>
                </span>
                <span>
                    <strong>Bantuan Akun</strong>
                    <small>Password dikelola oleh admin</small>
                </span>
                <i class="fi fi-rr-angle-small-right kss-account__menu-arrow" aria-hidden="true"></i>
            </button>
        @endif

        @if ($showLogout ?? false)
            <div class="kss-account__divider"></div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="kss-account__menu-item kss-account__menu-item--danger" role="menuitem">
                    <span class="kss-account__menu-icon" aria-hidden="true">
                        <i class="fi fi-br-exit"></i>
                    </span>
                    <span>
                        <strong>Keluar dari Akun</strong>
                        <small>Akhiri sesi pada perangkat ini</small>
                    </span>
                </button>
            </form>
        @endif
        @endif
    </div>
</div>
