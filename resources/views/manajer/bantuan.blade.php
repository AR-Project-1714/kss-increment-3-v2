@extends('manajer.layouts.app')

@push('styles')
    <style>
        .help-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .help-card {
            display: flex;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(37, 99, 235, .06);
        }

        .help-card__icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 9px;
            color: var(--blue-main);
            background-color: var(--blue-main-10);
            font-size: 15px;
        }

        .help-card__content {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .help-card__title {
            font-size: 13px;
            font-weight: 600;
            color: var(--black);
        }

        .help-card__text,
        .help-list {
            font-size: 11px;
            line-height: 1.65;
            color: var(--black-secondary);
        }

        .help-list {
            margin: 0;
            padding-left: 16px;
        }

        .help-flow {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .help-step {
            position: relative;
            padding: 14px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--blue-main-3);
        }

        .help-step__number {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            border-radius: 50%;
            color: #fff;
            background-color: var(--blue-main);
            font-size: 11px;
            font-weight: 700;
        }

        .help-status-table {
            width: 100%;
            min-width: 560px;
            border-collapse: collapse;
            font-size: 11px;
        }

        .help-status-table th,
        .help-status-table td {
            padding: 12px;
            border-bottom: 1px solid var(--smooth-border);
            text-align: left;
            vertical-align: top;
        }

        .help-status-table th {
            color: var(--black);
            background-color: var(--blue-main-3);
            font-weight: 600;
        }

        .help-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            border: 1px solid var(--blue-main-10);
            border-radius: 8px;
            color: var(--blue-main);
            background-color: var(--blue-main-3);
            font-size: 11px;
            line-height: 1.6;
        }

        .help-note i {
            position: relative;
            top: 2px;
            flex: 0 0 auto;
        }

        @media (max-width: 900px) {
            .help-grid,
            .help-flow {
                grid-template-columns: 1fr;
            }

            .help-card {
                padding: 14px;
            }
        }
    </style>
@endpush

@section('content')
    <main class="page-content">
        <div class="page-header">
            <span class="page-title">Pusat Bantuan</span>
            <span class="page-subtitle">Panduan singkat untuk memahami alur laporan, tanda tangan, arsip, dan batas akses akun manajer.</span>
        </div>

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Ringkasan Sistem</span>
                <span class="section-card__subtitle">Akun manajer dipakai untuk meninjau laporan yang sudah diterima regu tujuan, memberi tanda tangan final, dan melihat arsip laporan.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-dashboard"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Dashboard Manajer</span>
                            <span class="help-card__text">Berisi card ringkasan dan daftar laporan masuk dari divisi. Untuk saat ini divisi yang aktif adalah Operasional.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-file-signature"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Tanda Tangan Manajer</span>
                            <span class="help-card__text">Laporan dapat ditandatangani setelah statusnya sudah diterima oleh regu tujuan. Setelah dikonfirmasi, laporan masuk ke Arsip Laporan.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-folder-open"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Arsip Laporan</span>
                            <span class="help-card__text">Menampilkan laporan berstatus diserahkan dan ditanda tangani. Dari sini manajer dapat mencari, meninjau, mengunduh, atau menghapus arsip.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-shield-check"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Batas Akses</span>
                            <span class="help-card__text">Akun manajer hanya diarahkan ke halaman manajer. Halaman petugas operasional, pemeliharaan, dan safety tidak dapat diakses oleh manajer.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Alur Laporan</span>
                <span class="section-card__subtitle">Urutan ini membantu manajer memahami kapan laporan muncul dan kapan bisa masuk arsip.</span>
            </div>
            <div class="section-card__body">
                <div class="help-flow">
                    <div class="help-step">
                        <span class="help-step__number">1</span>
                        <div class="help-card__content">
                            <span class="help-card__title">Petugas membuat laporan</span>
                            <span class="help-card__text">Petugas operasional mengisi laporan shift harian dan dapat menyimpannya sebagai draft.</span>
                        </div>
                    </div>
                    <div class="help-step">
                        <span class="help-step__number">2</span>
                        <div class="help-card__content">
                            <span class="help-card__title">Laporan diserahkan</span>
                            <span class="help-card__text">Setelah selesai, laporan dikirim ke regu penerima sesuai pilihan pada Info Umum.</span>
                        </div>
                    </div>
                    <div class="help-step">
                        <span class="help-step__number">3</span>
                        <div class="help-card__content">
                            <span class="help-card__title">Regu penerima menerima</span>
                            <span class="help-card__text">Regu tujuan meninjau dan menandatangani laporan. Setelah itu laporan muncul di dashboard manajer.</span>
                        </div>
                    </div>
                    <div class="help-step">
                        <span class="help-step__number">4</span>
                        <div class="help-card__content">
                            <span class="help-card__title">Manajer mengarsipkan</span>
                            <span class="help-card__text">Manajer meninjau laporan, menekan tanda tangan, lalu laporan tersedia di menu Arsip Laporan.</span>
                        </div>
                    </div>
                </div>

                <div class="help-note">
                    <i class="fi fi-rr-info"></i>
                    <span>Jika laporan belum muncul di dashboard manajer, biasanya laporan tersebut belum ditandatangani oleh regu penerima atau masih berada sebagai draft/pending di sisi petugas.</span>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Status Laporan</span>
                <span class="section-card__subtitle">Gunakan tabel ini untuk membaca arti status yang tampil pada dashboard dan arsip.</span>
            </div>
            <div class="section-card__body">
                <div class="table-responsive-wrapper">
                    <table class="help-status-table">
                        <thead>
                            <tr>
                                <th>Status Sistem</th>
                                <th>Tampilan</th>
                                <th>Arti untuk Manajer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>submitted</code></td>
                                <td>Diserahkan</td>
                                <td>Laporan sudah dikirim oleh petugas dan masih dalam proses penerimaan/tinjauan.</td>
                            </tr>
                            <tr>
                                <td><code>acknowledged</code></td>
                                <td>Diterima</td>
                                <td>Laporan sudah diterima regu tujuan dan bisa ditinjau oleh manajer dari dashboard.</td>
                            </tr>
                            <tr>
                                <td><code>approved</code></td>
                                <td>Diarsipkan</td>
                                <td>Laporan sudah dikonfirmasi manajer dan tersedia di Arsip Laporan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Pencarian dan Filter Arsip</span>
                <span class="section-card__subtitle">Arsip dirancang agar laporan lama mudah ditemukan tanpa membuka satu per satu.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-search"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Kolom Pencarian</span>
                            <ul class="help-list">
                                <li>Bisa mencari ID dokumen, tanggal, shift, regu, nama kapal, karyawan, truck, atau isi laporan.</li>
                                <li>Saran pencarian muncul saat mengetik dan tertutup otomatis saat kursor keluar dari area pencarian.</li>
                                <li>Tekan Enter untuk mencari ke seluruh arsip.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-filter"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Filter dan Urutan</span>
                            <ul class="help-list">
                                <li>Gunakan filter tanggal, divisi, regu, shift, dan status untuk mempersempit daftar.</li>
                                <li>Pilihan Terbaru/Terlama mengatur urutan laporan berdasarkan tanggal laporan.</li>
                                <li>Tombol Reset mengembalikan daftar ke kondisi awal.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Jika Ada Kendala</span>
                <span class="section-card__subtitle">Beberapa kondisi umum yang dapat dicek sebelum menghubungi admin sistem.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-triangle-warning"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Laporan tidak muncul</span>
                            <span class="help-card__text">Pastikan laporan sudah diserahkan oleh petugas dan sudah diterima oleh regu tujuan. Laporan draft tidak tampil pada dashboard manajer.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-user-gear"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Tanda tangan belum tersedia</span>
                            <span class="help-card__text">Jika area tanda tangan kosong atau data akun tidak sesuai, hubungi admin sistem untuk memeriksa data user dan file tanda tangan.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-download"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Download lambat</span>
                            <span class="help-card__text">File PDF dibuat dari isi laporan. Jika pertama kali dibuka terasa lebih lama, sistem akan memakai file yang sudah tersimpan pada proses berikutnya.</span>
                        </div>
                    </div>
                    <div class="help-card">
                        <span class="help-card__icon"><i class="fi fi-rr-mobile-notch"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Akses dari HP</span>
                            <span class="help-card__text">Gunakan tombol menu di navbar untuk membuka atau menutup sidebar. Tabel arsip dan tab laporan dapat digeser horizontal pada layar kecil.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
