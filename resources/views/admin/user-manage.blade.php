@extends('admin.layouts.app')

@section('title', 'KSS Admin - Kelola Pengguna')
@section('active', 'user')

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

    /* User table columns */
    .thead th.col-no,       .tbody td.col-no       { width: 50px; flex: none; justify-content: center; padding: 12px 0; color: var(--black-secondary); }
    .thead th.col-name,     .tbody td.col-name     { min-width: 150px; }
    .thead th.col-username, .tbody td.col-username { min-width: 120px; }
    .thead th.col-role,     .tbody td.col-role     { min-width: 100px; }
    .thead th.col-regu,     .tbody td.col-regu     { min-width: 100px; }
    .thead th.col-status,   .tbody td.col-status   { min-width: 110px; }
    .thead th.col-aksi,     .tbody td.col-aksi     { min-width: 180px; gap: 8px; flex-wrap: nowrap; }

    .tbody td.col-username { color: var(--black-secondary); font-weight: 400; }

    /* Status badges */
    .status {
        display: inline-flex;
        padding: 3px 8px;
        align-items: center;
        gap: 5px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 500;
    }

    .status-dot { width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; flex-shrink: 0; }
    .status.aktif    { border: 1px solid var(--success);  color: var(--success);  background-color: var(--success-10); }
    .status.nonaktif { border: 1px solid var(--red-main); color: var(--red-main); background-color: var(--red-main-10); }

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
    td.col-aksi .btn-act.view { background-color: var(--orange-main); width: 30px; padding: 7px; }
    td.col-aksi .btn-act.view:hover { background-color: var(--orange-hover); transform: translateY(-1px); }
    td.col-aksi .btn-act.delete { background-color: var(--red-main); width: 30px; padding: 7px; }
    td.col-aksi .btn-act.delete:hover { background-color: var(--red-hover); transform: translateY(-1px); }

    .user-status-switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }

    .user-status-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .user-status-switch__track {
        width: 38px;
        height: 22px;
        padding: 2px;
        border-radius: 999px;
        background-color: var(--red-main-10);
        border: 1px solid rgba(210,0,0,0.22);
        transition: 0.2s ease;
        flex-shrink: 0;
    }

    .user-status-switch__thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: block;
        background-color: var(--red-main);
        box-shadow: 0 2px 5px rgba(15,23,42,0.16);
        transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .user-status-switch input:checked + .user-status-switch__track {
        background-color: var(--success-10);
        border-color: rgba(16,185,129,0.26);
    }

    .user-status-switch input:checked + .user-status-switch__track .user-status-switch__thumb {
        transform: translateX(16px);
        background-color: var(--success);
    }

    .user-status-switch input:focus-visible + .user-status-switch__track {
        box-shadow: 0 0 0 3px var(--blue-main-10);
    }

    .user-status-switch:has(input:disabled) {
        cursor: not-allowed;
        opacity: 0.75;
    }

    .user-status-switch__label {
        min-width: 58px;
        font-size: 10px;
        font-weight: 600;
        color: var(--red-main);
    }

    .user-status-switch.is-active .user-status-switch__label { color: var(--success); }

    .signature-upload {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border: 1px dashed var(--blue-main-25);
        border-radius: 8px;
        background-color: var(--main-bg);
    }

    .signature-upload__preview {
        width: 120px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--smooth-border);
        border-radius: 8px;
        background-color: var(--white);
        overflow: hidden;
        flex-shrink: 0;
        color: var(--muted);
        font-size: 10px;
        font-weight: 600;
        text-align: center;
    }

    .signature-upload__preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .signature-upload__control {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .signature-upload__hint {
        font-size: 10px;
        color: var(--muted);
        font-weight: 400;
    }

    .kss-password-wrap {
        position: relative;
        width: 100%;
    }

    .kss-password-wrap .kss-modal__input {
        padding-right: 40px;
    }

    .kss-password-toggle {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: var(--muted);
        cursor: pointer;
        transition: color 0.2s ease, background-color 0.2s ease;
    }

    .kss-password-toggle:hover {
        color: var(--blue-main);
        background-color: var(--blue-main-10);
    }

    .kss-password-toggle i {
        font-size: 15px;
        line-height: 1;
    }
</style>
@endpush

@section('content')
@php
    $users = $users ?? collect([
        ['no' => 1, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 2, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'nonaktif', 'status_label' => 'Non-Aktif'],
        ['no' => 3, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 4, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
        ['no' => 5, 'name' => 'Mustari S,T', 'username' => 'admin', 'role' => 'Admin', 'regu' => 'Regu B', 'status' => 'aktif',    'status_label' => 'Aktif'],
    ]);

    $roles = $roles ?? collect();
@endphp

<div class="page-header">
    <span class="page-title">Kelola Pengguna</span>
    <span class="page-subtitle">Manajemen akun staff, hak akses (Role-Based), dan reset sandi (Separation of Duties).</span>
</div>

@component('admin.layouts.card', ['title' => 'Daftar Pengguna'])
    <!-- Toolbar -->
    <form class="archive-toolbar" method="GET" action="{{ route('admin.user-manage') }}">
        <div class="search-action-group">
            <div class="search-box">
                <span><i class="fi fi-rr-search"></i></span>
                <input type="text" name="q" value="{{ $userSearch ?? '' }}" placeholder="Cari Pengguna">
            </div>
            <button type="submit" class="btn-tool"><i class="fi fi-rr-search"></i> Cari</button>
        </div>
        <button type="button" class="btn-tool btn-tool--primary" id="btnAddUser">
            <i class="fi fi-rr-user-add"></i> Tambah Pengguna
        </button>
    </form>

    <!-- Table -->
    <div class="table-responsive-wrapper">
        <table>
            <tr class="thead d-flex justify-content-between align-items-center">
                <th class="col-no">No</th>
                <th class="col-name">Nama Lengkap</th>
                <th class="col-username">Username</th>
                <th class="col-role">Role</th>
                <th class="col-regu">Regu</th>
                <th class="col-status">Status</th>
                <th class="col-aksi">Aksi</th>
            </tr>

            @forelse ($users as $u)
                <tr class="tbody d-flex justify-content-between align-items-center"
                    data-user-name="{{ $u['name'] }}"
                    data-user-username="{{ $u['username'] }}"
                    data-user-role="{{ $u['role'] }}"
                    data-user-role-id="{{ $u['role_id'] ?? '' }}"
                    data-user-regu="{{ $u['regu'] }}"
                    data-user-group="{{ $u['group_value'] ?? $u['regu'] }}"
                    data-user-status="{{ $u['status'] }}"
                    data-user-self="{{ !empty($u['is_self']) ? '1' : '0' }}"
                    data-user-is-admin="{{ !empty($u['is_admin']) ? '1' : '0' }}"
                    data-user-update-url="{{ $u['update_url'] ?? '' }}"
                    data-user-signature-url="{{ $u['signature_url'] ?? '' }}">
                    <td class="col-no">{{ $u['no'] }}</td>
                    <td class="col-name">{{ $u['name'] }}</td>
                    <td class="col-username">{{ $u['username'] }}</td>
                    <td class="col-role">{{ $u['role'] }}</td>
                    <td class="col-regu">{{ $u['regu'] }}</td>
                    <td class="col-status">
                        <form method="POST" action="{{ $u['status_url'] ?? '#' }}">
                            @csrf
                            @method('PATCH')
                            <label class="user-status-switch {{ $u['status'] === 'aktif' ? 'is-active' : '' }}" title="Aktifkan atau nonaktifkan pengguna">
                                <input type="checkbox"
                                       class="js-user-status-toggle"
                                       @checked($u['status'] === 'aktif')
                                       aria-label="Status pengguna {{ $u['name'] }}"
                                       data-confirm
                                       data-confirm-submit="true"
                                       data-confirm-tone="warning"
                                       data-confirm-title="{{ $u['status'] === 'aktif' ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}"
                                       data-confirm-subtitle="Status akun akan diperbarui."
                                       data-confirm-message="Perubahan status langsung mempengaruhi akses login pengguna."
                                       data-confirm-summary="{{ $u['name'] }} - {{ $u['role'] }}"
                                       data-confirm-label="Ubah Status"
                                       data-confirm-icon="fi fi-rr-refresh">
                                <span class="user-status-switch__track"><span class="user-status-switch__thumb"></span></span>
                                <span class="user-status-switch__label">{{ $u['status_label'] }}</span>
                            </label>
                        </form>
                    </td>
                    <td class="col-aksi">
                        <button type="button" class="btn-act edit js-user-edit"><i class="fi fi-rr-pencil"></i> Edit</button>
                        <button type="button" class="btn-act view js-user-view" title="Lihat"><i class="fi fi-rr-eye"></i></button>
                        <form method="POST" action="{{ $u['destroy_url'] ?? '#' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn-act delete"
                                    title="Hapus"
                                    data-confirm
                                    data-confirm-submit="true"
                                    data-confirm-tone="danger"
                                    data-confirm-title="Hapus pengguna?"
                                    data-confirm-subtitle="Akun akan dihapus dari daftar pengguna."
                                    data-confirm-message="Pastikan pengguna ini memang tidak lagi membutuhkan akses ke sistem."
                                    data-confirm-summary="{{ $u['name'] }} - {{ $u['role'] }} - {{ $u['regu'] }}"
                                    data-confirm-label="Hapus Pengguna">
                                <i class="fi fi-rr-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                @php $userIsSearching = ($userSearch ?? '') !== ''; @endphp
                @include('admin.layouts.empty-state', [
                    'icon' => $userIsSearching ? 'fi fi-rr-search' : 'fi fi-rr-users',
                    'title' => $userIsSearching ? 'Tidak ada pengguna yang cocok' : 'Belum ada pengguna',
                    'message' => $userIsSearching
                        ? 'Tidak ada pengguna yang sesuai dengan pencarian "'.$userSearch.'". Coba kata kunci lain atau periksa ejaannya.'
                        : 'Tambahkan pengguna baru lewat tombol "Tambah Pengguna" untuk mulai mengelola akun dan hak akses.',
                ])
            @endforelse
        </table>
    </div>
    @include('admin.layouts.pagination', ['paginator' => $users, 'label' => 'pengguna'])
@endcomponent

<div class="modal-overlay" id="userFormModal" aria-hidden="true">
    <div class="modal-box modal-box--wide" role="dialog" aria-modal="true" aria-labelledby="userFormTitle">
        <form method="POST" action="{{ route('admin.users.store') }}" id="userForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="userFormMethod" value="POST">
            <div class="kss-modal__header">
                <div class="kss-modal__icon">
                    <i class="fi fi-rr-user-add" id="userFormIcon"></i>
                </div>
                <div class="kss-modal__heading">
                    <div class="kss-modal__title" id="userFormTitle">Tambah Pengguna</div>
                    <div class="kss-modal__subtitle" id="userFormSubtitle">Lengkapi detail akun dan hak akses pengguna.</div>
                </div>
                <button type="button" class="kss-modal__close" data-modal-close aria-label="Tutup modal">
                    <i class="fi fi-rr-cross-small"></i>
                </button>
            </div>

            <div class="kss-modal__body">
                <div class="kss-modal__grid">
                    <div class="kss-modal__field">
                        <label for="userNameInput">Nama Lengkap</label>
                        <input class="kss-modal__input" id="userNameInput" name="name" type="text" placeholder="cth, Budi Santoso" data-modal-focus required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userUsernameInput">Username</label>
                        <input class="kss-modal__input" id="userUsernameInput" name="username" type="text" placeholder="cth, budi.santoso" required>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userRoleInput">Role</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="userRoleInput" name="role_id" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->id }}" @selected($roleOption->name === \App\Models\Role::OPERATIONAL)>
                                        {{ \App\Models\Role::displayName($roleOption->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userReguInput">Regu</label>
                        <div class="kss-modal__select-wrapper">
                            <select class="kss-modal__native-select" id="userReguInput" name="group">
                                <option value="Kantor">Kantor</option>
                                <option value="A">Regu A</option>
                                <option value="B">Regu B</option>
                                <option value="C">Regu C</option>
                                <option value="D">Regu D</option>
                            </select>
                            <i class="fi fi-rr-angle-small-down kss-modal__select-icon"></i>
                        </div>
                    </div>
                    <div class="kss-modal__field">
                        <label>Status</label>
                        <label class="user-status-switch is-active" id="userStatusInputWrap">
                            <input type="hidden" name="status" value="nonaktif">
                            <input type="checkbox" id="userStatusInput" name="status" value="aktif" checked>
                            <span class="user-status-switch__track"><span class="user-status-switch__thumb"></span></span>
                            <span class="user-status-switch__label" id="userStatusInputLabel">Aktif</span>
                        </label>
                    </div>
                    <div class="kss-modal__field">
                        <label for="userPasswordInput">Password Awal</label>
                        <div class="kss-password-wrap">
                            <input class="kss-modal__input" id="userPasswordInput" name="password" type="password" minlength="5" placeholder="Min. 5 karakter">
                            <button type="button" class="kss-password-toggle" id="userPasswordToggle" aria-label="Tampilkan password" title="Tampilkan password">
                                <i class="fi fi-rr-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="kss-modal__field kss-modal__field--full">
                        <label for="userSignatureInput">Tanda Tangan PNG</label>
                        <div class="signature-upload">
                            <div class="signature-upload__preview" id="userSignaturePreview">Belum ada tanda tangan</div>
                            <div class="signature-upload__control">
                                <input class="kss-modal__input" id="userSignatureInput" name="signature" type="file" accept="image/png">
                                <span class="signature-upload__hint">Gunakan file PNG, maksimal 2 MB. Saat edit, kosongkan jika tidak ingin mengganti tanda tangan.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kss-modal__footer">
                <button type="button" class="kss-modal__button" data-modal-close>Batal</button>
                <button type="submit" class="kss-modal__button kss-modal__button--primary" id="userFormSubmit">
                    <i class="fi fi-rr-disk"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userModal = document.getElementById('userFormModal');
        const userTitle = document.getElementById('userFormTitle');
        const userSubtitle = document.getElementById('userFormSubtitle');
        const userIcon = document.getElementById('userFormIcon');
        const userSubmit = document.getElementById('userFormSubmit');
        const userForm = document.getElementById('userForm');
        const userFormMethod = document.getElementById('userFormMethod');
        const userStoreUrl = @json(route('admin.users.store'));
        const userStatusWrap = document.getElementById('userStatusInputWrap');
        const userStatusLabel = document.getElementById('userStatusInputLabel');
        const fields = {
            name: document.getElementById('userNameInput'),
            username: document.getElementById('userUsernameInput'),
            role: document.getElementById('userRoleInput'),
            regu: document.getElementById('userReguInput'),
            status: document.getElementById('userStatusInput'),
            password: document.getElementById('userPasswordInput'),
            signature: document.getElementById('userSignatureInput'),
        };
        const signaturePreview = document.getElementById('userSignaturePreview');
        const passwordToggle = document.getElementById('userPasswordToggle');
        let editingIsSelf = false;

        function setPasswordVisible(visible) {
            if (!passwordToggle) return;
            fields.password.type = visible ? 'text' : 'password';
            const icon = passwordToggle.querySelector('i');
            if (icon) icon.className = visible ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye';
            const label = visible ? 'Sembunyikan password' : 'Tampilkan password';
            passwordToggle.setAttribute('aria-label', label);
            passwordToggle.title = label;
        }

        if (passwordToggle) {
            passwordToggle.addEventListener('click', function () {
                setPasswordVisible(fields.password.type === 'password');
            });
        }

        function setFieldsDisabled(disabled) {
            Object.values(fields).forEach(field => field.disabled = disabled);
            if (userSubmit) userSubmit.hidden = disabled;
            window.KssAdminModal.syncSelects(userModal);
        }

        function setUserStatus(isActive) {
            fields.status.checked = isActive;
            userStatusWrap.classList.toggle('is-active', isActive);
            userStatusLabel.textContent = isActive ? 'Aktif' : 'Non-Aktif';
        }

        function fillUserForm(data = {}) {
            fields.name.value = data.name || '';
            fields.username.value = data.username || '';
            fields.role.value = data.roleId || fields.role.options[0]?.value || '';
            fields.regu.value = data.group || 'A';
            setUserStatus((data.status || 'aktif').toLowerCase() === 'aktif');
            fields.password.value = '';
            setPasswordVisible(false);
            fields.password.placeholder = data.mode === 'edit' ? 'Kosongkan jika tidak diubah' : 'Min. 5 karakter';
            fields.password.required = data.mode !== 'edit' && data.mode !== 'view';
            fields.signature.value = '';
            setSignaturePreview(data.signatureUrl || '');
            window.KssAdminModal.syncSelects(userModal);
        }

        function setSignaturePreview(url) {
            if (!signaturePreview) return;

            if (url) {
                signaturePreview.innerHTML = `<img src="${url}" alt="Preview tanda tangan">`;
            } else {
                signaturePreview.textContent = 'Belum ada tanda tangan';
            }
        }

        function openUserModal(mode, row) {
            editingIsSelf = row?.dataset.userSelf === '1';
            const data = row ? {
                name: row.dataset.userName,
                username: row.dataset.userUsername,
                roleId: row.dataset.userRoleId,
                group: row.dataset.userGroup,
                status: row.dataset.userStatus,
                signatureUrl: row.dataset.userSignatureUrl,
                mode,
            } : { mode };

            fillUserForm(data);
            setFieldsDisabled(mode === 'view');
            if (userForm) userForm.action = mode === 'edit' && row?.dataset.userUpdateUrl ? row.dataset.userUpdateUrl : userStoreUrl;
            if (userFormMethod) userFormMethod.value = mode === 'edit' ? 'PUT' : 'POST';

            if (mode === 'add') {
                userTitle.textContent = 'Tambah Pengguna';
                userSubtitle.textContent = 'Buat akun baru beserta role dan regu pengguna.';
                userIcon.className = 'fi fi-rr-user-add';
                userSubmit.innerHTML = '<i class="fi fi-rr-disk"></i> Simpan Pengguna';
            } else if (mode === 'edit') {
                userTitle.textContent = 'Edit Pengguna';
                userSubtitle.textContent = 'Perbarui detail akun dan hak akses pengguna.';
                userIcon.className = 'fi fi-rr-pencil';
                userSubmit.innerHTML = '<i class="fi fi-rr-disk"></i> Simpan Perubahan';
            } else {
                userTitle.textContent = 'Detail Pengguna';
                userSubtitle.textContent = 'Ringkasan informasi akun pengguna.';
                userIcon.className = 'fi fi-rr-eye';
            }

            window.KssAdminModal.open(userModal);
        }

        document.getElementById('btnAddUser')?.addEventListener('click', () => openUserModal('add'));

        fields.status?.addEventListener('change', () => {
            if (editingIsSelf && !fields.status.checked) {
                window.showAdminToast?.('error', 'Tidak diizinkan', 'Anda tidak bisa menonaktifkan akun Anda sendiri. Minta admin lain untuk menonaktifkannya.');
                setUserStatus(true);
                return;
            }
            setUserStatus(fields.status.checked);
        });

        fields.signature?.addEventListener('change', function () {
            const file = fields.signature.files?.[0];

            if (!file) {
                setSignaturePreview('');
                return;
            }

            if (file.type !== 'image/png') {
                fields.signature.value = '';
                setSignaturePreview('');
                window.showAdminToast?.('error', 'File tidak valid', 'File tanda tangan harus berformat PNG.');
                return;
            }

            setSignaturePreview(URL.createObjectURL(file));
        });

        document.querySelectorAll('.js-user-status-toggle').forEach(function (toggle) {
            const ownerRow = toggle.closest('tr');

            // Cegah admin menonaktifkan akunnya sendiri lewat tombol status di tabel.
            // Capture phase + stopImmediatePropagation agar berjalan sebelum dialog konfirmasi global.
            if (ownerRow?.dataset.userSelf === '1') {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    window.showAdminToast?.('error', 'Tidak diizinkan', 'Anda tidak bisa menonaktifkan akun Anda sendiri. Minta admin lain untuk menonaktifkannya.');
                }, true);
            }

            toggle.addEventListener('change', function () {
                const row = toggle.closest('tr');
                const switchWrap = toggle.closest('.user-status-switch');
                const label = switchWrap?.querySelector('.user-status-switch__label');
                const isActive = toggle.checked;

                switchWrap?.classList.toggle('is-active', isActive);
                if (label) label.textContent = isActive ? 'Aktif' : 'Non-Aktif';
                if (row) row.dataset.userStatus = isActive ? 'aktif' : 'nonaktif';
            });
        });

        document.querySelectorAll('.js-user-edit').forEach(button => {
            button.addEventListener('click', () => openUserModal('edit', button.closest('tr')));
        });

        document.querySelectorAll('.js-user-view').forEach(button => {
            button.addEventListener('click', () => openUserModal('view', button.closest('tr')));
        });
    });
</script>
@endpush
