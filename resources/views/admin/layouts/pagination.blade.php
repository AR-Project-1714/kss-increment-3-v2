@php
    $label = $label ?? 'data';
    $paginator = $paginator ?? null;
    $total = $paginator && method_exists($paginator, 'total') ? $paginator->total() : 0;
    $firstItem = $paginator && method_exists($paginator, 'firstItem') ? $paginator->firstItem() : ($total > 0 ? 1 : null);
    $lastItem = $paginator && method_exists($paginator, 'lastItem') ? $paginator->lastItem() : $total;
@endphp

@if ($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="admin-pagination">
        <div class="admin-pagination__info">
            Menampilkan {{ $firstItem }}-{{ $lastItem }} dari {{ $total }} {{ $label }}
        </div>
        <div class="admin-pagination__list">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination__disabled"><i class="fi fi-rr-angle-small-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="admin-pagination__link" aria-label="Halaman sebelumnya">
                    <i class="fi fi-rr-angle-small-left"></i>
                </a>
            @endif

            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                <a href="{{ $url }}"
                   class="admin-pagination__link {{ $paginator->currentPage() === $page ? 'active' : '' }}"
                   aria-label="Halaman {{ $page }}">
                    {{ $page }}
                </a>
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="admin-pagination__link" aria-label="Halaman berikutnya">
                    <i class="fi fi-rr-angle-small-right"></i>
                </a>
            @else
                <span class="admin-pagination__disabled"><i class="fi fi-rr-angle-small-right"></i></span>
            @endif
        </div>
    </div>
@endif
