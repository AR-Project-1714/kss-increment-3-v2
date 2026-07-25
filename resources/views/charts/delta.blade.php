{{-- Badge pill perubahan untuk kartu KPI.

     Arah panah dan warna sengaja dipisah: metrik seperti rasio kerusakan
     memakai panah turun dengan warna hijau karena penurunannya justru berarti
     membaik.

     Arah 'none' dipakai untuk angka yang bukan perubahan — misalnya tingkat
     penyelesaian — karena panah apa pun di situ akan salah dibaca.

     Saat delta tidak tersedia (belum ada pembanding), badge tidak dirender;
     keterangannya ditampilkan kartu sebagai catatan di bawah angka. --}}
@php
    $direction = $delta['direction'] ?? 'flat';
    $tone = $delta['tone'] ?? 'flat';
    $arrow = match ($direction) {
        'up' => 'fi fi-rr-arrow-trend-up',
        'down' => 'fi fi-rr-arrow-trend-down',
        'none' => null,
        default => 'fi fi-rr-minus-small',
    };
@endphp

@if ($delta['available'] ?? false)
    <span class="kpi-card__delta kpi-card__delta--{{ $tone }}">
        @if ($arrow)
            <i class="{{ $arrow }}" aria-hidden="true"></i>
        @endif
        <span>{{ $delta['text'] ?? '-' }}</span>
    </span>
@endif
