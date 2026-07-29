<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor Excel halaman Performa Operasional.
 *
 * Berkasnya menyalin isi halaman apa adanya, satu sheet per bagian:
 *
 *   Kinerja Operasional — rekap kegiatan mengikuti format laporan manajemen:
 *                         bulan berjalan · bulan sebelumnya · akumulasi
 *   Ringkasan        — empat KPI + beban kerja + status rasio kerusakan
 *   Per Kegiatan     — lima kegiatan operasi beserta satuan dan analisisnya
 *   Tren Bulanan     — tabel 6 bulan (tonase, laporan, per shift)
 *   Regu & Kegiatan  — perbandingan regu + komposisi jenis kegiatan
 *   Peringkat Lembur — jam terbanyak & paling sering, berdampingan
 *
 * Sheet "Kapal Dilayani" sudah tidak ada: blok kapal dihapus dari halaman
 * Kinerja Operasi, dan ekspor memang harus menggambarkan halamannya. Data kapal
 * pada laporan harian sendiri tidak disentuh.
 *
 * Satuan ditulis di kolom tersendiri karena tidak seragam: container dicatat
 * dalam Teus sedangkan kegiatan lain dalam Ton, sehingga penerimanya tidak
 * keliru menjumlahkan keduanya.
 *
 * Berbeda dari ekspor arsip yang menulis semua nilai sebagai teks, angka di
 * sini ditulis sebagai sel numerik dengan format tampilan — rekap performa
 * memang dimaksudkan untuk dihitung ulang (SUM, rata-rata) oleh penerimanya.
 */
class PerformanceExportService
{
    private const HEADER_FILL = 'E5F1FF';

    private const FORMAT_INT = '#,##0';
    private const FORMAT_ONE_DECIMAL = '#,##0.0';
    private const FORMAT_PERCENT_TEXT = '0.00"%"';

    /**
     * @param  array<string, mixed>  $report  hasil OperationalPerformanceService::performanceReport()
     * @param  array<int, string>  $contextLines  keterangan periode/filter/pengekspor
     */
    public function build(array $report, array $contextLines): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->recapSheet($spreadsheet->createSheet(), $report, $contextLines);
        $this->summarySheet($spreadsheet->createSheet(), $report, $contextLines);
        $this->activitySheet($spreadsheet->createSheet(), $report);
        $this->trendSheet($spreadsheet->createSheet(), $report);
        $this->groupActivitySheet($spreadsheet->createSheet(), $report);
        $this->overtimeSheet($spreadsheet->createSheet(), $report);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Tambahkan sheet gambaran besar untuk workbook khusus Rincian Kegiatan.
     * Semua kegiatan tetap ditampilkan, termasuk yang nilainya nol, agar
     * manajer dapat membedakan "tidak ada aktivitas" dari "tidak diekspor".
     *
     * @param  array<string, mixed>  $report
     * @param  array<int, string>  $contextLines
     */
    public function addActivityOverviewSheet(
        Spreadsheet $spreadsheet,
        array $report,
        array $contextLines
    ): Worksheet
    {
        $sheet = $spreadsheet->createSheet();
        $this->recapSheet($sheet, $report, $contextLines, true, 'Gambaran Besar');

        return $sheet;
    }

    // ============================================================
    // Sheet 1 — Rekap kegiatan (format laporan manajemen)
    // ============================================================

    /**
     * Rekap kegiatan dalam tata letak yang dipakai laporan manajemen: satu blok
     * per jenis kegiatan, tiap blok dibaca dalam tiga kelompok kolom — bulan
     * berjalan, bulan-bulan sebelumnya di dalam periode, dan akumulasinya.
     *
     * Kegiatan dengan susunan kolom yang sama digabung ke satu blok, persis
     * seperti bongkar dan muat kontainer yang berbagi satu kepala tabel.
     *
     * @param  array<string, mixed>  $report
     * @param  array<int, string>  $contextLines
     */
    private function recapSheet(
        Worksheet $sheet,
        array $report,
        array $contextLines,
        bool $includeEmpty = false,
        string $sheetTitle = 'Kinerja Operasional'
    ): void
    {
        $sheet->setTitle($sheetTitle);

        $recap = $report['activityRecap'] ?? [];
        $labels = $recap['labels'] ?? [];

        $groups = array_values(array_filter([
            ['key' => 'month', 'title' => 'BULAN SEKARANG', 'range' => $labels['month'] ?? null],
            ($recap['hasPrevious'] ?? false)
                ? ['key' => 'previous', 'title' => 'SEBELUMNYA', 'range' => $labels['previous'] ?? null]
                : null,
            ['key' => 'total', 'title' => 'AKUMULASI', 'range' => $labels['total'] ?? null],
        ]));

        // Baris yang seluruh angkanya nol tidak ikut dicetak, sama seperti di
        // halaman — rekap kosong tidak menambah informasi apa pun.
        $rows = $recap['rows'] ?? [];

        if (!$includeEmpty) {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => $row['total']['count'] > 0 || $row['total']['value'] > 0
            ));
        }

        $year = $this->periodYear($labels['total'] ?? '');

        $row = $this->writeHeading(
            $sheet,
            'KINERJA OPERASIONAL'.($year === null ? '' : ' TAHUN '.$year),
            array_merge($contextLines, [
                'Bongkar muat hasil produksi, bahan baku, dan barang penunjang produksi.',
                'Kolom Akumulasi adalah jumlah kedua kelompok sebelumnya. Kontainer bersatuan '
                .'Teus sehingga tidak dijumlahkan bersama kegiatan bersatuan Ton.',
            ])
        );

        if ($rows === []) {
            $sheet->setCellValue('A'.$row, 'Belum ada kegiatan tercatat pada periode ini.');
            $sheet->getStyle('A'.$row)->getFont()->setItalic(true);
            $this->autoSize($sheet, 4);

            return;
        }

        // Judul dibentang selebar tabel terlebar, mengikuti berkas contoh.
        $titleWidth = 1 + count($groups) * $this->widestRecapBlock($rows);
        $sheet->mergeCells('A1:'.Coordinate::stringFromColumnIndex($titleWidth).'1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($this->recapBlocks($rows) as $block) {
            $row = $this->writeRecapBlock($sheet, $row, $block, $groups);
        }

        $this->autoSize($sheet, 1 + count($groups) * 4);
    }

    /**
     * Jumlah kolom pada blok terlebar, untuk membentangkan judul.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function widestRecapBlock(array $rows): int
    {
        $widest = 0;

        foreach ($this->recapBlocks($rows) as $block) {
            $widest = max($widest, count($block['columns']));
        }

        return max($widest, 1);
    }

    /**
     * Kelompokkan baris rekap yang susunan kolomnya sama menjadi satu blok.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{columns: array<int, array<string, string>>, rows: array<int, array<string, mixed>>}>
     */
    private function recapBlocks(array $rows): array
    {
        $blocks = [];

        foreach ($rows as $row) {
            $columns = [['key' => 'count', 'label' => strtoupper($row['countLabel']), 'decimals' => 0]];

            if ($row['hasDelivery']) {
                $columns[] = ['key' => 'delivery', 'label' => 'KIRIM ('.strtoupper($row['unit']).')', 'decimals' => 2];
            }

            $columns[] = ['key' => 'value', 'label' => 'MUAT ('.strtoupper($row['unit']).')', 'decimals' => 2];

            if ($row['hasDamage']) {
                $columns[] = ['key' => 'damage', 'label' => 'KERUSAKAN ('.strtoupper($row['unit']).')', 'decimals' => 2];
            }

            $signature = implode('|', array_column($columns, 'label'));
            $last = array_key_last($blocks);

            if ($last !== null && $blocks[$last]['signature'] === $signature) {
                $blocks[$last]['rows'][] = $row;

                continue;
            }

            $blocks[] = ['signature' => $signature, 'columns' => $columns, 'rows' => [$row]];
        }

        return $blocks;
    }

    /**
     * Satu blok rekap: dua baris kepala bertingkat lalu barisan kegiatannya.
     * Mengembalikan baris awal untuk blok berikutnya.
     *
     * @param  array{columns: array<int, array<string, string>>, rows: array<int, array<string, mixed>>}  $block
     * @param  array<int, array<string, ?string>>  $groups
     */
    private function writeRecapBlock(Worksheet $sheet, int $startRow, array $block, array $groups): int
    {
        $columns = $block['columns'];
        $width = count($columns);
        $headRow = $startRow;
        $subRow = $startRow + 1;

        $sheet->setCellValue('A'.$headRow, 'KEGIATAN');
        $sheet->mergeCells('A'.$headRow.':A'.$subRow);

        $columnIndex = 2;

        foreach ($groups as $group) {
            $first = Coordinate::stringFromColumnIndex($columnIndex);
            $last = Coordinate::stringFromColumnIndex($columnIndex + $width - 1);

            $sheet->setCellValue($first.$headRow, $group['title'].($group['range'] ? ' ('.$group['range'].')' : ''));

            if ($width > 1) {
                $sheet->mergeCells($first.$headRow.':'.$last.$headRow);
            }

            foreach ($columns as $offset => $column) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + $offset).$subRow, $column['label']);
            }

            $columnIndex += $width;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($columnIndex - 1);

        $sheet->getStyle("A{$headRow}:{$lastColumn}{$subRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headRow}:{$lastColumn}{$subRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $sheet->getStyle("A{$headRow}:{$lastColumn}{$subRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $row = $subRow + 1;

        foreach ($block['rows'] as $entry) {
            $sheet->setCellValueExplicit('A'.$row, (string) $entry['label'], DataType::TYPE_STRING);

            $columnIndex = 2;

            foreach ($groups as $group) {
                foreach ($columns as $offset => $column) {
                    $cell = Coordinate::stringFromColumnIndex($columnIndex + $offset).$row;
                    $value = $this->num((float) ($entry[$group['key']][$column['key']] ?? 0), $column['decimals']);

                    $sheet->setCellValue($cell, $value['v']);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($value['f']);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $columnIndex += $width;
            }

            $row++;
        }

        $sheet->getStyle("A{$headRow}:{$lastColumn}".($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

        return $row + 1;
    }

    /**
     * Tahun dari label periode, untuk judul sheet. Label selalu diakhiri tahun
     * (mis. "1 Jan – 28 Jul 2026"); bila tidak terbaca, judulnya tanpa tahun.
     */
    private function periodYear(string $label): ?string
    {
        return preg_match('/(\d{4})\s*$/', $label, $match) === 1 ? $match[1] : null;
    }

    // ============================================================
    // Sheet 2 — Ringkasan
    // ============================================================

    /**
     * @param  array<string, mixed>  $report
     * @param  array<int, string>  $contextLines
     */
    private function summarySheet(Worksheet $sheet, array $report, array $contextLines): void
    {
        $sheet->setTitle('Ringkasan');

        $row = $this->writeHeading($sheet, 'Performa Operasional — Ringkasan', $contextLines);

        $summary = $report['summary'] ?? [];
        $damage = $summary['damageRatio'] ?? [];
        $hasDamageBase = $damage['hasBase'] ?? true;

        $row = $this->writeTable($sheet, $row, 'Indikator Utama', ['Indikator', 'Nilai', 'Satuan', 'Perubahan'], [
            ['Tonase Ditangani', $this->num($summary['tonnage']['value'] ?? 0, 1), 'Ton', $this->deltaText($summary['tonnage']['delta'] ?? [])],
            ['Tonase per Shift', $this->num($summary['tonnagePerShift']['value'] ?? 0, 1), 'Ton', $this->deltaText($summary['tonnagePerShift']['delta'] ?? [])],
            [
                'Rasio Kerusakan',
                $hasDamageBase ? $this->num($summary['damageRatio']['value'] ?? 0, 2, self::FORMAT_PERCENT_TEXT) : 'Belum ada muatan kantong',
                '%',
                $this->deltaText($damage['delta'] ?? []),
            ],
            [
                'Laporan Masuk',
                $this->num($summary['reports']['value'] ?? $report['reportCount'] ?? 0),
                'Laporan',
                $this->deltaText($summary['reports']['delta'] ?? []),
            ],
        ]);

        // Status kerusakan mengikuti zona pada gauge di halaman: ambangnya
        // harus sama supaya ekspor tidak bercerita lain dari layarnya.
        $damageValue = (float) ($summary['damageRatio']['value'] ?? 0);
        $damageStatus = match (true) {
            ! $hasDamageBase => 'Belum ada muatan kantong pada periode ini',
            $damageValue < 0.5 => 'Terkendali (di bawah 0,5%)',
            $damageValue < 1.0 => 'Perlu dipantau (0,5% – 1%)',
            default => 'Perlu tindak lanjut (di atas 1%)',
        };

        $sheet->setCellValue('A'.$row, 'Status rasio kerusakan: '.$damageStatus);
        $sheet->getStyle('A'.$row)->getFont()->setItalic(true)->setSize(10);
        $row += 2;

        $workload = $report['workload'] ?? [];

        $workloadRows = [
            ['Personil rata-rata per shift', $this->num($workload['personnelPerShift'] ?? 0, 1), 'orang', '-'],
            ['Jam lembur tercatat', $this->num($workload['overtimeHours']['value'] ?? 0, 1), 'jam', $this->deltaText($workload['overtimeHours']['delta'] ?? [])],
            ['Entri lembur', $this->num($workload['overtimeCount']['value'] ?? 0), 'entri', $this->deltaText($workload['overtimeCount']['delta'] ?? [])],
            ['Relief & pengganti', $this->num($workload['reliefCount']['value'] ?? 0), 'kali', $this->deltaText($workload['reliefCount']['delta'] ?? [])],
            ['Ketepatan waktu lapor', $this->num($workload['punctuality']['value'] ?? 0, 1, self::FORMAT_PERCENT_TEXT), '%', $this->deltaText($workload['punctuality']['delta'] ?? [])],
        ];

        foreach ($report['shifts'] ?? [] as $shift) {
            $workloadRows[] = [
                'Tonase '.$this->shiftLabel($shift['name'] ?? null),
                $this->num($shift['tonnage'] ?? 0, 1),
                'Ton',
                ($shift['reports'] ?? 0).' laporan',
            ];
        }

        $this->writeTable($sheet, $row, 'Beban Kerja', ['Uraian', 'Nilai', 'Satuan', 'Keterangan'], $workloadRows);

        $this->autoSize($sheet, 4);
    }

    // ============================================================
    // Sheet 2 — Performa per kegiatan
    // ============================================================

    /**
     * Kelima kegiatan operasi apa adanya, lengkap dengan satuannya.
     *
     * Sheet ini sengaja tidak menuliskan baris total: menjumlahkan Ton dengan
     * Teus tidak punya arti, dan total tonase yang sah sudah ada di sheet
     * Ringkasan.
     *
     * @param  array<string, mixed>  $report
     */
    private function activitySheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Per Kegiatan');

        $row = $this->writeHeading($sheet, 'Performa per Kegiatan', [
            'Periode '.($report['periodLabel'] ?? '-').' · perubahan dihitung '.($report['comparisonLabel'] ?? '-').'.',
            'Container dicatat dalam Teus sehingga tidak dijumlahkan bersama kegiatan bersatuan Ton.',
        ]);

        $rows = [];

        foreach ($report['activityCards'] ?? [] as $card) {
            $rows[] = [
                $card['label'] ?? '-',
                $this->num($card['value'] ?? 0, 1),
                $card['unit'] ?? '-',
                $this->deltaText($card['delta'] ?? []),
            ];
        }

        $row = $this->writeTable($sheet, $row, 'Kegiatan Operasi', [
            'Jenis Kegiatan', 'Nilai', 'Satuan', 'Perubahan',
        ], $rows, 'Belum ada kegiatan tercatat pada periode ini.');

        // Analisis per kegiatan mengikuti panel di halaman: sebaran shift,
        // regu teratas, dan beban lembur pada laporan yang memuat kegiatan itu.
        $analysisRows = [];

        foreach ($report['activityPanels'] ?? [] as $panel) {
            $shifts = [];

            foreach ($panel['shifts'] ?? [] as $shift) {
                $shifts[$shift['name']] = (float) $shift['value'];
            }

            $topGroup = ($panel['groups'] ?? [])[0] ?? null;

            $analysisRows[] = [
                $panel['label'] ?? '-',
                $panel['unit'] ?? '-',
                $this->num($panel['value'] ?? 0, 1),
                $this->num($panel['composition']['contribution'] ?? 0, 1, self::FORMAT_PERCENT_TEXT),
                $this->num($shifts['Pagi'] ?? 0, 1),
                $this->num($shifts['Sore'] ?? 0, 1),
                $this->num($shifts['Malam'] ?? 0, 1),
                $topGroup === null ? '-' : 'Regu '.$topGroup['name'].' ('.number_format($topGroup['value'], 1, ',', '.').' '.$panel['unit'].')',
                $this->num($panel['workload']['reports'] ?? 0),
                $this->num($panel['workload']['overtimeHours'] ?? 0, 1),
            ];
        }

        $this->writeTable($sheet, $row, 'Analisis per Kegiatan', [
            'Jenis Kegiatan', 'Satuan', 'Nilai', 'Porsi Satuan Sama',
            'Shift Pagi', 'Shift Sore', 'Shift Malam',
            'Regu Teratas', 'Laporan', 'Jam Lembur',
        ], $analysisRows, 'Belum ada kegiatan tercatat pada periode ini.');

        $this->autoSize($sheet, 10);
    }

    // ============================================================
    // Sheet 3 — Tren bulanan
    // ============================================================

    /**
     * @param  array<string, mixed>  $report
     */
    private function trendSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Tren Bulanan');

        $row = $this->writeHeading($sheet, 'Tren 6 Bulan Terakhir', [
            'Tonase per shift kerja diambil dari grafik "Tonase per Shift" pada halaman.',
        ]);

        // Tren utama dan pecahan shift datang dari dua query berbeda tetapi
        // periodenya identik, jadi digabung per indeks bulan.
        $shiftByMonth = [];
        foreach ($report['shiftTrend'] ?? [] as $index => $bucket) {
            $shiftByMonth[$index] = $bucket;
        }

        $rows = [];
        foreach ($report['trend'] ?? [] as $index => $bucket) {
            $shiftBucket = $shiftByMonth[$index] ?? [];

            $rows[] = [
                $bucket['label'] ?? '-',
                $this->num($bucket['tonnage'] ?? 0, 1),
                // Kolom sendiri, bukan ditambahkan ke tonase: container
                // bersatuan Teus sehingga tidak sejenis dengan Ton.
                $this->num($bucket['teus'] ?? 0),
                $this->num($bucket['reports'] ?? 0),
                $this->num($bucket['tonnagePerShift'] ?? 0, 1),
                $this->num($bucket['damageRatio'] ?? 0, 2, self::FORMAT_PERCENT_TEXT),
                $this->num($shiftBucket['Pagi'] ?? 0, 1),
                $this->num($shiftBucket['Sore'] ?? 0, 1),
                $this->num($shiftBucket['Malam'] ?? 0, 1),
            ];
        }

        $this->writeTable($sheet, $row, 'Rekap Bulanan', [
            'Bulan', 'Tonase (Ton)', 'Container (Teus)', 'Laporan', 'Ton/Shift', 'Rasio Kerusakan',
            'Shift Pagi (Ton)', 'Shift Sore (Ton)', 'Shift Malam (Ton)',
        ], $rows);

        $this->autoSize($sheet, 9);
    }

    // ============================================================
    // Sheet 3 — Regu & kegiatan
    // ============================================================

    /**
     * @param  array<string, mixed>  $report
     */
    private function groupActivitySheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Regu & Kegiatan');

        $row = $this->writeHeading($sheet, 'Perbandingan Regu & Komposisi Kegiatan', [
            'Periode '.($report['periodLabel'] ?? '-').' · perubahan dihitung '.($report['comparisonLabel'] ?? '-').'.',
            'Tabel regu selalu memuat seluruh regu meski filter regu aktif — sama seperti '
            .'di halaman, karena gunanya memang untuk membandingkan antar regu.',
        ]);

        $groupRows = [];
        foreach ($report['groups'] ?? [] as $group) {
            $groupRows[] = [
                'Regu '.($group['name'] ?? '-'),
                $this->num($group['reports'] ?? 0),
                $this->num($group['tonnage'] ?? 0, 1),
                $this->num($group['tonnagePerShift'] ?? 0, 1),
                $this->num($group['damageRatio'] ?? 0, 2, self::FORMAT_PERCENT_TEXT),
                $this->deltaText($group['delta'] ?? []),
            ];
        }

        $row = $this->writeTable($sheet, $row, 'Perbandingan Regu (diurutkan menurut tonase)', [
            'Regu', 'Laporan', 'Tonase (Ton)', 'Ton/Shift', 'Rasio Kerusakan', 'Δ Tonase',
        ], $groupRows, 'Belum ada laporan operasi pada periode ini.');

        $activityRows = [];
        foreach ($report['activities'] ?? [] as $activity) {
            $activityRows[] = [
                $activity['label'] ?? '-',
                $this->num($activity['tonnage'] ?? 0, 1),
                $this->num($activity['contribution'] ?? 0, 1, self::FORMAT_PERCENT_TEXT),
                $this->deltaText($activity['delta'] ?? []),
            ];
        }

        // Hanya kegiatan bersatuan Ton yang muncul di sini, karena porsinya
        // dihitung terhadap total tonase. Container ada di sheet Per Kegiatan.
        $this->writeTable($sheet, $row, 'Komposisi Kegiatan (satuan Ton)', [
            'Jenis Kegiatan', 'Tonase (Ton)', 'Porsi', 'Δ Tonase',
        ], $activityRows, 'Belum ada tonase pada periode ini.');

        $this->autoSize($sheet, 6);
    }

    // ============================================================
    // Sheet 4 — Peringkat lembur
    // ============================================================

    /**
     * @param  array<string, mixed>  $report
     */
    private function overtimeSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Peringkat Lembur');

        $row = $this->writeHeading($sheet, 'Peringkat Lembur Personil', [
            'Dua ukuran dipakai karena sebagian entri lembur diisi tanpa jam — '
            .'personil yang sering diminta belum tentu muncul pada daftar jam.',
        ]);

        $leaders = $report['overtimeLeaders'] ?? ['hours' => [], 'count' => []];

        $hourRows = [];
        foreach ($leaders['hours'] ?? [] as $index => $person) {
            $hourRows[] = [
                $index + 1,
                $person['name'] ?? '-',
                $this->num($person['hours'] ?? 0, 1),
                $this->num($person['count'] ?? 0),
            ];
        }

        $row = $this->writeTable($sheet, $row, 'Jam Lembur Terbanyak', [
            'Peringkat', 'Nama', 'Total Jam', 'Frekuensi (kali)',
        ], $hourRows, 'Belum ada lembur dengan jam tercatat.');

        $countRows = [];
        foreach ($leaders['count'] ?? [] as $index => $person) {
            $countRows[] = [
                $index + 1,
                $person['name'] ?? '-',
                $this->num($person['count'] ?? 0),
                $this->num($person['hours'] ?? 0, 1),
            ];
        }

        $this->writeTable($sheet, $row, 'Paling Sering Lembur', [
            'Peringkat', 'Nama', 'Frekuensi (kali)', 'Total Jam',
        ], $countRows, 'Belum ada lembur tercatat pada periode ini.');

        $this->autoSize($sheet, 4);
    }

    // ============================================================
    // Pembantu penulisan sel
    // ============================================================

    /**
     * Judul sheet + baris konteks miring. Mengembalikan baris kosong pertama
     * setelahnya.
     *
     * @param  array<int, string>  $contextLines
     */
    private function writeHeading(Worksheet $sheet, string $title, array $contextLines): int
    {
        $row = 1;
        $sheet->setCellValue('A'.$row, $title);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $row++;

        foreach ($contextLines as $line) {
            $sheet->setCellValue('A'.$row, $line);
            $sheet->getStyle('A'.$row)->getFont()->setItalic(true)->setSize(10);
            $row++;
        }

        return $row + 1;
    }

    /**
     * Satu tabel bertajuk: judul kecil tebal, header berlatar biru muda,
     * lalu barisan data. Mengembalikan baris awal untuk tabel berikutnya.
     *
     * Nilai numerik dinyatakan lewat array ['v' => angka, 'f' => format] dari
     * helper num(); selain itu ditulis sebagai teks eksplisit agar Excel tidak
     * menebak-nebak (mis. "1-25 Jul" bukan tanggal).
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function writeTable(
        Worksheet $sheet,
        int $startRow,
        string $title,
        array $headers,
        array $rows,
        string $emptyText = 'Tidak ada data.'
    ): int {
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $row = $startRow;

        $sheet->setCellValue('A'.$row, $title);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;

        $headerRow = $row;
        foreach ($headers as $index => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$headerRow, $label);
        }
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $row++;

        if ($rows === []) {
            $sheet->setCellValue('A'.$row, $emptyText);
            $sheet->getStyle('A'.$row)->getFont()->setItalic(true);

            return $row + 2;
        }

        foreach ($rows as $values) {
            foreach (array_values($values) as $index => $value) {
                $cell = Coordinate::stringFromColumnIndex($index + 1).$row;

                if (is_array($value) && array_key_exists('v', $value)) {
                    $sheet->setCellValue($cell, $value['v']);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($value['f']);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                }
            }

            $row++;
        }

        $sheet->getStyle("A{$headerRow}:{$lastColumn}".($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

        return $row + 2;
    }

    /**
     * Bungkus angka beserta format tampilannya untuk writeTable.
     *
     * @return array{v: float|int, f: string}
     */
    private function num(float|int $value, int $decimals = 0, ?string $format = null): array
    {
        return [
            'v' => round((float) $value, $decimals),
            'f' => $format ?? ($decimals > 0 ? self::FORMAT_ONE_DECIMAL : self::FORMAT_INT),
        ];
    }

    /**
     * Teks perubahan mengikuti konvensi badge di halaman: tanda +/− sesuai
     * arah, atau keterangan "Belum ada data"-nya apa adanya.
     *
     * @param  array<string, mixed>  $delta
     */
    private function deltaText(array $delta): string
    {
        if (! ($delta['available'] ?? false)) {
            return $delta['text'] ?? '-';
        }

        $sign = match ($delta['direction'] ?? 'flat') {
            'up' => '+',
            'down' => '−',
            default => '',
        };

        return $sign.($delta['text'] ?? '-');
    }

    private function shiftLabel(?string $shift): string
    {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1'], true) => 'Shift Pagi',
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2'], true) => 'Shift Sore',
            in_array($normalized, ['3', 'malam', 'shift 3'], true) => 'Shift Malam',
            default => $shift ? 'Shift '.$shift : 'Shift -',
        };
    }

    private function autoSize(Worksheet $sheet, int $columns): void
    {
        foreach (range(1, $columns) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }
    }
}
