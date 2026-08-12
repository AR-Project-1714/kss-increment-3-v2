<div
    class="kss-notification-center"
    data-notification-root
    data-index-url="{{ route('notifications.index') }}"
    data-read-url-template="{{ route('notifications.read', ['notification' => '__ID__']) }}"
    data-read-all-url="{{ route('notifications.read-all') }}"
    data-csrf-token="{{ csrf_token() }}"
>
    <button
        type="button"
        class="kss-notification-center__trigger"
        data-notification-trigger
        aria-label="Buka notifikasi"
        aria-haspopup="dialog"
        aria-expanded="false"
    >
        <i class="fi fi-rr-bell" aria-hidden="true"></i>
        <span class="kss-notification-center__badge" data-notification-badge hidden></span>
    </button>

    <section
        class="kss-notification-center__panel"
        data-notification-panel
        role="dialog"
        aria-label="Kotak notifikasi"
        aria-hidden="true"
        hidden
    >
        <div class="kss-notification-center__header">
            <div>
                <strong>Notifikasi</strong>
                <span data-notification-refresh-label>Pembaruan sistem dan laporan</span>
            </div>
            <button type="button" data-notification-read-all disabled>Tandai semua dibaca</button>
        </div>

        <div class="kss-notification-center__content" data-notification-content>
            <div class="kss-notification-center__state" data-notification-loading>
                <i class="fi fi-rr-spinner" aria-hidden="true"></i>
                <strong>Memuat notifikasi</strong>
                <span>Menyiapkan pembaruan terbaru untuk Anda.</span>
            </div>
        </div>

        <div class="kss-notification-center__footer">
            Notifikasi selesai atau kedaluwarsa akan hilang otomatis.
        </div>
    </section>

    <span class="kss-notification-center__live" data-notification-live aria-live="polite"></span>
</div>

@once
    <script src="{{ asset('js/components/notification-center.js') }}?v={{ @filemtime(public_path('js/components/notification-center.js')) }}"></script>
@endonce
