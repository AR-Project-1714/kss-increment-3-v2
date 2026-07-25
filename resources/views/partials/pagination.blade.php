{{--
    Paginasi bersama untuk daftar berhalaman (arsip admin, riwayat laporan
    pemeliharaan & K3). Dipakai menggantikan $paginator->links() bawaan Laravel,
    yang merender markup Tailwind mentah dan tampil berantakan di aplikasi ini.

    Variabel:
      $paginator : instance LengthAwarePaginator
      $label     : sebutan data untuk teks ringkasan (mis. "laporan")
--}}
@php
    $label = $label ?? 'data';
    $paginator = $paginator ?? null;
    $total = $paginator && method_exists($paginator, 'total') ? $paginator->total() : 0;
    $firstItem = $paginator && method_exists($paginator, 'firstItem') ? $paginator->firstItem() : ($total > 0 ? 1 : null);
    $lastItem = $paginator && method_exists($paginator, 'lastItem') ? $paginator->lastItem() : $total;
@endphp

@if ($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    @php($paginationWindow = \App\Support\PaginationWindow::build($paginator))

    <div class="kss-pagination">
        <div class="kss-pagination__info">
            Menampilkan {{ $firstItem }}-{{ $lastItem }} dari {{ $total }} {{ $label }}
        </div>
        <div class="kss-pagination__list">
            @if ($paginator->onFirstPage())
                <span class="kss-pagination__disabled"><i class="fi fi-rr-angle-small-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="kss-pagination__link" aria-label="Halaman sebelumnya">
                    <i class="fi fi-rr-angle-small-left"></i>
                </a>
            @endif

            {{-- Jendela nomor halaman: halaman pertama & terakhir selalu
                 terlihat sebagai pintasan lompat ke ujung, elipsis mengisi
                 jarak yang terpotong di tengah. --}}
            @foreach ($paginationWindow as $item)
                @if ($item['type'] === 'ellipsis')
                    <span class="kss-pagination__ellipsis" aria-hidden="true">&hellip;</span>
                @else
                    <a href="{{ $paginator->url($item['page']) }}"
                       class="kss-pagination__link {{ $paginator->currentPage() === $item['page'] ? 'active' : '' }}"
                       aria-label="Halaman {{ $item['page'] }}"
                       @if ($paginator->currentPage() === $item['page']) aria-current="page" @endif>
                        {{ $item['page'] }}
                    </a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="kss-pagination__link" aria-label="Halaman berikutnya">
                    <i class="fi fi-rr-angle-small-right"></i>
                </a>
            @else
                <span class="kss-pagination__disabled"><i class="fi fi-rr-angle-small-right"></i></span>
            @endif
        </div>
    </div>
@endif
