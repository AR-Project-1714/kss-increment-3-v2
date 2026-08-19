<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Kerangka spreadsheet ekspor generik: judul, baris konteks (waktu ekspor, filter
 * aktif, jumlah baris), lalu tabel data dengan header tebal dan lebar kolom
 * otomatis. Dipakai oleh setiap tombol "Ekspor" di panel admin (arsip laporan,
 * log aktivitas, master data, dst.) supaya berkas yang dihasilkan konsisten
 * satu sama lain.
 *
 * Tata letaknya mengikuti kebiasaan berkas korporat:
 *   - Judul tebal di A1, disusul baris konteks miring abu-abu.
 *   - Satu baris kosong, lalu tabel data.
 *   - Header berlatar biru merek dengan teks putih, dibekukan (freeze pane)
 *     dan dipasangi AutoFilter sehingga bisa disaring & diurutkan di Excel.
 *   - Garis tabel tipis, baris selang-seling, dan pengaturan cetak yang
 *     mengulang baris header di tiap halaman.
 */
trait BuildsExportSpreadsheet
{
    /**
     * Batas baris per sekali ekspor. Melindungi memori server dan mencegah
     * berkas raksasa yang sebenarnya menandakan filter terlalu longgar.
     */
    protected const EXPORT_ROW_LIMIT = 5000;

    /** Warna merek — sama dengan --blue-main pada antarmuka web. */
    private const EXPORT_BRAND_RGB = '2563EB';

    /** Latar baris genap; sangat muda supaya tetap terbaca saat dicetak hitam-putih. */
    private const EXPORT_BAND_RGB = 'F5F8FF';

    private const EXPORT_BORDER_RGB = 'D6DEEB';

    /**
     * Lebar kolom minimum & maksimum (satuan karakter Excel). Batas bawah
     * menjaga kolom sempit seperti "No" tetap terbaca; batas atas mencegah satu
     * sel berisi keterangan panjang melebarkan kolom sampai layar penuh.
     */
    private const EXPORT_MIN_COLUMN_WIDTH = 6;

    private const EXPORT_MAX_COLUMN_WIDTH = 46;

    /**
     * Ruang ekstra di kanan teks. Kolom header butuh lebih banyak karena tombol
     * AutoFilter digambar di dalam selnya dan akan menutupi label tanpa ini.
     */
    private const EXPORT_HEADER_PADDING = 4;

    private const EXPORT_CELL_PADDING = 2;

    /**
     * @param  array<int, string>  $contextLines  baris info di bawah judul, mis. "Diekspor: ...", "Filter aktif: ..."
     * @param  array<int, string>  $headers  label kolom
     * @param  Collection<int, array<int, string|int>>  $rows  tiap baris = nilai kolom terurut sesuai $headers
     * @param  string|null  $sheetTitle  nama tab sheet; default diambil dari $title
     */
    protected function buildExportSpreadsheet(
        string $title,
        array $contextLines,
        array $headers,
        Collection $rows,
        ?string $sheetTitle = null
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sanitizeSheetTitle($sheetTitle ?? $title));

        $spreadsheet->getProperties()
            ->setCreator('Sistem Laporan KSS')
            ->setTitle($title);

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $line = 1;
        $sheet->setCellValue("A{$line}", $title);
        $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(14)
            ->getColor()->setRGB(self::EXPORT_BRAND_RGB);
        $sheet->getRowDimension($line)->setRowHeight(22);
        $line++;

        foreach ($contextLines as $contextLine) {
            $sheet->setCellValue("A{$line}", $contextLine);
            $sheet->getStyle("A{$line}")->getFont()->setItalic(true)->setSize(10)
                ->getColor()->setRGB('64748B');
            $line++;
        }

        $line++; // baris kosong pemisah antara konteks dan tabel

        $headerRow = $line;
        foreach ($headers as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}{$headerRow}", $label);
        }

        $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::EXPORT_BRAND_RGB);
        $headerStyle->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);
        $sheet->freezePane('A'.($headerRow + 1));

        $dataRow = $headerRow + 1;
        foreach ($rows as $values) {
            // Nilai ditulis sebagai string eksplisit: ID dokumen seperti "OPS-2026-001"
            // atau timestamp seperti "07/2026" tidak boleh ditebak Excel sebagai
            // tanggal/angka/formula.
            foreach (array_values($values) as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit("{$column}{$dataRow}", (string) $value, DataType::TYPE_STRING);
            }

            // Baris selang-seling supaya mata tidak lompat baris saat membaca
            // tabel lebar.
            if (($dataRow - $headerRow) % 2 === 0) {
                $sheet->getStyle("A{$dataRow}:{$lastColumn}{$dataRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::EXPORT_BAND_RGB);
            }

            $dataRow++;
        }

        $lastRow = $dataRow - 1;

        if ($lastRow >= $headerRow) {
            $tableRange = "A{$headerRow}:{$lastColumn}{$lastRow}";

            // AutoFilter dipasang pada seluruh tabel, jadi tiap kolom header
            // punya tombol saring & urut bawaan Excel.
            $sheet->setAutoFilter($tableRange);

            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB(self::EXPORT_BORDER_RGB);
            $sheet->getStyle($tableRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Lebar kolom dihitung sendiri, BUKAN lewat setAutoSize(). Auto-size
        // mengukur seluruh isi kolom termasuk judul dan baris konteks yang
        // menempati kolom A — akibatnya kolom pertama ("No") ikut selebar
        // kalimat "Diekspor: ... oleh ...". Di sini hanya header dan baris data
        // yang dihitung.
        $this->applyColumnWidths($sheet, $headers, $rows);

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        // Baris header ikut tercetak di tiap halaman.
        $pageSetup->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);

        $sheet->setSelectedCell('A'.($headerRow + 1));

        return $spreadsheet;
    }

    /**
     * Pasang lebar tetap tiap kolom dari isi tabelnya saja — label header dan
     * nilai barisnya — lalu jepit ke rentang yang wajar. Kolom yang isinya
     * kelewat panjang dipotong di batas atas dan teksnya dibungkus.
     *
     * @param  array<int, string>  $headers
     * @param  Collection<int, array<int, string|int>>  $rows
     */
    private function applyColumnWidths($sheet, array $headers, Collection $rows): void
    {
        foreach (array_keys($headers) as $index) {
            $headerLength = mb_strlen((string) $headers[$index]) + self::EXPORT_HEADER_PADDING;

            $cellLength = 0;
            foreach ($rows as $values) {
                $value = array_values($values)[$index] ?? '';
                $cellLength = max($cellLength, mb_strlen((string) $value));
            }
            $cellLength += self::EXPORT_CELL_PADDING;

            $width = max($headerLength, $cellLength);
            $needsWrap = $width > self::EXPORT_MAX_COLUMN_WIDTH;
            $width = min(max($width, self::EXPORT_MIN_COLUMN_WIDTH), self::EXPORT_MAX_COLUMN_WIDTH);

            $column = Coordinate::stringFromColumnIndex($index + 1);
            $dimension = $sheet->getColumnDimension($column);
            $dimension->setAutoSize(false);
            $dimension->setWidth($width);

            if ($needsWrap) {
                $sheet->getStyle($column.'1:'.$column.'1048576')->getAlignment()->setWrapText(true);
            }
        }
    }

    /**
     * Nama tab sheet Excel: maksimal 31 karakter dan tidak boleh memuat
     * : \ / ? * [ ].
     */
    private function sanitizeSheetTitle(string $title): string
    {
        $clean = preg_replace('/[:\\\\\/?*\[\]]/', ' ', $title) ?? $title;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        return mb_substr($clean === '' ? 'Data' : $clean, 0, 31);
    }
}
