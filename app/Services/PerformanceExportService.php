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
 *   Ringkasan        — empat KPI + beban kerja + status rasio kerusakan
 *   Tren Bulanan     — tabel 6 bulan (tonase, laporan, kapal, per shift)
 *   Regu & Kegiatan  — perbandingan regu + komposisi jenis kegiatan
 *   Peringkat Lembur — jam terbanyak & paling sering, berdampingan
 *   Kapal Dilayani   — daftar kunjungan kapal beserta realisasinya
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

        $this->summarySheet($spreadsheet->createSheet(), $report, $contextLines);
        $this->trendSheet($spreadsheet->createSheet(), $report);
        $this->groupActivitySheet($spreadsheet->createSheet(), $report);
        $this->overtimeSheet($spreadsheet->createSheet(), $report);
        $this->shipSheet($spreadsheet->createSheet(), $report);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // ============================================================
    // Sheet 1 — Ringkasan
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
            ['Kapal Dilayani', $this->num($summary['ships']['value'] ?? 0), 'Kapal', $this->deltaText($summary['ships']['delta'] ?? [])],
            ['Tonase per Shift', $this->num($summary['tonnagePerShift']['value'] ?? 0, 1), 'Ton', $this->deltaText($summary['tonnagePerShift']['delta'] ?? [])],
            [
                'Rasio Kerusakan',
                $hasDamageBase ? $this->num($summary['damageRatio']['value'] ?? 0, 2, self::FORMAT_PERCENT_TEXT) : 'Belum ada muatan kantong',
                '%',
                $this->deltaText($damage['delta'] ?? []),
            ],
            ['Jumlah Laporan Masuk', $this->num($report['reportCount'] ?? 0), 'Laporan', '-'],
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
    // Sheet 2 — Tren bulanan
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
                $this->num($bucket['reports'] ?? 0),
                $this->num($bucket['ships'] ?? 0),
                $this->num($bucket['tonnagePerShift'] ?? 0, 1),
                $this->num($bucket['damageRatio'] ?? 0, 2, self::FORMAT_PERCENT_TEXT),
                $this->num($shiftBucket['Pagi'] ?? 0, 1),
                $this->num($shiftBucket['Sore'] ?? 0, 1),
                $this->num($shiftBucket['Malam'] ?? 0, 1),
            ];
        }

        $this->writeTable($sheet, $row, 'Rekap Bulanan', [
            'Bulan', 'Tonase (Ton)', 'Laporan', 'Kapal', 'Ton/Shift', 'Rasio Kerusakan',
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

        $this->writeTable($sheet, $row, 'Komposisi Kegiatan', [
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
    // Sheet 5 — Kapal
    // ============================================================

    /**
     * @param  array<string, mixed>  $report
     */
    private function shipSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Kapal Dilayani');

        $row = $this->writeHeading($sheet, 'Kapal Dilayani', [
            'Satu baris mewakili satu kunjungan kapal pada periode '.($report['periodLabel'] ?? '-').'.',
        ]);

        $rows = [];
        foreach ($report['ships'] ?? [] as $ship) {
            $rows[] = [
                $ship['ship_name'] ?? '-',
                $ship['type'] ?? '-',
                $ship['agent'] ?? '-',
                $ship['jetty'] ?? '-',
                $this->num($ship['capacity'] ?? 0),
                $this->num($ship['loaded'] ?? 0, 1),
                ($ship['realization'] ?? null) === null
                    ? 'Kapasitas belum diisi'
                    : $this->num($ship['realization'], 1, self::FORMAT_PERCENT_TEXT),
                $ship['moment'] ?? '-',
                $this->num($ship['report_count'] ?? 0),
            ];
        }

        $this->writeTable($sheet, $row, 'Daftar Kunjungan', [
            'Kapal', 'Jenis', 'Agen', 'Dermaga', 'Kapasitas (Ton)', 'Termuat (Ton)', 'Realisasi', 'Waktu Sandar', 'Jumlah Laporan',
        ], $rows, 'Belum ada kegiatan kapal pada periode ini.');

        $this->autoSize($sheet, 9);
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
