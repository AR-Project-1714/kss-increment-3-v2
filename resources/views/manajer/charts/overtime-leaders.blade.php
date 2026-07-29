{{-- Papan peringkat lembur personil.

     Ditampilkan dalam dua ukuran karena keduanya menjawab hal berbeda: jam
     lembur menunjukkan beban waktu, frekuensi menunjukkan seberapa sering
     seseorang diminta. Sebagian entri lembur diisi tanpa jam, sehingga orang
     yang muncul di daftar frekuensi belum tentu ada di daftar jam.

     Daftar dipotong di sepuluh nama pertama, sisanya ada di DOM tetapi
     disembunyikan CSS dan dibuka lewat tombol di bawah tiap kolom. Dengan
     begitu kartunya tetap ringkas untuk pembacaan sekilas, sementara daftar
     penuh tetap satu klik jauhnya — dan tetap terbaca mesin pencari maupun
     pembaca layar tanpa permintaan tambahan ke server.

     Parameter:
       $leaders  ['hours' => [...], 'count' => [...]] dari OperationalPerformanceService
       $visible  jumlah baris yang tampil sebelum daftar dibuka (bawaan 10)
--}}
@php
    $leaders = $leaders ?? ['hours' => [], 'count' => []];
    $visible = (int) ($visible ?? 10);
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');

    $panels = [
        [
            'key' => 'hours',
            'title' => 'Jam Lembur Terbanyak',
            'color' => 'var(--chart-3)',
            'unit' => 'jam',
            'decimals' => 1,
        ],
        [
            'key' => 'count',
            'title' => 'Paling Sering Lembur',
            'color' => 'var(--chart-5)',
            'unit' => 'kali',
            'decimals' => 0,
        ],
    ];

    $hasAny = ($leaders['hours'] ?? []) !== [] || ($leaders['count'] ?? []) !== [];
@endphp

@if (! $hasAny)
    <div class="perf-empty">Belum ada lembur tercatat pada periode ini.</div>
@else
    <div class="leader-board">
        @foreach ($panels as $panel)
            @php
                $rows = array_values($leaders[$panel['key']] ?? []);
                $total = count($rows);
                $hidden = max($total - $visible, 0);

                // Id daftar diturunkan dari isinya, bukan dari nomor acak,
                // supaya keluaran halaman tetap sama bila datanya sama.
                $listId = 'leader-'.$panel['key'].'-'.substr(md5($panel['key'].'|'.implode('|', array_column($rows, 'name'))), 0, 6);
            @endphp

            <div class="leader-board__panel">
                <span class="leader-board__title">{{ $panel['title'] }}</span>

                @if ($rows === [])
                    <span class="leader-board__empty">Belum ada data dengan jam tercatat.</span>
                @else
                    <ol id="{{ $listId }}" class="leader-board__list {{ $hidden > 0 ? 'is-collapsed' : '' }}">
                        @foreach ($rows as $index => $person)
                            <li class="leader-board__item {{ $index >= $visible ? 'leader-board__item--extra' : '' }}">
                                <span class="leader-board__rank">{{ $index + 1 }}</span>

                                <div class="leader-board__body">
                                    <div class="leader-board__head">
                                        <span class="leader-board__name">{{ $person['name'] }}</span>
                                        <span class="leader-board__value">
                                            {{ $fmt($person[$panel['key']], $panel['decimals']) }} {{ $panel['unit'] }}
                                        </span>
                                    </div>

                                    <div class="leader-board__track"
                                         data-chart-tip
                                         data-tip-title="{{ $person['name'] }}"
                                         data-tip-rows="{{ json_encode([
                                            ['label' => 'Jam lembur', 'value' => $fmt($person['hours'], 1).' jam', 'color' => 'var(--chart-3)'],
                                            ['label' => 'Frekuensi', 'value' => $fmt($person['count']).' kali', 'color' => 'var(--chart-5)'],
                                         ]) }}">
                                        <span class="leader-board__fill"
                                              style="width: {{ round($person['share'], 2) }}%; background-color: {{ $panel['color'] }};"></span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($hidden > 0)
                        {{-- Jumlahnya disebut di label supaya pembaca tahu seberapa
                             panjang daftar sebelum memutuskan membukanya. --}}
                        <button type="button"
                                class="leader-board__more"
                                data-leader-toggle
                                aria-controls="{{ $listId }}"
                                aria-expanded="false"
                                data-label-more="Lihat semua {{ $fmt($total) }} personil"
                                data-label-less="Tampilkan {{ $visible }} teratas">
                            <span data-leader-toggle-label>Lihat semua {{ $fmt($total) }} personil</span>
                            <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
                        </button>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
@endif
