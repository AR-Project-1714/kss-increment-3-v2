@extends('admin.layouts.app')

@section('title', 'KSS Admin - Pusat Bantuan')
@section('active', 'help')

@push('styles')
<style>
    .help-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 0.42fr);
        gap: 20px;
        align-items: start;
    }

    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }

    .archive-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .help-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border: 1px solid var(--smooth-border);
        border-radius: 50px;
        background-color: var(--main-bg);
        flex: 1 1 360px;
        max-width: 520px;
    }

    .search-box i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

    .search-box input {
        border: none;
        background: transparent;
        outline: none;
        font-family: inherit;
        font-size: 12px;
        color: var(--black);
        width: 100%;
    }

    .btn-tool {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-tool i { position: relative; top: 1px; }
    .btn-tool:hover { background-color: var(--blue-main-5); border-color: var(--blue-main-25); color: var(--blue-main); }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    .help-topics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .topic-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--main-bg);
        transition: 0.2s ease;
    }

    .topic-card:hover {
        border-color: var(--blue-main-25);
        background-color: var(--blue-main-5);
    }

    .topic-card__icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background-color: var(--blue-main-10);
        color: var(--blue-main);
        font-size: 15px;
    }

    .topic-card__icon.green { background-color: var(--success-10); color: var(--success); }
    .topic-card__icon.orange { background-color: var(--orange-main-10); color: var(--orange-main); }
    .topic-card__icon.red { background-color: var(--red-main-10); color: var(--red-main); }
    .topic-card__icon i { position: relative; top: 2px; }

    .topic-card__title { font-size: 12px; font-weight: 700; color: var(--black); }
    .topic-card__text { margin-top: 3px; font-size: 10px; line-height: 1.5; color: var(--muted); }

    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .faq-item {
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        overflow: hidden;
    }

    .faq-item summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 14px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        color: var(--black);
    }

    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary i { color: var(--blue-main); transition: transform 0.2s ease; }
    .faq-item[open] summary i { transform: rotate(180deg); }

    .faq-item__body {
        padding: 0 14px 14px;
        font-size: 11px;
        line-height: 1.7;
        color: var(--black-secondary);
    }

    .help-side {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .help-panel {
        border-radius: 10px;
        background-color: var(--white);
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        padding: 18px;
    }

    .help-panel__title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: var(--black);
        margin-bottom: 12px;
    }

    .help-panel__title i { color: var(--blue-main); position: relative; top: 1px; }

    .support-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .support-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--smooth-border);
        font-size: 11px;
        color: var(--muted);
    }

    .support-item:last-child { border-bottom: none; padding-bottom: 0; }
    .support-item strong { color: var(--black); font-size: 12px; }

    .status-pill {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        color: var(--success);
        background-color: var(--success-10);
    }

    @media (max-width: 1100px) {
        .help-layout { grid-template-columns: 1fr; }
        .help-topics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 640px) {
        .help-topics { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $topics = [
        ['title' => 'Akun & Role', 'text' => 'Status akun, role pengguna, dan pembagian akses.', 'icon' => 'fi fi-sr-user', 'color' => ''],
        ['title' => 'Laporan Operasional', 'text' => 'Alur laporan, arsip, tanda tangan, dan export dokumen.', 'icon' => 'fi fi-sr-document', 'color' => 'green'],
        ['title' => 'Backup Sistem', 'text' => 'Jadwal cadangan, restore, dan validasi file backup.', 'icon' => 'fi fi-sr-cloud-upload', 'color' => 'orange'],
        ['title' => 'Master Data', 'text' => 'Data karyawan, unit, truck, dan inventaris sistem.', 'icon' => 'fi fi-sr-database', 'color' => ''],
        ['title' => 'Audit Log', 'text' => 'Rekam aktivitas pengguna dan kejadian keamanan.', 'icon' => 'fi fi-sr-document-signed', 'color' => 'red'],
        ['title' => 'Integrasi File', 'text' => 'Lampiran laporan, tanda tangan, dan file export.', 'icon' => 'fi fi-sr-folder', 'color' => 'green'],
    ];

    $faqs = [
        ['q' => 'Mengapa menu admin bisa dibuka tanpa login?', 'a' => 'Mode ini disiapkan sebagai preview tampilan agar halaman admin dapat diuji selama pengembangan. Proteksi autentikasi dapat dipasang kembali saat halaman masuk tahap produksi.'],
        ['q' => 'Apa yang perlu dicek jika backup gagal?', 'a' => 'Periksa kapasitas storage, izin tulis folder penyimpanan, koneksi database, dan log sistem pada waktu eksekusi backup.'],
        ['q' => 'Bagaimana status pengguna dinonaktifkan?', 'a' => 'Status pengguna dapat diubah lewat toggle pada tabel Kelola Pengguna. Pada integrasi backend, perubahan ini sebaiknya dikonfirmasi dan dicatat ke audit log.'],
        ['q' => 'Data master apa saja yang perlu dijaga?', 'a' => 'Data karyawan, unit, truck, dan inventaris menjadi referensi laporan. Perubahan data master perlu mempertimbangkan riwayat laporan yang sudah dibuat.'],
    ];
@endphp

<div class="page-header">
    <span class="page-title">Pusat Bantuan</span>
    <span class="page-subtitle">Referensi bantuan untuk admin sistem, audit, backup, dan pengelolaan data.</span>
</div>

<div class="help-layout">
    @component('admin.layouts.card', ['title' => 'Bantuan Admin'])
        <div class="help-toolbar">
            <div class="search-box">
                <span><i class="fi fi-rr-search"></i></span>
                <input type="text" placeholder="Cari topik bantuan">
            </div>
            <button type="button" class="btn-tool btn-tool--primary" data-modal-target="supportTicketModal">
                <i class="fi fi-rr-comment-alt"></i> Buat Tiket Bantuan
            </button>
        </div>

        <div class="help-topics">
            @foreach ($topics as $topic)
                <div class="topic-card">
                    <div class="topic-card__icon {{ $topic['color'] }}"><i class="{{ $topic['icon'] }}"></i></div>
                    <div>
                        <div class="topic-card__title">{{ $topic['title'] }}</div>
                        <div class="topic-card__text">{{ $topic['text'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="faq-list">
            @foreach ($faqs as $faq)
                <details class="faq-item" @if($loop->first) open @endif>
                    <summary>
                        <span>{{ $faq['q'] }}</span>
                        <i class="fi fi-rr-angle-small-down"></i>
                    </summary>
                    <div class="faq-item__body">{{ $faq['a'] }}</div>
                </details>
            @endforeach
        </div>
    @endcomponent

    <div class="help-side">
        <div class="help-panel">
            <div class="help-panel__title"><i class="fi fi-sr-headset"></i> Kontak Dukungan</div>
            <div class="support-list">
                <div class="support-item">
                    <span>Admin Sistem</span>
                    <strong>IT KSS</strong>
                </div>
                <div class="support-item">
                    <span>Jam Respons</span>
                    <strong>08:00 - 17:00</strong>
                </div>
                <div class="support-item">
                    <span>Status Layanan</span>
                    <span class="status-pill">Normal</span>
                </div>
            </div>
        </div>

        <div class="help-panel">
            <div class="help-panel__title"><i class="fi fi-sr-book-alt"></i> Dokumen Cepat</div>
            <div class="support-list">
                <div class="support-item">
                    <span>Dokumentasi proyek</span>
                    <strong>DOKUMENTASI.md</strong>
                </div>
                <div class="support-item">
                    <span>Pembaruan fitur</span>
                    <strong>PEMBARUAN_IMPLEMENTASI.md</strong>
                </div>
                <div class="support-item">
                    <span>Route admin</span>
                    <strong>/admin/*</strong>
                </div>
            </div>
        </div>

        <div class="help-panel">
            <div class="help-panel__title"><i class="fi fi-sr-shield-check"></i> Catatan Kontrol</div>
            <div class="kss-modal__message">
                Aktivitas sensitif seperti restore backup, hapus data, dan perubahan status pengguna sebaiknya masuk ke audit log saat integrasi backend aktif.
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="supportTicketModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="supportTicketTitle">
        <form data-preview-submit>
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-comment-alt"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="supportTicketTitle">Buat Tiket Bantuan</div>
                    <div class="kss-modal__subtitle">Catat kebutuhan bantuan untuk admin sistem atau tim IT.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid">
                    <div class="kss-modal__field">
                        <label for="ticketCategory">Kategori</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="ticketCategory">
                                <option>Akun & Role</option>
                                <option>Laporan Operasional</option>
                                <option>Backup Sistem</option>
                                <option>Master Data</option>
                                <option>Audit Log</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="ticketPriority">Prioritas</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="ticketPriority">
                                <option>Normal</option>
                                <option>Tinggi</option>
                                <option>Kritis</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="ticketTitle">Judul Tiket</label>
                        <input class="kss-modal__input" id="ticketTitle" type="text" placeholder="Ringkasan kendala" data-modal-focus>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="ticketDescription">Detail Bantuan</label>
                        <textarea class="kss-modal__textarea" id="ticketDescription" placeholder="Tuliskan detail kendala atau permintaan bantuan"></textarea>
                    </div>
                </div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary">
                    <i class="fi fi-rr-paper-plane"></i> Kirim Tiket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
