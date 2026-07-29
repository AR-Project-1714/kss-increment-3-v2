{{-- Tabel peringkat lembur personil.

     Urutan utama mengikuti total jam lembur. Perubahan posisi membandingkan
     urutan tersebut dengan periode pembanding yang setara, bukan dengan data
     sesaat di browser. Sepuluh nama pertama ditampilkan lebih dulu; sisanya
     tetap tersedia melalui tombol "lihat semua".

     Parameter:
       $leaders  ['ranking' => [...]] dari OperationalPerformanceService
       $visible  jumlah baris yang tampil sebelum daftar dibuka (bawaan 10)
--}}
@php
    $rows = array_values($leaders['ranking'] ?? []);
    $visible = max(1, (int) ($visible ?? 10));
    $hidden = max(count($rows) - $visible, 0);
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $tableId = 'overtime-ranking-'.substr(md5(implode('|', array_column($rows, 'name'))), 0, 8);
@endphp

@if ($rows === [])
    <div class="perf-empty">Belum ada lembur tercatat pada periode ini.</div>
@else
    <div class="overtime-ranking" data-overtime-ranking>
        <div class="overtime-ranking__scroll">
            <table class="overtime-ranking__table">
                <caption class="overtime-ranking__caption">
                    Peringkat personil berdasarkan total jam lembur
                </caption>
                <thead>
                    <tr>
                        <th scope="col" aria-sort="ascending">
                            <span class="overtime-ranking__heading">Posisi</span>
                            <span class="overtime-ranking__sort-controls" role="group" aria-label="Urutkan posisi">
                                <button type="button"
                                        class="overtime-ranking__sort is-active"
                                        data-overtime-sort="asc"
                                        aria-label="Urutkan posisi dari terkecil ke terbesar"
                                        aria-pressed="true"
                                        title="Urutkan posisi dari terkecil ke terbesar">
                                    <i class="fi fi-rr-caret-up" aria-hidden="true"></i>
                                </button>
                                <button type="button"
                                        class="overtime-ranking__sort"
                                        data-overtime-sort="desc"
                                        aria-label="Urutkan posisi dari terbesar ke terkecil"
                                        aria-pressed="false"
                                        title="Urutkan posisi dari terbesar ke terkecil">
                                    <i class="fi fi-rr-caret-down" aria-hidden="true"></i>
                                </button>
                            </span>
                        </th>
                        <th scope="col">Nama Petugas</th>
                        <th scope="col" class="overtime-ranking__number">Jumlah Lembur</th>
                        <th scope="col" class="overtime-ranking__number">Total Jam Lembur</th>
                        <th scope="col" class="overtime-ranking__number">Rata-rata Jam Lembur</th>
                    </tr>
                </thead>
                <tbody id="{{ $tableId }}"
                       class="overtime-ranking__body {{ $hidden > 0 ? 'is-collapsed' : '' }}"
                       data-visible-count="{{ $visible }}">
                    @foreach ($rows as $index => $person)
                        @php
                            $group = strtoupper(trim((string) ($person['group'] ?? '-')));
                            $groupInitial = $group === '' || $group === '-' ? '?' : mb_substr($group, 0, 1);
                            $groupKey = in_array($groupInitial, ['A', 'B', 'C', 'D'], true)
                                ? strtolower($groupInitial)
                                : 'other';
                            $movement = $person['movement'] ?? 'new';
                            $movementValue = (int) ($person['movementValue'] ?? 0);
                            $movementLabel = match ($movement) {
                                'up' => 'Naik '.$movementValue.' posisi',
                                'down' => 'Turun '.$movementValue.' posisi',
                                'same' => 'Posisi tetap',
                                default => 'Baru pada periode ini',
                            };
                        @endphp
                        <tr class="{{ $index >= $visible ? 'overtime-ranking__row--extra' : '' }}"
                            data-overtime-position="{{ $person['position'] ?? $index + 1 }}">
                            <td>
                                <div class="overtime-ranking__position">
                                    <span class="overtime-ranking__rank">{{ $person['position'] ?? $index + 1 }}</span>

                                    <span class="overtime-ranking__movement overtime-ranking__movement--{{ $movement }}"
                                          title="{{ $movementLabel }}"
                                          aria-label="{{ $movementLabel }}">
                                        @if ($movement === 'up')
                                            <i class="fi fi-rr-arrow-trend-up" aria-hidden="true"></i> {{ $movementValue }}
                                        @elseif ($movement === 'down')
                                            <i class="fi fi-rr-arrow-trend-down" aria-hidden="true"></i> {{ $movementValue }}
                                        @elseif ($movement === 'same')
                                            <span aria-hidden="true">—</span>
                                        @else
                                            Baru
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="overtime-ranking__person">
                                    <span class="overtime-ranking__team"
                                          aria-label="{{ $groupInitial === '?' ? 'Regu belum tercatat' : 'Regu '.$group }}">
                                        <span class="overtime-ranking__group overtime-ranking__group--{{ $groupKey }}"
                                              aria-hidden="true">{{ $groupInitial }}</span>
                                    </span>
                                    <span class="overtime-ranking__name">{{ $person['name'] }}</span>
                                </div>
                            </td>
                            <td class="overtime-ranking__number">
                                <strong>{{ $fmt($person['count'] ?? 0) }}</strong>
                                <span>kali</span>
                            </td>
                            <td class="overtime-ranking__number">
                                <strong>{{ $fmt($person['hours'] ?? 0, 1) }}</strong>
                                <span>jam</span>
                            </td>
                            <td class="overtime-ranking__number">
                                <strong>{{ $fmt($person['averageHours'] ?? 0, 1) }}</strong>
                                <span>jam</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($hidden > 0)
            <button type="button"
                    class="overtime-ranking__more"
                    data-leader-toggle
                    aria-controls="{{ $tableId }}"
                    aria-expanded="false"
                    data-label-more="Lihat semua {{ $fmt(count($rows)) }} personil"
                    data-label-less="Tampilkan {{ $visible }} teratas">
                <span data-leader-toggle-label>Lihat semua {{ $fmt(count($rows)) }} personil</span>
                <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
            </button>
        @endif
    </div>
@endif
