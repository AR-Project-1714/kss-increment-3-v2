{{--
    Partial form laporan shift harian, dipakai bersama oleh halaman buat (create)
    dan ubah (edit). Wrapper masing-masing yang menyetel variabel berikut:
      $formAction          : URL submit form
      $isEdit              : true saat mode ubah (mengirim @method('PUT'))
      $headerTitle         : judul di header form
      $headerDocumentLabel : label "ID:" (mis. "Draft Baru" atau "#OPS-...")
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
        .step-info-note i { position: relative; top: 1px; flex-shrink: 0; }
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

            /* Tab bongkar tabs shrink */
            .tab-bongkar .tab { min-width: 130px !important; }

            .tab-container { font-size: 10px !important; min-height: 34px !important; }

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

            /* Sub-tabs pack tighter so they sit 2 per row */
            .tab-sections { min-width: 120px !important; font-size: 11px !important; padding: 8px !important; }

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
            color: var(--dark-main);
            font-size: 12px;
            font-weight: 600;
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

        .ship-operation-status-options input[value="completed"]:checked + span {
            border-color: var(--success);
            background-color: var(--success);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 12px var(--success-40);
        }

        /* MOBILE: rapikan status pekerjaan kapal (muat pupuk & urea) dan
           baris input Laporan Harian pada muat urea agar tidak berdesakan. */
        @media (max-width: 480px) {
            .ship-operation-status {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .ship-operation-status-options { width: 100%; }
            .ship-operation-status-options label { flex: 1 1 0; min-width: 0; }
            .ship-operation-status-options span { width: 100%; }

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
    const savedFormPayload = @json(old('form_payload') ? json_decode(old('form_payload'), true) : (isset($report) ? $report->payload : null));
    const currentReportId = @json(isset($report) ? $report->id : null);
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
        return Object.entries(masterEmployeesGrouped || {})
            .filter(([groupName]) => predicate(groupName))
            .flatMap(([, employees]) => Array.isArray(employees) ? employees : Object.values(employees || {}))
            .filter(employee => employee && employee.name);
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
        const radio = Array.from((root || document).querySelectorAll(`[name="${config.statusName}"]`))
            .find(input => input.value === status);
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

        setShipOperationStatus(pane, config, 'active');

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

    // Hanya kolom "nama karyawan" (subfield [name]) yang perlu disarankan dari daftar
    // karyawan. Kolom lain seperti [description]/[work_area]/[time_in] pada log yang
    // sama TIDAK boleh ikut ketiban list ini (mis. keterangan OP.7 punya datalist sendiri).
    const EMPLOYEE_NAME_ARRAY_FIELDS = /(employee_shift_logs|overtime_logs|op7_logs|replacement_logs|other_activity_logs)\[[^\]]+\]\[name\]$/i;

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
            } else if (EMPLOYEE_NAME_ARRAY_FIELDS.test(name) || /(operator|foreman|stevedoring|petugas)/i.test(name)) {
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
            'ship_operation_urea_id', 'ship_operation_urea_status',
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
        ['step-muat-kantong', 'step-muat-curah', 'section-bahan-baku', 'section-container'].forEach(sectionId => {
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
        if (/^(timesheets|bulk_logs)\[/.test(name)) return;

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

    function ensureTimesheetRowsForName(name) {
        const timesheetMatch = name.match(/^timesheets\[(\d+)]\[([^\]]+)]\[(\d+)]/);
        const bulkMatch = name.match(/^bulk_logs\[(\d+)]\[(\d+)]/);
        if (!timesheetMatch && !bulkMatch) return;
        if (controlsByName(name).length > 0) return;

        const sequence = Number(timesheetMatch?.[1] || bulkMatch?.[1] || 1);
        const category = timesheetMatch?.[2] || null;
        const targetIndex = Number(timesheetMatch?.[3] || bulkMatch?.[2] || 0);
        const prefix = timesheetMatch
            ? `timesheets[${sequence}][${category}]`
            : `bulk_logs[${sequence}]`;
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

        if (status === 'draft') {
            form.querySelectorAll('[required]').forEach(input => {
                input.dataset.wasRequired = 'true';
                input.required = false;
            });
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
        return ['sakit', 'cuti', 'tidak masuk'].includes(String(value || '').toLowerCase());
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

    function employeeShiftRowHtml(employee, index, locked = false) {
        const { timeIn, timeOut } = currentWorkTimes();

        return `
            <div class="body"${locked ? ' data-locked-role="true"' : ''}>
                <div class="table-column no"><span>${index + 1}</span></div>
                <div class="table-column main">
                    <div class="table-input-wrapper">
                        <span class="icon"><i class="fi fi-sr-user-time"></i></span>
                        <input type="text" name="employee_shift_logs[${index}][name]" value="${escapeHtml(employee.name)}" placeholder="Nama Karyawan"${locked ? ' readonly title="Kepala Regu / Wakil Kepala Regu terkunci"' : ''}>
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
                    ${locked ? '<span class="icon text-muted" title="Baris terkunci"><i class="fi fi-rr-lock"></i></span>' : '<button type="button" class="btn-trash-row"><i class="fi fi-rr-trash"></i></button>'}
                </div>
            </div>
        `;
    }

    function op7RowHtml(employee, index) {
        const { timeIn, timeOut } = currentWorkTimes();
        const mapping = OP7_FORKLIFT_DEFAULTS[index] || { no_forklift: '', work_area: '' };

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

        // Baris 1 & 2 dikunci tetap: Kepala Regu (KARU) lalu Wakil Kepala Regu.
        const isWakaru = employee => /wakil/i.test(employee.position || '');
        const isKaru = employee => !isWakaru(employee) && /karu|kepala regu/i.test(employee.position || '');
        const karu = employees.find(isKaru);
        const wakaru = employees.find(isWakaru);
        const leaders = [karu, wakaru].filter(Boolean);
        const rest = employees.filter(employee => !leaders.includes(employee));
        const ordered = [...leaders, ...rest];

        insertRows(employeeTable, ordered.map((employee, index) =>
            employeeShiftRowHtml(employee, index, leaders.includes(employee))));
        applyMasterDatalists(employeeTable);
        hydrateTableSelects(employeeTable);
        applyShiftTimesToEmployeeRows();
    }

    function renderOp7Rows(groupValue = null) {
        const op7Table = document.querySelector('#section-op7 .table-wrapper:not(.red) .table-input');
        const group = groupValue || document.querySelector('[name="group_name"]')?.value || currentUserGroup;
        const employees = employeesForOp7Group(group);

        if (!op7Table || employees.length === 0) return;

        insertRows(op7Table, employees.map((employee, index) => op7RowHtml(employee, index)));
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
        const timeRangeSelect = document.querySelector('[name="time_range"]');
        const normalizedShift = String(shiftSelect?.value || '').toLowerCase();

        const shiftTimes = {
            '1': '07.00 - 15.00',
            pagi: '07.00 - 15.00',
            '2': '15.00 - 23.00',
            siang: '15.00 - 23.00',
            sore: '15.00 - 23.00',
            '3': '23.00 - 07.00',
            malam: '23.00 - 07.00',
        };

        if (timeRangeSelect && shiftTimes[normalizedShift]) {
            setSelectValue(timeRangeSelect, shiftTimes[normalizedShift]);
        }
    }

    function currentWitaShiftDefaults() {
        if (currentWitaHour >= 7 && currentWitaHour < 15) {
            return { shift: 'Pagi', timeRange: '07.00 - 15.00' };
        }

        if (currentWitaHour >= 15 && currentWitaHour < 23) {
            return { shift: 'Sore', timeRange: '15.00 - 23.00' };
        }

        return { shift: 'Malam', timeRange: '23.00 - 07.00' };
    }

    function applyDefaultShiftByWita() {
        if (isEditMode) return;

        const shiftSelect = document.querySelector('[name="shift"]');
        const timeRangeSelect = document.querySelector('[name="time_range"]');
        if (!shiftSelect || shiftSelect.value) return;

        const defaults = currentWitaShiftDefaults();
        setSelectValue(shiftSelect, defaults.shift);

        if (timeRangeSelect && !timeRangeSelect.value) {
            setSelectValue(timeRangeSelect, defaults.timeRange);
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
        resetAccumulationSummaries(row);

        row.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.type === 'hidden') {
                input.value = '';
                return;
            }

            if (input.type === 'radio') {
                input.checked = input.name.includes('ship_operation')
                    ? input.value === 'active'
                    : input.value === 'Baik';
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
        applyMasterDatalists(clone);
        hydrateTableSelects(clone);
        initPickers(clone);

        if (tableInput.closest('#section-shift')) {
            applyShiftTimesToRow(clone);
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
            if (isOp7Source) syncOp7Replacements();
            return;
        }

        row.remove();
        reindexTable(tableInput);
        if (isOp7Source) syncOp7Replacements();
    }

    function updateAccumulation(input) {
        const row = input.closest('.body, .form-card-content');
        if (!row) return;

        const canAccumulate = hasBagLoadingDetails(row);
        const current = canAccumulate ? Number(row.querySelector('[name*="qty_current"], [name*="_current_"]')?.value || 0) : 0;
        const previous = canAccumulate ? Number(row.querySelector('[name*="qty_prev"], [name*="_prev_"]')?.value || 0) : 0;
        const totalInput = row.querySelector('[name*="qty_total"], [name*="qty_accumulated"]');
        const summary = input.closest('.form-card')?.querySelector('.accumulated');
        const total = current + previous;

        if (totalInput) totalInput.value = total || '';
        if (summary) summary.textContent = total || 0;
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

    function showActivity(section, sequence) {
        section.querySelectorAll('.btn-activity').forEach(tab => tab.classList.toggle('active', Number(tab.dataset.sequence) === sequence));
        section.querySelectorAll('.activity-pane').forEach(pane => {
            const isActive = Number(pane.dataset.sequence) === sequence;
            pane.classList.toggle('d-none', !isActive);
            pane.classList.toggle('d-flex', isActive);
        });
    }

    function createActivityTab(section, tabBar, plusMinus, sequence) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-activity';
        button.dataset.sequence = sequence;
        button.textContent = `Kegiatan ${sequence}`;
        button.addEventListener('click', () => showActivity(section, sequence));
        tabBar.insertBefore(button, plusMinus);
        return button;
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
        createActivityTab(section, tabBar, plusMinus, 1).classList.add('active');

        addButton?.addEventListener('click', () => {
            const panes = section.querySelectorAll('.activity-pane');
            const source = Array.from(panes).find(item => !item.classList.contains('d-none')) || panes[panes.length - 1];
            const sequence = panes.length + 1;
            const clone = source.cloneNode(true);

            clearRow(clone);
            resetTimesheetContent(clone);
            setSequence(clone, sequence);
            clone.classList.add('d-none');
            clone.classList.remove('d-flex');
            content.insertBefore(clone, buttonRow);
            createActivityTab(section, tabBar, plusMinus, sequence);
            applyMasterDatalists(clone);
            prepareShipOperationFields(clone);
            initPickers(clone);
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
                const tab = section.querySelectorAll('.btn-activity')[index];
                if (tab) {
                    tab.dataset.sequence = newSequence;
                    tab.textContent = `Kegiatan ${newSequence}`;
                }
            });

            showActivity(section, Math.max(1, activeSequence - 1));
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
    initActivitySection('step-muat-kantong');
    initActivitySection('step-muat-curah');
    initActivitySection(document.getElementById('section-bahan-baku'));
    initActivitySection(document.getElementById('section-container'));
    restoreSavedPayload();
    applyAbsenceStateToEmployeeRows();
    syncOp7Replacements();
    syncPayload();
    refreshAllTimesheetTimelines();
    validateReportGroupRoute({ enforce: false });

    document.querySelector('[name="group_name"]')?.addEventListener('change', event => {
        renderEmployeeShiftRows(event.target.value);
        renderOp7Rows(event.target.value);
        rebuildRoleDatalists(event.target.value);
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

    form?.addEventListener('submit', () => {
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

        if (event.target.matches('[name^="op7_logs"][name$="[description]"]')) {
            syncOp7Replacements();
        }
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

        const timesheetRow = event.target.closest('.timesheet-input');
        if (timesheetRow) {
            clearTimesheetValidation(timesheetRow);
        }

        if (event.target.matches('.time-picker-input')) {
            const original = event.target.value;
            const numbers = original.replace(/\D/g, '').slice(0, 4);
            event.target.value = numbers.length > 2 ? `${numbers.slice(0, 2)}:${numbers.slice(2)}` : numbers;
        }

        if (event.target.matches('input[data-suggest]')) {
            openSuggestFor(event.target);
        }

        // Jam Kerja rentang (satu input, mis. "23:00 - 04:00"): pengguna cukup
        // ketik angkanya saja, simbol ":" dan " - " otomatis disisipkan.
        if (event.target.matches('.time-range-input')) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 8);
            let formatted = digits.slice(0, 2);
            if (digits.length > 2) formatted += ':' + digits.slice(2, 4);
            if (digits.length > 4) formatted += ' - ' + digits.slice(4, 6);
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
    });
});
</script>
@endpush

@section('content')
    <!-- MAIN APP WRAPPER -->
    <form id="mainReportForm" action="{{ $formAction }}" method="POST" class="content d-flex flex-column align-items-start align-self-stretch gap-30 p-content">
        @csrf
        @if (! empty($isEdit))
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
            <button type="button" id="btnSaveDraft" class="btn-new d-flex justify-content-center align-items-center gap-10">
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
    <!-- STEP 4: BONGKAR                            -->
    <!-- ========================================== -->
    @include('report-ops.sections.step4-bongkar')

    <!-- ========================================== -->
    <!-- STEP 5: TRACKING                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step5-gudangturba')

    <!-- ========================================== -->
    <!-- STEP 6: CEK UNIT                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step6-cekunit')

    <!-- ========================================== -->
    <!-- STEP 7: KARYAWAN                           -->
    <!-- ========================================== -->
    @include('report-ops.sections.step7-karyawan')

    </form>
@endsection

@push('modals')
    <!-- MODAL KONFIRMASI (REFERENSI GAYA DASHBOARD) -->
    @php
        $finishReceiverGroup = strtoupper((string) old('received_by_group', isset($report) ? $report->received_by_group : ''));
    @endphp
    <div class="modal-overlay" id="finishModal">
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
