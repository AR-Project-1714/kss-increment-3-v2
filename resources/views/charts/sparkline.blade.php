{{-- Sparkline area enam bulan.

     Dipakai kartu KPI dan kepala panel kegiatan, jadi ditaruh di charts/ —
     bukan di salah satu layout — supaya keduanya tidak sempat berbeda bentuk.

     Warnanya mengikuti nada delta ('up' hijau, 'down' merah, 'flat' abu),
     bukan nilai deretnya sendiri: yang ingin dibaca sekilas adalah "periode ini
     lebih baik atau lebih buruk daripada pembandingnya", sementara bentuk
     kurvanya menjelaskan bagaimana sampai ke sana. Isian gradiennya memakai
     currentColor sehingga satu definisi melayani ketiga nada.

     Parameter:
       $points  hasil sparklinePoints() dari OperationalPerformanceService
       $tone    'up' | 'down' | 'flat'
       $id      pembeda gradien; wajib unik dalam satu halaman
       $class   kelas tambahan pada elemen svg (opsional)
       $label   teks aria-label. Bila kosong, svg ditandai aria-hidden karena
                artinya sudah disebut elemen lain di sekitarnya (opsional)
--}}
@php
    $points = trim($points ?? '');

    // Titik pertama & terakhir dipakai untuk menutup bentuk isian sampai ke
    // dasar kanvas, sehingga garisnya punya area.
    $sparkPoints = $points === '' ? [] : explode(' ', $points);
    $firstX = $sparkPoints === [] ? '0' : (explode(',', $sparkPoints[0])[0] ?? '0');
    $lastX = $sparkPoints === [] ? '100' : (explode(',', end($sparkPoints))[0] ?? '100');
    $areaPoints = $points.' '.$lastX.',24 '.$firstX.',24';

    $label = trim($label ?? '');
@endphp

@if ($points !== '')
    <svg class="spark spark--{{ $tone ?? 'flat' }} {{ $class ?? '' }}"
         viewBox="0 0 100 24"
         preserveAspectRatio="none"
         @if ($label !== '') role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
        <defs>
            <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="currentColor" stop-opacity="0.26"></stop>
                <stop offset="100%" stop-color="currentColor" stop-opacity="0"></stop>
            </linearGradient>
        </defs>
        <polygon class="spark__area" points="{{ $areaPoints }}" fill="url(#{{ $id }})"></polygon>
        <polyline class="spark__line" points="{{ $points }}"></polyline>
    </svg>
@endif
