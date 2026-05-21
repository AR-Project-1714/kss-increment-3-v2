@extends('admin.layouts.app')

@section('title', 'KSS Admin — Master Data')
@section('active', 'master')

@push('styles')
<style>
    /* =============================================
       SECTION CARD
       ============================================= */
    .section-card {
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(37,99,235,0.07);
        transition: background-color 0.3s ease;
    }

    .section-card__title { font-size: 16px; font-weight: 600; color: var(--black); }

    /* =============================================
       BREADCRUMB
       ============================================= */
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 400;
    }
    .page-breadcrumb__root { color: var(--muted); }
    .page-breadcrumb__sep { color: var(--muted); font-size: 11px; display: flex; position: relative; top: 1px; }
    .page-breadcrumb__current { color: var(--black-secondary); font-weight: 500; }

    /* =============================================
       TOOLBAR
       ============================================= */
    .archive-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

    .archive-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-action-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1 1 540px;
        max-width: 620px;
        min-width: 0;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border: 1px solid var(--smooth-border);
        border-radius: 50px;
        background-color: var(--main-bg);
        flex: 1 1 auto;
        max-width: none;
        min-width: 260px;
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

    .search-box input::placeholder { color: var(--muted); }

    .btn-tool {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        color: var(--black-secondary);
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-tool i { position: relative; top: 1px; }
    .btn-tool--primary { background-color: var(--blue-main); border-color: var(--blue-main); color: #fff; }
    .btn-tool--primary:hover { background-color: var(--blue-hover); border-color: var(--blue-hover); color: #fff; }

    .search-action-group .btn-tool {
        height: 44px;
        flex-shrink: 0;
        padding: 0 16px;
    }

    @media (max-width: 640px) {
        .search-action-group {
            flex: 1 1 100%;
            max-width: none;
        }

        .search-box {
            min-width: 0;
        }
    }

    /* =============================================
       MASTER DATA PANES (Tab switching)
       ============================================= */
    .master-pane { display: none; }
    .master-pane.active { display: block; }

    /* =============================================
       TABLE
       ============================================= */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: var(--blue-main-25) transparent;
    }

    .table-responsive-wrapper::-webkit-scrollbar { height: 6px; }
    .table-responsive-wrapper::-webkit-scrollbar-track { background: transparent; border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb { background-color: var(--blue-main-25); border-radius: 10px; }
    .table-responsive-wrapper::-webkit-scrollbar-thumb:hover { background-color: var(--blue-main-40); }

    .table-responsive-wrapper table { min-width: 900px; width: 100%; }

    .thead { background-color: var(--blue-main-5); border-radius: 6px; }

    .thead th {
        display: flex;
        padding: 10px;
        align-items: center;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black-secondary);
    }

    .tbody { border-bottom: 1px solid var(--smooth-border); transition: background-color 0.15s ease-in-out; }
    .tbody:hover { background-color: var(--blue-main-3); }

    .tbody td {
        display: flex;
        align-items: center;
        padding: 12px 10px;
        flex: 1 0 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--black);
    }

    /* Columns */
    .thead th.col-no,       .tbody td.col-no       { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }
    .thead th.col-name,     .tbody td.col-name     { min-width: 150px; }
    .thead th.col-npk,      .tbody td.col-npk      { min-width: 110px; }
    .thead th.col-group,    .tbody td.col-group    { min-width: 100px; }
    .thead th.col-position, .tbody td.col-position { min-width: 100px; }
    .thead th.col-type,     .tbody td.col-type     { min-width: 120px; }
    .thead th.col-plate,    .tbody td.col-plate    { min-width: 120px; }
    .thead th.col-desc,     .tbody td.col-desc     { min-width: 200px; flex: 2 0 0; }
    .thead th.col-category, .tbody td.col-category { min-width: 130px; }
    .thead th.col-aksi,     .tbody td.col-aksi     { min-width: 180px; gap: 8px; flex-wrap: nowrap; }

    /* Action buttons */
    td.col-aksi .btn-act {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 10px;
        border: none;
        border-radius: 6px;
        color: #fff;
        font-family: inherit;
        font-size: 10px;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: 0.2s ease-out;
    }

    td.col-aksi .btn-act i { position: relative; top: 1px; }
    td.col-aksi .btn-act.edit { background-color: var(--blue-main); }
    td.col-aksi .btn-act.edit:hover { background-color: var(--blue-hover); transform: translateY(-1px); }
    td.col-aksi .btn-act.delete { background-color: var(--red-main); }
    td.col-aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }
</style>
@endpush

@section('content')
@php
    $employees = $employees ?? collect([
        ['no' => 1, 'npk' => '2000.1.010', 'name' => 'Mustari S,T',         'group' => 'Kantor', 'position' => 'Admin'],
        ['no' => 2, 'npk' => '2000.1.011', 'name' => 'Budi Santoso',        'group' => 'Regu A', 'position' => 'Operator'],
        ['no' => 3, 'npk' => '2000.1.012', 'name' => 'Andi Wijaya',         'group' => 'Regu B', 'position' => 'Mekanik'],
        ['no' => 4, 'npk' => '2000.1.013', 'name' => 'Siti Aminah',         'group' => 'Kantor', 'position' => 'Staff'],
    ]);
    $units = $units ?? collect([
        ['no' => 1, 'name' => 'Excavator PC200',    'type' => 'Alat Berat'],
        ['no' => 2, 'name' => 'Dump Truck HD465',   'type' => 'Kendaraan'],
        ['no' => 3, 'name' => 'Bulldozer D85ESS',   'type' => 'Alat Berat'],
        ['no' => 4, 'name' => 'Wheel Loader WA380', 'type' => 'Alat Berat'],
    ]);
    $trucks = $trucks ?? collect([
        ['no' => 1, 'name' => 'Hino 500',        'plate' => 'B 9012 KSS', 'desc' => 'Truk Angkut Material'],
        ['no' => 2, 'name' => 'Mitsubishi Fuso', 'plate' => 'B 9013 KSS', 'desc' => 'Truk Tangki Air'],
        ['no' => 3, 'name' => 'Isuzu Giga',      'plate' => 'B 9014 KSS', 'desc' => 'Truk Angkut Batu'],
        ['no' => 4, 'name' => 'Scania P360',     'plate' => 'B 9015 KSS', 'desc' => 'Truk Trailer'],
    ]);
    $inventories = $inventories ?? collect([
        ['no' => 1, 'name' => 'Helm Safety',        'category' => 'APD'],
        ['no' => 2, 'name' => 'Sepatu Boots',       'category' => 'APD'],
        ['no' => 3, 'name' => 'Oli Mesin SAE 40',   'category' => 'Sparepart'],
        ['no' => 4, 'name' => 'Ban Truck 1000-20',  'category' => 'Sparepart'],
    ]);
    $masterActions = $masterActions ?? [
        'karyawan' => ['store' => '#'],
        'unit' => ['store' => '#'],
        'truck' => ['store' => '#'],
        'inventaris' => ['store' => '#'],
    ];
@endphp

<div class="page-header">
    <span class="page-title">Master Data</span>
    <div class="page-breadcrumb">
        <span class="page-breadcrumb__root">Data Master</span>
        <span class="page-breadcrumb__sep"><i class="fi fi-rr-angle-small-right"></i></span>
        <span class="page-breadcrumb__current" id="masterCrumb">Data Karyawan</span>
    </div>
</div>

@component('admin.layouts.card', ['title' => 'Data Karyawan', 'titleId' => 'masterTitle'])
    <!-- Toolbar -->
    <form class="archive-toolbar" method="GET" action="{{ route('admin.datamaster') }}">
        <input type="hidden" name="pane" id="masterPaneInput" value="{{ $activePane ?? 'karyawan' }}">
        <div class="search-action-group">
            <div class="search-box">
                <span><i class="fi fi-rr-search"></i></span>
                <input type="text" name="q" value="{{ $masterSearch ?? '' }}" placeholder="Cari Karyawan" id="masterSearch">
            </div>
            <button type="submit" class="btn-tool"><i class="fi fi-rr-search"></i> Cari</button>
        </div>
        <button type="button" class="btn-tool btn-tool--primary" id="masterAddBtn">
            <i class="fi fi-rr-user-add" id="masterAddIcon"></i> <span id="masterAddText">Tambah Pengguna</span>
        </button>
    </form>

    <!-- PANE: Master Employees -->
    <div class="master-pane active" data-pane="karyawan">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-npk">NPK</th>
                    <th class="col-name">Name</th>
                    <th class="col-group">Group</th>
                    <th class="col-position">Position</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @foreach ($employees as $e)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $e['update_url'] ?? '' }}">
                        <td class="col-no">{{ $e['no'] }}</td>
                        <td class="col-npk">{{ $e['npk'] }}</td>
                        <td class="col-name">{{ $e['name'] }}</td>
                        <td class="col-group">{{ $e['group'] }}</td>
                        <td class="col-position">{{ $e['position'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $e['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <!-- PANE: Master Units -->
    <div class="master-pane" data-pane="unit">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-type">Type</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @foreach ($units as $u)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $u['update_url'] ?? '' }}">
                        <td class="col-no">{{ $u['no'] }}</td>
                        <td class="col-name">{{ $u['name'] }}</td>
                        <td class="col-type">{{ $u['type'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $u['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <!-- PANE: Master Trucks -->
    <div class="master-pane" data-pane="truck">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-plate">Plate Number</th>
                    <th class="col-desc">Description</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @foreach ($trucks as $t)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $t['update_url'] ?? '' }}">
                        <td class="col-no">{{ $t['no'] }}</td>
                        <td class="col-name">{{ $t['name'] }}</td>
                        <td class="col-plate">{{ $t['plate'] }}</td>
                        <td class="col-desc">{{ $t['desc'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $t['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <!-- PANE: Master Inventory Items -->
    <div class="master-pane" data-pane="inventaris">
        <div class="table-responsive-wrapper">
            <table>
                <tr class="thead d-flex justify-content-between align-items-center">
                    <th class="col-no">No</th>
                    <th class="col-name">Name</th>
                    <th class="col-category">Category</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
                @foreach ($inventories as $i)
                    <tr class="tbody d-flex justify-content-between align-items-center" data-update-url="{{ $i['update_url'] ?? '' }}">
                        <td class="col-no">{{ $i['no'] }}</td>
                        <td class="col-name">{{ $i['name'] }}</td>
                        <td class="col-category">{{ $i['category'] }}</td>
                        <td class="col-aksi">
                            <button type="button" class="btn-act edit js-master-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                            <form method="POST" action="{{ $i['destroy_url'] ?? '#' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-act delete js-master-delete"><i class="fi fi-rr-trash"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    @if (($activePane ?? 'karyawan') === 'karyawan' && method_exists($employees, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $employees, 'label' => 'karyawan'])
    @elseif (($activePane ?? 'karyawan') === 'unit' && method_exists($units, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $units, 'label' => 'unit'])
    @elseif (($activePane ?? 'karyawan') === 'truck' && method_exists($trucks, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $trucks, 'label' => 'truck'])
    @elseif (($activePane ?? 'karyawan') === 'inventaris' && method_exists($inventories, 'links'))
        @include('admin.layouts.pagination', ['paginator' => $inventories, 'label' => 'inventaris'])
    @endif
@endcomponent

<div class="modal-overlay" id="masterFormModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="masterFormTitle">
        <form method="POST" action="#" id="masterForm">
            @csrf
            <input type="hidden" name="_method" id="masterFormMethod" value="POST">
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-database" id="masterFormIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="masterFormTitle">Tambah Data Master</div>
                    <div class="kss-modal__subtitle" id="masterFormSubtitle">Lengkapi data sesuai kategori master yang aktif.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>
            <div class="kss-modal__body">
                <div class="kss-modal__grid" id="masterFormFields"></div>
            </div>
            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary" id="masterFormSubmit">
                    <i class="fi fi-rr-disk"></i> Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // MASTER DATA TAB SWITCHING (via submenu sidebar)
        const masterTabs = {
            karyawan:   { title: 'Data Karyawan',   search: 'Cari Karyawan',   add: 'Tambah Karyawan',   icon: 'fi fi-rr-user-add' },
            unit:       { title: 'Data Unit',       search: 'Cari Unit',       add: 'Tambah Unit',       icon: 'fi fi-rr-add' },
            truck:      { title: 'Data Truck',      search: 'Cari Truck',      add: 'Tambah Truck',      icon: 'fi fi-rr-add' },
            inventaris: { title: 'Data Inventaris', search: 'Cari Inventaris', add: 'Tambah Inventaris', icon: 'fi fi-rr-add' }
        };

        const masterSchemas = {
            karyawan: {
                label: 'Karyawan',
                icon: 'fi fi-rr-user',
                fields: [
                    { key: 'npk', label: 'NPK', placeholder: '2000.1.010' },
                    { key: 'name', label: 'Nama Karyawan', placeholder: 'Nama lengkap' },
                    { key: 'group', label: 'Group', type: 'select', options: ['Kantor', 'Regu A', 'Regu B', 'Regu C'] },
                    { key: 'position', label: 'Posisi', placeholder: 'Operator' },
                ],
            },
            unit: {
                label: 'Unit',
                icon: 'fi fi-rr-truck-side',
                fields: [
                    { key: 'name', label: 'Nama Unit', placeholder: 'Excavator PC200' },
                    { key: 'type', label: 'Tipe Unit', type: 'select', options: ['Alat Berat', 'Kendaraan', 'Forklift', 'Support'] },
                ],
            },
            truck: {
                label: 'Truck',
                icon: 'fi fi-rr-truck-moving',
                fields: [
                    { key: 'name', label: 'Nama Truck', placeholder: 'Hino 500' },
                    { key: 'plate', label: 'Nomor Polisi', placeholder: 'B 9012 KSS' },
                    { key: 'desc', label: 'Deskripsi', type: 'textarea', placeholder: 'Fungsi atau catatan truck' },
                ],
            },
            inventaris: {
                label: 'Inventaris',
                icon: 'fi fi-rr-box-open',
                fields: [
                    { key: 'name', label: 'Nama Inventaris', placeholder: 'Helm Safety' },
                    { key: 'category', label: 'Kategori', type: 'select', options: ['APD', 'Sparepart', 'Tools', 'Consumable'] },
                ],
            },
        };

        const masterTitle    = document.getElementById('masterTitle');
        const masterCrumb     = document.getElementById('masterCrumb');
        const masterSearch    = document.getElementById('masterSearch');
        const masterPaneInput = document.getElementById('masterPaneInput');
        const masterAddText   = document.getElementById('masterAddText');
        const masterAddIcon   = document.getElementById('masterAddIcon');
        const masterAddBtn    = document.getElementById('masterAddBtn');
        const masterPanes     = document.querySelectorAll('.master-pane');
        const masterMenuItems = document.querySelectorAll('.sidebar__submenu-item[data-pane]');
        const masterFormModal = document.getElementById('masterFormModal');
        const masterFormTitle = document.getElementById('masterFormTitle');
        const masterFormSubtitle = document.getElementById('masterFormSubtitle');
        const masterFormIcon = document.getElementById('masterFormIcon');
        const masterFormFields = document.getElementById('masterFormFields');
        const masterFormSubmit = document.getElementById('masterFormSubmit');
        const masterForm = document.getElementById('masterForm');
        const masterFormMethod = document.getElementById('masterFormMethod');
        const masterActions = @json($masterActions);
        let activeMasterPane = 'karyawan';

        function switchMasterPane(pane) {
            const cfg = masterTabs[pane];
            if (!cfg) return;
            activeMasterPane = pane;
            masterPanes.forEach(p => p.classList.toggle('active', p.getAttribute('data-pane') === pane));
            masterMenuItems.forEach(m => m.classList.toggle('active', m.getAttribute('data-pane') === pane));
            if (masterTitle)   masterTitle.textContent = cfg.title;
            if (masterCrumb)   masterCrumb.textContent = cfg.title;
            if (masterSearch)  masterSearch.placeholder = cfg.search;
            if (masterPaneInput) masterPaneInput.value = pane;
            if (masterAddText) masterAddText.textContent = cfg.add;
            if (masterAddIcon) masterAddIcon.className = cfg.icon;
        }

        function readMasterRow(row, pane) {
            const text = selector => row.querySelector(selector)?.textContent.trim() || '';
            if (pane === 'karyawan') {
                return { npk: text('.col-npk'), name: text('.col-name'), group: text('.col-group'), position: text('.col-position') };
            }
            if (pane === 'unit') {
                return { name: text('.col-name'), type: text('.col-type') };
            }
            if (pane === 'truck') {
                return { name: text('.col-name'), plate: text('.col-plate'), desc: text('.col-desc') };
            }
            return { name: text('.col-name'), category: text('.col-category') };
        }

        function addField(field, value, index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'kss-modal__field';
            if (field.type === 'textarea') wrapper.classList.add('kss-modal__field--full');

            const label = document.createElement('label');
            label.setAttribute('for', `masterField_${field.key}`);
            label.textContent = field.label;
            wrapper.appendChild(label);

            let control;
            if (field.type === 'select') {
                const selectWrapper = document.createElement('div');
                selectWrapper.className = 'kss-modal__select-wrapper';

                control = document.createElement('select');
                control.className = 'kss-modal__native-select';
                field.options.forEach(optionText => {
                    const option = document.createElement('option');
                    option.textContent = optionText;
                    option.value = optionText;
                    control.appendChild(option);
                });
                selectWrapper.appendChild(control);

                const icon = document.createElement('i');
                icon.className = 'fi fi-rr-angle-small-down kss-modal__select-icon';
                selectWrapper.appendChild(icon);

                control.id = `masterField_${field.key}`;
                control.name = field.key;
                if (value && Array.from(control.options).some(option => option.value === value)) {
                    control.value = value;
                } else {
                    control.selectedIndex = 0;
                }
                if (index === 0) control.dataset.modalFocus = 'true';
                wrapper.appendChild(selectWrapper);
                masterFormFields.appendChild(wrapper);
                return;
            } else if (field.type === 'textarea') {
                control = document.createElement('textarea');
                control.className = 'kss-modal__textarea';
            } else {
                control = document.createElement('input');
                control.type = 'text';
                control.className = 'kss-modal__input';
            }

            control.id = `masterField_${field.key}`;
            control.name = field.key;
            control.value = value || '';
            control.placeholder = field.placeholder || '';
            if (index === 0) control.dataset.modalFocus = 'true';
            wrapper.appendChild(control);
            masterFormFields.appendChild(wrapper);
        }

        function openMasterForm(mode, pane, values = {}) {
            const schema = masterSchemas[pane];
            if (!schema) return;

            masterFormTitle.textContent = `${mode === 'edit' ? 'Edit' : 'Tambah'} ${schema.label}`;
            masterFormSubtitle.textContent = mode === 'edit'
                ? `Perbarui informasi ${schema.label.toLowerCase()} yang dipilih.`
                : `Masukkan detail ${schema.label.toLowerCase()} baru ke master data.`;
            masterFormIcon.className = schema.icon;
            masterFormSubmit.innerHTML = `<i class="fi fi-rr-disk"></i> ${mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Data'}`;
            if (masterForm) masterForm.action = mode === 'edit' && values.updateUrl ? values.updateUrl : (masterActions[pane]?.store || '#');
            if (masterFormMethod) masterFormMethod.value = mode === 'edit' ? 'PUT' : 'POST';

            masterFormFields.replaceChildren();
            schema.fields.forEach((field, index) => addField(field, values[field.key], index));
            window.KssAdminModal.initSelects(masterFormModal);
            window.KssAdminModal.syncSelects(masterFormModal);
            window.KssAdminModal.open(masterFormModal);
        }

        masterMenuItems.forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const pane = item.getAttribute('data-pane');
                switchMasterPane(pane);

                const nextUrl = new URL(item.href);
                window.history.replaceState({}, '', nextUrl);
            });
        });

        const initialPane = new URLSearchParams(window.location.search).get('pane') || @json($activePane ?? 'karyawan');
        switchMasterPane(initialPane);

        masterAddBtn?.addEventListener('click', function () {
            openMasterForm('add', activeMasterPane);
        });

        document.querySelectorAll('.js-master-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const row = button.closest('tr');
                openMasterForm('edit', pane, { ...readMasterRow(row, pane), updateUrl: row?.dataset.updateUrl || '' });
            });
        });

        document.querySelectorAll('.js-master-delete').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const pane = button.closest('.master-pane')?.getAttribute('data-pane') || activeMasterPane;
                const cfg = masterTabs[pane];
                const rowData = readMasterRow(button.closest('tr'), pane);
                button.dataset.confirmTone = 'danger';
                button.dataset.confirmTitle = `Hapus ${cfg.title}?`;
                button.dataset.confirmSubtitle = 'Data master akan dihapus dari daftar.';
                button.dataset.confirmMessage = 'Pastikan data ini sudah tidak dipakai pada laporan atau referensi operasional.';
                button.dataset.confirmSummary = rowData.name || rowData.npk || cfg.title;
                button.dataset.confirmLabel = 'Hapus Data';
                button.dataset.confirmIcon = 'fi fi-rr-trash';
                button.dataset.confirmSubmit = 'true';
                window.KssAdminModal.confirm(button);
            });
        });
    });
</script>
@endpush
