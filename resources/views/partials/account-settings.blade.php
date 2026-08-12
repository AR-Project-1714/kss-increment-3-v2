@php
    $accountSettingsUser = auth()->user();
    $accountSettingsInitials = collect(preg_split('/\s+/', trim((string) ($accountSettingsUser->name ?? 'Pengguna'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $accountSettingsPhotoUrl = $accountSettingsUser->profile_photo_path
        ? asset($accountSettingsUser->profile_photo_path)
        : '';
@endphp

<div class="kss-account-modal kss-account-photo" id="accountPhotoModal" data-account-photo-modal aria-hidden="true">
    <div
        class="kss-account-modal__panel kss-account-photo__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="accountPhotoTitle"
        aria-describedby="accountPhotoSubtitle"
        tabindex="-1"
    >
        <div class="kss-account-modal__header">
            <span class="kss-account-modal__icon" aria-hidden="true">
                <i class="fi fi-rr-camera"></i>
            </span>
            <span class="kss-account-modal__heading">
                <strong id="accountPhotoTitle">Ganti Foto Profil</strong>
                <small id="accountPhotoSubtitle">Pilih foto yang jelas agar akun lebih mudah dikenali.</small>
            </span>
            <button type="button" class="kss-account-modal__close" data-account-photo-close aria-label="Tutup modal foto profil">
                <i class="fi fi-rr-cross-small"></i>
            </button>
        </div>

        <form
            action="{{ route('account.profile-photo.update') }}"
            method="POST"
            enctype="multipart/form-data"
            data-account-photo-form
            data-account-photo-delete-url="{{ route('account.profile-photo.delete') }}"
            novalidate
        >
            @csrf
            @method('PATCH')

            <div class="kss-account-modal__body kss-account-photo__body">
                <div class="kss-account-modal__alert" data-account-photo-alert role="alert" aria-live="assertive" hidden></div>

                <div class="kss-account-photo__preview-wrap">
                    <span class="kss-account-photo__preview" aria-hidden="true">
                        <img
                            src="{{ $accountSettingsPhotoUrl }}"
                            alt=""
                            data-account-photo-preview
                            @if (! $accountSettingsPhotoUrl) hidden @endif
                        >
                        <span data-account-photo-preview-fallback @if ($accountSettingsPhotoUrl) hidden @endif>
                            {{ $accountSettingsInitials ?: 'U' }}
                        </span>
                    </span>
                    <span class="kss-account-photo__preview-copy">
                        <strong>Preview foto profil</strong>
                        <small data-account-photo-file-info>Belum ada foto baru yang dipilih.</small>
                    </span>
                </div>

                <input
                    id="accountProfilePhoto"
                    type="file"
                    name="profile_photo"
                    accept="image/jpeg,image/png,image/webp"
                    data-account-photo-input
                    hidden
                >

                <button
                    type="button"
                    class="kss-account-photo__dropzone"
                    data-account-photo-dropzone
                    aria-describedby="accountPhotoRules"
                >
                    <span class="kss-account-photo__dropzone-icon" aria-hidden="true">
                        <i class="fi fi-rr-cloud-upload-alt"></i>
                    </span>
                    <span class="kss-account-photo__dropzone-copy">
                        <strong>Tarik foto ke sini atau pilih dari perangkat</strong>
                        <small>Klik area ini untuk mencari foto.</small>
                    </span>
                </button>

                <div class="kss-account-photo__rules" id="accountPhotoRules">
                    <span><i class="fi fi-rr-picture" aria-hidden="true"></i> JPG, PNG, atau WebP</span>
                    <span><i class="fi fi-rr-resize" aria-hidden="true"></i> Minimal 96 × 96 piksel</span>
                    <span><i class="fi fi-rr-file" aria-hidden="true"></i> Maksimal 10 MB</span>
                    <span><i class="fi fi-rr-shield-check" aria-hidden="true"></i> Otomatis diamankan &amp; diubah ke WebP</span>
                </div>

                <div class="kss-account-photo__progress" data-account-photo-progress aria-live="polite" hidden>
                    <span class="kss-account-photo__progress-copy">
                        <strong data-account-photo-progress-label>Mengunggah foto...</strong>
                        <span data-account-photo-progress-value>0%</span>
                    </span>
                    <span
                        class="kss-account-photo__progress-track"
                        role="progressbar"
                        aria-label="Progres unggah foto profil"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                        data-account-photo-progress-track
                    >
                        <span data-account-photo-progress-bar></span>
                    </span>
                </div>
            </div>

            <div class="kss-account-modal__footer">
                <button
                    type="button"
                    class="kss-account-button kss-account-button--danger-outline"
                    data-account-photo-delete-open
                    @if (! $accountSettingsPhotoUrl) hidden @endif
                >
                    <i class="fi fi-rr-trash" aria-hidden="true"></i>
                    Hapus Foto
                </button>
                <button type="button" class="kss-account-button kss-account-button--secondary" data-account-photo-close>Batal</button>
                <button type="submit" class="kss-account-button kss-account-button--primary" data-account-photo-submit disabled>
                    <i class="fi fi-rr-cloud-upload-alt" aria-hidden="true"></i>
                    <span data-account-photo-submit-label>Simpan Foto</span>
                </button>

                <div class="kss-account-photo__delete-confirm" data-account-photo-delete-confirm hidden>
                    <span>
                        <strong>Hapus foto profil?</strong>
                        <small>Avatar akan kembali menjadi inisial nama.</small>
                    </span>
                    <button type="button" class="kss-account-button kss-account-button--secondary" data-account-photo-delete-cancel>Tidak</button>
                    <button type="button" class="kss-account-button kss-account-button--danger" data-account-photo-delete-confirm-button>
                        <i class="fi fi-rr-trash" aria-hidden="true"></i>
                        <span data-account-photo-delete-label>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="kss-account-modal" id="accountPasswordModal" data-account-modal aria-hidden="true">
    <div
        class="kss-account-modal__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="accountPasswordTitle"
        aria-describedby="accountPasswordSubtitle"
        tabindex="-1"
    >
        <div class="kss-account-modal__header">
            <span class="kss-account-modal__icon" aria-hidden="true">
                <i class="fi fi-rr-key"></i>
            </span>
            <span class="kss-account-modal__heading">
                <strong id="accountPasswordTitle">Ubah Password</strong>
                <small id="accountPasswordSubtitle">Gunakan password baru yang aman dan mudah Anda ingat.</small>
            </span>
            <button type="button" class="kss-account-modal__close" data-account-modal-close aria-label="Tutup modal">
                <i class="fi fi-rr-cross-small"></i>
            </button>
        </div>

        <form action="{{ route('account.password.update') }}" method="POST" data-account-form novalidate>
            @csrf
            @method('PATCH')

            <div class="kss-account-modal__body">
                <div class="kss-account-modal__alert" data-account-alert role="alert" aria-live="assertive" hidden></div>

                <div class="kss-account-field">
                    <label for="accountCurrentPassword">Password Saat Ini</label>
                    <div class="kss-account-field__control">
                        <input
                            id="accountCurrentPassword"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Masukkan password saat ini"
                            maxlength="255"
                            required
                            data-account-input="current_password"
                        >
                        <button type="button" data-password-toggle aria-label="Tampilkan password saat ini" aria-pressed="false">
                            <i class="fi fi-rr-eye"></i>
                        </button>
                    </div>
                    <span class="kss-account-field__error" data-account-error="current_password" aria-live="polite"></span>
                </div>

                <div class="kss-account-field">
                    <label for="accountNewPassword">Password Baru</label>
                    <div class="kss-account-field__control">
                        <input
                            id="accountNewPassword"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                            minlength="8"
                            maxlength="255"
                            required
                            data-account-input="password"
                        >
                        <button type="button" data-password-toggle aria-label="Tampilkan password baru" aria-pressed="false">
                            <i class="fi fi-rr-eye"></i>
                        </button>
                    </div>
                    <span class="kss-account-field__error" data-account-error="password" aria-live="polite"></span>
                </div>

                <div class="kss-account-field">
                    <label for="accountPasswordConfirmation">Konfirmasi Password Baru</label>
                    <div class="kss-account-field__control">
                        <input
                            id="accountPasswordConfirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Ketik ulang password baru"
                            minlength="8"
                            maxlength="255"
                            required
                            data-account-input="password_confirmation"
                        >
                        <button type="button" data-password-toggle aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                            <i class="fi fi-rr-eye"></i>
                        </button>
                    </div>
                    <span class="kss-account-field__error" data-account-error="password_confirmation" aria-live="polite"></span>
                </div>

                <div class="kss-password-checks" aria-live="polite">
                    <span data-password-check="length">
                        <i class="fi fi-rr-circle-small" aria-hidden="true"></i>
                        Minimal 8 karakter
                    </span>
                    <span data-password-check="different">
                        <i class="fi fi-rr-circle-small" aria-hidden="true"></i>
                        Berbeda dari password saat ini
                    </span>
                    <span data-password-check="matching">
                        <i class="fi fi-rr-circle-small" aria-hidden="true"></i>
                        Konfirmasi password sama
                    </span>
                </div>
            </div>

            <div class="kss-account-modal__footer">
                <button type="button" class="kss-account-button kss-account-button--secondary" data-account-modal-close>Batal</button>
                <button type="submit" class="kss-account-button kss-account-button--primary" data-account-submit disabled>
                    <i class="fi fi-rr-disk" aria-hidden="true"></i>
                    <span data-account-submit-label>Simpan Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/components/account-settings.js') }}?v={{ @filemtime(public_path('js/components/account-settings.js')) }}"></script>
