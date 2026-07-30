{{--
    Markup bar "pilih laporan" + panel progres bundel latar.

    Ditempatkan tepat di atas tabel arsip oleh halaman pemanggil:
        @include('partials.archive-bulk-bar', [
            'context'      => 'admin',      // atau 'manajer'
            'total'        => $archiveTotal,
            'pageCount'    => $archivePageCount,
            'instantLimit' => $archiveInstantLimit,
            'bundleLimit'  => $archiveBundleLimit,
            'search'       => $archiveSearch,
            'date'         => $selectedDate,
            'division'     => $selectedDivision,
            'group'        => $selectedGroup,
            'shift'        => $selectedShift,
            'status'       => $selectedStatus,
        ])

    CSS & JS-nya ada di partials/archive-bulk-download.blade.php (di-include
    sekali di akhir halaman).

    Catatan struktur: form TIDAK membungkus tabel (tiap baris punya form aksinya
    sendiri) dan panel progres berada DI LUAR form — bundel yang sedang jalan
    harus tetap terlihat walau pilihannya sudah dibersihkan.
--}}
@php
    $bulkRoute = $context === 'manajer' ? 'manajer.archive.bulk-download' : 'admin.archive.bulk-download';
    $bundleRoute = $context === 'manajer' ? 'manajer.archive.bundles.store' : 'admin.archive.bundles.store';
@endphp

<form method="POST"
      action="{{ route($bulkRoute) }}"
      id="archive-bulk-form"
      class="is-empty"
      data-bulk-form
      data-context="{{ $context }}"
      data-filter-total="{{ $total }}"
      data-instant-limit="{{ $instantLimit }}"
      data-bundle-limit="{{ $bundleLimit }}"
      data-bundle-url="{{ route($bundleRoute) }}">
    @csrf
    <input type="hidden" name="all" value="0" data-bulk-all>
    {{-- Dipakai server hanya saat mode "pilih semua": bundel mengikuti filter
         aktif, bukan daftar kunci baris yang tampil di halaman ini. --}}
    <input type="hidden" name="q" value="{{ $search ?? '' }}">
    <input type="hidden" name="tanggal" value="{{ $date ?? '' }}">
    <input type="hidden" name="divisi" value="{{ $division ?? 'all' }}">
    <input type="hidden" name="regu" value="{{ ($group ?? 'ALL') === 'ALL' ? 'all' : $group }}">
    <input type="hidden" name="shift" value="{{ $shift ?? 'all' }}">
    <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
    <div data-bulk-keys hidden></div>

    <div class="archive-bulk-bar" data-bulk-bar hidden>
        <span class="archive-bulk-bar__info">
            <i class="fi fi-rr-check-double" aria-hidden="true"></i>
            <span data-bulk-count>0 laporan dipilih</span>
            <span class="archive-bulk-bar__hint" data-bulk-hint>Sampai {{ $instantLimit }} laporan diunduh langsung</span>
        </span>
        <div class="archive-bulk-bar__actions">
            @if ($total > $pageCount)
                <button type="button" class="btn-tool" data-bulk-select-all>
                    <i class="fi fi-rr-list-check" aria-hidden="true"></i>
                    Pilih semua {{ $total }} hasil
                </button>
            @endif
            <button type="button" class="btn-tool" data-bulk-clear>
                <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                Batal pilih
            </button>
            <button type="submit" class="btn-tool btn-tool--primary" data-bulk-submit>
                <i class="fi fi-rr-file-download" aria-hidden="true"></i>
                <span data-bulk-submit-label>Unduh ZIP</span>
            </button>
        </div>
    </div>
</form>

<div class="archive-bundle-panel" data-bundle-panel hidden>
    <div class="archive-bundle-panel__head">
        <span class="archive-bundle-panel__title">
            <i class="fi fi-rr-box-open" aria-hidden="true"></i>
            <span data-bundle-title>Menyiapkan bundel di latar</span>
        </span>
        <span class="archive-bundle-panel__meta" data-bundle-meta></span>
    </div>

    <div class="archive-bundle-progress"
         role="progressbar"
         aria-label="Progres penyiapan bundel"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-valuenow="0"
         data-bundle-progress>
        <span class="archive-bundle-progress__fill" data-bundle-fill></span>
    </div>

    <div class="archive-bundle-panel__foot">
        <span class="archive-bundle-panel__hint" data-bundle-hint></span>
        <div class="archive-bundle-panel__actions">
            <button type="button" class="btn-tool" data-bundle-dismiss>
                <i class="fi fi-rr-cross-small" aria-hidden="true"></i>
                <span data-bundle-dismiss-label>Batalkan</span>
            </button>
            <button type="button" class="btn-tool btn-tool--primary" data-bundle-download hidden>
                <i class="fi fi-rr-file-download" aria-hidden="true"></i>
                Unduh ZIP
            </button>
        </div>
    </div>
</div>
