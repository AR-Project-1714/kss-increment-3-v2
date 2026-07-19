@extends('manajer.layouts.app')

@push('styles')
    <style>
        /* ---- Toolbar pencarian ---- */
        .help-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .help-search { position: relative; flex: 1 1 320px; max-width: 540px; }

        .help-searchbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border: 1px solid var(--smooth-border);
            border-radius: 50px;
            background-color: var(--main-bg);
        }

        .help-searchbox i { color: var(--muted); font-size: 13px; position: relative; top: 1px; }

        .help-searchbox input {
            border: none;
            background: transparent;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            color: var(--black);
            width: 100%;
            padding-right: 22px;
        }

        .help-searchbox input::placeholder { color: var(--muted); }

        .help-search__clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: none;
            border-radius: 50%;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
        }

        .help-search__clear i { position: relative; top: 1px; }
        .help-toolbar__hint { font-size: 11px; color: var(--muted); }

        /* ---- Tab navigasi (gaya report-tab project, sticky) ---- */
        .help-tabs {
            position: sticky;
            top: 0;
            z-index: 6;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            align-content: center;
            gap: 5px 10px;
            padding: 5px;
            overflow-x: auto;
            overflow-y: hidden;
            background-color: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.7);
            scrollbar-width: none;
        }

        body.dark-mode .help-tabs {
            background-color: rgba(15, 23, 42, 0.72);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .help-tabs::-webkit-scrollbar { display: none; }

        .help-tab {
            position: relative;
            z-index: 1;
            display: flex;
            min-width: 130px;
            flex: 1 0 auto;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            color: var(--black-secondary);
            white-space: nowrap;
            cursor: pointer;
            transition: color 0.2s ease-out, background-color 0.2s ease-out;
        }

        .help-tab .icon-tab { position: relative; top: 1px; display: inline-flex; }
        .help-tab .icon-tab i { position: relative; top: 1px; font-size: 12px; }
        .help-tab:hover { background-color: var(--blue-main-10); color: var(--blue-main); }
        .help-tab.active,
        .help-tab.active:hover { color: #fff; background: transparent; }

        .help-tab-indicator {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            height: auto;
            width: 0;
            border-radius: 8px;
            background: var(--blue-main);
            box-shadow: 0 0 4px 0 var(--blue-main-40);
            transform: translateX(0);
            transition: transform 0.34s cubic-bezier(.22,1,.36,1), width 0.34s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 0;
        }

        .help-section { scroll-margin-top: 64px; }

        /* ---- Kartu konten ---- */
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

        .help-card__icon i { position: relative; top: 1px; }
        .help-card__icon.green  { color: var(--success);     background-color: var(--success-10); }
        .help-card__icon.orange { color: var(--orange-main); background-color: var(--orange-main-10); }

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

        .help-list { margin: 0; padding-left: 16px; }

        /* ---- Alur per divisi ---- */
        .help-flow-block { display: flex; flex-direction: column; gap: 8px; }

        .help-flow-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 600;
            color: var(--black);
        }

        .help-flow-label .dot { width: 8px; height: 8px; border-radius: 50%; background-color: var(--blue-main); }
        .help-flow-label .dot.orange { background-color: var(--orange-main); }

        .help-flow {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .help-flow.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

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

        .help-step__number.orange { background-color: var(--orange-main); }

        /* ---- Langkah ringkas (numbered list) ---- */
        .help-steps { display: flex; flex-direction: column; gap: 10px; }

        .help-row {
            display: flex;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
        }

        .help-row__num {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--blue-main);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
        }

        .help-row__title { font-size: 12px; font-weight: 600; color: var(--black); }
        .help-row__text { margin-top: 2px; font-size: 11px; line-height: 1.6; color: var(--black-secondary); }

        /* ---- Tabel status ---- */
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
            line-height: 1.55;
        }

        .help-status-table th {
            color: var(--black);
            background-color: var(--blue-main-3);
            font-weight: 600;
        }

        .help-status-table tr:last-child td { border-bottom: none; }

        .help-status-table code {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 5px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .help-tag {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .help-tag.orange { color: var(--orange-main); background-color: var(--orange-main-10); }
        .help-tag.cyan   { color: var(--cyan-main);   background-color: var(--cyan-main-10); }
        .help-tag.blue   { color: var(--blue-main);   background-color: var(--blue-main-10); }

        /* ---- Catatan ---- */
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

        .help-note i { position: relative; top: 2px; flex: 0 0 auto; }
        .help-note strong { color: var(--black); }

        /* ---- Empty state ---- */
        .help-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
            padding: 36px 16px;
        }

        .help-empty__icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-size: 20px;
        }

        .help-empty__icon i { position: relative; top: 2px; }
        .help-empty__title { font-size: 13px; font-weight: 600; color: var(--black); }
        .help-empty__text { font-size: 11px; color: var(--muted); }

        .is-hidden { display: none !important; }

        @media (max-width: 900px) {
            .help-grid,
            .help-flow,
            .help-flow.cols-3 {
                grid-template-columns: 1fr;
            }

            .help-card { padding: 14px; }
        }

        @media (max-width: 560px) {
            .help-tab { min-width: 44px; padding: 9px 10px; gap: 0; }
            .help-tab span:not(.icon-tab) { display: none; }
        }
    </style>
@endpush

@section('content')
    <main class="page-content">
        @php
            $nav = [
                ['id' => 'ringkasan', 'label' => 'Ringkasan',      'icon' => 'fi fi-rr-dashboard'],
                ['id' => 'alur',      'label' => 'Alur Laporan',   'icon' => 'fi fi-rr-route'],
                ['id' => 'ttd',       'label' => 'Tanda Tangan',   'icon' => 'fi fi-rr-file-signature'],
                ['id' => 'status',    'label' => 'Status Laporan', 'icon' => 'fi fi-rr-time-check'],
                ['id' => 'arsip',     'label' => 'Pencarian Arsip','icon' => 'fi fi-rr-search'],
                ['id' => 'kendala',   'label' => 'Jika Ada Kendala','icon' => 'fi fi-rr-triangle-warning'],
            ];
        @endphp

        <div class="page-header">
            <span class="page-title">Pusat Bantuan</span>
            <span class="page-subtitle">Panduan menggunakan akun manajer: meninjau laporan masuk, menandatangani, dan mengelola arsip dari tiga divisi.</span>
        </div>

        {{-- Pencarian --}}
        <div class="section-card">
            <div class="section-card__body">
                <div class="help-toolbar">
                    <div class="help-search">
                        <div class="help-searchbox">
                            <i class="fi fi-rr-search"></i>
                            <input type="text" id="helpSearch" placeholder="Cari bantuan, mis. tanda tangan, status, filter arsip" autocomplete="off">
                        </div>
                        <button type="button" class="help-search__clear is-hidden" id="helpSearchClear" aria-label="Bersihkan pencarian">
                            <i class="fi fi-rr-cross-small"></i>
                        </button>
                    </div>
                    <span class="help-toolbar__hint">Mengetik akan menyaring topik di bawah secara langsung.</span>
                </div>
            </div>
        </div>

        {{-- Tab navigasi (sticky + glassmorphism, gaya tab form petugas) --}}
        <div class="help-tabs" id="helpTabs">
            @foreach ($nav as $item)
                <button type="button" class="help-tab" data-tab="{{ $item['id'] }}">
                    <span class="icon-tab"><i class="{{ $item['icon'] }}"></i></span>
                    <span>{{ $item['label'] }}</span>
                </button>
            @endforeach
            <span class="help-tab-indicator" id="helpTabIndicator"></span>
        </div>

        <div class="help-empty is-hidden" id="helpEmpty">
            <div class="help-empty__icon"><i class="fi fi-rr-search"></i></div>
            <div class="help-empty__title">Topik tidak ditemukan</div>
            <div class="help-empty__text">Coba kata kunci lain, mis. "arsip", "diterima", atau "unduh".</div>
        </div>

        {{-- RINGKASAN --}}
        <div class="section-card help-section" id="ringkasan">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Ringkasan Sistem</span>
                <span class="section-card__subtitle">Akun manajer dipakai untuk meninjau laporan yang masuk, memberi tanda tangan final, dan melihat arsip laporan dari divisi Operasional, Pemeliharaan, dan Safety/K3.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon"><i class="fi fi-rr-dashboard"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Dashboard Manajer</span>
                            <span class="help-card__text">Berisi kartu ringkasan dan daftar laporan masuk dari tiga divisi. Gunakan tab divisi (Semua, Operasional, Pemeliharaan, Safety) untuk menyaring laporan yang menunggu tanda tangan.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon green"><i class="fi fi-rr-file-signature"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Tanda Tangan Manajer</span>
                            <span class="help-card__text">Laporan yang sudah masuk dashboard dapat ditinjau lalu ditandatangani. Setelah dikonfirmasi, laporan otomatis pindah ke Arsip Laporan.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon orange"><i class="fi fi-rr-folder-open"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Arsip Laporan</span>
                            <span class="help-card__text">Menampilkan laporan yang sudah ditandatangani/diarsipkan. Dari sini manajer dapat mencari, meninjau, mengunduh, atau menghapus arsip.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon"><i class="fi fi-rr-shield-check"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Batas Akses</span>
                            <span class="help-card__text">Akun manajer hanya diarahkan ke halaman manajer. Halaman petugas operasional, pemeliharaan, dan safety tidak dapat diakses oleh manajer.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALUR --}}
        <div class="section-card help-section" id="alur">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Alur Laporan</span>
                <span class="section-card__subtitle">Alurnya sedikit berbeda antar divisi. Urutan ini membantu memahami kapan laporan muncul di dashboard dan kapan masuk arsip.</span>
            </div>
            <div class="section-card__body">
                <div class="help-flow-block" data-help-item>
                    <span class="help-flow-label"><span class="dot"></span> Operasional</span>
                    <div class="help-flow">
                        <div class="help-step">
                            <span class="help-step__number">1</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Petugas membuat laporan</span>
                                <span class="help-card__text">Petugas operasional mengisi laporan shift dan dapat menyimpannya sebagai draft.</span>
                            </div>
                        </div>
                        <div class="help-step">
                            <span class="help-step__number">2</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Diserahkan ke regu tujuan</span>
                                <span class="help-card__text">Laporan dikirim ke regu penerima sesuai pilihan pada Info Umum.</span>
                            </div>
                        </div>
                        <div class="help-step">
                            <span class="help-step__number">3</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Regu tujuan menerima</span>
                                <span class="help-card__text">Regu penerima meninjau &amp; menandatangani. Status menjadi "Diterima" dan laporan muncul di dashboard manajer.</span>
                            </div>
                        </div>
                        <div class="help-step">
                            <span class="help-step__number">4</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Manajer mengarsipkan</span>
                                <span class="help-card__text">Manajer meninjau, menekan tanda tangan, lalu laporan tersedia di Arsip Laporan.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-flow-block" data-help-item>
                    <span class="help-flow-label"><span class="dot orange"></span> Pemeliharaan &amp; Safety/K3</span>
                    <div class="help-flow cols-3">
                        <div class="help-step">
                            <span class="help-step__number orange">1</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Petugas membuat laporan</span>
                                <span class="help-card__text">Kasi Pemeliharaan atau petugas Safety mengisi laporan dan dapat menyimpannya sebagai draft.</span>
                            </div>
                        </div>
                        <div class="help-step">
                            <span class="help-step__number orange">2</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Diserahkan</span>
                                <span class="help-card__text">Setelah dikirim, laporan langsung muncul di dashboard manajer dengan status "Diserahkan".</span>
                            </div>
                        </div>
                        <div class="help-step">
                            <span class="help-step__number orange">3</span>
                            <div class="help-card__content">
                                <span class="help-card__title">Manajer mengarsipkan</span>
                                <span class="help-card__text">Manajer meninjau lalu menandatangani. Tidak ada tahap "Diterima"; laporan langsung masuk Arsip.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-note" data-help-item>
                    <i class="fi fi-rr-info"></i>
                    <span>Jika sebuah laporan belum muncul di dashboard: untuk Operasional biasanya laporan belum ditandatangani regu penerima; untuk Pemeliharaan/Safety biasanya laporan masih draft dan belum diserahkan oleh petugas.</span>
                </div>
            </div>
        </div>

        {{-- TANDA TANGAN --}}
        <div class="section-card help-section" id="ttd">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Cara Menandatangani Laporan</span>
                <span class="section-card__subtitle">Langkah menandatangani laporan masuk dari dashboard manajer.</span>
            </div>
            <div class="section-card__body">
                <div class="help-steps">
                    <div class="help-row" data-help-item>
                        <span class="help-row__num">1</span>
                        <div>
                            <div class="help-row__title">Buka Dashboard &amp; pilih divisi</div>
                            <div class="help-row__text">Pada dashboard, gunakan tab divisi untuk melihat laporan yang menunggu tanda tangan. Angka pada tab menunjukkan jumlah laporan masuk.</div>
                        </div>
                    </div>
                    <div class="help-row" data-help-item>
                        <span class="help-row__num">2</span>
                        <div>
                            <div class="help-row__title">Tinjau isi laporan</div>
                            <div class="help-row__text">Klik tombol Lihat untuk membuka pratinjau. Periksa isi dan tanda tangan sebelumnya sebelum mengesahkan.</div>
                        </div>
                    </div>
                    <div class="help-row" data-help-item>
                        <span class="help-row__num">3</span>
                        <div>
                            <div class="help-row__title">Tanda tangani</div>
                            <div class="help-row__text">Tekan tombol tanda tangan. Sistem mencatat Anda sebagai penyetuju beserta waktunya.</div>
                        </div>
                    </div>
                    <div class="help-row" data-help-item>
                        <span class="help-row__num">4</span>
                        <div>
                            <div class="help-row__title">Laporan masuk arsip</div>
                            <div class="help-row__text">Status berubah menjadi "Diarsipkan", laporan pindah ke menu Arsip Laporan, dan PDF finalnya disiapkan untuk diunduh.</div>
                        </div>
                    </div>
                </div>
                <div class="help-note" data-help-item>
                    <i class="fi fi-rr-user-gear"></i>
                    <span>Jika area tanda tangan Anda kosong, kemungkinan file tanda tangan akun belum diunggah. Hubungi admin sistem untuk melengkapi data akun pada menu Kelola Pengguna.</span>
                </div>
            </div>
        </div>

        {{-- STATUS --}}
        <div class="section-card help-section" id="status">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Status Laporan</span>
                <span class="section-card__subtitle">Gunakan tabel ini untuk membaca arti status yang tampil pada dashboard dan arsip.</span>
            </div>
            <div class="section-card__body">
                <div class="table-responsive-wrapper" data-help-item>
                    <table class="help-status-table">
                        <thead>
                            <tr>
                                <th>Status sistem</th>
                                <th>Tampilan</th>
                                <th>Arti untuk Manajer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>submitted</code></td>
                                <td><span class="help-tag orange">Diserahkan</span></td>
                                <td>Sudah dikirim petugas. Untuk Pemeliharaan &amp; Safety, laporan ini sudah siap Anda tinjau dan tandatangani.</td>
                            </tr>
                            <tr>
                                <td><code>acknowledged</code></td>
                                <td><span class="help-tag cyan">Diterima</span></td>
                                <td>Khusus Operasional: sudah diterima regu tujuan dan siap ditandatangani manajer dari dashboard.</td>
                            </tr>
                            <tr>
                                <td><code>approved</code></td>
                                <td><span class="help-tag blue">Diarsipkan</span></td>
                                <td>Sudah Anda tandatangani dan tersedia di Arsip Laporan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ARSIP --}}
        <div class="section-card help-section" id="arsip">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Pencarian dan Filter Arsip</span>
                <span class="section-card__subtitle">Arsip dirancang agar laporan lama mudah ditemukan tanpa membuka satu per satu.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card" data-help-item>
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
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon orange"><i class="fi fi-rr-filter"></i></span>
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

        {{-- KENDALA --}}
        <div class="section-card help-section" id="kendala">
            <div class="section-card__header d-flex flex-column align-items-start">
                <span class="section-card__title">Jika Ada Kendala</span>
                <span class="section-card__subtitle">Beberapa kondisi umum yang dapat dicek sebelum menghubungi admin sistem.</span>
            </div>
            <div class="section-card__body">
                <div class="help-grid">
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon orange"><i class="fi fi-rr-triangle-warning"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Laporan tidak muncul</span>
                            <span class="help-card__text">Pastikan laporan sudah diserahkan petugas. Untuk Operasional, laporan baru muncul setelah diterima regu tujuan. Laporan draft tidak tampil di dashboard manajer.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon"><i class="fi fi-rr-user-gear"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Tanda tangan belum tersedia</span>
                            <span class="help-card__text">Jika area tanda tangan kosong atau data akun tidak sesuai, hubungi admin sistem untuk memeriksa data user dan file tanda tangan.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
                        <span class="help-card__icon green"><i class="fi fi-rr-download"></i></span>
                        <div class="help-card__content">
                            <span class="help-card__title">Download lambat</span>
                            <span class="help-card__text">File PDF dibuat dari isi laporan. Jika pertama kali dibuka terasa lebih lama, sistem akan memakai file yang sudah tersimpan pada proses berikutnya.</span>
                        </div>
                    </div>
                    <div class="help-card" data-help-item>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var scroller = document.querySelector('.page-content');
        var tabsWrap = document.getElementById('helpTabs');
        var indicator = document.getElementById('helpTabIndicator');
        var tabs = Array.prototype.slice.call(document.querySelectorAll('.help-tab'));
        var sections = Array.prototype.slice.call(document.querySelectorAll('.help-section[id]'));
        var currentId = null;

        function tabFor(id) { return tabs.filter(function (t) { return t.getAttribute('data-tab') === id; })[0]; }

        function moveIndicator(tab) {
            if (!indicator) return;
            if (!tab || tab.classList.contains('is-hidden')) { indicator.style.width = '0px'; return; }
            indicator.style.width = tab.offsetWidth + 'px';
            indicator.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
        }

        function setActive(id) {
            currentId = id;
            tabs.forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === id); });
            var tab = tabFor(id);
            moveIndicator(tab);
            if (tab && tabsWrap) {
                var left = tab.offsetLeft, right = left + tab.offsetWidth;
                if (left < tabsWrap.scrollLeft) tabsWrap.scrollTo({ left: left - 16, behavior: 'smooth' });
                else if (right > tabsWrap.scrollLeft + tabsWrap.clientWidth) tabsWrap.scrollTo({ left: right - tabsWrap.clientWidth + 16, behavior: 'smooth' });
            }
        }

        function jumpTo(id) {
            var target = document.getElementById(id);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Saat scroll dipicu klik tab, kunci scroll-spy sebentar agar tab aktif tidak "loncat".
        var spyLocked = false, spyTimer = null;
        function lockSpy() { spyLocked = true; clearTimeout(spyTimer); spyTimer = setTimeout(function () { spyLocked = false; }, 650); }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var id = tab.getAttribute('data-tab');
                setActive(id);   // langsung pindahkan tab aktif saat diklik
                lockSpy();
                jumpTo(id);
            });
        });

        var visible = {};
        if ('IntersectionObserver' in window && scroller) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) { visible[entry.target.id] = entry.isIntersecting; });
                if (spyLocked) return;
                // Di dasar halaman, section terakhir mungkin tak bisa naik penuh — paksa tab terakhir aktif.
                if (scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4) {
                    setActive(sections[sections.length - 1].id);
                    return;
                }
                for (var i = 0; i < sections.length; i++) {
                    if (visible[sections[i].id]) { if (sections[i].id !== currentId) setActive(sections[i].id); break; }
                }
            }, { root: scroller, rootMargin: '-96px 0px -55% 0px', threshold: 0 });
            sections.forEach(function (s) { observer.observe(s); });
        }

        if (sections.length) setActive(sections[0].id);
        window.addEventListener('resize', function () { moveIndicator(tabFor(currentId)); });

        // Pencarian (filter langsung)
        var input = document.getElementById('helpSearch');
        var clearBtn = document.getElementById('helpSearchClear');
        var emptyState = document.getElementById('helpEmpty');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-help-item]'));

        function applySearch(raw) {
            var q = (raw || '').trim().toLowerCase();
            var anyVisible = false;

            items.forEach(function (item) {
                var match = q === '' || (item.textContent || '').toLowerCase().indexOf(q) !== -1;
                item.classList.toggle('is-hidden', !match);
                if (match) anyVisible = true;
            });

            sections.forEach(function (sec) {
                var secItems = sec.querySelectorAll('[data-help-item]');
                if (!secItems.length) return;
                var hasVisible = Array.prototype.some.call(secItems, function (i) { return !i.classList.contains('is-hidden'); });
                var hide = q !== '' && !hasVisible;
                sec.classList.toggle('is-hidden', hide);
                var tab = tabFor(sec.id);
                if (tab) tab.classList.toggle('is-hidden', hide);
            });

            if (emptyState) emptyState.classList.toggle('is-hidden', !(q !== '' && !anyVisible));
            if (clearBtn) clearBtn.classList.toggle('is-hidden', q === '');
            moveIndicator(tabFor(currentId));
        }

        if (input) {
            input.addEventListener('input', function () { applySearch(input.value); });
            input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; applySearch(''); } });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () { input.value = ''; applySearch(''); input.focus(); });
        }
    });
</script>
@endpush
