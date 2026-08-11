@php
    $accountUser = auth()->user();
    $accountInitials = collect(preg_split('/\s+/', trim((string) ($accountUser->name ?? 'Pengguna'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $accountPhotoUrl = $accountUser->profile_photo_path ? asset($accountUser->profile_photo_path) : '';
@endphp

<div class="kss-account" data-account-root>
    <button
        type="button"
        class="kss-account__trigger"
        data-account-trigger
        aria-haspopup="menu"
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

    <div class="kss-account__popover" data-account-popover role="menu" aria-hidden="true">
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
    </div>
</div>
