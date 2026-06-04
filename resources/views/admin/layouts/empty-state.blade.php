{{--
    Baris empty state / hasil pencarian kosong untuk tabel admin.

    Cara pakai (di dalam <table>, pada cabang @empty):
        @include('admin.layouts.empty-state', [
            'icon' => 'fi fi-rr-users',
            'title' => 'Belum ada pengguna',
            'message' => 'Tambahkan pengguna baru untuk mulai mengelola akun.',
        ])
--}}
@php
    $icon = $icon ?? 'fi fi-rr-inbox';
    $title = $title ?? 'Belum ada data';
    $message = $message ?? null;
@endphp
<tr class="tbody table-empty-row">
    <td class="table-empty-cell">
        <div class="table-empty-state">
            <div class="table-empty-state__icon"><i class="{{ $icon }}"></i></div>
            <div class="table-empty-state__title">{{ $title }}</div>
            @if ($message)
                <div class="table-empty-state__text">{{ $message }}</div>
            @endif
        </div>
    </td>
</tr>
