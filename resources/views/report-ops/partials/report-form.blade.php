{{--
    Partial form laporan shift harian, dipakai bersama oleh halaman buat (create)
    dan ubah (edit). Wrapper masing-masing yang menyetel variabel berikut:
      $formAction          : URL submit form
      $formMethod          : 'PUT' bila menimpa baris yang sudah ada (create pun
                             begitu: drafnya sudah direservasi saat form dibuka)
      $isEdit              : true saat membuka laporan lama, false untuk form baru
      $headerTitle         : judul di header form
      $headerDocumentLabel : label "ID:" (mis. "#OPS-2026-007")
      $discardBlankUrl     : URL pembuang draft reservasi yang ditinggal kosong
                             (null saat mode ubah)
      $draftButtonLabel    : teks tombol simpan draft/pembaruan
--}}
@push('styles')
<style>
        /* Form Navigation Buttons */
        .btn-form {
            display: flex; width: 125px; padding: 12px 20px; justify-content: center;
            align-items: center; gap: 10px; border-radius: 10px; border: none;
            transition: .2s ease-out; font-size: 14px; color: var(--dark-secondary); font-weight: 500;
        }
        .btn-form.back { background-color: var(--orange-main); color: var(--button-color); border: 1px solid var(--black-10); gap: 0; }
        .btn-form.back:hover { background-color: var(--orange-hover); }
        .btn-form.back .icon {
            opacity: 0; max-width: 0; margin-right: 0; overflow: hidden;
            transition: max-width 0.3s ease, opacity 0.3s ease, margin-right 0.3s ease, transform 0.3s ease;
            transform: translateX(10px); display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-form.back:hover .icon { opacity: 1; max-width: 20px; margin-right: 10px; transform: translateX(0); }

        .btn-form.cancel { background-color: var(--white); border: 1px solid var(--black-10); }
        .btn-form.cancel:hover { background-color: var(--red-main-10); color: var(--red-hover); }
        .btn-form.cancel .icon {
            position: relative; top: 0px; display: inline-flex; justify-content: center; align-items: center;
            width: 18px; height: 18px; line-height: 0; transition: transform 0.3s ease; transform-origin: center;
        }
        .btn-form.cancel:hover .icon { transform: rotate(90deg); }
        .btn-form .icon { position: relative; top: 3px; display: inline-block; transition: transform 0.2s ease; }

        .btn-form.next { background-color: var(--blue-main); color: var(--button-color); gap: 0; }
        .btn-form.next .icon {
            opacity: 0; max-width: 0; margin-left: 0; overflow: hidden;
            transition: max-width 0.3s ease, opacity 0.3s ease, margin-left 0.3s ease, transform 0.3s ease;
            transform: translateX(-10px); display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-form.next:hover .icon { opacity: 1; max-width: 20px; margin-left: 10px; transform: translateX(0); }

        /* Submit / Finish Button Styling */
        .btn-form.finish { background-color: var(--success); color: var(--button-color); gap: 0; }
        .btn-form.finish .icon {
            opacity: 0; max-width: 0; margin-left: 0; overflow: hidden;
            transition: max-width 0.3s ease, opacity 0.3s ease, margin-left 0.3s ease, transform 0.3s ease;
            transform: translateX(-10px); display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-form.finish:hover { background-color: var(--success-hover); }
        .btn-form.finish:hover .icon { opacity: 1; max-width: 20px; margin-left: 10px; transform: translateX(0); }

        /* =========================================
           MAIN LAYOUT AND CARDS
           ========================================= */
        .content { max-width: 1800px; margin: 0 auto; width: 100%; }
        .main-content { background-color: var(--white); box-shadow: 0 2px 4px 0 var(--blue-main-10); }
        /* STICKY HEADER - NOTIFICATION STYLE (One UI) */
        .content-header {
            position: relative; /* Default Normal Document Flow */
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 2px 4px 0 var(--blue-main-10);
            border: 1px solid transparent;
        }

        body.dark-mode .content-header {
            background-color: #1E293B;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Mode Sticky: Liquid Glass Island Pop-down dari Atas */
        .content-header.is-sticky {
            position: fixed;
            top: 20px;
            left: 50%;
            max-width: 240px;
            padding: 6px 8px !important;
            background-color: rgba(255, 255, 255, 0.65) !important;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 100px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
            justify-content: center;
            z-index: 9999;

            /* Initial Hidden State (Berada diluar atas layar) */
            transform: translate(-50%, -150%) scale(0.9);
            opacity: 0;
            pointer-events: none;

            /* One UI Elastic Pop-down Transition */
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease-out;
        }

        body.dark-mode .content-header.is-sticky {
            background-color: rgba(15, 23, 42, 0.65) !important;
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
        }

        /* Tampilkan State Pop-down */
        .content-header.is-sticky.show-sticky {
            transform: translate(-50%, 0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .title-header { display: flex; flex-direction: column; gap: 2px; }
        .title-header .text-header { color: var(--dark-main); letter-spacing: -0.3px; }
        .content-header.is-sticky .title-header { display: none; }

        /* Button Simpan Draft Default */
        .content-header .btn-new {
            color: var(--button-color);
            padding: 12px 24px;
            margin: 0;
            background-color: var(--orange-main);
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .content-header .btn-new span.btn-text { font-size: 14px; font-weight: 500; }
        .content-header .icon-new { position: relative; top: 1px; font-size: 16px; transition: all 0.5s; }
        .content-header .icon-new i { position: relative; top: 2px; }
            /* Button Draft Sticky */
        .content-header.is-sticky .btn-new {
            border-radius: 100px;
            padding: 8px 18px;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            width: 100%;
            justify-content: center;
            gap: 6px;
        }
        .content-header.is-sticky .btn-new span.btn-text { font-size: 12px; }
        .content-header.is-sticky .btn-new .icon-new { font-size: 13px; }

        .content-header .btn-new:hover {
            background-color: var(--orange-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.35);
        }
        .content-header.is-sticky .btn-new:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4);
        }

        /* Custom Radio Buttons */
        .table-column.radio { display: flex; max-width: 250px; padding: 0 10px; align-items: center; gap: 10px; flex: 1 0 0; }
        .table-column.radio span { text-align: center; flex: 1 0 0; }

        .radio-group-custom { display: flex; gap: 8px; width: 100%; }
        .radio-custom { position: relative; flex: 1; display: flex; }
        .radio-custom input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

        .radio-custom label {
            display: flex; padding: 11px 15px; justify-content: center; align-items: center; gap: 10px; flex: 1 0 0;
            border: 1px solid var(--divider, #dee2e6); border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 500;
            color: var(--muted, #6c757d); background-color: var(--white, #ffffff); transition: all 0.2s ease-in-out; margin: 0;
        }
        .radio-custom label i { font-size: 12px; display: flex; align-items: center; }

        /* Style saat "Baik" dipilih (Hijau) */
        .radio-custom.baik input[type="radio"]:checked + label { border-color: var(--success, #198754); background-color: var(--success-10, #d1e7dd); color: var(--success, #198754); }
        /* Style saat "Rusak" dipilih (Merah) */
        .radio-custom.rusak input[type="radio"]:checked + label { border-color: var(--red-main, #dc3545); background-color: var(--red-main-10, #f8d7da); color: var(--red-main, #dc3545); }

        /* Catatan bantuan tiap langkah form: teks ringkas pembantu petugas
           (selaras gaya .form-meta-note pada modul Pemeliharaan/Safety). */
        .step-info-note {
            display: flex; align-items: flex-start; gap: 6px;
            width: 100%; align-self: stretch; margin-bottom: 4px;
            font-size: 11px; color: var(--muted); line-height: 1.5;
        }
        /* Posisi vertikal ikon (top) dikendalikan secara terpusat di
           officer-icon-alignment.blade.php, bukan di sini -- top di sini
           ditimpa !important oleh partial tersebut. */
        .step-info-note i { flex-shrink: 0; }
        .step-info-note strong { font-weight: 600; color: inherit; }

        /* =========================================
           RESPONSIVE DESIGN (BREAKPOINTS)
           ========================================= */

        /* TABLET (≤ 1024px) */
        @media (max-width: 1024px) {
            .p-content { padding: 0 40px !important; }
        }

        /* MOBILE (≤ 768px) */
        @media (max-width: 768px) {
            body { gap: 16px; }
            .p-content { padding: 0 16px !important; }
            .p-navbar { padding: 12px 16px !important; }
            .size-logo { width: 82px !important; }
            .header-right { gap: 10px !important; }

            /* Content header: stack title + button vertically */
            .content-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }
            .content-header .btn-new { width: 100% !important; justify-content: center !important; }

            /* Sticky island should stay compact — center override */
            .content-header.is-sticky {
                flex-direction: row !important;
                align-items: center !important;
                gap: 0 !important;
            }

            /* Form box padding */
            .header-form { padding: 14px 16px !important; flex-wrap: wrap !important; gap: 8px !important; }
            .content-form { padding: 16px !important; }

            /* Reduce form card min-widths so they wrap sooner */
            .form-card { min-width: 220px !important; }
            .timesheet-card { min-width: 280px !important; }

            /* Tab Bongkar: label "Bongkar / Muat Container" cukup panjang —
               perkecil font di kedua tab (bukan cuma satu) agar tetap sebaris. */
            .tab-group-bongkar .tab-sections { font-size: 11px !important; }

            .tab-sections { min-height: 34px !important; }

            .tab-sections .op7 { font-size: 10px !important; }

            /* Sub-tab header (Cek Unit / Karyawan): stack tabs above the action button */
            .inspection-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }
            .tab-group { max-width: 100% !important; }
            .set-all-good { width: 100% !important; justify-content: center !important; }

            /* Rentang Jam Kerja: pindah ke baris sendiri agar field terlihat penuh */
            .rentang-jam-group { flex: 1 0 100% !important; }
            .rentang-jam-wrapper .input-wrapper { min-width: 0; }

            /* Timesheet: tombol Tambah jadi full-width saat turun baris */
            .btn-add-activity { width: 100% !important; justify-content: center !important; }
            .timesheet-input input.activity-input { min-width: 120px !important; }

            /* Form nav buttons */
            .btn-form { width: 110px !important; padding: 10px 16px !important; font-size: 13px !important; }

            /* Modal */
            .pop-up.signed {
                width: calc(100vw - 40px) !important;
                max-width: 420px;
                padding: 18px !important;
            }
        }

        /* SMALL MOBILE (≤ 480px) */
        @media (max-width: 480px) {
            .p-content { padding: 0 12px !important; }
            .p-navbar { padding: 10px 12px !important; }
            .size-logo { width: 68px !important; }

            /* Hide greeting on tiny screens */
            .info-officer { display: none !important; }
            .header-left .divider-vertical { display: none !important; }

            /* Shrink page title */
            .fsize-20 { font-size: 16px !important; }

            /* Form tab: icon-only on very small screens */
            .list-form-tab span:not(.icon-tab) { display: none !important; }
            .list-form-tab {
                min-width: 40px !important;
                padding: 8px 10px !important;
                gap: 0 !important;
                flex: 1 0 auto !important;
            }

            /* Hide form counter chip */
            .counter-form { display: none !important; }

            /* Tighter form box padding */
            .header-form { padding: 10px 12px !important; }
            .content-form { padding: 12px !important; }

            /* Form cards go near full width */
            .form-card { min-width: 150px !important; }
            .timesheet-card { min-width: 240px !important; }

            /* Sub-tab segmented control: biarkan aturan icon-only (≤640px di
               report-ops.css) yang mengatur lebar; di sini cukup perkecil font. */
            .tab-sections { font-size: 11px !important; }

            /* Rentang jam tetap nyaman dilihat */
            .rentang-jam-wrapper { gap: 8px !important; }

            /* Form grid gap reduction */
            .form-grid { gap: 14px !important; }

            /* Box buttons: keep cancel + next on same row, shrink widths */
            .box-button { gap: 10px !important; }
            .btn-form { width: auto !important; flex: 1 !important; padding: 10px !important; font-size: 12px !important; }

            /* Modal tighter */
            .pop-up.signed {
                width: calc(100vw - 24px) !important;
                padding: 14px !important;
                border-radius: 16px !important;
            }
        }

        .activity-pane {
            width: 100%;
            gap: 15px;
        }

        .activity-pane.d-none {
            display: none !important;
        }

        .ship-operation-field {
            position: relative;
        }

        .ship-operation-suggestions {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            z-index: 35;
            display: none;
            max-height: 280px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid var(--blue-main-10);
            border-radius: 12px;
            background-color: var(--white);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
        }

        .ship-operation-suggestions.show {
            display: block;
        }

        .ship-operation-suggestion {
            width: 100%;
            border: none;
            border-radius: 9px;
            background: transparent;
            padding: 10px 12px;
            text-align: left;
            transition: .16s ease-out;
        }

        .ship-operation-suggestion:hover {
            background-color: var(--blue-main-10);
        }

        .ship-operation-suggestion-title {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: var(--dark-main);
            font-size: 12px;
            font-weight: 700;
        }

        .ship-operation-suggestion-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
            color: var(--muted);
            font-size: 10px;
        }

        .ship-operation-suggestion-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-weight: 600;
        }

        .ship-operation-suggestions-empty {
            padding: 12px;
            color: var(--muted);
            font-size: 11px;
            text-align: center;
        }

        /* Autocomplete kustom multi-nilai (nama petugas / nomor unit). */
        .kss-suggest-dropdown {
            position: fixed;
            z-index: 9999;
            display: none;
            max-height: 220px;
            overflow-y: auto;
            padding: 6px;
            border: 1px solid var(--blue-main-25);
            border-radius: 10px;
            background-color: var(--white);
            box-shadow: 0 16px 34px rgba(15, 23, 42, .16);
        }
        .kss-suggest-dropdown.show { display: block; }
        .kss-suggest-option {
            display: block;
            width: 100%;
            border: none;
            border-radius: 7px;
            background: transparent;
            padding: 8px 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 500;
            color: var(--dark-main);
            cursor: pointer;
            transition: .12s ease-out;
        }
        .kss-suggest-option:hover,
        .kss-suggest-option.active { background-color: var(--blue-main-10); color: var(--blue-main); }

        .container-capacity-group {
            grid-column: span 2;
        }
        .container-capacity-fields {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 10px;
        }
        .container-capacity-field {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 8px;
        }
        .container-capacity-field .capacity-label,
        .container-capacity-fields .capacity-separator {
            color: var(--dark-main);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .container-capacity-group {
                grid-column: span 1;
            }
            .container-capacity-fields {
                grid-template-columns: 1fr;
            }
            .container-capacity-fields .capacity-separator {
                display: none;
            }
        }

        .ship-operation-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            padding: 12px;
            border: 1px solid var(--blue-main-10);
            border-radius: 12px;
            background-color: var(--blue-main-5);
        }

        .ship-operation-status-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--dark-main);
            font-size: 12px;
            font-weight: 600;
        }

        .status-info-icon {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
            font-size: 9px;
            cursor: help;
        }

        .status-info-icon .status-info-tip {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 10px);
            width: max-content;
            max-width: min(240px, calc(100vw - 32px));
            padding: 9px 11px;
            border-radius: 10px;
            /* Frosted bersama — lihat components/frosted-surface.css. Teks kini
               memakai token tema karena latarnya ikut berbalik di dark mode. */
            border: 1px solid var(--kss-frost-border);
            background-color: var(--kss-frost-surface);
            -webkit-backdrop-filter: var(--kss-frost-filter);
            backdrop-filter: var(--kss-frost-filter);
            color: var(--dark-main);
            font-size: 11px;
            font-weight: 500;
            line-height: 1.45;
            text-align: left;
            white-space: normal;
            box-shadow:
                inset 0 1px 0 var(--kss-frost-edge),
                var(--kss-frost-shadow);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateX(-50%) translateY(4px);
            transition: opacity .16s ease-out, transform .16s ease-out;
            z-index: 20;
        }

        /* Panah dibuat dari kotak yang diputar, bukan segitiga border, supaya
           ikut membawa kaca beku dan hairline yang sama. */
        .status-info-icon .status-info-tip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            width: 10px;
            height: 10px;
            margin-top: -6px;
            border-right: 1px solid var(--kss-frost-border);
            border-bottom: 1px solid var(--kss-frost-border);
            border-radius: 0 0 3px 0;
            background-color: var(--kss-frost-surface);
            -webkit-backdrop-filter: var(--kss-frost-filter);
            backdrop-filter: var(--kss-frost-filter);
            transform: translateX(-50%) rotate(45deg);
        }

        .status-info-icon:hover .status-info-tip,
        .status-info-icon:focus-visible .status-info-tip {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        .ship-operation-status-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ship-operation-status-options label {
            margin: 0;
        }

        .ship-operation-status-options input {
            position: absolute;
            opacity: 0;
        }

        .ship-operation-status-options span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 8px 12px;
            border: 1px solid var(--black-10);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--dark-secondary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: .16s ease-out;
        }

        .ship-operation-status-options input:checked + span {
            border-color: var(--blue-main);
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .ship-operation-status-options input:focus-visible + span {
            outline: 3px solid var(--blue-main-10);
            outline-offset: 2px;
            border-color: var(--blue-main);
        }

        .ship-operation-status-options input[value="completed"]:checked + span {
            border-color: var(--success);
            background-color: var(--success);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 12px var(--success-40);
        }

        .ship-operation-handover {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--blue-main-25);
            border-radius: 12px;
            background-color: var(--blue-main-5);
            color: var(--dark-main);
        }

        .ship-operation-handover__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .ship-operation-handover__copy {
            display: flex;
            flex: 1 1 auto;
            min-width: 0;
            flex-direction: column;
            gap: 2px;
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .ship-operation-handover__copy strong { font-size: 12px; }

        .ship-operation-handover__state {
            flex: 0 0 auto;
            padding: 5px 9px;
            border-radius: 999px;
            background-color: var(--orange-main-10);
            color: var(--orange-main);
            font-size: 10px;
            font-weight: 700;
        }

        .ship-operation-handover__state[data-state="active"] {
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .ship-operation-handover__state[data-state="completed"] {
            background-color: var(--success-10);
            color: var(--success);
        }

        .ship-operation-handover__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            min-width: 0;
        }

        .ship-operation-handover .ship-operation-status {
            width: auto;
            padding: 0;
            border: 0;
            border-radius: 0;
            background-color: transparent;
        }

        .ship-operation-handover .ship-operation-status-label {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .ship-operation-handover .ship-operation-status-options {
            flex-wrap: nowrap;
        }

        .ship-operation-handover .ship-operation-status-options span {
            min-height: 30px;
            padding: 6px 10px;
            font-size: 10px;
            white-space: nowrap;
        }

        .ship-operation-review {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 12px;
            border: 1px solid var(--smooth-border);
            border-radius: 12px;
            background-color: var(--black-5);
        }

        .ship-operation-review__heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .ship-operation-review__heading > span:first-child {
            color: var(--dark-main);
            font-size: 12px;
            font-weight: 700;
        }

        .ship-operation-review__count {
            flex: 0 0 auto;
            color: var(--dark-secondary);
            font-size: 10px;
            font-weight: 600;
        }

        .ship-operation-review__list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: min(320px, 42vh);
            overflow-y: auto;
        }

        .ship-operation-review__item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid var(--smooth-border);
            border-radius: 10px;
            background-color: var(--white);
        }

        .ship-operation-review__identity {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 2px;
        }

        .ship-operation-review__identity strong {
            color: var(--dark-main);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .ship-operation-review__identity span {
            color: var(--dark-secondary);
            font-size: 10px;
        }

        .ship-operation-review__choices { display: flex; gap: 6px; }

        .ship-operation-review__choice {
            min-height: 32px;
            padding: 6px 9px;
            border: 1px solid var(--smooth-border);
            border-radius: 8px;
            background-color: var(--white);
            color: var(--dark-secondary);
            font-size: 10px;
            font-weight: 700;
        }

        .ship-operation-review__choice:hover,
        .ship-operation-review__choice:focus-visible {
            border-color: var(--blue-main);
            color: var(--blue-main);
            outline: none;
        }

        .ship-operation-review__choice.is-active[data-operation-status="active"] {
            border-color: var(--blue-main);
            background-color: var(--blue-main-10);
            color: var(--blue-main);
        }

        .ship-operation-review__choice.is-active[data-operation-status="completed"] {
            border-color: var(--success);
            background-color: var(--success-10);
            color: var(--success);
        }

        .ship-operation-review__alert {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            color: var(--red-main);
            font-size: 11px;
            line-height: 1.45;
        }

        .ship-operation-review__empty {
            color: var(--dark-secondary);
            font-size: 11px;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .ship-operation-handover {
                grid-template-columns: 32px minmax(0, 1fr);
                align-items: start;
            }

            .ship-operation-handover__actions {
                grid-column: 2;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        /* MOBILE: rapikan status pekerjaan kapal (muat pupuk & urea) dan
           baris input Laporan Harian pada muat urea agar tidak berdesakan. */
        @media (max-width: 480px) {
            .ship-operation-status {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                min-width: 0;
                max-width: 100%;
            }
            .ship-operation-status-label { min-width: 0; }
            .status-info-icon .status-info-tip {
                left: auto;
                right: -48px;
                width: min(200px, calc(100vw - 64px));
                transform: translateY(4px);
            }
            .status-info-icon:hover .status-info-tip,
            .status-info-icon:focus-visible .status-info-tip { transform: translateY(0); }
            .status-info-icon .status-info-tip::after { left: auto; right: 51px; transform: rotate(45deg); }
            .ship-operation-status-options { width: 100%; }
            .ship-operation-status-options label { flex: 1 1 0; min-width: 0; }
            .ship-operation-status-options span { width: 100%; }
            .ship-operation-handover {
                grid-template-columns: 28px minmax(0, 1fr);
                padding: 10px;
            }
            .ship-operation-handover__icon {
                width: 28px;
                height: 28px;
                flex-basis: 28px;
            }
            .ship-operation-handover__actions {
                grid-column: 1 / -1;
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
                padding-top: 2px;
            }
            .ship-operation-handover__state { align-self: flex-start; }
            .ship-operation-handover .ship-operation-status { width: 100%; }
            .ship-operation-handover .ship-operation-status-options {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }
            .ship-operation-handover .ship-operation-status-options label { min-width: 0; }
            .ship-operation-handover .ship-operation-status-options span { width: 100%; }
            .ship-operation-review__item { grid-template-columns: 1fr; }
            .ship-operation-review__choices { display: grid; grid-template-columns: 1fr 1fr; }
            .ship-operation-review__choice { width: 100%; }

            /* Aktivitas tetap sebaris dengan input jam, mengisi sisa ruang. */
            .cob-line .activity-input { flex: 1 1 0 !important; min-width: 0 !important; }
            .cob-line .cob-wrapper { width: 100%; justify-content: space-between; }
            .cob-line .cob-wrapper input { flex: 1 1 auto; width: auto; text-align: left; }
        }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mainReportForm');
    const statusInput = document.getElementById('reportStatus');
    const payloadInput = document.getElementById('formPayload');
    const saveDraftButton = document.getElementById('btnSaveDraft');
    const today = '{{ \Carbon\Carbon::now('Asia/Makassar')->toDateString() }}';
    const currentWitaHour = @json((int) \Carbon\Carbon::now('Asia/Makassar')->format('G'));
    const currentUserGroup = @json(strtoupper((string) (auth()->user()->group ?? '')));
    const isEditMode = @json(isset($report));

    const masterEmployeesGrouped = @json($employeesGrouped ?? []);
    const masterVehicles = @json($vehicles ?? []);
    const masterInventories = @json($inventories ?? []);
    const masterShelters = @json($environments ?? []);
    const masterTrucks = @json($trucks ?? []);
    const lastUnitHandoverConditions = @json($lastUnitHandoverConditions ?? []);
    const lastOp7Rosters = @json($lastOp7Rosters ?? []);
    const savedFormPayload = @json(old('form_payload') ? json_decode(old('form_payload'), true) : (isset($report) ? $report->payload : null));
    const currentReportId = @json(isset($report) ? $report->id : null);
    const carryForwardOperations = @json($carryForwardOperations ?? []);
    const shipOperationSuggestUrl = @json(route('report-ops.ship-operations.suggestions'));
    let shipOperationSearchTimer = null;
    let shipOperationSearchController = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function unique(values) {
        return [...new Set(values.filter(Boolean).map(value => String(value).trim()).filter(Boolean))];
    }

    function reportNumericValue(value) {
        if (typeof window.parseReportNumber === 'function') {
            return window.parseReportNumber(value, 'locale') ?? 0;
        }

        const text = String(value ?? '').trim();
        if (text === '') return 0;

        const normalized = text.includes(',')
            ? text.replace(/\./g, '').replace(',', '.')
            : text;

        return Number(normalized) || 0;
    }

    function reportLocalizedNumber(value) {
        if (value === '' || value === null || value === undefined) return '';
        if (typeof window.formatReportNumber === 'function') {
            return window.formatReportNumber(value, false, 'flexible');
        }

        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 6 }).format(Number(value) || 0);
    }

    function createDatalist(id, values) {
        let datalist = document.getElementById(id);
        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = id;
            document.body.appendChild(datalist);
        }

        datalist.innerHTML = unique(values)
            .map(value => `<option value="${escapeHtml(value)}"></option>`)
            .join('');
    }

    function flattenEmployeeNames() {
        return Object.values(masterEmployeesGrouped || {})
            .flatMap(group => Array.isArray(group) ? group : Object.values(group || {}))
            .map(employee => employee && employee.name);
    }

    function normalizeGroupName(value) {
        return String(value || '')
            .toUpperCase()
            .replace(/^OP\.?7\s+GROUP\s+/, '')
            .replace(/^GROUP\s+/, '')
            .replace(/^GRUP\s+/, '')
            .trim();
    }

    function normalizeExactGroupName(value) {
        return String(value || '')
            .toUpperCase()
            .replace(/\s+/g, ' ')
            .replace(/^OP7\s+/, 'OP.7 ')
            .trim();
    }

    function employeesFromGroups(predicate) {
        // Satu karyawan bisa tercatat di dua kelompok sekaligus — mis. personil
        // Relief/Bengkel yang punya penugasan regu shift, atau data lama yang
        // regunya tersimpan 'A' sementara yang baru 'Group A'. Keduanya lolos
        // predikat yang sama, jadi hasilnya disaring agar tampil sekali saja.
        const seen = new Set();

        return Object.entries(masterEmployeesGrouped || {})
            .filter(([groupName]) => predicate(groupName))
            .flatMap(([, employees]) => Array.isArray(employees) ? employees : Object.values(employees || {}))
            .filter(employee => {
                if (!employee || !employee.name) return false;

                const key = employee.id ?? String(employee.name).trim().toLowerCase();
                if (seen.has(key)) return false;

                seen.add(key);
                return true;
            });
    }

    function employeesForGroup(groupValue) {
        const normalized = normalizeGroupName(groupValue);

        return employeesFromGroups(groupName => {
            const exactGroupName = normalizeExactGroupName(groupName);

            if (exactGroupName.startsWith('OP.7 GROUP')) {
                return false;
            }

            return normalizeGroupName(groupName) === normalized;
        });
    }

    // ----- Memori susunan karyawan OP.7 -----
    // Susunan (urutan baris, no. forklift, area kerja) yang terakhir disimpan
    // petugas untuk regu ini, dipakai sebagai titik awal form baru. Master data
    // tetap jadi cadangan bila regu tersebut belum pernah punya laporan.
    // Hanya OP.7 yang diingat — daftar karyawan shift selalu mulai dari master.
    // Sengaja tidak dipakai saat mengedit laporan lama supaya isi laporan itu
    // sendiri tidak pernah tertimpa.
    function rememberedOp7Roster(groupValue) {
        if (isEditMode) return [];

        const normalized = normalizeGroupName(groupValue);
        if (!normalized) return [];

        const roster = (lastOp7Rosters || {})[normalized];

        return Array.isArray(roster) ? roster.filter(entry => entry && entry.name) : [];
    }

    function employeesForOp7Group(groupValue) {
        const normalized = normalizeGroupName(groupValue);
        if (!normalized) return [];

        return employeesFromGroups(groupName => normalizeExactGroupName(groupName) === `OP.7 GROUP ${normalized}`);
    }

    // ----- Sugesti berbasis jabatan (Checker untuk tally, Operator FL/OP.7
    // untuk operator forklift, Driver untuk field driver), opsional difilter
    // berdasarkan group yang dipilih. -----
    const ROLE_POSITIONS = {
        checker: ['checker'],
        forkliftOperator: ['operator fl', 'operator op.7'],
        driver: ['driver'],
    };

    function allOperationalEmployees() {
        return employeesFromGroups(() => true);
    }

    // Karyawan Relief: hanya personil dari group Relief 1 / Relief 2.
    function reliefEmployees() {
        return employeesFromGroups(groupName => normalizeExactGroupName(groupName).startsWith('RELIEF'));
    }

    // ----- Saran nama per tabel karyawan -----
    // Tiap tabel punya kandidat yang berbeda, jadi daftarnya tidak lagi dipukul
    // rata "seluruh karyawan". Datalist sifatnya saran, bukan pembatas: nama di
    // luar daftar tetap boleh diketik petugas.
    function employeeNames(...lists) {
        return unique(lists.flat().map(employee => employee && employee.name));
    }

    // Regu shift & OP.7 dipakai apa adanya; kalau group belum dipilih (atau
    // regu itu memang kosong di master) daftar penuh yang dipakai agar petugas
    // tidak kehilangan saran sama sekali.
    function groupNamesOrAll(employees) {
        return employees.length ? employeeNames(employees) : employeeNames(allOperationalEmployees());
    }

    function employeesByPosition(positionKeys, groupValue) {
        const wanted = positionKeys.map(p => p.toLowerCase());
        const matches = list => list.filter(e => e && e.position && wanted.includes(String(e.position).trim().toLowerCase()));

        if (groupValue) {
            const grouped = employeesForGroup(groupValue).concat(employeesForOp7Group(groupValue));
            const filtered = matches(grouped);
            // Jika group terpilih tidak punya karyawan jabatan tsb, jangan kosong:
            // tampilkan semua kandidat dari seluruh group.
            if (filtered.length) return filtered;
        }

        return matches(allOperationalEmployees());
    }

    function rebuildRoleDatalists(groupValue = null) {
        const group = groupValue || document.querySelector('[name="group_name"]')?.value || '';
        createDatalist('master-checker-list', employeesByPosition(ROLE_POSITIONS.checker, group).map(e => e.name));
        createDatalist('master-forklift-operator-list', employeesByPosition(ROLE_POSITIONS.forkliftOperator, group).map(e => e.name));
        createDatalist('master-driver-list', employeesByPosition(ROLE_POSITIONS.driver, group).map(e => e.name));
    }

    /**
     * Daftar saran tabel Karyawan, ikut berubah saat group diganti.
     *
     * Shift & OP.7 dipersempit ke anggota regu yang bersangkutan — itulah yang
     * sah mengisi kedua tabel tsb. Pengganti, Lembur, dan Kegiatan Lain tetap
     * memuat semua karyawan operasional karena pelakunya bisa dari mana saja,
     * hanya urutannya yang didahulukan: Relief untuk pengganti, anggota regu
     * sendiri untuk lembur/kegiatan lain.
     */
    function rebuildEmployeeDatalists(groupValue = null) {
        const group = groupValue || document.querySelector('[name="group_name"]')?.value || '';
        const shift = employeesForGroup(group);
        const op7 = employeesForOp7Group(group);
        const all = allOperationalEmployees();

        createDatalist('master-shift-list', groupNamesOrAll(shift));
        createDatalist('master-op7-list', groupNamesOrAll(op7));
        createDatalist('master-replacement-list', employeeNames(reliefEmployees(), shift, op7, all));
        createDatalist('master-group-employee-list', employeeNames(shift, op7, all));
    }

    function syncCustomSelectLabel(select) {
        if (!select) return;

        const selectedOption = select.options[select.selectedIndex];
        const trigger = select.nextElementSibling;

        if (trigger && trigger.classList.contains('custom-input')) {
            const label = trigger.querySelector('span') || trigger;
            label.textContent = selectedOption ? selectedOption.text : '';
            trigger.classList.toggle('text-placeholder', !selectedOption || selectedOption.disabled || selectedOption.value === '');
        }

        const optionsContainer = trigger?.nextElementSibling;
        if (optionsContainer && optionsContainer.classList.contains('custom-options-container')) {
            optionsContainer.querySelectorAll('.custom-option').forEach(option => {
                option.classList.toggle('selected', option.dataset.value === select.value);
            });
        }
    }

    function setSelectValue(select, value) {
        if (!select || value === null || value === undefined || value === '') return false;

        const option = Array.from(select.options).find(item => item.value === value);
        if (!option) return false;

        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncCustomSelectLabel(select);

        return true;
    }

    // Jam Kerja adalah input teks bebas (bukan select): nilainya tetap diisi
    // otomatis mengikuti Shift, tapi petugas boleh menimpanya dengan jam
    // custom bila kehadiran karyawan memang berbeda dari jam kerja standar.
    function setTimeRangeValue(input, value) {
        if (!input || !value || input.value === value) return false;

        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    }

    // Mask Jam Kerja (#jam-kerja): template "__:__ - __:__" selalu utuh —
    // ":" dan " - " tampil permanen di posisi tetapnya, termasuk saat kosong,
    // dan tidak pernah bisa terhapus oleh petugas. Digit yang belum diisi
    // ditampilkan sebagai "_"; mengetik menimpa slot digit di posisi kursor
    // (gaya input tanggal/kadaluarsa kartu), Backspace/Delete hanya
    // mengosongkan slot digit dan melompati simbol pemisah.
    function initJamKerjaMask() {
        const input = document.getElementById('jam-kerja');
        if (!input) return;

        const BLANK = '_';
        const TEMPLATE = ['_', '_', ':', '_', '_', ' ', '-', ' ', '_', '_', ':', '_', '_'];
        // Indeks karakter pada TEMPLATE yang menampung tiap slot digit ke-0..7
        // (jam-mulai puluhan/satuan, menit-mulai puluhan/satuan, jam-akhir
        // puluhan/satuan, menit-akhir puluhan/satuan).
        const DIGIT_POS = [0, 1, 3, 4, 8, 9, 11, 12];

        function slotsFromValue(value) {
            const text = String(value || '');
            return DIGIT_POS.map(pos => {
                const ch = text[pos];
                return ch && /\d/.test(ch) ? ch : null;
            });
        }

        function slotIndexForCaret(caret) {
            return DIGIT_POS.filter(pos => pos < caret).length;
        }

        function caretForSlotIndex(index) {
            return index < DIGIT_POS.length ? DIGIT_POS[index] : TEMPLATE.length;
        }

        function render(slots) {
            const next = slots.slice();

            // Bungkus jam >23 hanya bila kedua digit jamnya sudah terisi.
            [[0, 1], [4, 5]].forEach(([tens, ones]) => {
                if (next[tens] !== null && next[ones] !== null) {
                    const wrapped = wrapTimeHourDigits(next[tens] + next[ones]);
                    next[tens] = wrapped[0];
                    next[ones] = wrapped[1];
                }
            });

            const out = TEMPLATE.slice();
            DIGIT_POS.forEach((pos, i) => { out[pos] = next[i] ?? BLANK; });

            return out.join('');
        }

        function apply(slots, caretSlotIndex) {
            const previousValue = input.value;
            input.value = render(slots);
            const pos = caretForSlotIndex(caretSlotIndex);
            input.setSelectionRange(pos, pos);
            input.setCustomValidity(input.value.includes(BLANK) ? 'Jam kerja belum lengkap.' : '');
            input.dispatchEvent(new Event('input', { bubbles: true }));

            // Karena nilainya diisi lewat skrip (bukan pengetikan native), browser
            // tidak lagi otomatis memicu "change" saat field ini di-blur. Dipicu
            // manual di sini supaya Masuk/Pulang karyawan tetap ikut ter-update
            // begitu Jam Kerja lengkap — tak perlu menunggu pindah fokus dulu.
            if (input.value !== previousValue && !input.value.includes(BLANK)) {
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function snapCaretToSlot() {
            const pos = input.selectionStart ?? 0;
            const snapped = caretForSlotIndex(slotIndexForCaret(pos));
            if (pos !== snapped) input.setSelectionRange(snapped, snapped);
        }

        input.addEventListener('beforeinput', event => {
            const slots = slotsFromValue(input.value);
            const start = input.selectionStart ?? 0;
            const end = input.selectionEnd ?? 0;
            const fromSlot = slotIndexForCaret(start);
            const toSlot = slotIndexForCaret(end);

            if (['insertText', 'insertFromPaste', 'insertFromDrop', 'insertReplacementText'].includes(event.inputType)) {
                event.preventDefault();

                const incoming = String(
                    event.data ?? event.dataTransfer?.getData('text') ?? ''
                ).replace(/\D/g, '');
                if (!incoming) return;

                const next = slots.slice();
                for (let i = fromSlot; i < toSlot && i < next.length; i++) next[i] = null;

                let cursor = fromSlot;
                for (const digit of incoming) {
                    if (cursor >= next.length) break;
                    next[cursor] = digit;
                    cursor++;
                }

                apply(next, cursor);
                return;
            }

            if (event.inputType === 'deleteContentBackward') {
                event.preventDefault();
                const next = slots.slice();

                if (start !== end) {
                    for (let i = fromSlot; i < toSlot && i < next.length; i++) next[i] = null;
                    apply(next, fromSlot);
                } else if (fromSlot > 0) {
                    next[fromSlot - 1] = null;
                    apply(next, fromSlot - 1);
                }
                return;
            }

            if (event.inputType === 'deleteContentForward') {
                event.preventDefault();
                const next = slots.slice();

                if (start !== end) {
                    for (let i = fromSlot; i < toSlot && i < next.length; i++) next[i] = null;
                    apply(next, fromSlot);
                } else if (fromSlot < next.length) {
                    next[fromSlot] = null;
                    apply(next, fromSlot);
                }
            }
        });

        input.addEventListener('click', () => setTimeout(snapCaretToSlot, 0));
        input.addEventListener('focus', () => setTimeout(snapCaretToSlot, 0));

        // Jaring pengaman: field yang diisi lewat skrip tidak selalu memicu
        // "change" bawaan browser saat blur, jadi dipicu manual di sini juga.
        input.addEventListener('blur', () => input.dispatchEvent(new Event('change', { bubbles: true })));

        // Render awal: pastikan template "__:__ - __:__" tampil sejak muat
        // halaman, baik saat kosong maupun saat sudah ada nilai dari server.
        apply(slotsFromValue(input.value), DIGIT_POS.length);
    }

    function shipOperationConfig(input) {
        const name = input?.name || '';
        let match = name.match(/^ship_name_(\d+)$/);

        if (match) {
            return {
                type: 'muat_kantong',
                sequence: Number(match[1]),
                idName: `ship_operation_id_${match[1]}`,
                statusName: `ship_operation_status_${match[1]}`,
                fields: {
                    ship_name: `ship_name_${match[1]}`,
                    agent: `agent_${match[1]}`,
                    jetty: `jetty_${match[1]}`,
                    destination: `destination_${match[1]}`,
                    capacity: `capacity_${match[1]}`,
                    wo_number: `wo_number_${match[1]}`,
                    cargo_type: `cargo_type_${match[1]}`,
                    marking: `marking_${match[1]}`,
                    arrival_time: `arrival_time_${match[1]}`,
                    qty_delivery_prev: `qty_delivery_prev_${match[1]}`,
                    qty_loading_prev: `qty_loading_prev_${match[1]}`,
                    qty_damage_prev: `qty_damage_prev_${match[1]}`,
                },
            };
        }

        match = name.match(/^ship_name_urea_(\d+)$/);

        if (match) {
            return {
                type: 'muat_curah',
                sequence: Number(match[1]),
                idName: `ship_operation_urea_id_${match[1]}`,
                statusName: `ship_operation_urea_status_${match[1]}`,
                fields: {
                    ship_name: `ship_name_urea_${match[1]}`,
                    agent: `agent_urea_${match[1]}`,
                    jetty: `jetty_urea_${match[1]}`,
                    destination: `destination_urea_${match[1]}`,
                    capacity: `capacity_urea_${match[1]}`,
                    stevedoring: `stevedoring_urea_${match[1]}`,
                    commodity: `commodity_urea_${match[1]}`,
                    berthing_time: `berthing_time_urea_${match[1]}`,
                    start_loading_time: `start_loading_time_urea_${match[1]}`,
                },
            };
        }

        // Bongkar bahan baku dan container tidak mencatat waktu sandar maupun
        // jenis muatan, jadi field yang ikut terisi dari saran hanya keterangan
        // kapal yang memang ada di formnya.
        match = name.match(/^ship_name_material_(\d+)$/);

        if (match) {
            return {
                type: 'bongkar_bahan_baku',
                sequence: Number(match[1]),
                idName: `ship_operation_material_id_${match[1]}`,
                statusName: `ship_operation_material_status_${match[1]}`,
                fields: {
                    ship_name: `ship_name_material_${match[1]}`,
                    agent: `agent_material_${match[1]}`,
                    jetty: `jetty_material_${match[1]}`,
                    capacity: `capacity_material_${match[1]}`,
                },
            };
        }

        match = name.match(/^ship_name_container_(\d+)$/);

        if (match) {
            return {
                type: 'container',
                sequence: Number(match[1]),
                idName: `ship_operation_container_id_${match[1]}`,
                statusName: `ship_operation_container_status_${match[1]}`,
                fields: {
                    ship_name: `ship_name_container_${match[1]}`,
                    agent: `agent_container_${match[1]}`,
                    jetty: `jetty_container_${match[1]}`,
                    capacity: `capacity_container_${match[1]}`,
                },
            };
        }

        match = name.match(/^ship_name_ammonia_(\d+)$/);

        if (match) {
            return {
                type: 'muat_amoniak',
                sequence: Number(match[1]),
                idName: `ship_operation_ammonia_id_${match[1]}`,
                statusName: `ship_operation_ammonia_status_${match[1]}`,
                fields: {
                    ship_name: `ship_name_ammonia_${match[1]}`,
                    agent: `agent_ammonia_${match[1]}`,
                    jetty: `jetty_ammonia_${match[1]}`,
                    destination: `destination_ammonia_${match[1]}`,
                    capacity: `capacity_ammonia_${match[1]}`,
                    stevedoring: `stevedoring_ammonia_${match[1]}`,
                    commodity: `commodity_ammonia_${match[1]}`,
                    berthing_time: `berthing_time_ammonia_${match[1]}`,
                    start_loading_time: `start_loading_time_ammonia_${match[1]}`,
                },
            };
        }

        return null;
    }

    function namedControl(root, name) {
        return Array.from((root || document).querySelectorAll('[name]')).find(control => control.name === name) || null;
    }

    function setNamedControlValue(root, name, value) {
        const control = namedControl(root, name);
        if (!control || value === null || value === undefined) return;

        setControlValue(control, String(value));
    }

    function setShipOperationStatus(root, config, status) {
        const radios = Array.from((root || document).querySelectorAll(`[name="${config.statusName}"]`));
        radios.forEach(input => { input.checked = false; });

        const radio = radios.find(input => input.value === status);
        if (radio) setControlValue(radio, status);
    }

    function operationDropdownFor(input) {
        const wrapper = input.closest('.form-group');
        if (!wrapper) return null;

        wrapper.classList.add('ship-operation-field');
        input.setAttribute('autocomplete', 'off');

        let dropdown = wrapper.querySelector('.ship-operation-suggestions');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'ship-operation-suggestions';
            wrapper.appendChild(dropdown);
        }

        bindShipOperationDropdownEvents(wrapper, dropdown);

        return dropdown;
    }

    function closeShipOperationDropdowns() {
        document.querySelectorAll('.ship-operation-suggestions.show').forEach(dropdown => {
            dropdown.classList.remove('show');
            dropdown.innerHTML = '';
        });
    }

    function closeShipOperationDropdown(dropdown) {
        dropdown?.classList.remove('show');
        if (dropdown) dropdown.innerHTML = '';
    }

    function pointInsideRect(x, y, rect, padding = 0) {
        return x >= rect.left - padding
            && x <= rect.right + padding
            && y >= rect.top - padding
            && y <= rect.bottom + padding;
    }

    function pointInsideShipOperationArea(wrapper, x, y) {
        const input = wrapper?.querySelector('input[name^="ship_name_"]');
        const dropdown = wrapper?.querySelector('.ship-operation-suggestions');
        const rects = [input, dropdown]
            .filter(element => element && element.getClientRects().length > 0)
            .map(element => element.getBoundingClientRect());

        if (rects.length === 0) return false;

        const safeRect = {
            left: Math.min(...rects.map(rect => rect.left)),
            right: Math.max(...rects.map(rect => rect.right)),
            top: Math.min(...rects.map(rect => rect.top)),
            bottom: Math.max(...rects.map(rect => rect.bottom)),
        };

        return pointInsideRect(x, y, safeRect, 10);
    }

    let shipOperationPointerTimer = null;
    function scheduleShipOperationAreaCheck(wrapper, event) {
        if (!wrapper || !event) return;

        if (shipOperationPointerTimer) window.clearTimeout(shipOperationPointerTimer);

        const pointer = { x: event.clientX, y: event.clientY };
        shipOperationPointerTimer = window.setTimeout(() => {
            const dropdown = wrapper.querySelector('.ship-operation-suggestions.show');
            if (dropdown && !pointInsideShipOperationArea(wrapper, pointer.x, pointer.y)) {
                closeShipOperationDropdown(dropdown);
            }
        }, 90);
    }

    function bindShipOperationDropdownEvents(wrapper, dropdown) {
        if (!wrapper || !dropdown || wrapper.dataset.shipOperationDropdownBound === 'true') return;

        wrapper.dataset.shipOperationDropdownBound = 'true';
        wrapper.addEventListener('mouseleave', event => scheduleShipOperationAreaCheck(wrapper, event));
        dropdown.addEventListener('mouseleave', event => scheduleShipOperationAreaCheck(wrapper, event));
        wrapper.addEventListener('mouseenter', () => {
            if (shipOperationPointerTimer) window.clearTimeout(shipOperationPointerTimer);
        });
        dropdown.addEventListener('mouseenter', () => {
            if (shipOperationPointerTimer) window.clearTimeout(shipOperationPointerTimer);
        });
    }

    function handleShipOperationPointerMove(event) {
        const openDropdowns = Array.from(document.querySelectorAll('.ship-operation-suggestions.show'));
        if (openDropdowns.length === 0) return;

        if (shipOperationPointerTimer) window.clearTimeout(shipOperationPointerTimer);

        const pointer = { x: event.clientX, y: event.clientY };
        shipOperationPointerTimer = window.setTimeout(() => {
            openDropdowns.forEach(dropdown => {
                const wrapper = dropdown.closest('.ship-operation-field');
                if (!pointInsideShipOperationArea(wrapper, pointer.x, pointer.y)) {
                    closeShipOperationDropdown(dropdown);
                }
            });
        }, 90);
    }

    function renderShipOperationSuggestions(input, items) {
        const dropdown = operationDropdownFor(input);
        if (!dropdown) return;

        if (!Array.isArray(items) || items.length === 0) {
            dropdown.innerHTML = '';
            dropdown.classList.remove('show');
            return;
        }

        dropdown.innerHTML = items.map(item => {
            const meta = [
                item.agent,
                item.jetty,
                item.destination,
                item.wo_number || item.commodity,
            ].filter(Boolean);

            return `
                <button type="button" class="ship-operation-suggestion" data-operation-id="${escapeHtml(item.id)}">
                    <span class="ship-operation-suggestion-title">
                        <span>${escapeHtml(item.ship_name)}</span>
                        <span class="ship-operation-suggestion-chip">${escapeHtml(item.status_label || 'Aktif')}</span>
                    </span>
                    <span class="ship-operation-suggestion-meta">
                        ${meta.map(value => `<span>${escapeHtml(value)}</span>`).join('')}
                        <span>Update ${escapeHtml(item.updated_diff || '-')}</span>
                    </span>
                </button>
            `;
        }).join('');

        items.forEach(item => {
            const button = dropdown.querySelector(`[data-operation-id="${item.id}"]`);
            button?.addEventListener('mousedown', event => event.preventDefault());
            button?.addEventListener('click', () => applyShipOperation(input, item));
        });

        dropdown.classList.add('show');
    }

    function fetchShipOperationSuggestions(input) {
        const config = shipOperationConfig(input);
        if (!config || !shipOperationSuggestUrl) return;

        if (shipOperationSearchTimer) window.clearTimeout(shipOperationSearchTimer);

        shipOperationSearchTimer = window.setTimeout(async () => {
            if (shipOperationSearchController) shipOperationSearchController.abort();
            shipOperationSearchController = new AbortController();

            try {
                const url = new URL(shipOperationSuggestUrl, window.location.origin);
                url.searchParams.set('type', config.type);
                url.searchParams.set('q', input.value.trim());
                if (currentReportId) url.searchParams.set('exclude_report_id', currentReportId);

                const response = await fetch(url.toString(), {
                    signal: shipOperationSearchController.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('request failed');

                const payload = await response.json();
                renderShipOperationSuggestions(input, payload.items || []);
            } catch (error) {
                if (error.name !== 'AbortError') renderShipOperationSuggestions(input, []);
            }
        }, 500);
    }

    function applyShipOperation(input, item) {
        const config = shipOperationConfig(input);
        const pane = input.closest('.activity-pane') || document;
        if (!config || !pane) return;

        input.dataset.applyingOperation = 'true';

        setNamedControlValue(pane, config.idName, item.id);
        Object.entries(config.fields).forEach(([key, name]) => {
            if (key.startsWith('qty_')) {
                setNamedControlValue(pane, name, item.accumulation?.[key] ?? 0);
                return;
            }

            setNamedControlValue(pane, name, item[key] ?? '');
        });

        // Bongkar bahan baku memakai satu kapal lintas shift dengan jenis bahan
        // dan kemasan yang sama, jadi rinciannya ikut diteruskan — bukan hanya
        // keterangan kapalnya.
        if (config.type === 'bongkar_bahan_baku') {
            applyMaterialCarryForward(pane, item.accumulation?.materials || []);
        }

        // Memilih kapal hanya menentukan identitas pelayarannya. Keputusan
        // berjalan/selesai harus dibuat ulang oleh petugas pada akhir laporan.
        setShipOperationStatus(pane, config, '');

        pane.querySelectorAll('[name*="qty_current"], [name*="qty_prev"], [name*="_current_"], [name*="_prev_"]').forEach(updateAccumulation);
        input.dataset.applyingOperation = 'false';
        closeShipOperationDropdowns();
        syncPayload();
    }

    function clearShipOperationSelection(input) {
        if (input.dataset.applyingOperation === 'true') return;

        const config = shipOperationConfig(input);
        const pane = input.closest('.activity-pane') || document;
        if (!config || !pane) return;

        setNamedControlValue(pane, config.idName, '');

        ['qty_delivery_prev', 'qty_loading_prev', 'qty_damage_prev'].forEach(key => {
            if (config.fields[key]) setNamedControlValue(pane, config.fields[key], 0);
        });

        pane.querySelectorAll('[name*="qty_current"], [name*="qty_prev"], [name*="_current_"], [name*="_prev_"]').forEach(updateAccumulation);
    }

    function prepareShipOperationFields(root = document) {
        root.querySelectorAll('input[name^="ship_name_"]').forEach(input => {
            if (shipOperationConfig(input)) operationDropdownFor(input);
        });
    }

    // Field nama operator forklift (kapal & gudang) - sarankan karyawan
    // berjabatan Operator FL / Operator OP.7 sesuai group, BUKAN nomor unit forklift.
    const FORKLIFT_OPERATOR_FIELDS = /^(operator_ship_\d+|operator_warehouse_\d+|opr_forklift(_\d+)?|turba_forklift_operator)$/i;

    // Tiap tabel log karyawan punya daftar sarannya sendiri (lihat
    // rebuildEmployeeDatalists). Yang dicocokkan selalu kolom "nama karyawan"
    // (subfield [name]) saja — kolom lain seperti [description]/[work_area]/
    // [time_in] pada baris yang sama tidak boleh ikut ketiban daftar karyawan,
    // mis. keterangan OP.7 yang punya datalist sendiri.

    function applyMasterDatalists(root = document) {
        root.querySelectorAll('input[type="text"], input:not([type])').forEach(input => {
            const name = input.getAttribute('name') || '';

            // Jam Kerja (rentang waktu bebas) & Kegiatan Lain: input manual murni,
            // tidak perlu (dan tidak boleh) mendapat saran datalist apa pun.
            if (/_work_(start|end)$/i.test(name) || /\[work_time\]$/i.test(name) || /other_activity_logs\[[^\]]+\]\[description\]$/i.test(name)) {
                input.removeAttribute('list');
                return;
            }

            if (/tally/i.test(name)) {
                // Tally = Checker: hanya sarankan karyawan berjabatan Checker.
                input.setAttribute('list', 'master-checker-list');
            } else if (FORKLIFT_OPERATOR_FIELDS.test(name)) {
                // Operator Forklift: sarankan karyawan berjabatan Operator FL / Operator OP.7.
                input.setAttribute('list', 'master-forklift-operator-list');
            } else if (/driver/i.test(name)) {
                // Driver: hanya sarankan karyawan berjabatan Driver.
                input.setAttribute('list', 'master-driver-list');
            } else if (/relief_logs/i.test(name)) {
                // Karyawan Relief: hanya sarankan personil group Relief 1 / Relief 2.
                input.setAttribute('list', 'master-relief-list');
            } else if (/employee_shift_logs\[[^\]]+\]\[name\]$/i.test(name)) {
                // Karyawan Shift: hanya anggota regu yang dipilih (termasuk
                // personil Relief/Bengkel yang punya penugasan ke regu itu).
                input.setAttribute('list', 'master-shift-list');
            } else if (/op7_logs\[[^\]]+\]\[name\]$/i.test(name)) {
                // Karyawan OP.7: hanya anggota OP.7 regu yang dipilih.
                input.setAttribute('list', 'master-op7-list');
            } else if (/replacement_logs\[[^\]]+\]\[name\]$/i.test(name)) {
                // Pengganti: Relief lebih dulu, lalu anggota regu, lalu sisanya.
                input.setAttribute('list', 'master-replacement-list');
            } else if (/(overtime_logs|other_activity_logs)\[[^\]]+\]\[name\]$/i.test(name)) {
                // Lembur & Kegiatan Lain: anggota regu sendiri lebih dulu.
                input.setAttribute('list', 'master-group-employee-list');
            } else if (/(operator|foreman|stevedoring|petugas)/i.test(name)) {
                input.setAttribute('list', 'master-employee-list');
            }

            if (/truck_number/i.test(name) || /^turba_trl_no$/i.test(name) || /^truck_petugas_(bb|cont)_\d+$/i.test(name)) {
                // Nomor truck / Nomor Trailer (No Truck Bongkar & tracking): sarankan
                // unit berkode TRL (Trailer) / TRT (Tronton). Override daftar karyawan
                // yang mungkin terpasang karena nama field mengandung "petugas".
                input.setAttribute('list', 'master-trucknum-list');
            } else if (/truck_name/i.test(name)) {
                input.setAttribute('list', 'master-truck-list');
            }

            if (FORKLIFT_OPERATOR_FIELDS.test(name)) {
                // Sudah ditangani di atas (daftar nama operator) - jangan ditimpa jadi daftar nomor unit.
            } else if ((/forklift/i.test(name) && !/operator/i.test(name)) || /^turba_fl_no$/i.test(name)) {
                // Nomor forklift (termasuk Nomor Forklift tracking pupuk kantong): hanya sarankan unit berkode FL.
                // Field "operator forklift" dikecualikan karena berisi nama orang.
                input.setAttribute('list', 'master-forklift-list');
            } else if (/unit_logs\[[^\]]+\]\[item_name\]/i.test(name)) {
                // Cek unit umum: tetap tampilkan seluruh unit.
                input.setAttribute('list', 'master-unit-list');
            }

            if (/inventory_logs\[[^\]]+\]\[item_name\]/i.test(name)) {
                input.setAttribute('list', 'master-inventory-list');
            }

            // Field petugas (nama tally/operator/driver) & nomor unit (FL/TRL) pada
            // Bongkar/Turba/Muat boleh diisi lebih dari satu (dipisah koma) dgn saran
            // autocomplete per-nama. Kecuali baris log Karyawan (OP.7/pengganti/shift/
            // relief/lembur/kegiatan lain) yang tetap satu nilai + datalist bawaan.
            const assignedList = input.getAttribute('list');
            const isLogArrayField = /(op7_logs|replacement_logs|employee_shift_logs|other_activity_logs|relief_logs|overtime_logs)\[/i.test(name);
            if (MULTI_SUGGEST_LISTS.includes(assignedList) && ! isLogArrayField) {
                input.setAttribute('data-suggest', assignedList);
                input.setAttribute('data-multi', 'true');
                input.setAttribute('autocomplete', 'off');
                input.removeAttribute('list');
            }
        });
    }

    // Datalist yang boleh dipakai sebagai autocomplete multi-nilai (koma).
    const MULTI_SUGGEST_LISTS = [
        'master-checker-list',
        'master-forklift-operator-list',
        'master-driver-list',
        'master-forklift-list',
        'master-trucknum-list',
    ];

    // ===== Autocomplete kustom multi-nilai (dipisah koma) =====
    // Field ditandai data-suggest="<id datalist master>" & data-multi="true".
    // Saran difilter berdasarkan potongan teks setelah koma terakhir, sehingga
    // tetap muncul untuk nama ke-2, ke-3, dst. Sumber opsi dibaca live dari
    // elemen <datalist> master (ikut ter-update saat group berubah).
    const SUGGEST_DROPDOWN_ID = 'kss-suggest-dropdown';
    let suggestActiveInput = null;
    let suggestActiveIndex = -1;

    function suggestOptionsFrom(listId) {
        const datalist = document.getElementById(listId);
        if (!datalist) return [];
        return Array.from(datalist.querySelectorAll('option')).map(option => option.value).filter(Boolean);
    }

    function suggestTokenBounds(input) {
        const value = input.value;
        const caret = input.selectionStart ?? value.length;
        const start = value.lastIndexOf(',', caret - 1) + 1;
        let end = value.indexOf(',', caret);
        if (end === -1) end = value.length;
        return { start, end };
    }

    function suggestCurrentToken(input) {
        if (input.dataset.multi !== 'true') return input.value.trim();
        const { start, end } = suggestTokenBounds(input);
        return input.value.slice(start, end).trim();
    }

    function ensureSuggestDropdown() {
        let dropdown = document.getElementById(SUGGEST_DROPDOWN_ID);
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = SUGGEST_DROPDOWN_ID;
            dropdown.className = 'kss-suggest-dropdown';
            document.body.appendChild(dropdown);
        }
        return dropdown;
    }

    function positionSuggestDropdown(input, dropdown) {
        const rect = input.getBoundingClientRect();
        dropdown.style.left = `${rect.left}px`;
        dropdown.style.top = `${rect.bottom + 4}px`;
        dropdown.style.width = `${Math.max(rect.width, 140)}px`;
    }

    function closeSuggestDropdown() {
        const dropdown = document.getElementById(SUGGEST_DROPDOWN_ID);
        if (dropdown) dropdown.classList.remove('show');
        suggestActiveInput = null;
        suggestActiveIndex = -1;
    }

    function openSuggestFor(input) {
        const listId = input.dataset.suggest;
        if (!listId) return;

        const isMulti = input.dataset.multi === 'true';
        const options = suggestOptionsFrom(listId);
        const token = suggestCurrentToken(input);
        const query = token.toLowerCase();
        const tokenIsExactOption = isMulti
            && query !== ''
            && options.some(option => option.toLowerCase() === query);

        if (tokenIsExactOption) {
            closeSuggestDropdown();
            return;
        }

        const chosen = isMulti
            ? input.value.split(',').map(part => part.trim().toLowerCase()).filter(Boolean)
            : [];

        let matches = options.filter(option => {
            const low = option.toLowerCase();
            if (isMulti && low !== query && chosen.includes(low)) return false;
            return query === '' ? true : low.includes(query);
        }).slice(0, 12);

        const dropdown = ensureSuggestDropdown();
        if (matches.length === 0) {
            dropdown.classList.remove('show');
            suggestActiveInput = null;
            suggestActiveIndex = -1;
            return;
        }

        dropdown.innerHTML = matches
            .map((match, index) => `<button type="button" class="kss-suggest-option${index === 0 ? ' active' : ''}" data-value="${escapeHtml(match)}">${escapeHtml(match)}</button>`)
            .join('');
        suggestActiveInput = input;
        suggestActiveIndex = 0;
        positionSuggestDropdown(input, dropdown);
        dropdown.classList.add('show');
    }

    function applySuggestValue(input, value) {
        if (input.dataset.multi !== 'true') {
            input.value = value;
        } else {
            const { start, end } = suggestTokenBounds(input);
            const before = input.value.slice(0, start).replace(/\s*$/, '');
            const after = input.value.slice(end);
            const connector = before === '' ? '' : (before.endsWith(',') ? ' ' : ', ');
            input.value = `${before}${connector}${value}${after}`.replace(/,\s*$/, '');
            const caret = `${before}${connector}${value}`.length;
            try { input.setSelectionRange(caret, caret); } catch (e) {}
        }
        input.dataset.suggestApplying = 'true';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        delete input.dataset.suggestApplying;
        input.focus();
        closeSuggestDropdown();
    }

    function highlightSuggest(delta) {
        const dropdown = document.getElementById(SUGGEST_DROPDOWN_ID);
        if (!dropdown || !dropdown.classList.contains('show')) return;
        const options = Array.from(dropdown.querySelectorAll('.kss-suggest-option'));
        if (options.length === 0) return;
        suggestActiveIndex = (suggestActiveIndex + delta + options.length) % options.length;
        options.forEach((option, index) => option.classList.toggle('active', index === suggestActiveIndex));
        options[suggestActiveIndex].scrollIntoView({ block: 'nearest' });
    }

    function clearTemplateValues() {
        const demoValues = new Set(['Trailer KSS-01', 'Sabarudin', 'Nurul Huda']);
        document.querySelectorAll('input[type="text"], input:not([type])').forEach(input => {
            if (demoValues.has(input.value)) {
                input.value = '';
            }
        });

        document.querySelectorAll('.timeline-section .timeline-item').forEach(item => item.remove());
    }

    function setTodayDate() {
        const dateInput = document.querySelector('[name="report_date"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = today;
        }
    }

    window.__reportSyncPayload = syncPayload; // dipakai autosave draft (partials.report-autosave)
    function syncPayload() {
        if (!form || !payloadInput) return;

        const fields = [];
        const data = new FormData(form);
        data.forEach((value, key) => {
            if (['_token', 'form_payload'].includes(key)) return;
            fields.push({ key, value });
        });

        payloadInput.value = JSON.stringify({ fields });
    }

    function payloadFields() {
        const fields = savedFormPayload?.fields;

        return Array.isArray(fields)
            ? fields.filter(field => field && (field.name || field.key))
            : [];
    }

    function controlsByName(name) {
        if (!form || !name) return [];

        return Array.from(form.querySelectorAll('[name]')).filter(control => control.name === name);
    }

    function fieldName(field) {
        return String(field.name || field.key || '');
    }

    function fieldValue(field) {
        return field.value === null || field.value === undefined ? '' : String(field.value);
    }

    function maxSequenceForSection(fields, sectionId) {
        const kantongPrefixes = [
            'ship_name', 'agent', 'jetty', 'destination', 'capacity', 'wo_number', 'cargo_type', 'marking',
            'arrival_time', 'operating_gang', 'tkbm_count', 'foreman', 'qty_delivery_current',
            'qty_delivery_prev', 'qty_loading_current', 'qty_loading_prev', 'qty_damage_current', 'qty_damage_prev',
            'tally_warehouse', 'driver_name', 'truck_number', 'tally_ship', 'operator_ship',
            'forklift_ship', 'operator_warehouse', 'forklift_warehouse',
            'ship_operation_id', 'ship_operation_status',
        ];
        const curahPrefixes = [
            'ship_name_urea', 'jetty_urea', 'destination_urea', 'agent_urea', 'stevedoring_urea',
            'commodity_urea', 'capacity_urea', 'berthing_time_urea', 'start_loading_time_urea',
            'cob_received_urea', 'cob_delivered_urea', 'loading_qty_urea',
            'ship_operation_urea_id', 'ship_operation_urea_status',
        ];
        const ammoniaPrefixes = [
            'ship_name_ammonia', 'jetty_ammonia', 'destination_ammonia', 'agent_ammonia',
            'stevedoring_ammonia', 'commodity_ammonia', 'capacity_ammonia',
            'berthing_time_ammonia', 'start_loading_time_ammonia',
            'cob_received_ammonia', 'cob_delivered_ammonia', 'loading_qty_ammonia',
            'ship_operation_ammonia_id', 'ship_operation_ammonia_status',
        ];
        const materialPrefixes = [
            'ship_name_material', 'agent_material', 'jetty_material', 'capacity_material',
            'tally_kapal', 'opr_forklift', 'no_forklift_bb', 'tally_pengiriman', 'driver_petugas_bb', 'truck_petugas_bb',
            'material_work_start', 'material_work_end',
        ];
        const containerPrefixes = [
            'ship_name_container', 'agent_container', 'jetty_container', 'capacity_container', 'capacity_full_container',
            'tally_muat', 'tally_gudang', 'driver_petugas_cont', 'truck_petugas_cont',
        ];

        return fields.reduce((max, field) => {
            const name = fieldName(field);
            let match = null;

            if (sectionId === 'step-muat-kantong') {
                match = name.match(new RegExp(`^(${kantongPrefixes.join('|')})_(\\d+)$`)) || name.match(/^timesheets\[(\d+)]/);
            }

            if (sectionId === 'step-muat-curah') {
                match = name.match(new RegExp(`^(${curahPrefixes.join('|')})_(\\d+)$`)) || name.match(/^bulk_logs\[(\d+)]/);
            }

            if (sectionId === 'step-muat-amoniak') {
                match = name.match(new RegExp(`^(${ammoniaPrefixes.join('|')})_(\\d+)$`)) || name.match(/^ammonia_logs\[(\d+)]/);
            }

            if (sectionId === 'section-bahan-baku') {
                match = name.match(new RegExp(`^(${materialPrefixes.join('|')})_(\\d+)$`)) || name.match(/^unloading_materials_(\d+)\[/);
            }

            if (sectionId === 'section-container') {
                match = name.match(new RegExp(`^(${containerPrefixes.join('|')})_(\\d+)$`)) || name.match(/^unloading_containers_(\d+)\[/);
            }

            return match ? Math.max(max, Number(match[2] || match[1] || 1)) : max;
        }, 1);
    }

    function ensureActivityPanes(fields) {
        ['step-muat-kantong', 'step-muat-curah', 'step-muat-amoniak', 'section-bahan-baku', 'section-container'].forEach(sectionId => {
            const section = document.getElementById(sectionId);
            const targetCount = maxSequenceForSection(fields, sectionId);
            const addButton = section?.querySelector('.plus-minus-tab .btn.add');

            if (!section || !addButton || targetCount <= 1) return;

            while (section.querySelectorAll('.activity-pane').length < targetCount) {
                addButton.click();
            }
        });
    }

    function ensureTableRowsForName(name) {
        if (/^(timesheets|bulk_logs|ammonia_logs)\[/.test(name) || /^unloading_materials_/.test(name)) return;

        const match = name.match(/^([^\[]+)\[(\d+)]/);
        if (!match || controlsByName(name).length > 0) return;

        const [,, indexValue] = match;
        const targetIndex = Number(indexValue);
        const prefix = `${match[1]}[`;
        const seedControl = Array.from(form.querySelectorAll('[name]')).find(control => control.name.startsWith(prefix));
        const tableInput = seedControl?.closest('.table-input');
        const addButton = tableInput?.querySelector('.btn-tambah-baris');

        if (!tableInput || !addButton) return;

        while (rowsOf(tableInput).length <= targetIndex) {
            addTableRow(addButton);
        }
    }

    function materialPackageGroups(root) {
        return Array.from(root?.querySelectorAll('[data-material-package-group]') || []);
    }

    // Ledger = wadah seluruh kelompok kemasan pada satu kegiatan. Dipakai
    // sebagai titik pijak karena jumlah kelompoknya sekarang bisa berubah.
    function materialPackageLedgerOf(node) {
        if (!node) return null;
        if (node.matches?.('[data-material-package-ledger]')) return node;

        return node.closest?.('[data-material-package-ledger]')
            || node.querySelector?.('[data-material-package-ledger]')
            || null;
    }

    // Penomoran ulang butuh pane karena nomor kegiatan ada di sana. Sebelum
    // pane terbentuk (saat form pertama disusun), ledger dipakai sebagai
    // gantinya dan nomor kegiatannya jatuh ke 1.
    function materialPackageScopeOf(node) {
        return node?.closest?.('.activity-pane') || materialPackageLedgerOf(node);
    }

    const MATERIAL_PACKAGE_CUSTOM_CODE = 'custom';

    function isMaterialPackageNewOption(option) {
        return option?.hasAttribute('data-material-package-new') === true;
    }

    function materialPackageOptionEntry(option) {
        return {
            value: option.value,
            // Kemasan katalog memakai kodenya sebagai value; kemasan tambahan
            // memakai value unik supaya beberapa kemasan buatan petugas dapat
            // dibedakan walau kodenya sama-sama "custom".
            code: option.dataset.packageCode || option.value,
            family: option.dataset.packageFamily || '',
            label: option.dataset.packageLabel || option.text,
            factor: Number(option.dataset.packageFactor || 0) || 0,
            hint: option.dataset.packageHint || '',
        };
    }

    function materialPackageFamilyOf(group) {
        if (!group) return '';

        const selected = group.querySelector('[data-material-package-select]')?.selectedOptions?.[0];

        return group.dataset.materialPackageFamily
            || selected?.dataset.packageFamily
            || '';
    }

    function materialPackageOptionMatchesFamily(group, option) {
        if (isMaterialPackageNewOption(option)) return true;

        const groupFamily = materialPackageFamilyOf(group);
        const optionFamily = option?.dataset.packageFamily || '';

        return ! groupFamily || ! optionFamily || groupFamily === optionFamily;
    }

    function materialPackageOptionsOf(ledger) {
        const select = ledger?.querySelector('[data-material-package-select]');

        return Array.from(select?.options || [])
            .filter(option => ! isMaterialPackageNewOption(option))
            .map(materialPackageOptionEntry);
    }

    // Pemicu hasil penggandaan kelompok membawa tampilan lamanya tetapi tidak
    // membawa event listener-nya. Penanda ini yang membedakan pemicu yang
    // benar-benar hidup dari salinan mati, karena atribut ikut tersalin.
    const materialPackageLiveTriggers = new WeakSet();

    function closeMaterialPackageDropdowns() {
        document.querySelectorAll('.custom-options-container.open').forEach(list => list.classList.remove('open'));
        document.querySelectorAll('.custom-input.focus-active').forEach(trigger => trigger.classList.remove('focus-active'));
        document.querySelectorAll('.material-package-group.is-dropdown-open').forEach(group => group.classList.remove('is-dropdown-open'));
    }

    /**
     * Dropdown kemasan memakai anatomi kontrol kustom yang sama dengan isian
     * lain pada form ini, tetapi disusun ulang di sini karena daftarnya hidup:
     * kemasan tambahan bisa muncul kapan saja dan kemasan yang sudah dipakai
     * kelompok lain harus tampil non-aktif.
     */
    function renderMaterialPackageDropdown(group) {
        const select = group?.querySelector('[data-material-package-select]');
        const field = select?.closest('.input-wrapper');
        if (!select || !field) return;

        select.style.display = 'none';

        let trigger = field.querySelector(':scope > .custom-input[role="button"]');
        let list = field.querySelector(':scope > .custom-options-container');

        if (trigger && ! materialPackageLiveTriggers.has(trigger)) {
            trigger.remove();
            trigger = null;
        }

        if (!trigger) {
            trigger = document.createElement('div');
            trigger.className = 'custom-input d-flex align-items-center';
            trigger.tabIndex = 0;
            trigger.setAttribute('role', 'button');
            trigger.appendChild(document.createElement('span'));
            field.insertBefore(trigger, select.nextSibling);

            trigger.addEventListener('click', event => {
                // Penutup global menutup seluruh dropdown pada setiap klik;
                // klik pada pemicunya sendiri dikecualikan.
                event.stopPropagation();

                const shouldOpen = ! list.classList.contains('open');
                closeMaterialPackageDropdowns();
                list.classList.toggle('open', shouldOpen);
                trigger.classList.toggle('focus-active', shouldOpen);
                group.classList.toggle('is-dropdown-open', shouldOpen);
            });

            trigger.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeMaterialPackageDropdowns();

                    return;
                }

                if (! ['Enter', ' '].includes(event.key)) return;

                event.preventDefault();
                event.stopPropagation();
                trigger.click();
            });

            materialPackageLiveTriggers.add(trigger);
        }

        if (!list) {
            list = document.createElement('div');
            list.className = 'custom-options-container';
            field.appendChild(list);
        }

        const selected = select.options[select.selectedIndex] || null;
        trigger.querySelector('span').textContent = selected ? selected.text : '';
        trigger.setAttribute('aria-label', `Jenis kemasan: ${selected ? selected.text : 'belum dipilih'}`);

        list.textContent = '';
        Array.from(select.options).forEach(option => {
            const matchesFamily = materialPackageOptionMatchesFamily(group, option);
            option.hidden = ! matchesFamily;
            if (! matchesFamily) return;

            const item = document.createElement('div');
            item.className = 'custom-option';
            item.textContent = option.text;
            item.dataset.value = option.value;
            item.classList.toggle('selected', option.selected);
            // Kemasan yang sudah dipakai kelompok lain tetap terlihat agar
            // petugas tahu kemasannya ada, tetapi tidak dapat dipilih dua kali.
            item.classList.toggle('is-disabled', option.disabled);
            if (isMaterialPackageNewOption(option)) item.classList.add('is-new');

            item.addEventListener('click', event => {
                event.stopPropagation();
                closeMaterialPackageDropdowns();
                if (option.disabled) return;

                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            list.appendChild(item);
        });
    }

    function reindexMaterialPackageTables(pane) {
        if (!pane) return;

        const sequence = Number(pane.dataset?.sequence || 1);
        let globalIndex = 0;

        materialPackageGroups(pane).forEach(group => {
            const tableInput = group.querySelector('.table-input.material');
            rowsOf(tableInput).forEach((row, localIndex) => {
                row.querySelectorAll('.table-column.no span').forEach(span => {
                    span.textContent = localIndex + 1;
                });
                row.querySelectorAll('[name^="unloading_materials_"]').forEach(input => {
                    input.name = input.name.replace(
                        /unloading_materials_\d+\[\d+]/,
                        `unloading_materials_${sequence}[${globalIndex}]`,
                    );
                });
                globalIndex += 1;
            });
        });
    }

    function updateMaterialPackageSubtotal(group) {
        if (!group) return;

        // Faktor berasal dari atribut konfigurasi dengan notasi desimal baku
        // (0.05), bukan angka lokal yang diketik petugas. Number() mencegah
        // titik dibaca sebagai pemisah ribuan oleh parser angka Indonesia.
        const tonnageFactor = Number(group.dataset.materialTonnageFactor || 0) || 0;
        const rows = rowsOf(group.querySelector('.table-input.material'));
        let current = 0;
        let previous = 0;
        let total = 0;

        rows.forEach(row => {
            const rowValues = {
                current: reportNumericValue(row.querySelector('[name$="[qty_current]"]')?.value),
                previous: reportNumericValue(row.querySelector('[name$="[qty_prev]"]')?.value),
                total: reportNumericValue(row.querySelector('[name$="[qty_total]"]')?.value),
            };

            current += rowValues.current;
            previous += rowValues.previous;
            total += rowValues.total;
        });

        const values = { current, previous, total };
        Object.entries(values).forEach(([key, value]) => {
            const target = group.querySelector(`[data-material-subtotal="${key}"]`);
            const tonnageTarget = group.querySelector(`[data-material-subtotal-tonnage="${key}"]`);

            if (target) {
                target.textContent = value ? reportLocalizedNumber(value) : '0';
                window.fitReportNumberDisplay?.(target);
            }
            if (tonnageTarget) {
                tonnageTarget.textContent = value ? reportLocalizedNumber(value * tonnageFactor) : '0';
                window.fitReportNumberDisplay?.(tonnageTarget);
            }
        });

        // Ringkasan kepala kelompok — satu-satunya angka yang terlihat ketika
        // kelompoknya ditutup, jadi harus ikut diperbarui di sini.
        const rowCount = group.querySelector('[data-material-package-rowcount]');
        if (rowCount) rowCount.textContent = String(rows.length);

        const summaryBag = group.querySelector('[data-material-summary-bag]');
        const summaryTonnage = group.querySelector('[data-material-summary-tonnage]');
        if (summaryBag) summaryBag.textContent = current ? reportLocalizedNumber(current) : '0';
        if (summaryTonnage) summaryTonnage.textContent = current ? reportLocalizedNumber(current * tonnageFactor) : '0';
    }

    /**
     * Buka/tutup rincian satu kelompok kemasan. Tiap kelompok berdiri sendiri,
     * seperti akordeon lokasi pada Inspeksi K3.
     */
    function setMaterialPackageCollapsed(group, collapsed) {
        if (!group) return;

        group.classList.toggle('is-collapsed', collapsed);

        const toggle = group.querySelector('[data-material-package-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-label', collapsed ? 'Tampilkan rincian kemasan' : 'Sembunyikan rincian kemasan');
        }

        // Daftar kemasan yang sedang terbuka ikut ditutup supaya tidak
        // menggantung di atas kelompok yang barusan diciutkan.
        if (collapsed) closeMaterialPackageDropdowns();
    }

    function toggleMaterialPackageGroup(group) {
        setMaterialPackageCollapsed(group, !group?.classList.contains('is-collapsed'));
    }

    /**
     * Menyalin kemasan yang sedang dipilih ke seluruh baris kelompok. Kode
     * kemasan inilah yang dibaca server; labelnya ikut dikirim hanya sebagai
     * teks pencarian, dan faktor konversi tidak pernah dikirim sama sekali.
     */
    function syncMaterialPackageGroup(group) {
        if (!group) return;

        const select = group.querySelector('[data-material-package-select]');
        const option = select?.options[select.selectedIndex] || null;

        // Pilihan "Tambah Kemasan Baru" bukan kemasan; kelompoknya menunggu
        // isian pop-up dan tidak boleh menimpa kemasan yang sedang dipakai.
        if (isMaterialPackageNewOption(option)) return;

        const entry = option ? materialPackageOptionEntry(option) : null;
        const code = entry?.code || group.dataset.materialPackageCode || '';
        const family = entry?.family || group.dataset.materialPackageFamily || '';
        const label = entry?.label || group.dataset.materialPackageType || '';
        const factor = entry ? entry.factor : Number(group.dataset.materialTonnageFactor || 0) || 0;

        if (!code && !label) return;

        group.dataset.materialPackageValue = entry?.value || code;
        group.dataset.materialPackageFamily = family;
        group.dataset.materialPackageCode = code;
        group.dataset.materialPackageType = label;
        group.dataset.materialTonnageFactor = String(factor);

        group.querySelectorAll('[name$="[packaging_code]"]').forEach(input => {
            input.value = code;
        });
        group.querySelectorAll('[name$="[packaging_type]"]').forEach(input => {
            input.value = label;
        });
        // Faktor hanya dikirim untuk kemasan tambahan. Kemasan katalog memakai
        // faktor milik server, jadi kolomnya sengaja dikosongkan.
        group.querySelectorAll('[name$="[packaging_factor]"]').forEach(input => {
            input.value = code === MATERIAL_PACKAGE_CUSTOM_CODE ? String(factor) : '';
        });
        group.querySelectorAll('[data-material-package-title]').forEach(element => {
            element.textContent = label;
        });

        const hint = group.querySelector('[data-material-package-hint]');
        if (hint && entry?.hint) hint.textContent = entry.hint;

        renderMaterialPackageDropdown(group);
        updateMaterialPackageSubtotal(group);
    }

    /**
     * Satu kemasan hanya boleh dipakai satu kelompok dalam satu kegiatan.
     * Dua kelompok berkemasan sama membuat subtotalnya terbaca ganda di
     * laporan, jadi pilihan yang sudah terpakai dimatikan di kelompok lain.
     */
    function refreshMaterialPackageOptions(node) {
        const ledger = materialPackageLedgerOf(node);
        if (!ledger) return;

        const groups = materialPackageGroups(ledger);
        const used = groups.map(group => group.dataset.materialPackageValue || group.dataset.materialPackageCode || '');

        groups.forEach((group, index) => {
            const select = group.querySelector('[data-material-package-select]');
            Array.from(select?.options || []).forEach(option => {
                if (isMaterialPackageNewOption(option)) return;

                option.disabled = option.value !== used[index] && used.includes(option.value);
            });
        });

        groups.forEach(group => renderMaterialPackageDropdown(group));

        ledger.toggleAttribute('data-material-package-single', groups.length <= 1);

        const addButton = ledger.querySelector('[data-material-package-add]');
        if (addButton) addButton.hidden = groups.length >= materialPackageOptionsOf(ledger).length;
    }

    function addMaterialPackageGroup(node, preferredValue = null) {
        const ledger = materialPackageLedgerOf(node);
        const groups = materialPackageGroups(ledger);
        const source = groups[groups.length - 1];
        if (!ledger || !source) return null;

        const used = groups.map(group => group.dataset.materialPackageValue || group.dataset.materialPackageCode || '');
        const available = materialPackageOptionsOf(ledger)
            .map(option => option.value)
            .filter(value => !used.includes(value));

        const nextCode = available.includes(preferredValue) ? preferredValue : available[0];
        if (!nextCode) return null;

        const clone = source.cloneNode(true);
        const tableInput = clone.querySelector('.table-input.material');
        rowsOf(tableInput).slice(1).forEach(row => row.remove());
        rowsOf(tableInput).forEach(row => clearRow(row));
        resetTableSelectHydration(clone);

        const select = clone.querySelector('[data-material-package-select]');
        if (select) select.value = nextCode;

        // Kelompok yang baru ditambah selalu terbuka, walau kelompok sumbernya
        // sedang diciutkan.
        setMaterialPackageCollapsed(clone, false);

        ledger.insertBefore(clone, ledger.querySelector('[data-material-package-add]'));
        syncMaterialPackageGroup(clone);
        reindexMaterialPackageTables(materialPackageScopeOf(ledger));
        refreshMaterialPackageOptions(ledger);
        applyMasterDatalists(clone);
        hydrateTableSelects(clone);
        initPickers(clone);

        return clone;
    }

    function removeMaterialPackageGroup(group) {
        const ledger = materialPackageLedgerOf(group);
        if (!ledger || materialPackageGroups(ledger).length <= 1) return;

        const scope = materialPackageScopeOf(group);
        group.remove();
        reindexMaterialPackageTables(scope === group ? ledger : scope);
        refreshMaterialPackageOptions(ledger);
    }

    function initializeMaterialPackageGroups(root = document) {
        root.querySelectorAll('[data-material-package-group]').forEach(group => {
            syncMaterialPackageGroup(group);
            setMaterialPackageCollapsed(group, group.classList.contains('is-collapsed'));
        });
        root.querySelectorAll('[data-material-package-ledger]').forEach(ledger => {
            refreshMaterialPackageOptions(ledger);
        });
    }

    // ==========================================
    // KEMASAN TAMBAHAN (didaftarkan petugas)
    // ==========================================

    let materialPackageCustomSequence = 0;
    let materialPackageTargetGroup = null;

    function materialPackageModalElement() {
        return document.getElementById('materialPackageModal');
    }

    function materialPackageNumberText(value) {
        return (Math.round(value * 100) / 100).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    /** Keterangan konversi dibaca dari arah yang paling wajar untuk kemasannya. */
    function materialPackageHintText(factor) {
        return factor >= 1
            ? `1 Bag = ${materialPackageNumberText(factor)} Ton`
            : `${materialPackageNumberText(1 / factor)} Bag = 1 Ton`;
    }

    /** Daftarkan kemasan baru ke keluarga dropdown tempat petugas membuatnya. */
    function registerCustomMaterialPackage(label, factor, family = '') {
        const value = `${MATERIAL_PACKAGE_CUSTOM_CODE}:${++materialPackageCustomSequence}`;
        const hint = materialPackageHintText(factor);
        const packageFamily = family || (factor >= 1 ? 'jumbo' : 'bag');

        document.querySelectorAll('[data-material-package-select]').forEach(select => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = `Kemasan ${label}`;
            option.dataset.packageCode = MATERIAL_PACKAGE_CUSTOM_CODE;
            option.dataset.packageFamily = packageFamily;
            option.dataset.packageLabel = label;
            option.dataset.packageFactor = String(factor);
            option.dataset.packageHint = hint;
            select.insertBefore(option, select.querySelector('[data-material-package-new]'));
        });

        // Seluruh kegiatan menerima opsi barunya, tetapi render dropdown hanya
        // menampilkannya pada keluarga Jumbo atau Bag yang sesuai.
        document.querySelectorAll('[data-material-package-group]').forEach(group => {
            renderMaterialPackageDropdown(group);
        });

        return value;
    }

    function applyMaterialPackageValue(group, value) {
        const select = group?.querySelector('[data-material-package-select]');
        if (!select || !value) return;

        select.value = value;
        syncMaterialPackageGroup(group);
        refreshMaterialPackageOptions(group);
        syncPayload();
    }

    function setMaterialPackageError(message) {
        const box = materialPackageModalElement()?.querySelector('[data-material-package-error]');
        if (!box) return;

        box.textContent = message || '';
        box.classList.toggle('d-none', !message);
    }

    function materialPackageRatioInputs() {
        return {
            name: document.getElementById('materialPackageName'),
            bags: document.getElementById('materialPackageBags'),
            tons: document.getElementById('materialPackageTons'),
        };
    }

    function updateMaterialPackagePreview() {
        const preview = materialPackageModalElement()?.querySelector('[data-material-package-preview-value]');
        const { bags, tons } = materialPackageRatioInputs();
        if (!preview) return;

        const bagCount = Number(bags?.value);
        const tonCount = Number(tons?.value);

        preview.textContent = bagCount > 0 && tonCount > 0
            ? `${materialPackageNumberText(tonCount / bagCount)} Ton`
            : '—';
    }

    function openMaterialPackageModal(group) {
        const modal = materialPackageModalElement();
        const { name, bags, tons } = materialPackageRatioInputs();
        if (!modal || !group) return;

        materialPackageTargetGroup = group;
        if (name) name.value = '';
        if (bags) bags.value = '1';
        if (tons) tons.value = '1';
        setMaterialPackageError('');
        updateMaterialPackagePreview();
        modal.classList.add('show');
        setTimeout(() => name?.focus(), 60);
    }

    /**
     * Menutup pop-up. Selama kemasannya belum tersimpan, pilihan dropdown
     * dikembalikan ke kemasan sebelumnya supaya kelompok tidak tertinggal
     * dalam keadaan "Tambah Kemasan Baru".
     */
    function closeMaterialPackageModal({ restore = true } = {}) {
        const modal = materialPackageModalElement();
        const group = materialPackageTargetGroup;
        materialPackageTargetGroup = null;
        modal?.classList.remove('show');

        if (!restore || !group) return;

        const select = group.querySelector('[data-material-package-select]');
        if (!select) return;

        select.value = group.dataset.materialPackageValue || group.dataset.materialPackageCode || '';
        select.focus();
    }

    /**
     * Kendali pop-up diikat langsung ke tombolnya, bukan lewat delegasi di
     * document: skrip modal bersama menghentikan propagasi setiap klik di
     * dalam kartu pop-up, sehingga klik tidak pernah sampai ke document.
     */
    function initMaterialPackageModal() {
        const modal = materialPackageModalElement();
        if (!modal || modal.dataset.materialPackageReady === 'true') return;

        modal.dataset.materialPackageReady = 'true';

        modal.querySelector('[data-material-package-save]')?.addEventListener('click', event => {
            event.preventDefault();
            saveCustomMaterialPackage();
        });

        modal.querySelectorAll('.btn-close-modal').forEach(button => {
            button.addEventListener('click', () => closeMaterialPackageModal());
        });

        modal.addEventListener('click', event => {
            if (event.target === modal) closeMaterialPackageModal();
        });

        const { name, bags, tons } = materialPackageRatioInputs();

        [bags, tons].forEach(input => {
            input?.addEventListener('input', () => {
                setMaterialPackageError('');
                updateMaterialPackagePreview();
            });
        });

        name?.addEventListener('input', () => setMaterialPackageError(''));

        // Enter pada isian nama langsung menyimpan, seperti kebiasaan form
        // pendek lain.
        [name, bags, tons].forEach(input => {
            input?.addEventListener('keydown', event => {
                if (event.key !== 'Enter') return;

                event.preventDefault();
                saveCustomMaterialPackage();
            });
        });
    }

    function saveCustomMaterialPackage() {
        const group = materialPackageTargetGroup;
        const ledger = materialPackageLedgerOf(group);
        const { name, bags, tons } = materialPackageRatioInputs();
        if (!group || !ledger) return;

        const label = String(name?.value || '').replace(/\s+/g, ' ').trim();
        const bagCount = Number(bags?.value);
        const tonCount = Number(tons?.value);

        if (label === '') {
            setMaterialPackageError('Isi nama kemasannya lebih dulu.');
            name?.focus();

            return;
        }

        if (!(bagCount > 0) || !(tonCount > 0)) {
            setMaterialPackageError('Jumlah Bag dan Ton harus lebih besar dari nol.');

            return;
        }

        const factor = Math.round((tonCount / bagCount) * 10000) / 10000;

        // Batas yang sama dengan server, supaya salah ketik ketahuan di sini
        // dan bukan setelah laporan dikirim.
        if (factor < 0.0001 || factor > 100) {
            setMaterialPackageError('Perbandingan Bag dan Ton berada di luar batas wajar. Periksa kembali angkanya.');

            return;
        }

        const normalized = label.toLocaleLowerCase('id-ID');
        const existing = materialPackageOptionsOf(ledger)
            .find(option => option.label.toLocaleLowerCase('id-ID') === normalized);

        if (existing) {
            setMaterialPackageError(`Kemasan ${existing.label} sudah ada pada daftar. Pilih langsung dari dropdown.`);

            return;
        }

        applyMaterialPackageValue(group, registerCustomMaterialPackage(label, factor, materialPackageFamilyOf(group)));
        closeMaterialPackageModal({ restore: false });
    }

    /**
     * Dipakai saat kegiatan baru digandakan dari kegiatan yang sedang tampil.
     * Susunan kelompoknya dikembalikan ke kemasan bawaan, karena kegiatan baru
     * belum tentu membongkar kemasan yang sama dengan kegiatan sebelumnya.
     */
    function resetMaterialPackageRows(pane) {
        const ledger = materialPackageLedgerOf(pane);
        if (!ledger) return;

        const defaults = String(ledger.dataset.materialPackageDefaults || '')
            .split(',')
            .map(code => code.trim())
            .filter(Boolean);

        materialPackageGroups(ledger).forEach((group, index) => {
            if (defaults.length > 0 && index >= defaults.length) {
                group.remove();
                return;
            }

            // Kegiatan baru selalu dimulai dalam keadaan terbuka.
            setMaterialPackageCollapsed(group, false);

            const tableInput = group.querySelector('.table-input.material');
            rowsOf(tableInput).slice(1).forEach(row => row.remove());

            const select = group.querySelector('[data-material-package-select]');
            if (select && defaults[index]) select.value = defaults[index];
        });

        // Kegiatan sumber boleh saja menyisakan kurang dari jumlah kemasan
        // bawaan bila petugasnya menghapus kelompok.
        defaults.slice(materialPackageGroups(ledger).length).forEach(code => {
            addMaterialPackageGroup(ledger, code);
        });

        reindexMaterialPackageTables(pane);
        initializeMaterialPackageGroups(pane);
    }

    /**
     * Terjemahkan kemasan yang tersimpan di draf menjadi kode katalog. Draf
     * lama hanya menyimpan label, dan label "Jumbo Bag" dari sebelum ukuran
     * 1,5 Ton ada harus tetap jatuh ke Jumbo Bag 1 Ton.
     */
    function draftMaterialPackageValue(ledger, record) {
        const options = materialPackageOptionsOf(ledger);

        const normalize = value => String(value || '')
            .replace(/,/g, '.')
            .replace(/\s+/g, ' ')
            .trim()
            .toLocaleLowerCase('id-ID');

        const label = normalize(record.label);

        // Kemasan tambahan tidak ada pada dropdown bawaan, jadi didaftarkan
        // ulang dari draf beserta faktornya.
        if (record.code === MATERIAL_PACKAGE_CUSTOM_CODE) {
            const known = options.find(option => normalize(option.label) === label);
            if (known) return known.value;

            const factor = Number(record.factor);
            if (!label || !(factor > 0)) return '';

            return registerCustomMaterialPackage(String(record.label).replace(/\s+/g, ' ').trim(), factor);
        }

        if (record.code && options.some(option => option.value === record.code)) return record.code;

        if (!label) return '';

        const exact = options.find(option => normalize(option.label) === label);
        if (exact) return exact.value;

        const size = label.match(/\d+(\.\d+)?/);
        const isJumbo = label.includes('jumbo');
        const guess = options.find(option => {
            const candidate = normalize(option.label);
            if (candidate.includes('jumbo') !== isJumbo) return false;

            return !size || candidate.includes(size[0]);
        });

        return guess?.value || '';
    }

    /**
     * Menyusun ulang kelompok kemasan saat draf dipulihkan: jumlah kelompok,
     * kemasan tiap kelompok, dan jumlah barisnya mengikuti isi draf.
     */
    function ensureMaterialPackageRows(fields) {
        const records = new Map();

        fields.forEach(field => {
            const match = fieldName(field).match(/^unloading_materials_(\d+)\[(\d+)]\[([^\]]+)]$/);
            if (!match) return;

            const sequence = Number(match[1]);
            const itemIndex = Number(match[2]);
            const key = `${sequence}:${itemIndex}`;
            const record = records.get(key) || { sequence, itemIndex, code: '', label: '', factor: '' };
            if (match[3] === 'packaging_code') record.code = fieldValue(field).trim();
            if (match[3] === 'packaging_type') record.label = fieldValue(field).trim();
            if (match[3] === 'packaging_factor') record.factor = fieldValue(field).trim();
            records.set(key, record);
        });

        const bySequence = new Map();
        Array.from(records.values())
            .sort((a, b) => a.sequence - b.sequence || a.itemIndex - b.itemIndex)
            .forEach(record => {
                const sequenceRecords = bySequence.get(record.sequence) || [];
                sequenceRecords.push(record);
                bySequence.set(record.sequence, sequenceRecords);
            });

        bySequence.forEach((sequenceRecords, sequence) => {
            const pane = document.querySelector(`#section-bahan-baku .activity-pane[data-sequence="${sequence}"]`);
            const ledger = materialPackageLedgerOf(pane);
            if (!ledger) return;

            // Baris draf tersimpan berurutan per kelompok, sehingga urutan
            // kemunculan kemasan sekaligus menjadi urutan kelompoknya.
            const wanted = [];
            sequenceRecords.forEach(record => {
                const value = draftMaterialPackageValue(ledger, record);
                const existing = wanted.find(item => item.value === value);

                if (existing) {
                    existing.rows += 1;

                    return;
                }

                wanted.push({ value, rows: 1 });
            });

            applyMaterialPackageLayout(pane, wanted);
        });
    }

    /**
     * Menyusun kelompok kemasan beserta jumlah barisnya sesuai daftar yang
     * diminta. Dipakai pemulihan draf maupun penerusan rincian dari regu
     * sebelumnya, supaya keduanya menghasilkan susunan yang sama persis.
     *
     * @param wanted [{ value, rows }] urut sesuai urutan kelompok yang diinginkan
     */
    function applyMaterialPackageLayout(pane, wanted) {
        const ledger = materialPackageLedgerOf(pane);
        if (!ledger || !Array.isArray(wanted) || wanted.length === 0) return;

        while (materialPackageGroups(ledger).length > wanted.length) {
            const groups = materialPackageGroups(ledger);
            if (groups.length <= 1) break;

            groups[groups.length - 1].remove();
        }

        while (materialPackageGroups(ledger).length < wanted.length) {
            if (!addMaterialPackageGroup(ledger)) break;
        }

        materialPackageGroups(ledger).forEach((group, groupIndex) => {
            const target = wanted[groupIndex];
            if (!target) return;

            const select = group.querySelector('[data-material-package-select]');
            if (select && target.value && Array.from(select.options).some(option => option.value === target.value)) {
                select.value = target.value;
            }

            const tableInput = group.querySelector('.table-input.material');
            const addButton = tableInput?.querySelector('.btn-tambah-baris');
            if (!tableInput || !addButton) return;

            const targetRows = Math.max(1, target.rows);
            while (rowsOf(tableInput).length < targetRows) addTableRow(addButton);
            rowsOf(tableInput).slice(targetRows).forEach(row => row.remove());
        });

        reindexMaterialPackageTables(pane);
        initializeMaterialPackageGroups(pane);
    }

    /**
     * Meneruskan rincian bahan baku dari laporan terakhir kapal yang dipilih:
     * jenis bahan dan kemasannya disalin apa adanya, sedangkan akumulasi
     * terakhirnya menjadi nilai "Lalu". Kolom "Sekarang" sengaja dikosongkan
     * karena itulah yang harus diisi regu yang sedang bertugas.
     */
    function applyMaterialCarryForward(pane, rows) {
        const ledger = materialPackageLedgerOf(pane);
        if (!ledger || !Array.isArray(rows) || rows.length === 0) return;

        const buckets = [];
        rows.forEach(row => {
            const value = draftMaterialPackageValue(ledger, {
                code: String(row.packaging_code || ''),
                label: String(row.packaging_type || ''),
                factor: row.packaging_factor,
            });

            const bucket = buckets.find(item => item.value === value);

            if (bucket) {
                bucket.items.push(row);

                return;
            }

            buckets.push({ value, items: [row] });
        });

        applyMaterialPackageLayout(pane, buckets.map(bucket => ({ value: bucket.value, rows: bucket.items.length })));

        materialPackageGroups(ledger).forEach((group, groupIndex) => {
            const items = buckets[groupIndex]?.items || [];

            rowsOf(group.querySelector('.table-input.material')).forEach((row, rowIndex) => {
                const data = items[rowIndex];
                if (!data) return;

                const type = row.querySelector('[name$="[raw_material_type]"]');
                const previous = row.querySelector('[name$="[qty_prev]"]');
                const current = row.querySelector('[name$="[qty_current]"]');

                if (type) setControlValue(type, String(data.raw_material_type ?? ''));
                if (current) setControlValue(current, '');
                if (previous) {
                    const value = Number(data.qty_prev) || 0;
                    setControlValue(previous, value ? reportLocalizedNumber(value) : '');
                    updateAccumulation(previous);
                }
            });

            updateMaterialPackageSubtotal(group);
        });
    }

    function ensureTimesheetRowsForName(name) {
        const timesheetMatch = name.match(/^timesheets\[(\d+)]\[([^\]]+)]\[(\d+)]/);
        const bulkMatch = name.match(/^(bulk_logs|ammonia_logs)\[(\d+)]\[(\d+)]/);
        if (!timesheetMatch && !bulkMatch) return;
        if (controlsByName(name).length > 0) return;

        const sequence = Number(timesheetMatch?.[1] || bulkMatch?.[2] || 1);
        const category = timesheetMatch?.[2] || null;
        const targetIndex = Number(timesheetMatch?.[3] || bulkMatch?.[3] || 0);
        const prefix = timesheetMatch
            ? `timesheets[${sequence}][${category}]`
            : `${bulkMatch[1]}[${sequence}]`;
        const seedControl = Array.from(form.querySelectorAll('[name]')).find(control => control.name.startsWith(`${prefix}[`));
        const timesheetContent = seedControl?.closest('.timesheet-content');
        const addButton = timesheetContent?.querySelector('.btn-add-activity');

        if (!addButton) return;

        while (Array.from(form.querySelectorAll('[name]')).filter(control => control.name.startsWith(`${prefix}[`)).length / (timesheetMatch ? 2 : 3) <= targetIndex) {
            addTimesheetInput(addButton, { forceBlank: true });
        }
    }

    function ensureControlsForFields(fields) {
        ensureActivityPanes(fields);
        ensureMaterialPackageRows(fields);

        fields.forEach(field => {
            const name = fieldName(field);
            ensureTableRowsForName(name);
            ensureTimesheetRowsForName(name);
        });
    }

    function setControlValue(control, value) {
        if (!control) return;

        if (control.type === 'radio') {
            control.checked = control.value === value;
            if (control.checked) control.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        if (control.type === 'checkbox') {
            control.checked = value === control.value || value === 'on' || value === '1' || value === 'true';
            control.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        if (control.tagName === 'SELECT') {
            if (value !== '' && !Array.from(control.options).some(option => option.value === value)) {
                control.appendChild(new Option(value, value));
            }

            control.value = value;
            control.dispatchEvent(new Event('change', { bubbles: true }));
            syncCustomSelectLabel(control);
            return;
        }

        if (control.type === 'datetime-local') {
            control.value = normalizeDateTimeLocalValue(value);
            control.dispatchEvent(new Event('input', { bubbles: true }));
            control.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        if (control.dataset.localeNumber === 'true' && typeof window.setReportNumberValue === 'function') {
            window.setReportNumberValue(control, value);
            control.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        control.value = value;
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function restoreSavedPayload() {
        const fields = payloadFields();
        if (fields.length === 0) return;

        ensureControlsForFields(fields);

        fields.forEach(field => {
            const name = fieldName(field);
            const value = fieldValue(field);
            const controls = controlsByName(name);

            if (controls.length === 0) return;

            if (controls[0].type === 'radio') {
                const radio = controls.find(control => control.value === value);
                setControlValue(radio, value);
                return;
            }

            setControlValue(controls[0], value);
        });

        hydrateTableSelects();
        document.querySelectorAll('[name*="qty_current"], [name*="qty_prev"], [name*="_current_"], [name*="_prev_"]').forEach(updateAccumulation);
        initializeMaterialPackageGroups();
        syncPayload();
    }

    function submitFormSafely(targetForm) {
        const requestSubmit = window.HTMLFormElement?.prototype?.requestSubmit;
        const submit = window.HTMLFormElement?.prototype?.submit;

        if (typeof requestSubmit === 'function') {
            requestSubmit.call(targetForm);
            return;
        }

        if (typeof submit === 'function') {
            submit.call(targetForm);
            return;
        }

        if (typeof targetForm.submit === 'function') {
            targetForm.submit();
        }
    }

    function submitAs(status) {
        if (!form || !statusInput) return;

        window.__reportAutosaveSuppress = true; // pengiriman manual: matikan autosave
        statusInput.value = status;
        validateReportGroupRoute({ enforce: status !== 'draft' });
        validateContainerStatuses({ enforce: status !== 'draft' });

        if (status === 'draft') {
            form.querySelectorAll('[required]').forEach(input => {
                input.dataset.wasRequired = 'true';
                input.required = false;
            });

            // Mask Jam Kerja menandai custom validity saat masih ada slot
            // digit kosong ("_"); draft boleh disimpan walau belum lengkap.
            document.getElementById('jam-kerja')?.setCustomValidity('');
        }

        window.normalizeReportNumberInputs?.();
        syncPayload();

        submitFormSafely(form);
    }

    function formFocusControls() {
        if (!form) return [];

        return Array.from(form.querySelectorAll('input, select, textarea'))
            .filter(control => {
                if (control.disabled || control.readOnly || control.type === 'hidden') return false;
                if (control.closest('.d-none, [hidden]')) return false;

                const style = window.getComputedStyle(control);
                if (style.display === 'none' || style.visibility === 'hidden') return false;

                return control.getClientRects().length > 0;
            });
    }

    function focusSiblingFormControl(currentControl, direction = 1) {
        const controls = formFocusControls();
        const currentIndex = controls.indexOf(currentControl);
        const fallbackIndex = direction > 0 ? 0 : controls.length - 1;
        const nextControl = controls[currentIndex + direction] || controls[fallbackIndex];

        if (!nextControl || nextControl === currentControl) return;

        nextControl.focus({ preventScroll: false });
        if (typeof nextControl.select === 'function' && ['text', 'number', 'search', 'tel', 'url', 'email', 'password'].includes(nextControl.type)) {
            nextControl.select();
        }
    }

    function handleFormEnterNavigation(event) {
        if (event.key !== 'Enter' || event.isComposing) return;

        const target = event.target;
        if (!target || !target.closest || !target.closest('#mainReportForm')) return;
        if (!target.matches('input, select, textarea')) return;
        if (target.tagName === 'TEXTAREA' && !event.ctrlKey) return;
        if (['button', 'submit', 'reset', 'file'].includes(target.type)) return;

        event.preventDefault();
        focusSiblingFormControl(target, event.shiftKey ? -1 : 1);
    }

    function handleTimesheetEnterAction(event) {
        if (event.key !== 'Enter' || event.isComposing) return false;

        const target = event.target;
        if (!target?.matches?.('input')) return false;

        const row = target.closest('.timesheet-input');
        if (!row) return false;

        const isActivityInput = target.matches('input[name$="[activity]"]');
        const isCobInput = target.matches('input[name$="[cob]"]');
        if (!isActivityInput && !isCobInput) return false;

        const addButton = row.querySelector('.btn-add-activity');
        if (!addButton) return false;

        event.preventDefault();

        const cobInput = row.querySelector('input[name$="[cob]"]');
        if (isActivityInput && cobInput) {
            cobInput.focus({ preventScroll: false });
            cobInput.select?.();
            return true;
        }

        addTimesheetInput(addButton);
        return true;
    }

    function makeRadioCell(name, idPrefix, checkedValue = 'Baik') {
        const safePrefix = escapeHtml(idPrefix);
        const baikId = `${safePrefix}_baik`;
        const rusakId = `${safePrefix}_rusak`;

        return `
            <div class="radio-group-custom">
                <div class="radio-custom baik">
                    <input type="radio" name="${escapeHtml(name)}" id="${baikId}" value="Baik" ${checkedValue === 'Baik' ? 'checked' : ''}>
                    <label for="${baikId}"><i class="fi fi-rr-check"></i> Baik</label>
                </div>
                <div class="radio-custom rusak">
                    <input type="radio" name="${escapeHtml(name)}" id="${rusakId}" value="Rusak" ${checkedValue === 'Rusak' ? 'checked' : ''}>
                    <label for="${rusakId}"><i class="fi fi-rr-cross-small"></i> Rusak</label>
                </div>
            </div>
        `;
    }

    function conditionKey(value) {
        return String(value || '').trim().toLowerCase();
    }

    function previousHandoverCondition(category, item, fallback = 'Baik') {
        const categoryConditions = lastUnitHandoverConditions?.[category] || {};
        const masterId = item?.id === null || item?.id === undefined ? '' : String(item.id);
        const itemName = conditionKey(item?.name || item?.item_name || item);

        return categoryConditions.master?.[masterId]
            || categoryConditions.name?.[itemName]
            || fallback;
    }

    function setRadioValueByName(name, value) {
        const radio = Array.from(document.querySelectorAll(`input[type="radio"][name="${name}"]`))
            .find(input => input.value === value);

        if (radio) setControlValue(radio, value);
    }

    function applyPreviousShelterConditions() {
        document.querySelectorAll('input[name^="shelter_logs"][name$="[item_name]"]').forEach(input => {
            const condition = previousHandoverCondition('shelter', input.value, null);
            if (!condition) return;

            const match = input.name.match(/^shelter_logs\[(\d+)]/);
            if (!match) return;

            setRadioValueByName(`shelter_logs[${match[1]}][condition_received]`, condition);
            setRadioValueByName(`shelter_logs[${match[1]}][condition_handed_over]`, condition);
        });
    }

    function syncHandedOverWithReceived(receivedRadio, { force = false } = {}) {
        if (!receivedRadio?.checked || !receivedRadio.name.includes('[condition_received]')) return;

        const row = receivedRadio.closest('.body') || document;
        const handedOverName = receivedRadio.name.replace('[condition_received]', '[condition_handed_over]');
        const handedOverRadios = Array.from(row.querySelectorAll('input[type="radio"]'))
            .filter(input => input.name === handedOverName);
        const handedOverGroup = handedOverRadios[0]?.closest('.radio-group-custom');

        if (!force && handedOverGroup?.dataset.userAdjusted === 'true') return;

        const targetRadio = handedOverRadios.find(input => input.value === receivedRadio.value);
        if (targetRadio) setControlValue(targetRadio, receivedRadio.value);
    }

    function rowsOf(tableInput) {
        return Array.from(tableInput.children).filter(child => child.classList.contains('body'));
    }

    function insertRows(tableInput, rows) {
        if (!tableInput || rows.length === 0) return;

        rowsOf(tableInput).forEach(row => row.remove());
        const addButton = tableInput.querySelector('.btn-tambah-baris');

        rows.forEach(rowHtml => {
            const template = document.createElement('template');
            template.innerHTML = rowHtml.trim();
            tableInput.insertBefore(template.content.firstElementChild, addButton);
        });
    }

    function resetTableSelectHydration(root = document) {
        root.querySelectorAll('.tbl-custom-select-trigger, .tbl-custom-options').forEach(element => element.remove());
        root.querySelectorAll('select.tbl-native-select').forEach(select => {
            select.style.display = '';
        });
    }

    function hydrateTableSelects(root = document) {
        root.querySelectorAll('.tbl-select-wrapper').forEach(wrapper => {
            const nativeSelect = wrapper.querySelector('select.tbl-native-select');
            if (!nativeSelect || wrapper.querySelector('.tbl-custom-select-trigger')) return;

            nativeSelect.style.display = 'none';

            const triggerBox = document.createElement('div');
            triggerBox.className = 'tbl-custom-select-trigger d-flex align-items-center';

            const textSpan = document.createElement('span');
            triggerBox.appendChild(textSpan);
            wrapper.insertBefore(triggerBox, nativeSelect.nextSibling);

            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'tbl-custom-options';

            function updateTrigger() {
                const selectedOption = nativeSelect.options[nativeSelect.selectedIndex];
                textSpan.textContent = selectedOption ? selectedOption.text : '';
                triggerBox.classList.toggle('text-placeholder', !selectedOption || selectedOption.disabled || selectedOption.value === '');
                optionsContainer.querySelectorAll('.tbl-custom-option').forEach(option => {
                    option.classList.toggle('selected', option.dataset.value === nativeSelect.value);
                });
            }

            Array.from(nativeSelect.options).forEach(option => {
                if (option.disabled && option.hidden) return;

                const optionButton = document.createElement('div');
                optionButton.className = 'tbl-custom-option';
                optionButton.textContent = option.text;
                optionButton.dataset.value = option.value;
                optionButton.addEventListener('click', event => {
                    event.stopPropagation();
                    nativeSelect.value = optionButton.dataset.value;
                    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    optionsContainer.classList.remove('open');
                    triggerBox.classList.remove('focus-active');
                });
                optionsContainer.appendChild(optionButton);
            });

            wrapper.appendChild(optionsContainer);
            nativeSelect.addEventListener('change', updateTrigger);

            triggerBox.addEventListener('click', event => {
                event.stopPropagation();
                document.querySelectorAll('.tbl-custom-options.open').forEach(container => {
                    if (container !== optionsContainer) {
                        container.classList.remove('open');
                        container.previousElementSibling?.classList.remove('focus-active');
                    }
                });
                optionsContainer.classList.toggle('open');
                triggerBox.classList.toggle('focus-active');
            });

            updateTrigger();
        });
    }

    // Baris Lingkungan Shelter di-seed dari master "Data Lingkungan Operasi",
    // dikelompokkan per kategori (divider). Nama item editable, baris bisa
    // ditambah/dihapus (pakai mekanisme generik addTableRow/removeTableRow).
    function shelterRowHtml(item, index) {
        return `
            <div class="body">
                <div class="table-column no"><span>${index + 1}</span></div>
                <div class="table-column main">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-house-chimney"></i></span>
                        <input type="text" name="shelter_logs[${index}][item_name]" value="${escapeHtml(item?.name || '')}" placeholder="Nama Item">
                    </div>
                </div>
                <div class="table-column radio">${makeRadioCell(`shelter_logs[${index}][condition_received]`, `ling_terima_${index}`, previousHandoverCondition('shelter', item))}</div>
                <div class="table-column radio">${makeRadioCell(`shelter_logs[${index}][condition_handed_over]`, `ling_serah_${index}`, previousHandoverCondition('shelter', item))}</div>
                <div class="table-column delete"><button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button></div>
            </div>
        `;
    }

    function renderShelterRows() {
        const shelterTable = document.querySelector('#section-lingkungan .table-input');
        if (!shelterTable || !Array.isArray(masterShelters) || masterShelters.length === 0) return;

        const addButton = shelterTable.querySelector('.btn-tambah-baris');

        // Bersihkan baris & divider lama (sisakan head + tombol tambah).
        shelterTable.querySelectorAll('.body, .table-divide').forEach(el => el.remove());

        let index = 0;
        let lastCategory = null;
        masterShelters.forEach(item => {
            const category = (item.category || 'Umum').trim();
            if (category !== lastCategory) {
                const divider = document.createElement('div');
                divider.className = 'table-divide';
                divider.innerHTML = `<span>${escapeHtml(category)}</span>`;
                shelterTable.insertBefore(divider, addButton);
                lastCategory = category;
            }

            const template = document.createElement('template');
            template.innerHTML = shelterRowHtml(item, index).trim();
            shelterTable.insertBefore(template.content.firstElementChild, addButton);
            index++;
        });
    }

    function renderMasterCheckRows() {
        const vehicleTable = document.querySelector('#section-unit .table-input');
        const inventoryTable = document.querySelector('#section-inventaris .table-input');

        if (vehicleTable && Array.isArray(masterVehicles) && masterVehicles.length > 0) {
            insertRows(vehicleTable, masterVehicles.map((item, index) => `
                <div class="body">
                    <div class="table-column no"><span>${index + 1}</span></div>
                    <div class="table-column main">
                        <div class="table-input-wrapper">
                            <span class="icon"><i class="fi fi-sr-truck-side"></i></span>
                            <input type="hidden" name="unit_logs[${index}][master_unit_id]" value="${escapeHtml(item.id)}">
                            <input type="text" name="unit_logs[${index}][item_name]" value="${escapeHtml(item.name || (item.unit_number ? `${item.type || ''} ${item.unit_number}`.trim() : (item.type || '')))}">
                        </div>
                    </div>
                    <div class="table-column amount">
                        <div class="table-input-wrapper">
                            <span class="icon"><i class="fi fi-sr-gas-pump-alt"></i></span>
                            <input type="number" name="unit_logs[${index}][fuel_level]" placeholder="0">
                        </div>
                    </div>
                    <div class="table-column radio">${makeRadioCell(`unit_logs[${index}][condition_received]`, `unit_terima_${index}`, previousHandoverCondition('vehicle', item))}</div>
                    <div class="table-column radio">${makeRadioCell(`unit_logs[${index}][condition_handed_over]`, `unit_serah_${index}`, previousHandoverCondition('vehicle', item))}</div>
                </div>
            `));
        }

        if (inventoryTable && Array.isArray(masterInventories) && masterInventories.length > 0) {
            insertRows(inventoryTable, masterInventories.map((item, index) => `
                <div class="body">
                    <div class="table-column no"><span>${index + 1}</span></div>
                    <div class="table-column main">
                        <div class="table-input-wrapper">
                            <span class="icon"><i class="fi fi-sr-box-open"></i></span>
                            <input type="hidden" name="inventory_logs[${index}][master_inventory_item_id]" value="${escapeHtml(item.id)}">
                            <input type="text" name="inventory_logs[${index}][item_name]" value="${escapeHtml(item.name)}">
                        </div>
                    </div>
                    <div class="table-column amount">
                        <div class="table-input-wrapper">
                            <span class="icon"><i class="fi fi-sr-boxes"></i></span>
                            <input type="number" name="inventory_logs[${index}][quantity]" value="${escapeHtml(item.qty || 1)}" placeholder="0">
                        </div>
                    </div>
                    <div class="table-column radio">${makeRadioCell(`inventory_logs[${index}][condition_received]`, `inv_terima_${index}`, previousHandoverCondition('inventory', item))}</div>
                    <div class="table-column radio">${makeRadioCell(`inventory_logs[${index}][condition_handed_over]`, `inv_serah_${index}`, previousHandoverCondition('inventory', item))}</div>
                </div>
            `));
        }

        renderShelterRows();
        applyPreviousShelterConditions();
    }

    function turbaRowHtml(truck, index) {
        const truckName = truck?.name || truck?.plate_number || '';

        return `
            <div class="body">
                <div class="table-column no"><span>${index + 1}</span></div>
                <div class="table-column medium">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-truck-side"></i></span>
                        <input type="text" name="turba_deliveries[${index}][truck_name]" value="${escapeHtml(truckName)}" placeholder="Nama Truck">
                    </div>
                </div>
                <div class="table-column input-double">
                    <input type="text" name="turba_deliveries[${index}][do_so_number]" class="form-control-custom" placeholder="Nomor">
                    <input type="number" name="turba_deliveries[${index}][capacity]" class="form-control-custom" placeholder="0">
                </div>
                <div class="table-column medium">
                    <input type="text" name="turba_deliveries[${index}][marking_type]" class="form-control-custom" placeholder="Marking">
                </div>
                <div class="table-column input-triple">
                    <input type="number" name="turba_deliveries[${index}][qty_current]" class="form-control-custom" placeholder="0">
                    <input type="number" name="turba_deliveries[${index}][qty_prev]" class="form-control-custom" placeholder="0">
                    <input type="number" name="turba_deliveries[${index}][qty_accumulated]" class="form-control-custom" placeholder="0" readonly>
                </div>
                <div class="table-column delete">
                    <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                </div>
            </div>
        `;
    }

    function renderMasterTruckRows() {
        const turbaTable = document.querySelector('#step-gudang-turba .table-input');
        if (!turbaTable || !Array.isArray(masterTrucks) || masterTrucks.length === 0) return;

        insertRows(turbaTable, masterTrucks.map((truck, index) => turbaRowHtml(truck, index)));
    }

    function normalizeTimeForInput(value) {
        const normalized = String(value || '').trim().replace(/\./g, ':');
        const match = normalized.match(/^(\d{1,2}):?(\d{2})$/);

        if (!match) return normalized;

        return `${match[1].padStart(2, '0')}:${match[2]}`;
    }

    function currentWorkTimes() {
        const timeRange = String(document.querySelector('[name="time_range"]')?.value || '').replace(/\./g, ':');
        const [timeIn = '', timeOut = ''] = timeRange.split(/\s*-\s*/);

        return {
            timeIn: normalizeTimeForInput(timeIn),
            timeOut: normalizeTimeForInput(timeOut),
        };
    }

    function isAbsentDescription(value) {
        return ['sakit', 'izin', 'cuti', 'tidak masuk', 'tdk masuk'].includes(String(value || '').trim().toLowerCase());
    }

    function applyReliefAttendanceState(row) {
        if (!row?.querySelector('input[name^="relief_logs"]')) return false;

        const status = row.querySelector('input[name$="[attendance_status]"]')?.value;
        const isAbsent = isAbsentDescription(status);
        const timeInInput = row.querySelector('input[name$="[time_in]"]');
        const timeOutInput = row.querySelector('input[name$="[time_out]"]');
        const workTimeInput = row.querySelector('input[name$="[work_time]"]');

        [timeInInput, timeOutInput, workTimeInput].forEach(input => {
            if (!input) return;

            if (isAbsent) input.value = '';
            input.readOnly = isAbsent;
            input.classList.toggle('is-absence-cleared', isAbsent);
            input.setAttribute('aria-disabled', isAbsent ? 'true' : 'false');
        });

        return isAbsent;
    }

    const OP7_FORKLIFT_DEFAULTS = [
        { no_forklift: 'FL.KSS-100', work_area: 'P.6' },
        { no_forklift: 'FL.KSS-101', work_area: 'Popka' },
        { no_forklift: 'FL.KSS-102', work_area: 'Bagging-1' },
        { no_forklift: 'FL.KSS-104', work_area: 'Bagging-1' },
        { no_forklift: 'FL.KSS-105', work_area: 'Bagging-2' },
        { no_forklift: 'FL.KSS-106', work_area: 'Bagging-2' },
        { no_forklift: 'FL.KSS-108', work_area: 'Gudang Produk Tursina' },
        { no_forklift: 'FL.KSS-109', work_area: 'Blending' },
        { no_forklift: 'FL.KSS-103', work_area: 'Blending' },
        { no_forklift: 'FL.KSS-107', work_area: 'Blending' },
        { no_forklift: 'FL.KSS-110', work_area: 'Blending' },
    ];

    function applyOp7ForkliftDefaults(row, index) {
        if (!row) return;
        const mapping = OP7_FORKLIFT_DEFAULTS[index];
        if (!mapping) return;

        const forkliftInput = row.querySelector('input[name^="op7_logs"][name$="[no_forklift_]"]');
        const areaInput = row.querySelector('input[name^="op7_logs"][name$="[work_area]"]');

        if (forkliftInput && !forkliftInput.value) forkliftInput.value = mapping.no_forklift;
        if (areaInput && !areaInput.value) areaInput.value = mapping.work_area;
    }

    function op7DefaultForRow(row) {
        const forkliftInput = row?.querySelector?.('input[name^="op7_logs"][name$="[no_forklift_]"]');
        const match = forkliftInput?.name?.match(/^op7_logs\[(\d+)]/);
        const tableInput = row?.closest?.('.table-input');
        const fallbackIndex = tableInput ? rowsOf(tableInput).indexOf(row) : -1;
        const index = match ? Number(match[1]) : fallbackIndex;

        return OP7_FORKLIFT_DEFAULTS[index] || { no_forklift: '', work_area: '' };
    }

    function applyOp7AssignmentState(row, isAbsent) {
        const forkliftInput = row?.querySelector?.('input[name^="op7_logs"][name$="[no_forklift_]"]');
        const areaInput = row?.querySelector?.('input[name^="op7_logs"][name$="[work_area]"]');

        if (!forkliftInput || !areaInput) return;

        if (isAbsent) {
            if (forkliftInput.value) row.dataset.absentNoForklift = forkliftInput.value;
            if (areaInput.value) row.dataset.absentWorkArea = areaInput.value;

            forkliftInput.value = '';
            areaInput.value = '';
            forkliftInput.readOnly = true;
            areaInput.readOnly = true;
            forkliftInput.classList.add('is-auto-filled');
            areaInput.classList.add('is-auto-filled');
            return;
        }

        const defaults = op7DefaultForRow(row);

        forkliftInput.readOnly = false;
        areaInput.readOnly = false;
        forkliftInput.classList.remove('is-auto-filled');
        areaInput.classList.remove('is-auto-filled');

        if (!forkliftInput.value) forkliftInput.value = row.dataset.absentNoForklift || defaults.no_forklift;
        if (!areaInput.value) areaInput.value = row.dataset.absentWorkArea || defaults.work_area;

        delete row.dataset.absentNoForklift;
        delete row.dataset.absentWorkArea;
    }

    function op7ReplacementAssignment(row) {
        const defaults = op7DefaultForRow(row);

        return {
            forklift: row?.dataset?.absentNoForklift
                || row?.querySelector?.('input[name$="[no_forklift_]"]')?.value
                || defaults.no_forklift
                || '',
            area: row?.dataset?.absentWorkArea
                || row?.querySelector?.('input[name$="[work_area]"]')?.value
                || defaults.work_area
                || '',
        };
    }

    function applyAbsenceStateToRow(row) {
        if (!row) return false;

        const description = row.querySelector('[name$="[description]"]')?.value;
        const isAbsent = isAbsentDescription(description);
        const timeInInput = row.querySelector('input[name$="[time_in]"]');
        const timeOutInput = row.querySelector('input[name$="[time_out]"]');

        if (isAbsent) {
            if (timeInInput) timeInInput.value = '';
            if (timeOutInput) timeOutInput.value = '';
        }

        applyOp7AssignmentState(row, isAbsent);

        return isAbsent;
    }

    function applyShiftTimesToRow(row) {
        if (!row) return;

        const timeInInput = row.querySelector('input[name$="[time_in]"]');
        const timeOutInput = row.querySelector('input[name$="[time_out]"]');

        if (!timeInInput || !timeOutInput) return;

        if (applyAbsenceStateToRow(row)) return;

        const { timeIn, timeOut } = currentWorkTimes();
        timeInInput.value = timeIn;
        timeOutInput.value = timeOut;
    }

    function applyShiftTimesToEmployeeRows() {
        document.querySelectorAll('#section-shift .table-input .body, #section-op7 .table-wrapper:not(.red) .table-input .body').forEach(applyShiftTimesToRow);
    }

    function applyAbsenceStateToEmployeeRows() {
        document.querySelectorAll('#section-shift .table-input .body, #section-op7 .table-wrapper:not(.red) .table-input .body').forEach(applyAbsenceStateToRow);
    }

    // ===== Sinkronisasi otomatis OP.7 -> Daftar Pengganti =====
    // Saat operator OP.7 ditandai cuti/tidak masuk, satu baris pengganti dibuat
    // otomatis di tabel bawah dengan No.Forklift, Area Kerja, Masuk, & Keluar terisi
    // otomatis dari operator tsb. Petugas cukup mengisi nama penggantinya.
    let op7ReplacementUid = 0;

    function op7RowIsAbsent(row) {
        return row ? isAbsentDescription(row.querySelector('[name$="[description]"]')?.value) : false;
    }

    function setReplacementAutoField(row, selector, value) {
        const input = row.querySelector(selector);
        if (!input) return;
        if (!input.value) input.value = value;
    }

    function buildReplacementRow(repTable, uid) {
        const rows = rowsOf(repTable);
        const template = rows[rows.length - 1];
        if (!template) return null;

        const clone = template.cloneNode(true);
        clearRow(clone);
        resetTableSelectHydration(clone);
        clone.dataset.replacementFor = uid;
        clone.dataset.replacementCreated = 'true';
        // Baris otomatis: tidak dihapus manual (akan hilang sendiri saat operator hadir lagi)
        clone.querySelector('.table-column.delete')?.style.setProperty('visibility', 'hidden');

        repTable.insertBefore(clone, repTable.querySelector('.btn-tambah-baris'));
        applyMasterDatalists(clone);
        hydrateTableSelects(clone);
        return clone;
    }

    // Cari baris pengganti yang masih kosong & belum tertaut (mis. baris bawaan template)
    // agar diisi lebih dulu sebelum menambah baris baru.
    function findAdoptableReplacementRow(repTable) {
        return rowsOf(repTable).find(row =>
            !row.dataset.replacementFor &&
            !(row.querySelector('input[name$="[name]"]')?.value || '').trim()
        ) || null;
    }

    // Kembalikan baris adopsi (baris bawaan) menjadi baris manual kosong saat operator hadir lagi.
    function revertReplacementRow(row) {
        delete row.dataset.replacementFor;
        ['input[name$="[no_forklift_]"]', 'input[name$="[work_area]"]', 'input[name$="[time_in]"]', 'input[name$="[time_out]"]'].forEach(selector => {
            const input = row.querySelector(selector);
            if (!input) return;
            input.readOnly = false;
            input.classList.remove('is-auto-filled');
            input.value = '';
        });
        row.querySelector('.table-column.delete')?.style.removeProperty('visibility');
    }

    function syncOp7Replacements() {
        const op7Table = document.querySelector('#section-op7 .table-wrapper:not(.red) .table-input');
        const repTable = document.querySelector('#section-op7 .table-wrapper.red .table-input');
        if (!op7Table || !repTable) return;

        const { timeIn, timeOut } = currentWorkTimes();
        const op7Rows = rowsOf(op7Table);
        const activeUids = [];

        op7Rows.forEach(row => {
            if (!op7RowIsAbsent(row)) return;
            if (!row.dataset.op7Uid) row.dataset.op7Uid = 'op7-' + (++op7ReplacementUid);
            activeUids.push(row.dataset.op7Uid);
        });

        // Operator sudah hadir lagi / dihapus: baris buatan dibuang, baris adopsi dikembalikan
        rowsOf(repTable).forEach(row => {
            const forUid = row.dataset.replacementFor;
            if (!forUid || activeUids.includes(forUid)) return;

            if (row.dataset.replacementCreated === 'true') {
                row.remove();
            } else {
                revertReplacementRow(row);
            }
        });

        // Isi / perbarui baris pengganti untuk tiap operator OP.7 yang tidak masuk.
        // Baris bawaan yang masih kosong dipakai lebih dulu, baru menambah baris baru.
        op7Rows.forEach(row => {
            if (!op7RowIsAbsent(row)) return;
            const uid = row.dataset.op7Uid;
            const { forklift, area } = op7ReplacementAssignment(row);

            let repRow = rowsOf(repTable).find(r => r.dataset.replacementFor === uid);
            if (!repRow) {
                repRow = findAdoptableReplacementRow(repTable);
                if (repRow) {
                    repRow.dataset.replacementFor = uid;
                    repRow.querySelector('.table-column.delete')?.style.setProperty('visibility', 'hidden');
                } else {
                    repRow = buildReplacementRow(repTable, uid);
                    if (!repRow) return;
                }
            }

            setReplacementAutoField(repRow, 'input[name$="[no_forklift_]"]', forklift);
            setReplacementAutoField(repRow, 'input[name$="[work_area]"]', area);
            setReplacementAutoField(repRow, 'input[name$="[time_in]"]', timeIn);
            setReplacementAutoField(repRow, 'input[name$="[time_out]"]', timeOut);
        });

        reindexTable(repTable);
    }

    function employeeShiftRowHtml(employee, index) {
        const { timeIn, timeOut } = currentWorkTimes();

        return `
            <div class="body">
                <div class="table-column no"><span>${index + 1}</span></div>
                <div class="table-column main">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-user-time"></i></span>
                        <input type="text" name="employee_shift_logs[${index}][name]" value="${escapeHtml(employee.name)}" placeholder="Nama Karyawan">
                    </div>
                </div>
                <div class="table-column absent">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                        <input type="text" name="employee_shift_logs[${index}][time_in]" class="time-picker-input" value="${escapeHtml(timeIn)}" placeholder="00:00">
                    </div>
                </div>
                <div class="table-column absent">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                        <input type="text" name="employee_shift_logs[${index}][time_out]" class="time-picker-input" value="${escapeHtml(timeOut)}" placeholder="00:00">
                    </div>
                </div>
                <div class="table-column absent" style="overflow: visible;">
                    <div class="table-input-wrapper">
                        <input type="text" name="employee_shift_logs[${index}][description]" list="keterangan_absen_options" placeholder="Keterangan" autocomplete="off">
                    </div>
                </div>
                <div class="table-column delete">
                    <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                </div>
            </div>
        `;
    }

    function op7RowHtml(employee, index) {
        const { timeIn, timeOut } = currentWorkTimes();
        const fallback = OP7_FORKLIFT_DEFAULTS[index] || { no_forklift: '', work_area: '' };
        // Susunan dari laporan sebelumnya (bila ada) menang atas pemetaan
        // default per posisi — inilah yang membuat pemindahan area kerja
        // tercatat oleh petugas ikut terbawa ke laporan berikutnya.
        const mapping = {
            no_forklift: employee.no_forklift_ || fallback.no_forklift,
            work_area: employee.work_area || fallback.work_area,
        };

        return `
            <div class="body">
                <div class="table-column no"><span>${index + 1}</span></div>
                <div class="table-column main">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-user-helmet-safety"></i></span>
                        <input type="text" name="op7_logs[${index}][name]" value="${escapeHtml(employee.name)}" placeholder="Nama Karyawan OP.7">
                    </div>
                </div>
                <div class="table-column medium">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-forklift"></i></span>
                        <input type="text" name="op7_logs[${index}][no_forklift_]" value="${escapeHtml(mapping.no_forklift)}" placeholder="No. Forklift">
                    </div>
                </div>
                <div class="table-column medium">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-land-location"></i></span>
                        <input type="text" name="op7_logs[${index}][work_area]" value="${escapeHtml(mapping.work_area)}" placeholder="Area">
                    </div>
                </div>
                <div class="table-column absent">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-rr-time-quarter-past blue"></i></span>
                        <input type="text" name="op7_logs[${index}][time_in]" class="time-picker-input" value="${escapeHtml(timeIn)}" placeholder="00:00">
                    </div>
                </div>
                <div class="table-column absent">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-rr-time-check red"></i></span>
                        <input type="text" name="op7_logs[${index}][time_out]" class="time-picker-input" value="${escapeHtml(timeOut)}" placeholder="00:00">
                    </div>
                </div>
                <div class="table-column absent" style="overflow: visible;">
                    <div class="table-input-wrapper">
                        <input type="text" name="op7_logs[${index}][description]" list="keterangan_absen_options" placeholder="Keterangan" autocomplete="off">
                    </div>
                </div>
                <div class="table-column delete">
                    <button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>
                </div>
            </div>
        `;
    }

    function renderEmployeeShiftRows(groupValue = null) {
        const employeeTable = document.querySelector('#section-shift .table-input');
        const group = groupValue || document.querySelector('[name="group_name"]')?.value || currentUserGroup;
        const employees = employeesForGroup(group);

        if (!employeeTable || employees.length === 0) return;

        // Kepala Regu (KARU) dan Wakil Kepala Regu tetap ditampilkan lebih dahulu.
        const isWakaru = employee => /wakil/i.test(employee.position || '');
        const isKaru = employee => !isWakaru(employee) && /karu|kepala regu/i.test(employee.position || '');
        const karu = employees.find(isKaru);
        const wakaru = employees.find(isWakaru);
        const leaders = [karu, wakaru].filter(Boolean);
        const rest = employees.filter(employee => !leaders.includes(employee));
        const ordered = [...leaders, ...rest];

        insertRows(employeeTable, ordered.map((employee, index) =>
            employeeShiftRowHtml(employee, index)));
        applyMasterDatalists(employeeTable);
        hydrateTableSelects(employeeTable);
        applyShiftTimesToEmployeeRows();
    }

    function renderOp7Rows(groupValue = null) {
        const op7Table = document.querySelector('#section-op7 .table-wrapper:not(.red) .table-input');
        const group = groupValue || document.querySelector('[name="group_name"]')?.value || currentUserGroup;
        const remembered = rememberedOp7Roster(group);
        const employees = employeesForOp7Group(group);

        if (!op7Table || (remembered.length === 0 && employees.length === 0)) return;

        // Memori hanya memuat anggota OP.7 regu ini. Anggota master yang belum
        // ada di sana — personil baru, atau yang di laporan terakhir barisnya
        // diisi operator pinjaman — tetap ikut tampil, ditaruh setelahnya.
        const nameKey = value => String(value || '').trim().toLowerCase();
        const rememberedNames = new Set(remembered.map(entry => nameKey(entry.name)));
        const missing = employees.filter(employee => !rememberedNames.has(nameKey(employee.name)));

        // Baris 1 (FL.KSS-100 / P.6) adalah stasiun tetap "Operator P.6", bukan
        // karyawan bernama dari master data — karyawan OP.7 mengisi baris 2 dst.
        // Lihat OP7_FORKLIFT_DEFAULTS: 1 slot tetap + 10 karyawan = 11 baris.
        // Stasiun itu selalu dipasang di sini, bukan diambil dari memori.
        const rows = [{ name: 'Operator P.6' }, ...remembered, ...missing];

        insertRows(op7Table, rows.map((employee, index) => op7RowHtml(employee, index)));
        applyMasterDatalists(op7Table);
        hydrateTableSelects(op7Table);
    }

    function applyDefaultGroup() {
        const groupSelect = document.querySelector('[name="group_name"]');
        if (!groupSelect || isEditMode || !currentUserGroup || groupSelect.value) return;

        setSelectValue(groupSelect, currentUserGroup);
    }

    function syncTimeRangeWithShift() {
        const shiftSelect = document.querySelector('[name="shift"]');
        const timeRangeInput = document.querySelector('[name="time_range"]');
        const normalizedShift = String(shiftSelect?.value || '').toLowerCase();

        const shiftTimes = {
            '1': '07:00 - 15:00',
            pagi: '07:00 - 15:00',
            '2': '15:00 - 23:00',
            siang: '15:00 - 23:00',
            sore: '15:00 - 23:00',
            '3': '23:00 - 07:00',
            malam: '23:00 - 07:00',
        };

        if (timeRangeInput && shiftTimes[normalizedShift]) {
            setTimeRangeValue(timeRangeInput, shiftTimes[normalizedShift]);
        }
    }

    function currentWitaShiftDefaults() {
        if (currentWitaHour >= 7 && currentWitaHour < 15) {
            return { shift: 'Pagi', timeRange: '07:00 - 15:00' };
        }

        if (currentWitaHour >= 15 && currentWitaHour < 23) {
            return { shift: 'Sore', timeRange: '15:00 - 23:00' };
        }

        return { shift: 'Malam', timeRange: '23:00 - 07:00' };
    }

    function applyDefaultShiftByWita() {
        if (isEditMode) return;

        const shiftSelect = document.querySelector('[name="shift"]');
        const timeRangeInput = document.querySelector('[name="time_range"]');
        if (!shiftSelect || shiftSelect.value) return;

        const defaults = currentWitaShiftDefaults();
        setSelectValue(shiftSelect, defaults.shift);

        if (timeRangeInput && !timeRangeInput.value) {
            setTimeRangeValue(timeRangeInput, defaults.timeRange);
        }
    }

    const groupRouteWarningMessage = 'Group penerima harus berbeda dari group pengirim.';
    let groupRouteWarningShown = false;

    function reportGroupRouteControls() {
        const sender = document.querySelector('[name="group_name"]');
        const receiver = document.querySelector('[name="received_by_group"]');
        const warning = document.querySelector('[data-group-route-warning]');
        const receiverBox = receiver?.closest('.box-input-1');

        return { sender, receiver, warning, receiverBox };
    }

    function validateReportGroupRoute(options = {}) {
        const { enforce = statusInput?.value !== 'draft', showToast = false } = options;
        const { sender, receiver, warning, receiverBox } = reportGroupRouteControls();

        if (!sender || !receiver) return true;

        const senderGroup = normalizeGroupName(sender.value);
        const receiverGroup = normalizeGroupName(receiver.value);
        const isSameGroup = senderGroup !== '' && receiverGroup !== '' && senderGroup === receiverGroup;
        const message = isSameGroup
            ? `Group ${receiverGroup} tidak bisa menerima laporan dari group yang sama. Pilih group penerima yang berbeda.`
            : '';

        receiver.setCustomValidity(isSameGroup && enforce ? groupRouteWarningMessage : '');
        warning?.classList.toggle('d-none', !isSameGroup);
        if (warning) {
            const warningText = warning.querySelector('span') || warning;
            warningText.textContent = message || groupRouteWarningMessage;
        }
        receiverBox?.classList.toggle('route-invalid', isSameGroup);

        if (isSameGroup && showToast && !groupRouteWarningShown) {
            window.showReportToast?.('error', 'Group tidak valid', message);
            groupRouteWarningShown = true;
        }

        if (!isSameGroup) {
            groupRouteWarningShown = false;
        }

        return !isSameGroup;
    }

    window.validateReportGroupRoute = validateReportGroupRoute;

    // ==========================================
    // Penanda Empty/Full pada tabel container
    //
    // Pilihan ini yang memisahkan Bongkar Container dari Muat Container di
    // laporan manajer. Baris yang ada jumlahnya tetapi penandanya belum dipilih
    // tidak masuk kegiatan mana pun — angkanya hilang tanpa peringatan. Karena
    // itu baris seperti itu ditahan di sini, sebelum laporan dikirim.
    // ==========================================

    const containerStatusMessage = 'Isi Empty atau Full pada setiap baris container yang ada jumlahnya. '
        + 'Empty berarti bongkar, Full berarti muat.';

    const CONTAINER_STATUSES = ['Empty', 'Full'];

    function containerStatusInputs() {
        if (!form) return [];

        return Array.from(form.querySelectorAll('[name^="unloading_containers_"][name$="[status]"]'));
    }

    /**
     * Bentuk baku dari apa yang diketik, atau null bila belum terbaca.
     *
     * Sengaja hanya mengenali dua kata bakunya — jauh lebih longgar daripada
     * ContainerStatusNormalizer di server, yang tetap menjadi penentu akhir dan
     * masih menerjemahkan ejaan seperti "Container empty". Penjaga di layar ini
     * cukup mengarahkan petugas ke dua kata yang ada di daftar saran, bukan
     * menyalin ulang seluruh aturan server (yang pasti akan menyimpang).
     */
    function canonicalContainerStatus(value) {
        const typed = String(value ?? '').trim().toLowerCase();

        return CONTAINER_STATUSES.find(status => status.toLowerCase() === typed) ?? null;
    }

    // Baris yang jumlahnya nol tidak menggeser angka apa pun, jadi tidak perlu
    // ditahan — cukup baris yang benar-benar membawa muatan.
    function containerRowHasQuantity(input) {
        const row = input.closest('.body');
        const qty = row?.querySelector('input[name$="[qty_current]"]');

        return reportNumericValue(qty?.value) !== 0;
    }

    function validateContainerStatuses(options = {}) {
        const { enforce = statusInput?.value !== 'draft' } = options;
        let firstInvalid = null;

        containerStatusInputs().forEach(input => {
            const isUnreadable = enforce
                && containerRowHasQuantity(input)
                && canonicalContainerStatus(input.value) === null;

            input.setCustomValidity(isUnreadable ? containerStatusMessage : '');
            input.classList.toggle('is-invalid', isUnreadable);

            if (isUnreadable && !firstInvalid) firstInvalid = input;
        });

        return firstInvalid === null;
    }

    window.validateContainerStatuses = validateContainerStatuses;

    // Rapikan huruf begitu petugas selesai mengetik, supaya yang terlihat di
    // layar sama persis dengan yang tersimpan ("empty" -> "Empty").
    document.addEventListener('blur', event => {
        if (! event.target?.matches?.('[name^="unloading_containers_"][name$="[status]"]')) return;

        const canonical = canonicalContainerStatus(event.target.value);
        if (canonical !== null) event.target.value = canonical;

        validateContainerStatuses();
    }, true);

    // Didengarkan pada fase CAPTURE. Baris container bisa disusun ulang dan
    // di-clone oleh mekanisme "Tambah Baris"/"Kegiatan N", dan sebagian kendali
    // di dalam tabel menahan propagasi event-nya. Fase capture selalu dilewati
    // lebih dulu dan tidak bergantung pada bubbling, sehingga penandaan tetap
    // hidup tanpa perlu memasang listener ulang di tiap baris baru.
    ['change', 'input'].forEach(eventName => {
        document.addEventListener(eventName, event => {
            if (event.target?.matches?.('[name^="unloading_containers_"]')) {
                validateContainerStatuses();
            }
        }, true);
    });

    const bagLoadingDetailPrefixes = [
        'ship_operation_id',
        'ship_name',
        'agent',
        'jetty',
        'destination',
        'capacity',
        'wo_number',
        'cargo_type',
        'marking',
        'arrival_time',
        'operating_gang',
        'tkbm_count',
        'foreman',
    ];

    function isBagLoadingDetailControl(control) {
        const name = control?.getAttribute?.('name') || '';

        return bagLoadingDetailPrefixes.some(prefix => new RegExp(`^${prefix}_\\d+$`).test(name));
    }

    function hasBagLoadingDetails(row) {
        const pane = row?.closest?.('#step-muat-kantong .activity-pane');
        if (!pane) return true;

        return Array.from(pane.querySelectorAll('input, textarea, select'))
            .filter(isBagLoadingDetailControl)
            .some(control => String(control.value || '').trim() !== '');
    }

    function refreshPaneAccumulations(pane) {
        pane?.querySelectorAll?.('[name*="qty_current"], [name*="qty_prev"], [name*="_current_"], [name*="_prev_"]')
            .forEach(updateAccumulation);
    }

    function resetAccumulationSummaries(root) {
        root?.querySelectorAll?.('.form-card .accumulated').forEach(summary => {
            summary.textContent = '0';
        });
    }

    function clearRow(row) {
        delete row.dataset.op7Uid;
        delete row.dataset.absentNoForklift;
        delete row.dataset.absentWorkArea;
        delete row.dataset.replacementFor;
        delete row.dataset.replacementCreated;
        row.querySelector('.table-column.delete')?.style.removeProperty('visibility');
        row.querySelectorAll('.ship-operation-suggestions').forEach(dropdown => dropdown.remove());
        row.querySelectorAll('.ship-operation-handover').forEach(notice => {
            const status = notice.querySelector('.ship-operation-status');
            if (status) row.prepend(status);
            notice.remove();
        });
        row.querySelectorAll('[data-user-adjusted]').forEach(el => el.removeAttribute('data-user-adjusted'));
        resetAccumulationSummaries(row);

        row.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.type === 'hidden') {
                input.value = '';
                return;
            }

            if (input.type === 'radio') {
                input.checked = input.name.includes('ship_operation') ? false : input.value === 'Baik';
                return;
            }

            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
                return;
            }

            const name = input.getAttribute('name') || '';
            const keepReadonly = /\[(?:qty_prev|qty_total)]|_prev_|qty_accumulated/i.test(name);

            input.value = '';
            if (!keepReadonly) {
                input.readOnly = false;
                input.removeAttribute('readonly');
                input.classList.remove('is-auto-filled');
            }
        });
    }

    function updateIndexedAttributes(row, index) {
        row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
        });

        row.querySelectorAll('[id]').forEach(element => {
            element.id = element.id
                .replace(/_\d+_(baik|rusak)$/g, `_${index + 1}_$1`)
                .replace(/_\d+$/g, `_${index + 1}`);
        });

        row.querySelectorAll('label[for]').forEach(label => {
            label.setAttribute('for', label.getAttribute('for')
                .replace(/_\d+_(baik|rusak)$/g, `_${index + 1}_$1`)
                .replace(/_\d+$/g, `_${index + 1}`));
        });
    }

    function reindexTable(tableInput) {
        const materialPane = tableInput?.closest('#section-bahan-baku .activity-pane');
        if (materialPane && tableInput.closest('[data-material-package-group]')) {
            reindexMaterialPackageTables(materialPane);
            return;
        }

        rowsOf(tableInput).forEach((row, index) => {
            row.querySelectorAll('.table-column.no span').forEach(span => {
                span.textContent = index + 1;
            });
            updateIndexedAttributes(row, index);
        });
    }

    function addTableRow(button) {
        const tableInput = button.closest('.table-input');
        if (!tableInput) return;

        const rows = rowsOf(tableInput);
        const source = rows[rows.length - 1];
        if (!source) return;

        const clone = source.cloneNode(true);
        clearRow(clone);
        resetTableSelectHydration(clone);
        tableInput.insertBefore(clone, button);
        reindexTable(tableInput);
        syncMaterialPackageGroup(tableInput.closest('[data-material-package-group]'));
        applyMasterDatalists(clone);
        hydrateTableSelects(clone);
        initPickers(clone);

        if (tableInput.closest('#section-shift')) {
            applyShiftTimesToRow(clone);
        }

        if (tableInput.closest('#section-lembur')) {
            applyReliefAttendanceState(clone);
        }

        if (tableInput.closest('#section-op7') && !tableInput.closest('.red')) {
            const op7Rows = rowsOf(tableInput);
            const newIndex = op7Rows.indexOf(clone);
            applyOp7ForkliftDefaults(clone, newIndex);
            applyShiftTimesToRow(clone);
            syncOp7Replacements();
        }
    }

    function removeTableRow(button) {
        const tableInput = button.closest('.table-input');
        const row = button.closest('.body');
        if (!tableInput || !row) return;

        const isOp7Source = tableInput.closest('#section-op7') && !tableInput.closest('.red');

        const rows = rowsOf(tableInput);
        if (rows.length <= 1) {
            clearRow(row);
            syncMaterialPackageGroup(tableInput.closest('[data-material-package-group]'));
            if (isOp7Source) syncOp7Replacements();
            return;
        }

        row.remove();
        reindexTable(tableInput);
        syncMaterialPackageGroup(tableInput.closest('[data-material-package-group]'));
        if (isOp7Source) syncOp7Replacements();
    }

    function updateAccumulation(input) {
        const row = input.closest('.body, .form-card-content');
        if (!row) return;

        const canAccumulate = hasBagLoadingDetails(row);
        const current = canAccumulate ? reportNumericValue(row.querySelector('[name*="qty_current"], [name*="_current_"]')?.value) : 0;
        const previous = canAccumulate ? reportNumericValue(row.querySelector('[name*="qty_prev"], [name*="_prev_"]')?.value) : 0;
        const totalInput = row.querySelector('[name*="qty_total"], [name*="qty_accumulated"]');
        const summary = input.closest('.form-card')?.querySelector('.accumulated');
        const total = current + previous;

        if (totalInput) totalInput.value = total ? reportLocalizedNumber(total) : '';
        if (summary) summary.textContent = total ? reportLocalizedNumber(total) : '0';
        window.fitReportNumberDisplay?.(totalInput);
        window.fitReportNumberDisplay?.(summary);
        updateMaterialPackageSubtotal(row.closest('[data-material-package-group]'));
    }

    function replaceLastIndex(name, nextIndex) {
        const matches = [...name.matchAll(/\[\d+\]/g)];
        if (matches.length === 0) return name;

        const last = matches[matches.length - 1];
        return name.slice(0, last.index) + `[${nextIndex}]` + name.slice(last.index + last[0].length);
    }

    let timesheetRowId = 0;

    function initPickers(root = document) {
        if (typeof flatpickr === 'undefined') return;

        root.querySelectorAll('.time-picker-input').forEach(input => {
            if (input._flatpickr) return;
            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                allowInput: true,
                minuteIncrement: 1,
            });
        });
    }

    function normalizeDateTimeLocalValue(value) {
        const text = String(value || '').trim();
        if (!text) return '';

        const formatted = text.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
        if (formatted) return `${formatted[1]}T${formatted[2]}`;

        const numbers = text.replace(/\D/g, '').slice(0, 12);
        if (numbers.length < 12) return text;

        return `${numbers.slice(0, 4)}-${numbers.slice(4, 6)}-${numbers.slice(6, 8)}T${numbers.slice(8, 10)}:${numbers.slice(10, 12)}`;
    }

    function timesheetRows(content) {
        return Array.from(content?.children || []).filter(child => child.classList.contains('timesheet-input'));
    }

    function timelineSection(content) {
        return content?.querySelector(':scope > .timeline-section') || content?.querySelector('.timeline-section');
    }

    function timesheetPayload(row) {
        const time = row.querySelector('input[name$="[time]"]')?.value?.trim() || '';
        const activity = row.querySelector('input[name$="[activity]"]')?.value?.trim() || '';
        const cobInput = row.querySelector('input[name$="[cob]"]');
        const cob = cobInput?.value?.trim() || '';

        return { time, activity, cob, hasCob: Boolean(cobInput) };
    }

    function rowHasTimesheetData(row) {
        const payload = timesheetPayload(row);

        return payload.time !== '' || payload.activity !== '' || payload.cob !== '';
    }

    function isValidTimesheetTime(value) {
        return /^([01]\d|2[0-3]):[0-5]\d$/.test(String(value || '').trim());
    }

    function isValidTimesheetActivity(value) {
        const text = String(value || '').trim();

        return text.length >= 3 && /[A-Za-z]/.test(text);
    }

    function isValidTimesheetCob(value) {
        const text = String(value || '').trim();
        const number = Number(text);

        return text !== '' && Number.isFinite(number) && number >= 0;
    }

    function setTimesheetFieldInvalid(input, isInvalid) {
        if (!input) return;

        const wrapper = input.closest('.timesheet-input-wrapper, .cob-wrapper');
        (wrapper || input).classList.toggle('is-invalid', isInvalid);
    }

    // Jam tidak boleh melebihi 24:00; kelebihannya dibungkus ke jam nyata
    // (mis. digit "40" -> "16", 40 - 24 = 16). Dipakai oleh input .time-picker-input
    // dan .time-range-input di bawah.
    function wrapTimeHourDigits(digits) {
        if (digits.length < 2) return digits;

        const wrappedHour = String(Number(digits.substring(0, 2)) % 24).padStart(2, '0');

        return wrappedHour + digits.substring(2);
    }

    function clearTimesheetValidation(row) {
        if (!row) return;

        row.classList.remove('is-invalid');
        row.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
    }

    function showTimesheetValidationToast(message) {
        if (typeof window.showReportToast === 'function') {
            window.showReportToast('error', 'Input belum valid', message, 3600);
        }
    }

    function validateTimesheetRow(row) {
        const payload = timesheetPayload(row);
        const timeInput = row.querySelector('input[name$="[time]"]');
        const activityInput = row.querySelector('input[name$="[activity]"]');
        const cobInput = row.querySelector('input[name$="[cob]"]');
        const invalidFields = [];

        clearTimesheetValidation(row);

        if (!isValidTimesheetTime(payload.time)) {
            invalidFields.push({
                input: timeInput,
                message: 'Jam wajib diisi dengan format 24 jam, contoh 07:30 atau 15:45.',
            });
        }

        if (!isValidTimesheetActivity(payload.activity)) {
            invalidFields.push({
                input: activityInput,
                message: 'Aktivitas wajib berisi keterangan teks, bukan angka saja.',
            });
        }

        if (payload.hasCob && !isValidTimesheetCob(payload.cob)) {
            invalidFields.push({
                input: cobInput,
                message: 'COB wajib diisi dengan angka 0 atau lebih.',
            });
        }

        if (invalidFields.length === 0) return true;

        row.classList.add('is-invalid');
        invalidFields.forEach(field => setTimesheetFieldInvalid(field.input, true));
        invalidFields[0].input?.focus();
        showTimesheetValidationToast(invalidFields[0].message);

        return false;
    }

    function clearTimesheetRow(row) {
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            if (input._flatpickr) input._flatpickr.clear();
        });
        clearTimesheetValidation(row);
        row.classList.remove('d-none', 'timesheet-data-row');
        delete row.dataset.timesheetRowId;
    }

    function reindexTimesheetRows(content) {
        timesheetRows(content).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => {
                input.name = replaceLastIndex(input.name, index);
            });
        });
    }

    function prepareTimesheetRowId(row) {
        if (!row.dataset.timesheetRowId) {
            timesheetRowId += 1;
            row.dataset.timesheetRowId = `timesheet-${timesheetRowId}`;
        }

        return row.dataset.timesheetRowId;
    }

    function renderTimesheetTimelineItem(row) {
        const content = row.closest('.timesheet-content');
        const timeline = timelineSection(content);
        const payload = timesheetPayload(row);

        if (!timeline || !rowHasTimesheetData(row)) return;

        const rowId = prepareTimesheetRowId(row);
        const existingItem = timeline.querySelector(`[data-timesheet-row-id="${rowId}"]`);

        const cobLine = payload.hasCob
            ? `<span class="fsize-10 text-muted">COB: ${escapeHtml(payload.cob || '0')} Ton</span>`
            : '';
        const item = document.createElement('div');
        item.className = 'timeline-item';
        item.dataset.timesheetRowId = rowId;
        item.innerHTML = `
            <span class="dot"><i class="fi fi-sr-dot-circle"></i></span>
            <div class="content">
                <div class="description d-flex flex-column align-items-start flexible">
                    <span class="clock fsize-9">${escapeHtml(payload.time || '--:--')}</span>
                    <span class="fsize-12 fw-500">${escapeHtml(payload.activity || 'Tanpa keterangan')}</span>
                    ${cobLine}
                </div>
                <button type="button" class="btn-edit" title="Edit aktivitas"><i class="fi fi-rr-pencil"></i></button>
                <button type="button" class="btn-trash" title="Hapus aktivitas"><i class="fi fi-rr-trash"></i></button>
            </div>
        `;

        if (existingItem) {
            existingItem.replaceWith(item);
        } else {
            timeline.appendChild(item);
        }
    }

    function insertBlankTimesheetRow(content, sourceRow) {
        if (!content || !sourceRow) return null;

        const timeline = timelineSection(content);
        const clone = sourceRow.cloneNode(true);

        clearTimesheetRow(clone);
        content.insertBefore(clone, timeline || null);
        reindexTimesheetRows(content);
        applyMasterDatalists(clone);
        initPickers(clone);

        return clone;
    }

    function refreshTimesheetTimeline(content) {
        if (!content) return;

        timelineSection(content)?.querySelectorAll('.timeline-item').forEach(item => item.remove());

        let visibleBlankRow = null;

        timesheetRows(content).forEach(row => {
            if (rowHasTimesheetData(row)) {
                row.classList.add('d-none', 'timesheet-data-row');
                renderTimesheetTimelineItem(row);
                return;
            }

            if (!visibleBlankRow) {
                visibleBlankRow = row;
                row.classList.remove('d-none', 'timesheet-data-row');
                delete row.dataset.timesheetRowId;
                return;
            }

            row.remove();
        });

        const rows = timesheetRows(content);
        if (!visibleBlankRow && rows.length > 0) {
            insertBlankTimesheetRow(content, rows[rows.length - 1]);
        }

        reindexTimesheetRows(content);
    }

    function refreshAllTimesheetTimelines(root = document) {
        root.querySelectorAll('.timesheet-content').forEach(refreshTimesheetTimeline);
    }

    function resetTimesheetContent(root) {
        root.querySelectorAll('.timeline-section .timeline-item').forEach(item => item.remove());
        root.querySelectorAll('.timesheet-content').forEach(content => {
            const rows = timesheetRows(content);

            rows.forEach((row, index) => {
                if (index === 0) {
                    clearTimesheetRow(row);
                    return;
                }

                row.remove();
            });

            reindexTimesheetRows(content);
        });
    }

    function removeTimesheetTimelineItem(button) {
        const item = button.closest('.timeline-item');
        const content = item?.closest('.timesheet-content');
        const rowId = item?.dataset.timesheetRowId;

        if (!item || !content || !rowId) return;

        content.querySelector(`.timesheet-input[data-timesheet-row-id="${rowId}"]`)?.remove();
        item.remove();
        refreshTimesheetTimeline(content);
        syncPayload();
    }

    function cancelTimesheetEdit(content) {
        if (!content) return;
        const editingRow = content.querySelector('.timesheet-input.is-editing');
        if (!editingRow) return;

        const rowId = editingRow.dataset.timesheetRowId;
        editingRow.classList.remove('is-editing');
        editingRow.classList.add('d-none', 'timesheet-data-row');

        if (rowId) {
            timelineSection(content)?.querySelector(`[data-timesheet-row-id="${rowId}"]`)?.classList.remove('d-none');
        }
    }

    function startEditTimesheetTimelineItem(button) {
        const item = button.closest('.timeline-item');
        const content = item?.closest('.timesheet-content');
        const rowId = item?.dataset.timesheetRowId;
        if (!item || !content || !rowId) return;

        const editingRow = content.querySelector(`.timesheet-input[data-timesheet-row-id="${rowId}"]`);
        if (!editingRow) return;

        cancelTimesheetEdit(content);

        timesheetRows(content).forEach(row => {
            if (row !== editingRow && !rowHasTimesheetData(row)) row.remove();
        });

        const timeline = timelineSection(content);
        editingRow.classList.remove('d-none', 'timesheet-data-row');
        editingRow.classList.add('is-editing');
        clearTimesheetValidation(editingRow);

        if (timeline) content.insertBefore(editingRow, timeline);

        reindexTimesheetRows(content);

        item.classList.add('d-none');

        const activityInput = editingRow.querySelector('.activity-input');
        if (activityInput) {
            activityInput.focus();
            activityInput.select?.();
        }
    }

    function addTimesheetInput(button, options = {}) {
        const row = button.closest('.timesheet-input');
        if (!row || !row.parentElement) return;

        const content = row.parentElement;

        if (options.forceBlank) {
            insertBlankTimesheetRow(content, row);
            return;
        }

        if (!validateTimesheetRow(row)) {
            return;
        }

        const wasEditing = row.classList.contains('is-editing');
        row.classList.remove('is-editing');
        row.classList.add('d-none', 'timesheet-data-row');
        renderTimesheetTimelineItem(row);

        if (!wasEditing) {
            insertBlankTimesheetRow(content, row);
        } else {
            const hasBlankRow = timesheetRows(content).some(r => !rowHasTimesheetData(r));
            if (!hasBlankRow) {
                insertBlankTimesheetRow(content, row);
            }
        }
        syncPayload();
    }

    function setSequence(container, sequence) {
        container.dataset.sequence = sequence;

        container.querySelectorAll('[name]').forEach(input => {
            input.name = input.name
                .replace(/timesheets\[\d+\]/g, `timesheets[${sequence}]`)
                .replace(/bulk_logs\[\d+\]/g, `bulk_logs[${sequence}]`)
                .replace(/ammonia_logs\[\d+\]/g, `ammonia_logs[${sequence}]`)
                .replace(/unloading_materials_\d+(?=\[)/g, `unloading_materials_${sequence}`)
                .replace(/unloading_containers_\d+(?=\[)/g, `unloading_containers_${sequence}`)
                .replace(/_urea_\d+$/g, `_urea_${sequence}`)
                .replace(/_\d+$/g, `_${sequence}`);
        });

        container.querySelectorAll('[id]').forEach(element => {
            element.id = element.id
                .replace(/_urea_\d+$/g, `_urea_${sequence}`)
                .replace(/-\d+$/g, `-${sequence}`)
                .replace(/_\d+$/g, `_${sequence}`);
        });

        container.querySelectorAll('label[for]').forEach(label => {
            label.setAttribute('for', label.getAttribute('for')
                .replace(/_urea_\d+$/g, `_urea_${sequence}`)
                .replace(/-\d+$/g, `-${sequence}`)
                .replace(/_\d+$/g, `_${sequence}`));
        });
    }

    function updateLoadingQty(input) {
        const wrapper = input.closest('.shipment-details');
        if (!wrapper) return;

        const receivedInput = wrapper.querySelector('.cob-received-input');
        const deliveredInput = wrapper.querySelector('.cob-delivered-input');
        const qtyInput = wrapper.querySelector('.loading-qty-input');
        if (!receivedInput || !deliveredInput || !qtyInput) return;

        const received = reportNumericValue(receivedInput.value);
        const delivered = reportNumericValue(deliveredInput.value);
        const qty = Math.max(0, delivered - received);

        qtyInput.value = (receivedInput.value === '' && deliveredInput.value === '')
            ? ''
            : reportLocalizedNumber(qty);
        window.fitReportNumberDisplay?.(qtyInput);
    }

    // COB pada Laporan Harian adalah pembacaan kumulatif — entri terakhir yang
    // sudah terisi otomatis dipakai sebagai COB Diserahkan, tapi petugas boleh
    // menimpanya secara manual kapan saja (ditandai lewat data-user-adjusted).
    function updateCobDeliveredFromLogs(logInput) {
        const match = logInput.name.match(/^(bulk_logs|ammonia_logs)\[(\d+)]/);
        if (!match) return;

        const prefix = match[1] === 'bulk_logs' ? 'urea' : 'ammonia';
        const sequence = match[2];
        const pane = logInput.closest('.activity-pane') || document;
        const deliveredInput = namedControl(pane, `cob_delivered_${prefix}_${sequence}`);
        if (!deliveredInput) return;

        const wrapper = deliveredInput.closest('.form-group');
        if (wrapper?.dataset.userAdjusted === 'true') return;

        const logInputs = pane.querySelectorAll(`[name^="${match[1]}[${sequence}]"][name$="[cob]"]`);
        let lastValue = null;
        logInputs.forEach(control => {
            const value = String(control.value ?? '').trim();
            if (value !== '') lastValue = value;
        });

        if (lastValue === null || lastValue === deliveredInput.value) return;

        setControlValue(deliveredInput, lastValue);
    }

    function showActivity(section, sequence) {
        section.querySelectorAll('.btn-activity').forEach(tab => {
            const isActive = Number(tab.dataset.sequence) === sequence;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;
        });
        section.querySelectorAll('.activity-pane').forEach(pane => {
            const isActive = Number(pane.dataset.sequence) === sequence;
            pane.classList.toggle('d-none', !isActive);
            pane.classList.toggle('d-flex', isActive);
            pane.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    function createActivityTab(section, tabBar, plusMinus, sequence) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-activity';
        button.dataset.sequence = sequence;
        button.textContent = `Kegiatan ${sequence}`;
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-selected', 'false');
        button.tabIndex = -1;
        button.addEventListener('click', () => showActivity(section, sequence));
        tabBar.insertBefore(button, plusMinus);
        return button;
    }

    function positionShipOperationStatus(pane) {
        const status = pane?.querySelector('.ship-operation-status');
        if (!status) return;

        status.setAttribute('role', 'group');
        status.setAttribute('aria-label', 'Status pekerjaan kapal');

        const handoverActions = pane.querySelector('.ship-operation-handover__actions');
        if (handoverActions) {
            handoverActions.appendChild(status);
        } else {
            pane.prepend(status);
        }

        syncHandoverStatus(pane);
    }

    function initActivitySection(target) {
        // `target` boleh berupa id string (dipakai section dengan .content-form
        // & .box-button sendiri, mis. Muat Kantong/Curah) atau elemen langsung
        // (dipakai sub-tab yang berbagi .box-button dgn sub-tab lain, mis. Bongkar
        // Bahan Baku/Container — batasnya ditandai elemen `.activity-pane-end`).
        const section = typeof target === 'string' ? document.getElementById(target) : target;
        if (!section) return;

        const content = section.querySelector(':scope > .content-form') || section;
        const tabBar = section.querySelector('.tab-activity');
        const plusMinus = tabBar?.querySelector('.plus-minus-tab');
        const addButton = plusMinus?.querySelector('.btn.add');
        const removeButton = plusMinus?.querySelector('.btn.remove');
        const buttonRow = content.querySelector('.box-button') || content.querySelector('.activity-pane-end');

        if (!content || !tabBar || !plusMinus || !buttonRow) return;

        tabBar.querySelectorAll('.btn-activity').forEach(tab => tab.remove());

        const pane = document.createElement('div');
        pane.className = 'activity-pane d-flex flex-column align-items-start align-self-stretch';
        pane.dataset.sequence = 1;

        let current = tabBar.parentElement.nextElementSibling;
        while (current && current !== buttonRow) {
            const next = current.nextElementSibling;
            pane.appendChild(current);
            current = next;
        }

        content.insertBefore(pane, buttonRow);
        setSequence(pane, 1);
        reindexMaterialPackageTables(pane);
        positionShipOperationStatus(pane);
        createActivityTab(section, tabBar, plusMinus, 1).classList.add('active');
        showActivity(section, 1);

        addButton?.addEventListener('click', () => {
            const panes = section.querySelectorAll('.activity-pane');
            const source = Array.from(panes).find(item => !item.classList.contains('d-none')) || panes[panes.length - 1];
            const sequence = panes.length + 1;
            const clone = source.cloneNode(true);

            clearRow(clone);
            resetTimesheetContent(clone);
            setSequence(clone, sequence);
            resetMaterialPackageRows(clone);
            clone.classList.add('d-none');
            clone.classList.remove('d-flex');
            content.insertBefore(clone, buttonRow);
            createActivityTab(section, tabBar, plusMinus, sequence);
            applyMasterDatalists(clone);
            prepareShipOperationFields(clone);
            initPickers(clone);
            initializeMaterialPackageGroups(clone);
            positionShipOperationStatus(clone);
            showActivity(section, sequence);
        });

        removeButton?.addEventListener('click', () => {
            const panes = Array.from(section.querySelectorAll('.activity-pane'));
            if (panes.length <= 1) return;

            const activePane = panes.find(item => !item.classList.contains('d-none')) || panes[panes.length - 1];
            const activeSequence = Number(activePane.dataset.sequence);
            activePane.remove();
            section.querySelector(`.btn-activity[data-sequence="${activeSequence}"]`)?.remove();

            const remainingPanes = Array.from(section.querySelectorAll('.activity-pane'));
            remainingPanes.forEach((paneItem, index) => {
                const newSequence = index + 1;
                setSequence(paneItem, newSequence);
                reindexMaterialPackageTables(paneItem);
                const tab = section.querySelectorAll('.btn-activity')[index];
                if (tab) {
                    tab.dataset.sequence = newSequence;
                    tab.textContent = `Kegiatan ${newSequence}`;
                }
            });

            showActivity(section, Math.max(1, activeSequence - 1));
        });
    }

    function carrySection(type) {
        return {
            muat_kantong: document.getElementById('step-muat-kantong'),
            muat_curah: document.getElementById('step-muat-curah'),
            muat_amoniak: document.getElementById('step-muat-amoniak'),
            bongkar_bahan_baku: document.getElementById('section-bahan-baku'),
            container: document.getElementById('section-container'),
        }[type] || null;
    }

    function renderCarryForwardNotice(pane, item) {
        if (!pane || !item?.handover) return;

        pane.querySelector('.ship-operation-handover')?.remove();

        const notice = document.createElement('div');
        notice.className = 'ship-operation-handover';
        notice.innerHTML = `
            <span class="ship-operation-handover__icon"><i class="fi fi-rr-exchange"></i></span>
            <span class="ship-operation-handover__copy">
                <strong>Dibawa dari shift sebelumnya</strong>
                <span>${escapeHtml(item.handover.document_id || 'Laporan sebelumnya')} &bull; Shift ${escapeHtml(item.handover.shift || '-')} &bull; Regu ${escapeHtml(item.handover.group || '-')}</span>
            </span>
            <div class="ship-operation-handover__actions">
                <span class="ship-operation-handover__state" data-state="pending">Perlu dikonfirmasi</span>
            </div>
        `;
        pane.prepend(notice);
    }

    function syncHandoverStatus(pane) {
        const handover = pane?.querySelector('.ship-operation-handover');
        const state = handover?.querySelector('.ship-operation-handover__state');
        const checked = handover?.querySelector('.ship-operation-status input:checked');
        if (!handover || !state) return;

        const status = checked?.value || 'pending';
        state.dataset.state = status;
        state.textContent = status === 'active'
            ? 'Masih berjalan'
            : (status === 'completed' ? 'Selesai' : 'Perlu dikonfirmasi');
    }

    function hydrateSavedCarryForwardNotices() {
        if (!savedFormPayload || !Array.isArray(carryForwardOperations) || carryForwardOperations.length === 0) return;

        const operations = new Map(carryForwardOperations.map(item => [`${item.type}:${item.id}`, item]));

        document.querySelectorAll('.activity-pane input[name^="ship_name_"]').forEach(input => {
            const pane = input.closest('.activity-pane');
            const config = shipOperationConfig(input);
            const operationId = Number(namedControl(pane, config?.idName)?.value || 0);
            const item = operations.get(`${config?.type}:${operationId}`);

            if (!pane || !config || !item) return;

            renderCarryForwardNotice(pane, item);
            positionShipOperationStatus(pane);
        });
    }

    function hydrateCarryForwardOperations() {
        if (!Array.isArray(carryForwardOperations) || carryForwardOperations.length === 0 || savedFormPayload) return;

        const positions = {};

        carryForwardOperations.forEach(item => {
            const section = carrySection(item.type);
            if (!section) return;

            const position = positions[item.type] || 0;
            positions[item.type] = position + 1;

            while (section.querySelectorAll('.activity-pane').length <= position) {
                section.querySelector('.plus-minus-tab .btn.add')?.click();
            }

            const pane = section.querySelectorAll('.activity-pane')[position];
            const input = Array.from(pane?.querySelectorAll('input[name^="ship_name_"]') || [])
                .find(control => shipOperationConfig(control)?.type === item.type);

            if (!input) return;

            applyShipOperation(input, item);
            renderCarryForwardNotice(pane, item);
            positionShipOperationStatus(pane);
        });
    }

    /**
     * Penambahan pane saat memulihkan draft/handover sengaja membuka pane yang
     * baru dibuat agar proses pengisian internalnya selesai. Setelah seluruh
     * data terpasang, kembalikan tampilan awal ke Kegiatan 1. Perilaku tombol
     * tambah manual tidak memakai fungsi ini, sehingga petugas tetap langsung
     * diarahkan ke kegiatan baru yang hendak diisi.
     */
    function showFirstActivityOnInitialLoad() {
        [
            'step-muat-kantong',
            'step-muat-curah',
            'step-muat-amoniak',
            'section-bahan-baku',
            'section-container',
        ].forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (!section || section.querySelectorAll('.activity-pane').length <= 1) return;

            showActivity(section, 1);
        });
    }

    function setAllGood() {
        document.querySelectorAll('input[type="radio"][value="Baik"]').forEach(input => {
            input.checked = true;
        });
        document.querySelectorAll('select').forEach(select => {
            const baikOption = Array.from(select.options).find(option => option.value === 'Baik');
            if (baikOption) {
                select.value = 'Baik';
                select.dispatchEvent(new Event('change'));
            }
        });
    }

    createDatalist('master-employee-list', flattenEmployeeNames());
    createDatalist('master-relief-list', reliefEmployees().map(employee => employee.name));
    createDatalist('master-truck-list', (masterTrucks || []).flatMap(truck => [truck.name, truck.plate_number]));
    createDatalist('master-unit-list', (masterVehicles || []).map(vehicle => vehicle.name));
    // Sugesti nomor unit: cukup nomor kodenya saja (mis. "FL-15", "TRL-15"),
    // tanpa awalan jenis ("Forklift ..."/"Trailer ...").
    const unitNumbersByCode = (codes) => {
        const wanted = codes.map(code => code.toUpperCase());
        return (masterVehicles || [])
            .filter(vehicle => wanted.includes(String(vehicle.unit_code || '').toUpperCase()))
            .map(vehicle => String(vehicle.unit_number || '').trim() || String(vehicle.name || '').trim())
            .filter(Boolean);
    };
    createDatalist('master-trucknum-list', unitNumbersByCode(['TRL', 'TRT']));
    createDatalist('master-forklift-list', unitNumbersByCode(['FL']));
    createDatalist('master-inventory-list', (masterInventories || []).map(item => item.name));

    setTodayDate();
    clearTemplateValues();
    applyDefaultGroup();
    rebuildRoleDatalists();
    rebuildEmployeeDatalists();
    applyDefaultShiftByWita();
    if (!isEditMode || !document.querySelector('[name="time_range"]')?.value) {
        syncTimeRangeWithShift();
    }
    renderMasterCheckRows();
    renderMasterTruckRows();
    renderEmployeeShiftRows();
    renderOp7Rows();
    hydrateTableSelects();
    applyShiftTimesToEmployeeRows();
    applyMasterDatalists();
    prepareShipOperationFields();
    initPickers();
    initJamKerjaMask();
    initActivitySection('step-muat-kantong');
    initActivitySection('step-muat-curah');
    initActivitySection('step-muat-amoniak');
    initActivitySection(document.getElementById('section-bahan-baku'));
    initActivitySection(document.getElementById('section-container'));
    hydrateCarryForwardOperations();
    document.querySelectorAll('.cob-received-input, .cob-delivered-input').forEach(updateLoadingQty);
    restoreSavedPayload();
    initializeMaterialPackageGroups();
    initMaterialPackageModal();
    hydrateSavedCarryForwardNotices();
    showFirstActivityOnInitialLoad();
    applyAbsenceStateToEmployeeRows();
    syncOp7Replacements();
    syncPayload();
    refreshAllTimesheetTimelines();
    validateReportGroupRoute({ enforce: false });

    document.querySelector('[name="group_name"]')?.addEventListener('change', event => {
        renderEmployeeShiftRows(event.target.value);
        renderOp7Rows(event.target.value);
        rebuildRoleDatalists(event.target.value);
        rebuildEmployeeDatalists(event.target.value);
    });

    document.querySelector('[name="shift"]')?.addEventListener('change', () => {
        syncTimeRangeWithShift();
        applyShiftTimesToEmployeeRows();
    });

    document.querySelector('[name="time_range"]')?.addEventListener('change', () => {
        applyShiftTimesToEmployeeRows();
    });

    document.querySelectorAll('[name="group_name"], [name="received_by_group"]').forEach(select => {
        select.addEventListener('change', () => validateReportGroupRoute({ enforce: false, showToast: true }));
    });

    saveDraftButton?.addEventListener('click', () => submitAs('draft'));

    form?.addEventListener('change', event => {
        if (event.target.matches('input[type="radio"][name*="ship_operation"][name*="status"]')) {
            syncHandoverStatus(event.target.closest('.activity-pane'));
        }
    });

    form?.addEventListener('submit', () => {
        initializeMaterialPackageGroups();
        window.normalizeReportNumberInputs?.();
        syncPayload();
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.ship-operation-field')) {
            closeShipOperationDropdowns();
        }

        if (event.target.matches('input') && shipOperationConfig(event.target)) {
            operationDropdownFor(event.target);
            fetchShipOperationSuggestions(event.target);
            return;
        }

        const packageToggle = event.target.closest('[data-material-package-toggle]');
        if (packageToggle) {
            event.preventDefault();
            toggleMaterialPackageGroup(packageToggle.closest('[data-material-package-group]'));
            return;
        }

        // Area kosong kepala kelompok ikut menjadi pemicu; kontrol di dalamnya
        // sudah ditandai data-noprop.
        const packageHead = event.target.closest('[data-material-package-head]');
        if (packageHead && ! event.target.closest('[data-noprop]')) {
            toggleMaterialPackageGroup(packageHead.closest('[data-material-package-group]'));
            return;
        }

        const addPackageButton = event.target.closest('[data-material-package-add]');
        if (addPackageButton) {
            event.preventDefault();
            addMaterialPackageGroup(addPackageButton);
            syncPayload();
            return;
        }

        const removePackageButton = event.target.closest('[data-material-package-remove]');
        if (removePackageButton) {
            event.preventDefault();
            removeMaterialPackageGroup(removePackageButton.closest('[data-material-package-group]'));
            syncPayload();
            return;
        }

        const addRowButton = event.target.closest('.btn-tambah-baris');
        if (addRowButton) {
            event.preventDefault();
            addTableRow(addRowButton);
            return;
        }

        const deleteButton = event.target.closest('.btn-trash-row');
        if (deleteButton) {
            event.preventDefault();
            removeTableRow(deleteButton);
            return;
        }

        const timesheetEditButton = event.target.closest('.timeline-item .btn-edit');
        if (timesheetEditButton) {
            event.preventDefault();
            startEditTimesheetTimelineItem(timesheetEditButton);
            return;
        }

        const timesheetDeleteButton = event.target.closest('.timeline-item .btn-trash');
        if (timesheetDeleteButton) {
            event.preventDefault();
            removeTimesheetTimelineItem(timesheetDeleteButton);
            return;
        }

        const timesheetButton = event.target.closest('.btn-add-activity');
        if (timesheetButton) {
            event.preventDefault();
            addTimesheetInput(timesheetButton);
            return;
        }

        if (event.target.closest('.set-all-good')) {
            event.preventDefault();
            setAllGood();
        }
    });

    document.addEventListener('mousemove', handleShipOperationPointerMove);

    document.addEventListener('keydown', function (event) {
        if (handleTimesheetEnterAction(event)) return;
        handleFormEnterNavigation(event);
    });

    document.addEventListener('focusin', function (event) {
        if (event.target.matches('input') && shipOperationConfig(event.target)) {
            operationDropdownFor(event.target);
            fetchShipOperationSuggestions(event.target);
        }

        if (event.target.matches('input[data-suggest]')) {
            if (event.target.dataset.suggestApplying === 'true') return;
            openSuggestFor(event.target);
        }
    });

    // ===== Event wiring autocomplete kustom multi-nilai =====
    document.addEventListener('mousedown', function (event) {
        const option = event.target.closest('.kss-suggest-option');
        if (option && suggestActiveInput) {
            event.preventDefault(); // pertahankan fokus input
            applySuggestValue(suggestActiveInput, option.dataset.value || option.textContent.trim());
            return;
        }
        if (!event.target.closest('input[data-suggest]') && !event.target.closest('#' + SUGGEST_DROPDOWN_ID)) {
            closeSuggestDropdown();
        }
    });

    // Navigasi keyboard (capture agar mendahului navigasi Enter antar-field).
    document.addEventListener('keydown', function (event) {
        const dropdown = document.getElementById(SUGGEST_DROPDOWN_ID);
        if (!suggestActiveInput || !dropdown || !dropdown.classList.contains('show')) return;
        if (event.target !== suggestActiveInput) return;

        if (event.key === 'ArrowDown') { event.preventDefault(); highlightSuggest(1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); highlightSuggest(-1); }
        else if (event.key === 'Enter') {
            const active = dropdown.querySelector('.kss-suggest-option.active') || dropdown.querySelector('.kss-suggest-option');
            if (active) { event.preventDefault(); event.stopPropagation(); applySuggestValue(suggestActiveInput, active.dataset.value); }
        } else if (event.key === 'Escape') {
            closeSuggestDropdown();
        }
    }, true);

    document.addEventListener('focusout', function (event) {
        if (!event.target.matches('input[data-suggest]')) return;
        setTimeout(() => {
            if (suggestActiveInput === event.target) closeSuggestDropdown();
        }, 120);
    });

    window.addEventListener('scroll', () => { if (suggestActiveInput) closeSuggestDropdown(); }, true);
    window.addEventListener('resize', () => { if (suggestActiveInput) closeSuggestDropdown(); });

    document.addEventListener('change', function (event) {
        if (event.target.matches('input[type="radio"][name*="[condition_handed_over]"]') && event.isTrusted) {
            event.target.closest('.radio-group-custom')?.setAttribute('data-user-adjusted', 'true');
        }

        if (event.target.matches('input[type="radio"][name*="[condition_received]"]')) {
            syncHandedOverWithReceived(event.target);
        }

        if (event.target.matches('[name="group_name"], [name="received_by_group"]')) {
            validateReportGroupRoute({ enforce: false, showToast: true });
        }

        if (event.target.matches('[data-material-package-select]')) {
            const group = event.target.closest('[data-material-package-group]');

            if (isMaterialPackageNewOption(event.target.options[event.target.selectedIndex])) {
                openMaterialPackageModal(group);

                return;
            }

            syncMaterialPackageGroup(group);
            refreshMaterialPackageOptions(group);
        }

        if (isBagLoadingDetailControl(event.target)) {
            refreshPaneAccumulations(event.target.closest('.activity-pane'));
        }

        if (event.target.matches('[name="group_name"]')) {
            renderEmployeeShiftRows(event.target.value);
            renderOp7Rows(event.target.value);
            syncOp7Replacements();
        }

        if (event.target.matches('[name="shift"]')) {
            syncTimeRangeWithShift();
            applyShiftTimesToEmployeeRows();
            syncOp7Replacements();
        }

        if (event.target.matches('[name="time_range"]')) {
            applyShiftTimesToEmployeeRows();
            syncOp7Replacements();
        }

        if (event.target.matches('[name^="employee_shift_logs"][name$="[description]"], [name^="op7_logs"][name$="[description]"]')) {
            applyShiftTimesToRow(event.target.closest('.body'));
        }

        if (event.target.matches('input[name^="relief_logs"][name$="[attendance_status]"]')) {
            applyReliefAttendanceState(event.target.closest('.body'));
        }

        if (event.target.matches('[name^="op7_logs"][name$="[description]"]')) {
            syncOp7Replacements();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (!materialPackageModalElement()?.classList.contains('show')) return;

        event.preventDefault();
        closeMaterialPackageModal();
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('input') && shipOperationConfig(event.target)) {
            if (event.target.dataset.applyingOperation === 'true') return;
            clearShipOperationSelection(event.target);
            fetchShipOperationSuggestions(event.target);
        }

        if (isBagLoadingDetailControl(event.target)) {
            refreshPaneAccumulations(event.target.closest('.activity-pane'));
        }

        if (event.target.matches('.cob-received-input, .cob-delivered-input')) {
            if (event.target.matches('.cob-delivered-input') && event.isTrusted) {
                event.target.closest('.form-group')?.setAttribute('data-user-adjusted', 'true');
            }
            updateLoadingQty(event.target);
        }

        if (event.isTrusted && event.target.matches('[name^="bulk_logs["][name$="[cob]"], [name^="ammonia_logs["][name$="[cob]"]')) {
            updateCobDeliveredFromLogs(event.target);
        }

        const timesheetRow = event.target.closest('.timesheet-input');
        if (timesheetRow) {
            clearTimesheetValidation(timesheetRow);
        }

        if (event.target.matches('.time-picker-input')) {
            const original = event.target.value;
            const numbers = original.replace(/\D/g, '').slice(0, 4);
            // Jam tidak boleh melebihi 24:00; kelebihannya dibungkus ke jam nyata
            // (mis. ketik "40:00" -> otomatis jadi "16:00", 40 - 24 = 16).
            const wrapped = numbers.length >= 2 ? wrapTimeHourDigits(numbers) : numbers;
            event.target.value = wrapped.length > 2 ? `${wrapped.slice(0, 2)}:${wrapped.slice(2)}` : wrapped;
        }

        if (event.target.matches('input[data-suggest]')) {
            openSuggestFor(event.target);
        }

        // Jam Kerja rentang (satu input, mis. "23:00 - 04:00"): pengguna cukup
        // ketik angkanya saja, simbol ":" dan " - " otomatis disisipkan. Tiap
        // segmen jam dibungkus ke jam nyata bila lebih dari 24:00.
        if (event.target.matches('.time-range-input')) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 8);
            const startHour = digits.length >= 2 ? wrapTimeHourDigits(digits.slice(0, 2)) : digits.slice(0, 2);
            const endHour = digits.length >= 6 ? wrapTimeHourDigits(digits.slice(4, 6)) : digits.slice(4, 6);
            let formatted = startHour;
            if (digits.length > 2) formatted += ':' + digits.slice(2, 4);
            if (digits.length > 4) formatted += ' - ' + endHour;
            if (digits.length > 6) formatted += ':' + digits.slice(6, 8);
            event.target.value = formatted;
        }

        if (event.target.matches('[name*="qty_current"], [name*="qty_prev"], [name*="_current_"], [name*="_prev_"]')) {
            updateAccumulation(event.target);
        }

        if (event.target.matches('input[name^="op7_logs"][name$="[no_forklift_]"], input[name^="op7_logs"][name$="[work_area]"]')) {
            syncOp7Replacements();
        }

        // Keterangan OP.7 kini berupa input teks (bisa diketik manual / pilih dari datalist).
        // Perbarui status absensi & sinkronisasi baris pengganti saat diketik.
        if (event.target.matches('input[name^="op7_logs"][name$="[description]"]')) {
            applyShiftTimesToRow(event.target.closest('.body'));
            syncOp7Replacements();
        }

        // Keterangan Karyawan Shift juga input teks bebas: perbarui status absensi
        // (mengosongkan Masuk/Pulang) saat diketik, sama seperti OP.7.
        if (event.target.matches('input[name^="employee_shift_logs"][name$="[description]"]')) {
            applyShiftTimesToRow(event.target.closest('.body'));
        }

        if (event.target.matches('input[name^="relief_logs"][name$="[attendance_status]"]')) {
            applyReliefAttendanceState(event.target.closest('.body'));
        }
    });
});
</script>
@endpush

@section('content')
    <!-- MAIN APP WRAPPER -->
    <form id="mainReportForm" action="{{ $formAction }}" method="POST" class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content" data-discard-blank-url="{{ $discardBlankUrl ?? '' }}" data-store-url="{{ route('report-ops.store') }}">
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif
        <input type="hidden" name="status" id="reportStatus" value="submitted">
        <input type="hidden" name="form_payload" id="formPayload">

        <!-- STICKY HEADER (DINAMIS) -->
        <div class="content-header">
            <div class="title-header">
                <span class="text-header fw-600 fsize-20">{{ $headerTitle }}</span>
                <span class="note fw-300 fsize-12 text-secondary">ID: {{ $headerDocumentLabel }}</span>
            </div>
            <button type="button" id="btnSaveDraft" class="btn-new report-primary-action d-flex justify-content-center align-items-center gap-10">
                <div class="icon-new"><i class="fi fi-rr-disk"></i></div>
                <span class="btn-text fw-500">{{ $draftButtonLabel }}</span>
            </button>
        </div>

        <!-- MAIN TABS NAVIGATION -->
        <div class="tab-form">
            <div class="list-form-tab active" data-target="step-info-umum">
                <span class="icon-tab"><i class="fi fi-rr-document"></i></span>
                <span>Info Umum</span>
            </div>
            <div class="list-form-tab" data-target="step-muat-kantong">
                <span class="icon-tab"><i class="fi fi-rr-bag-seedling"></i></span>
                <span>Muat Kantong</span>
            </div>
            <div class="list-form-tab" data-target="step-muat-curah">
                <span class="icon-tab"><i class="fi fi-rr-truck-loading"></i></span>
                <span>Muat Curah</span>
            </div>
            <div class="list-form-tab" data-target="step-muat-amoniak">
                <span class="icon-tab"><i class="fi fi-rr-flask"></i></span>
                <span>Muat Amoniak</span>
            </div>
            <div class="list-form-tab" data-target="step-bongkar">
                <span class="icon-tab"><i class="fi fi-rr-box-open"></i></span>
                <span>Bongkar</span>
            </div>
            <div class="list-form-tab" data-target="step-gudang-turba">
                <span class="icon-tab"><i class="fi fi-rr-warehouse-alt"></i></span>
                <span>Tracking</span>
            </div>
            <div class="list-form-tab" data-target="step-cek-unit">
                <span class="icon-tab"><i class="fi fi-rr-pulse"></i></span>
                <span>Cek Unit</span>
            </div>
            <div class="list-form-tab" data-target="step-karyawan">
                <span class="icon-tab"><i class="fi fi-rr-employee-man"></i></span>
                <span>Karyawan</span>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- STEP 1: INFO UMUM                          -->
    <!-- ========================================== -->
    @include('report-ops.sections.step1-infoumum')

    <!-- ========================================== -->
    <!-- STEP 2: MUAT KANTONG                       -->
    <!-- ========================================== -->
    @include('report-ops.sections.step2-muatkantong')

    <!-- ========================================== -->
    <!-- STEP 3: MUAT CURAH                         -->
    <!-- ========================================== -->
    @include('report-ops.sections.step3-muatcurah')

    <!-- ========================================== -->
    <!-- STEP 4: MUAT AMONIAK                       -->
    <!-- ========================================== -->
    @include('report-ops.sections.step4-muatamoniak')

    <!-- ========================================== -->
    <!-- STEP 5: BONGKAR                            -->
    <!-- ========================================== -->
    @include('report-ops.sections.step4-bongkar')

    <!-- ========================================== -->
    <!-- STEP 6: TRACKING                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step5-gudangturba')

    <!-- ========================================== -->
    <!-- STEP 7: CEK UNIT                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step6-cekunit')

    <!-- ========================================== -->
    <!-- STEP 8: KARYAWAN                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step7-karyawan')

    </form>
@endsection

@push('modals')
    {{-- KEMASAN TAMBAHAN — didaftarkan petugas saat kemasan yang dibongkar
         belum ada pada katalog. Berlaku untuk laporan yang sedang diisi saja,
         jadi tidak ada data master yang berubah dari sini. --}}
    <div class="modal-overlay" id="materialPackageModal" role="dialog" aria-modal="true" aria-labelledby="materialPackageModalTitle">
        <div class="pop-up signed d-flex flex-column gap-20">
            <div class="pop-up-header d-flex justify-content-between align-items-center">
                <span class="fw-600 fsize-16" id="materialPackageModalTitle">Tambah Kemasan Baru</span>
                <button type="button" class="btn-close-modal border-0 bg-transparent text-muted" aria-label="Tutup"><i class="fi fi-br-cross fsize-10"></i></button>
            </div>

            <div class="pop-up-content d-flex flex-column gap-15">
                <div class="box-input-1">
                    <div class="box-label-1"><label for="materialPackageName">Nama Kemasan</label></div>
                    <div class="input-wrapper">
                        <input type="text" id="materialPackageName" class="custom-input" maxlength="100" placeholder="Contoh: Bag 40 Kg" autocomplete="off">
                    </div>
                </div>

                <div class="box-input-1">
                    <div class="box-label-1"><label for="materialPackageBags">Perbandingan Bag dan Ton</label></div>
                    <div class="material-package-ratio">
                        <div class="input-wrapper">
                            <input type="number" id="materialPackageBags" class="custom-input" min="0" step="any" inputmode="decimal" value="1" aria-label="Jumlah Bag">
                            <span class="input-icon">Bag</span>
                        </div>
                        <span class="material-package-ratio__equals" aria-hidden="true">=</span>
                        <div class="input-wrapper">
                            <input type="number" id="materialPackageTons" class="custom-input" min="0" step="any" inputmode="decimal" value="1" aria-label="Jumlah Ton">
                            <span class="input-icon">Ton</span>
                        </div>
                    </div>
                    <p class="material-package-ratio__help">Contoh: <strong>40 Bag = 1 Ton</strong> untuk kemasan kecil, atau <strong>1 Bag = 1,5 Ton</strong> untuk jumbo bag.</p>
                </div>

                <div class="material-package-preview" data-material-package-preview aria-live="polite">
                    <span>Setiap 1 Bag dihitung</span>
                    <strong data-material-package-preview-value>1 Ton</strong>
                </div>

                <p class="material-package-scope">Kemasan ini dipakai pada laporan yang sedang diisi saja dan tidak menambah daftar kemasan tetap.</p>

                <div class="material-package-error d-none" role="alert" data-material-package-error></div>
            </div>

            <div class="pop-up footer d-flex justify-content-end gap-10">
                <button type="button" class="btn cancel btn-close-modal">Batal</button>
                <button type="button" class="btn confirm" data-material-package-save>
                    <i class="fi fi-rr-box-open me-1"></i> Simpan Kemasan
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI (REFERENSI GAYA DASHBOARD) -->
    @php
        $finishReceiverGroup = strtoupper((string) old('received_by_group', isset($report) ? $report->received_by_group : ''));
    @endphp
    <div class="modal-overlay" id="finishModal" data-day-count-url="{{ route('report-ops.day-report-count') }}" data-report-id="{{ isset($report) ? $report->id : '' }}">
        <div class="pop-up signed d-flex flex-column gap-20">
            <div class="pop-up-header d-flex justify-content-between align-items-center">
                <span class="fw-600 fsize-16">Konfirmasi Penyelesaian</span>
                <button type="button" class="btn-close-modal border-0 bg-transparent text-muted"><i class="fi fi-br-cross fsize-10"></i></button>
            </div>

            <div class="pop-up-content d-flex flex-column gap-15">
                <div class="pop-up detail d-flex align-items-center">
                    <span class="icon-document"><i class="fi fi-sr-assept-document"></i></span>
                    <div class="d-flex flex-column">
                        <span class="fw-600 fsize-14">Kirim Laporan Sekarang?</span>
                        <span class="fsize-10 text-secondary">ID: {{ $headerDocumentLabel }}</span>
                    </div>
                </div>
                <p class="fsize-12 text-muted m-0">
                    Laporan ini akan dikirim ke <span class="fw-600" data-finish-receiver-label>{{ $finishReceiverGroup !== '' ? 'Regu '.$finishReceiverGroup : 'regu penerima yang dipilih' }}</span> untuk diterima dan ditandatangani. Setelah diterima, laporan akan diteruskan ke manajer.
                </p>
                <section class="ship-operation-review" aria-labelledby="ship-operation-review-title">
                    <div class="ship-operation-review__heading">
                        <span id="ship-operation-review-title">Konfirmasi status operasi kapal</span>
                        <span class="ship-operation-review__count" data-operation-review-count></span>
                    </div>
                    <div class="ship-operation-review__list" data-operation-review-list role="list"></div>
                    <div class="ship-operation-review__empty d-none" data-operation-review-empty>
                        Tidak ada kegiatan kapal pada laporan ini. Laporan dapat dikirim tanpa konfirmasi operasi kapal.
                    </div>
                    <div class="ship-operation-review__alert d-none" data-operation-review-alert role="alert" aria-live="polite">
                        <i class="fi fi-rr-triangle-warning" aria-hidden="true"></i>
                        <span>Konfirmasikan setiap kapal sebagai masih berjalan atau selesai sebelum mengirim laporan.</span>
                    </div>
                </section>
                <div class="day-report-warning d-none" data-day-report-warning style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:10px;border:1px solid var(--warning,#f0ad4e);background:var(--warning-10,#fff7e6)">
                    <i class="fi fi-rr-triangle-warning" style="color:var(--warning,#f0ad4e);margin-top:2px"></i>
                    <span class="fsize-12" style="line-height:1.5" data-day-report-warning-text></span>
                </div>
            </div>

            <div class="pop-up footer d-flex justify-content-end gap-10">
                <button type="button" class="btn cancel btn-close-modal">Periksa Lagi</button>
                <button type="button" id="btnFinalSubmit" class="btn confirm">
                    <i class="fi fi-rr-paper-plane me-1"></i> Ya, Kirim Laporan
                </button>
            </div>
        </div>
    </div>
@endpush

@include('partials.report-autosave')
@include('partials.report-peek')
