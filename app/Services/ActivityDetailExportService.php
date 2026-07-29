<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Workbook khusus menu Rincian Kegiatan.
 *
 * Sheet pertama adalah rekap lintas kegiatan seperti format laporan manajemen.
 * Sheet berikutnya membedah satu kegiatan per sheet: KPI, rekap periode,
 * sorotan otomatis, beban kerja, tabel rinci, dan dua chart native Excel.
 */
class ActivityDetailExportService
{
    private const NAVY = '16324F';

    private const BLUE = '2563EB';

    private const CYAN = '0891B2';

    private const GREEN = '16A34A';

    private const AMBER = 'D97706';

    private const RED = 'DC2626';

    private const TEXT = '0F172A';

    private const MUTED = '64748B';

    private const BORDER = 'CBD5E1';

    private const HEADER_FILL = 'DCEBFA';

    private const SOFT_FILL = 'F8FAFC';

    private const FORMAT_INT = '#,##0';

    private const FORMAT_ONE_DECIMAL = '#,##0.0';

    private const FORMAT_TWO_DECIMALS = '#,##0.00';

    private const FORMAT_PERCENT_TEXT = '0.0"%"';

    /** Nama tab dibuat pendek agar aman terhadap batas Excel 31 karakter. */
    private const SHEET_NAMES = [
        'muat_kantong' => 'Muat Kantong',
        'muat_curah' => 'Muat Curah',
        'muat_amoniak' => 'Muat Amoniak',
        'bongkar_bahan_baku' => 'Bongkar Bahan Baku',
        'bongkar_container' => 'Bongkar Container',
        'muat_container' => 'Muat Container',
        'trucking_turba' => 'Trucking Pupuk',
    ];

    public function __construct(
        private readonly PerformanceExportService $performanceExporter
    ) {}

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, array<string, mixed>>  $details
     * @param  array<int, string>  $contextLines
     */
    public function build(array $report, array $details, array $contextLines): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $spreadsheet->getProperties()
            ->setCreator('KSS')
            ->setTitle('Rincian Kegiatan Operasional')
            ->setSubject('Analisis kegiatan operasi untuk manajer')
            ->setDescription('Gambaran besar dan rincian per kegiatan berdasarkan filter aktif.');

        $overview = $this->performanceExporter->addActivityOverviewSheet(
            $spreadsheet,
            $report,
            $contextLines
        );
        // PerformanceExportService menambahkan dua catatan manajerial pada
        // heading rekap. Ikut hitung keduanya agar seluruh konteks terformat
        // dan dua baris kepala tabel tetap membeku saat sheet digulir.
        $this->polishOverviewSheet($overview, count($contextLines) + 2);

        $panels = [];
        foreach ($report['activityPanels'] ?? [] as $panel) {
            if (isset($panel['key'])) {
                $panels[$panel['key']] = $panel;
            }
        }

        foreach ($details as $key => $detail) {
            $this->activitySheet(
                $spreadsheet->createSheet(),
                (string) $key,
                $detail,
                $panels[$key] ?? [],
                $contextLines
            );
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function polishOverviewSheet(Worksheet $sheet, int $contextLineCount): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(85);
        $sheet->getTabColor()->setRGB(self::NAVY);

        $lastColumn = $sheet->getHighestColumn();
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::NAVY]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 18],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        for ($row = 2; $row <= $contextLineCount + 1; $row++) {
            if (! $sheet->getCell('A'.$row)->getValue()) {
                continue;
            }

            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::SOFT_FILL]],
                'font' => ['italic' => true, 'color' => ['rgb' => self::MUTED], 'size' => 10],
                'alignment' => ['wrapText' => true],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(19);
        }

        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(34);
        for ($column = 2; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))
                ->setAutoSize(false)
                ->setWidth(14);
        }

        // Judul + seluruh konteks + satu spacer + dua baris kepala tabel.
        $firstDataRow = $contextLineCount + 5;
        $sheet->freezePane('B'.$firstDataRow);
        $this->configurePage($sheet, "A1:{$lastColumn}".$sheet->getHighestRow());
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $panel
     * @param  array<int, string>  $contextLines
     */
    private function activitySheet(
        Worksheet $sheet,
        string $key,
        array $detail,
        array $panel,
        array $contextLines
    ): void {
        $sheet->setTitle(self::SHEET_NAMES[$key] ?? $this->safeSheetTitle($detail['label'] ?? $key));
        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(80);
        $sheet->getTabColor()->setRGB($this->tintColor($detail['tint'] ?? 'blue'));

        $row = $this->writeActivityHeading($sheet, $detail, $contextLines);
        $row = $this->writeMetricSection($sheet, $row, $detail);
        $row = $this->writeRecapSection($sheet, $row, $detail);
        $row = $this->writeInsightSection($sheet, $row, $detail, $panel);
        $row = $this->writeBreakdownSection($sheet, $row, $detail);
        $row = $this->writeOvertimeSection($sheet, $row, $detail);

        $detailStart = max($row, 50);
        $this->writeDetailTable($sheet, $detailStart, $detail);
        $this->writeChartSourcesAndCharts($sheet, $key, $detail);

        $this->sizeActivityColumns($sheet, $detail);
        $sheet->freezePane('A6');
        $sheet->setSelectedCell('A1');
        $this->configurePage($sheet, 'A1:U'.$sheet->getHighestRow());
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<int, string>  $contextLines
     */
    private function writeActivityHeading(Worksheet $sheet, array $detail, array $contextLines): int
    {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', strtoupper((string) ($detail['label'] ?? 'RINCIAN KEGIATAN')));
        $sheet->getStyle('A1:H1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::NAVY]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 17],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $lines = array_slice($contextLines, 0, 3);
        $lines[0] = 'Periode kegiatan: '.($detail['periodLabel'] ?? '-')
            .' | Tren: 6 bulan kalender terakhir sampai bulan berjalan.';

        foreach (array_values($lines) as $index => $line) {
            $row = $index + 2;
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->setCellValueExplicit('A'.$row, (string) $line, DataType::TYPE_STRING);
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::SOFT_FILL]],
                'font' => ['italic' => true, 'color' => ['rgb' => self::MUTED], 'size' => 9],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        return 6;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeMetricSection(Worksheet $sheet, int $startRow, array $detail): int
    {
        $row = $this->writeSectionTitle($sheet, $startRow, 'RINGKASAN MANAJERIAL', 'H');
        $headerRow = $row;
        $headers = ['Indikator', 'Nilai', 'Satuan', 'Keterangan'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $header);
        }
        $this->styleTableHeader($sheet, "A{$row}:D{$row}");
        $row++;

        $workload = $detail['workload'] ?? [];
        $rows = [[
            'Volume pada periode',
            $detail['value'] ?? 0,
            $detail['unit'] ?? '-',
            ($workload['reports'] ?? 0).' laporan memuat kegiatan',
            1,
        ]];

        foreach ($detail['metrics'] ?? [] as $metric) {
            $rows[] = [
                $metric['label'] ?? '-',
                $metric['value'] ?? null,
                $metric['unit'] ?? '-',
                $metric['caption'] ?? '-',
                (int) ($metric['decimals'] ?? 1),
            ];
        }

        $hasWorkloadBase = (int) ($workload['reports'] ?? 0) > 0;
        $punctuality = (float) ($workload['punctuality'] ?? 0);
        $rows[] = [
            'Personil rata-rata per shift',
            $workload['personnelPerShift'] ?? 0,
            'orang',
            'Pada laporan yang memuat kegiatan',
            1,
        ];
        $rows[] = [
            'Jam lembur tercatat',
            $workload['overtimeHours'] ?? 0,
            'jam',
            ($workload['overtimeCount'] ?? 0).' entri lembur',
            1,
        ];
        $rows[] = [
            'Ketepatan waktu laporan',
            $hasWorkloadBase ? $punctuality : null,
            '%',
            ! $hasWorkloadBase
                ? 'Belum ada laporan'
                : ($punctuality >= 90 ? 'Terkendali (>= 90%)' : 'Perlu perhatian (< 90%)'),
            1,
        ];

        foreach ($rows as $values) {
            $sheet->setCellValueExplicit('A'.$row, (string) $values[0], DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'B'.$row, $values[1], (int) $values[4], (string) $values[2]);
            $sheet->setCellValueExplicit('C'.$row, (string) $values[2], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D'.$row, (string) $values[3], DataType::TYPE_STRING);
            $row++;
        }

        $lastRow = $row - 1;
        $this->styleTableBody($sheet, "A{$headerRow}:D{$lastRow}");
        $sheet->getStyle('D'.($lastRow).":D{$lastRow}")->getFont()
            ->setColor(new Color(
                ! $hasWorkloadBase ? self::MUTED : ($punctuality >= 90 ? self::GREEN : self::RED)
            ))
            ->setBold(true);

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeRecapSection(Worksheet $sheet, int $startRow, array $detail): int
    {
        $recap = $detail['recap'] ?? [];
        $entry = $recap['row'] ?? null;

        $row = $this->writeSectionTitle($sheet, $startRow, 'REKAP PERIODE', 'H');

        if (! is_array($entry)) {
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->setCellValue('A'.$row, 'Belum ada rekap untuk kegiatan ini.');
            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setItalic(true)->setColor(
                new Color(self::MUTED)
            );

            return $row + 2;
        }

        $labels = $recap['labels'] ?? [];
        $headers = [
            'Uraian',
            "BULAN SEKARANG\n".($labels['month'] ?? '-'),
            "SEBELUMNYA\n".($labels['previous'] ?? 'Tidak ada'),
            "AKUMULASI\n".($labels['total'] ?? '-'),
            'Satuan',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $header);
        }
        $headerRow = $row;
        $this->styleTableHeader($sheet, "A{$row}:E{$row}");
        $sheet->getRowDimension($row)->setRowHeight(34);
        $row++;

        $recapRows = [[
            $entry['countLabel'] ?? 'Kapal',
            'count',
            strtolower((string) ($entry['countLabel'] ?? 'kapal')),
            0,
        ]];

        if ($entry['hasDelivery'] ?? false) {
            $recapRows[] = ['Kirim', 'delivery', $entry['unit'] ?? $detail['unit'] ?? '-', 2];
        }

        $recapRows[] = ['Muat', 'value', $entry['unit'] ?? $detail['unit'] ?? '-', 2];

        if ($entry['hasDamage'] ?? false) {
            $recapRows[] = ['Kerusakan', 'damage', $entry['unit'] ?? $detail['unit'] ?? '-', 2];
        }

        foreach ($recapRows as [$label, $key, $unit, $decimals]) {
            $sheet->setCellValueExplicit('A'.$row, (string) $label, DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'B'.$row, $entry['month'][$key] ?? 0, $decimals, (string) $unit);
            $this->writeNumber($sheet, 'C'.$row, $entry['previous'][$key] ?? 0, $decimals, (string) $unit);
            $sheet->setCellValue('D'.$row, '=SUM(B'.$row.':C'.$row.')');
            $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode(
                $this->numberFormat($decimals, (string) $unit)
            );
            $sheet->setCellValueExplicit('E'.$row, (string) $unit, DataType::TYPE_STRING);
            $row++;
        }

        $this->styleTableBody($sheet, "A{$headerRow}:E".($row - 1));

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $panel
     */
    private function writeInsightSection(
        Worksheet $sheet,
        int $startRow,
        array $detail,
        array $panel
    ): int {
        $row = $this->writeSectionTitle($sheet, $startRow, 'SOROTAN OTOMATIS UNTUK MANAJER', 'H');
        $insights = $this->managerInsights($detail, $panel);

        foreach ($insights as $index => $insight) {
            $sheet->mergeCells("A{$row}:H{$row}");
            $sheet->setCellValueExplicit('A'.$row, '• '.$insight, DataType::TYPE_STRING);
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $index === 0 ? 'EFF6FF' : self::SOFT_FILL],
                ],
                'font' => ['color' => ['rgb' => self::TEXT], 'size' => 10],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::BORDER]],
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeBreakdownSection(Worksheet $sheet, int $startRow, array $detail): int
    {
        $breakdown = array_slice($detail['breakdown'] ?? [], 0, 10);

        if ($breakdown === []) {
            return $startRow;
        }

        $row = $this->writeSectionTitle(
            $sheet,
            $startRow,
            strtoupper((string) ($detail['breakdownTitle'] ?? 'KOMPOSISI KEGIATAN')),
            'H'
        );
        $headerRow = $row;
        foreach (['Kategori', 'Nilai', 'Satuan', 'Porsi', 'Keterangan'] as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $header);
        }
        $this->styleTableHeader($sheet, "A{$row}:E{$row}");
        $row++;

        foreach ($breakdown as $item) {
            $sheet->setCellValueExplicit('A'.$row, (string) ($item['name'] ?? '-'), DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'B'.$row, $item['value'] ?? 0, 1, (string) ($detail['unit'] ?? '-'));
            $sheet->setCellValueExplicit('C'.$row, (string) ($detail['unit'] ?? '-'), DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'D'.$row, $item['contribution'] ?? 0, 1, '%');
            $sheet->setCellValueExplicit(
                'E'.$row,
                isset($item['trips']) ? ((int) $item['trips']).' rit' : '-',
                DataType::TYPE_STRING
            );
            $row++;
        }

        $this->styleTableBody($sheet, "A{$headerRow}:E".($row - 1));

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeOvertimeSection(Worksheet $sheet, int $startRow, array $detail): int
    {
        $leaders = array_slice($detail['overtime']['hours'] ?? [], 0, 5);

        if ($leaders === []) {
            return $startRow;
        }

        $row = $this->writeSectionTitle($sheet, $startRow, 'LEMBUR TERATAS', 'H');
        $headerRow = $row;
        foreach (['Peringkat', 'Nama', 'Total Jam', 'Frekuensi'] as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $header);
        }
        $this->styleTableHeader($sheet, "A{$row}:D{$row}");
        $row++;

        foreach ($leaders as $index => $leader) {
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValueExplicit('B'.$row, (string) ($leader['name'] ?? '-'), DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'C'.$row, $leader['hours'] ?? 0, 1, 'jam');
            $this->writeNumber($sheet, 'D'.$row, $leader['count'] ?? 0, 0, 'kali');
            $row++;
        }

        $this->styleTableBody($sheet, "A{$headerRow}:D".($row - 1));

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeDetailTable(Worksheet $sheet, int $startRow, array $detail): void
    {
        $table = $detail['table'] ?? [];
        $columns = $table['columns'] ?? [];
        $rows = $table['rows'] ?? [];

        $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($columns)));
        $row = $this->writeSectionTitle($sheet, $startRow, 'DAFTAR RINCIAN OPERASIONAL', $lastColumn);

        $caption = ($table['total'] ?? count($rows)).' baris ditemukan';
        if ($table['limited'] ?? false) {
            $caption .= ' • ditampilkan '.count($rows).' baris pertama (batas ekspor 5.000)';
        }
        if ($detail['tableCaption'] ?? null) {
            $caption .= ' • '.$detail['tableCaption'];
        }

        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValueExplicit('A'.$row, $caption, DataType::TYPE_STRING);
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()
            ->setItalic(true)
            ->setColor(new Color(self::MUTED));
        $row++;

        if (($table['blank'] ?? false) || $columns === [] || $rows === []) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue(
                'A'.$row,
                ($table['blank'] ?? false)
                    ? 'Baris kegiatan tercatat, tetapi belum berisi angka yang dapat dianalisis.'
                    : 'Belum ada rincian pada periode dan filter ini.'
            );
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()
                ->setItalic(true)
                ->setColor(new Color(self::MUTED));

            return;
        }

        $headerRow = $row;
        foreach ($columns as $index => $column) {
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($index + 1).$row,
                (string) ($column['label'] ?? '-')
            );
        }
        $this->styleTableHeader($sheet, "A{$row}:{$lastColumn}{$row}");
        $row++;

        foreach ($rows as $values) {
            foreach ($columns as $index => $column) {
                $cell = Coordinate::stringFromColumnIndex($index + 1).$row;
                $value = $values[$index] ?? null;
                $type = (string) ($column['type'] ?? 'muted');

                if (in_array($type, ['number', 'ratio'], true) && is_numeric($value)) {
                    $unit = $type === 'ratio' ? '%' : (string) ($column['unit'] ?? '');
                    $decimals = (int) ($column['decimals'] ?? ($type === 'ratio' ? 1 : 0));
                    $this->writeNumber($sheet, $cell, (float) $value, $decimals, $unit);
                } else {
                    $sheet->setCellValueExplicit(
                        $cell,
                        $value === null || $value === '' ? '-' : (string) $value,
                        DataType::TYPE_STRING
                    );
                }
            }
            $row++;
        }

        $lastRow = $row - 1;
        $this->styleTableBody($sheet, "A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
    }

    /**
     * Data sumber chart sengaja tetap terlihat agar angka grafik mudah diaudit.
     *
     * @param  array<string, mixed>  $detail
     */
    private function writeChartSourcesAndCharts(Worksheet $sheet, string $key, array $detail): void
    {
        $trendStart = 38;
        $sheet->setCellValue('M'.$trendStart, 'Bulan');
        $sheet->setCellValue('N'.$trendStart, 'Nilai ('.($detail['unit'] ?? '-').')');
        $this->styleTableHeader($sheet, "M{$trendStart}:N{$trendStart}");

        $trendRows = array_values($detail['trend'] ?? []);
        if ($trendRows === []) {
            $trendRows = [['label' => '-', 'value' => 0]];
        }

        foreach ($trendRows as $index => $point) {
            $row = $trendStart + 1 + $index;
            $sheet->setCellValueExplicit('M'.$row, (string) ($point['label'] ?? '-'), DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'N'.$row, $point['value'] ?? 0, 1, (string) ($detail['unit'] ?? ''));
        }
        $trendEnd = $trendStart + count($trendRows);
        $this->styleTableBody($sheet, "M{$trendStart}:N{$trendEnd}");

        [$rankTitle, $rankRows, $rankUnit] = $this->rankingChartRows($detail);
        $rankStart = 38;
        $sheet->setCellValue('P'.$rankStart, 'Kategori');
        $sheet->setCellValue('Q'.$rankStart, 'Nilai ('.$rankUnit.')');
        $this->styleTableHeader($sheet, "P{$rankStart}:Q{$rankStart}");

        foreach ($rankRows as $index => $point) {
            $row = $rankStart + 1 + $index;
            $sheet->setCellValueExplicit('P'.$row, (string) ($point['name'] ?? '-'), DataType::TYPE_STRING);
            $this->writeNumber($sheet, 'Q'.$row, $point['value'] ?? 0, 1, $rankUnit);
        }
        $rankEnd = $rankStart + count($rankRows);
        $this->styleTableBody($sheet, "P{$rankStart}:Q{$rankEnd}");

        $this->addLineChart(
            $sheet,
            'trend_'.$key,
            'Tren 6 Bulan - '.($detail['unit'] ?? '-'),
            'M',
            'N',
            $trendStart,
            $trendEnd
        );
        $this->addBarChart(
            $sheet,
            'ranking_'.$key,
            $rankTitle,
            'P',
            'Q',
            $rankStart,
            $rankEnd,
            $rankUnit
        );
    }

    private function addLineChart(
        Worksheet $sheet,
        string $name,
        string $title,
        string $categoryColumn,
        string $valueColumn,
        int $headerRow,
        int $lastRow
    ): void {
        $sheetRef = $this->quotedSheetReference($sheet);
        $pointCount = max(1, $lastRow - $headerRow);

        $labels = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheetRef}!\${$valueColumn}\${$headerRow}",
            null,
            1
        )];
        $categories = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheetRef}!\${$categoryColumn}\$".($headerRow + 1).":\${$categoryColumn}\${$lastRow}",
            null,
            $pointCount
        )];
        $values = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "{$sheetRef}!\${$valueColumn}\$".($headerRow + 1).":\${$valueColumn}\${$lastRow}",
            self::FORMAT_ONE_DECIMAL,
            $pointCount,
            [],
            'circle',
            self::BLUE
        )];

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            $labels,
            $categories,
            $values,
            DataSeries::DIRECTION_COL,
            false,
            DataSeries::STYLE_LINEMARKER
        );

        $chart = new Chart(
            $name,
            new Title($title),
            null,
            new PlotArea(null, [$series]),
            true,
            DataSeries::EMPTY_AS_ZERO,
            new Title('Bulan'),
            new Title('Volume')
        );
        $chart->setTopLeftPosition('M2');
        $chart->setBottomRightPosition('U17');
        $sheet->addChart($chart);
    }

    private function addBarChart(
        Worksheet $sheet,
        string $name,
        string $title,
        string $categoryColumn,
        string $valueColumn,
        int $headerRow,
        int $lastRow,
        string $unit
    ): void {
        $sheetRef = $this->quotedSheetReference($sheet);
        $pointCount = max(1, $lastRow - $headerRow);

        $labels = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheetRef}!\${$valueColumn}\${$headerRow}",
            null,
            1
        )];
        $categories = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "{$sheetRef}!\${$categoryColumn}\$".($headerRow + 1).":\${$categoryColumn}\${$lastRow}",
            null,
            $pointCount
        )];
        $values = [new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "{$sheetRef}!\${$valueColumn}\$".($headerRow + 1).":\${$valueColumn}\${$lastRow}",
            self::FORMAT_ONE_DECIMAL,
            $pointCount,
            [],
            null,
            self::CYAN
        )];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            $labels,
            $categories,
            $values,
            DataSeries::DIRECTION_BAR
        );

        $chart = new Chart(
            $name,
            new Title($title),
            null,
            new PlotArea(null, [$series]),
            true,
            DataSeries::EMPTY_AS_ZERO,
            new Title($unit),
            new Title('Kategori')
        );
        $chart->setTopLeftPosition('M19');
        $chart->setBottomRightPosition('U34');
        $sheet->addChart($chart);
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array{0: string, 1: array<int, array{name: string, value: float|int}>, 2: string}
     */
    private function rankingChartRows(array $detail): array
    {
        $breakdown = array_slice($detail['breakdown'] ?? [], 0, 8);

        if ($breakdown !== []) {
            return [
                (string) ($detail['breakdownTitle'] ?? 'Komposisi Kegiatan'),
                array_map(
                    static fn (array $row): array => [
                        'name' => (string) ($row['name'] ?? '-'),
                        'value' => (float) ($row['value'] ?? 0),
                    ],
                    $breakdown
                ),
                (string) ($detail['unit'] ?? '-'),
            ];
        }

        $groups = array_slice($detail['groups'] ?? [], 0, 8);

        if ($groups !== []) {
            return [
                'Kontribusi per Regu - '.($detail['unit'] ?? '-'),
                array_map(
                    static fn (array $row): array => [
                        'name' => 'Regu '.($row['name'] ?? '-'),
                        'value' => (float) ($row['value'] ?? 0),
                    ],
                    $groups
                ),
                (string) ($detail['unit'] ?? '-'),
            ];
        }

        $shifts = $detail['shiftSpread'] ?? [];
        if ($shifts === []) {
            $shifts = [
                ['name' => 'Pagi', 'value' => 0],
                ['name' => 'Sore', 'value' => 0],
                ['name' => 'Malam', 'value' => 0],
            ];
        }

        return [
            'Sebaran Laporan per Shift',
            array_map(
                static fn (array $row): array => [
                    'name' => 'Shift '.($row['name'] ?? '-'),
                    'value' => (float) ($row['value'] ?? 0),
                ],
                array_slice($shifts, 0, 8)
            ),
            'laporan',
        ];
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>  $panel
     * @return array<int, string>
     */
    private function managerInsights(array $detail, array $panel): array
    {
        $value = (float) ($detail['value'] ?? 0);
        $unit = (string) ($detail['unit'] ?? '-');
        $delta = $panel['delta'] ?? [];

        $insights = [];
        if ($value <= 0) {
            $insights[] = 'Belum ada volume kegiatan pada periode dan filter aktif.';
        } else {
            $comparison = ($delta['available'] ?? false)
                ? ' Perubahannya '.$this->deltaText($delta).' terhadap periode pembanding.'
                : ' Perbandingan periode belum tersedia.';
            $insights[] = 'Volume tercatat '.$this->formatNumber($value, 1).' '.$unit.'.'.$comparison;
        }

        $topGroup = ($detail['groups'] ?? [])[0] ?? null;
        $insights[] = $topGroup
            ? 'Kontributor regu tertinggi: Regu '.($topGroup['name'] ?? '-').' dengan '
                .$this->formatNumber((float) ($topGroup['value'] ?? 0), 1).' '.$unit
                .'. Peringkat tetap membandingkan seluruh regu.'
            : 'Belum ada peringkat regu yang dapat dibandingkan.';

        $dominantShift = $this->largestRow($detail['shiftSpread'] ?? []);
        $insights[] = $dominantShift
            ? 'Shift dengan laporan terbanyak: '.($dominantShift['name'] ?? '-').' ('
                .$this->formatNumber((float) ($dominantShift['value'] ?? 0), 0).' laporan).'
            : 'Belum ada sebaran laporan per shift.';

        $workload = $detail['workload'] ?? [];
        $punctuality = (float) ($workload['punctuality'] ?? 0);
        $insights[] = (int) ($workload['reports'] ?? 0) > 0
            ? 'Ketepatan waktu laporan '.$this->formatNumber($punctuality, 1)
                .'% dan lembur '.$this->formatNumber((float) ($workload['overtimeHours'] ?? 0), 1)
                .' jam. Ambang perhatian ketepatan waktu pada workbook ini adalah 90%.'
            : 'Ketepatan waktu dan beban lembur belum dapat dinilai karena belum ada laporan.';

        if ($detail['note'] ?? null) {
            $insights[] = (string) $detail['note'];
        }

        return array_slice($insights, 0, 5);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    private function largestRow(array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => ($b['value'] ?? 0) <=> ($a['value'] ?? 0)
        );

        return $rows[0];
    }

    private function writeSectionTitle(
        Worksheet $sheet,
        int $row,
        string $title,
        string $lastColumn
    ): int {
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->setCellValue('A'.$row, $title);
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::NAVY]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(21);

        return $row + 1;
    }

    private function styleTableHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::HEADER_FILL]],
            'font' => ['bold' => true, 'color' => ['rgb' => self::TEXT]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]],
            ],
        ]);
    }

    private function styleTableBody(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::BORDER]],
            ],
        ]);
    }

    private function writeNumber(
        Worksheet $sheet,
        string $cell,
        mixed $value,
        int $decimals,
        string $unit
    ): void {
        if ($value === null || ! is_numeric($value)) {
            $sheet->setCellValueExplicit($cell, '-', DataType::TYPE_STRING);

            return;
        }

        $sheet->setCellValue($cell, round((float) $value, $decimals));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(
            $this->numberFormat($decimals, $unit)
        );
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function numberFormat(int $decimals, string $unit): string
    {
        if ($unit === '%') {
            return $decimals >= 2 ? '0.00"%"' : self::FORMAT_PERCENT_TEXT;
        }

        return match (true) {
            $decimals >= 2 => self::FORMAT_TWO_DECIMALS,
            $decimals === 1 => self::FORMAT_ONE_DECIMAL,
            default => self::FORMAT_INT,
        };
    }

    private function deltaText(array $delta): string
    {
        if (! ($delta['available'] ?? false)) {
            return (string) ($delta['text'] ?? 'belum tersedia');
        }

        $sign = match ($delta['direction'] ?? 'flat') {
            'up' => '+',
            'down' => '-',
            default => '',
        };

        return $sign.($delta['text'] ?? '0');
    }

    private function formatNumber(float $value, int $decimals): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function sizeActivityColumns(Worksheet $sheet, array $detail): void
    {
        $sheet->getColumnDimension('A')->setWidth(29);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(24);
        $sheet->getColumnDimension('L')->setWidth(3);

        foreach ($detail['table']['columns'] ?? [] as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $type = (string) ($column['type'] ?? 'muted');
            $width = match ($type) {
                'name' => 24,
                'number', 'ratio' => 14,
                default => 19,
            };
            $sheet->getColumnDimension($letter)->setWidth(max(
                (float) $sheet->getColumnDimension($letter)->getWidth(),
                $width
            ));
        }

        foreach (['M' => 16, 'N' => 16, 'O' => 3, 'P' => 22, 'Q' => 16, 'R' => 3, 'S' => 12, 'T' => 12, 'U' => 12] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function configurePage(Worksheet $sheet, string $printArea): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea($printArea);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.3)
            ->setBottom(0.35)
            ->setLeft(0.3);
    }

    private function tintColor(string $tint): string
    {
        return match ($tint) {
            'green' => self::GREEN,
            'orange' => self::AMBER,
            'red' => self::RED,
            'cyan' => self::CYAN,
            default => self::BLUE,
        };
    }

    private function quotedSheetReference(Worksheet $sheet): string
    {
        return "'".str_replace("'", "''", $sheet->getTitle())."'";
    }

    private function safeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $title) ?: 'Rincian Kegiatan';

        return mb_substr($title, 0, 31);
    }
}
