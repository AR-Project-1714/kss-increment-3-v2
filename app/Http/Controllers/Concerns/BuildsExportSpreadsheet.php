<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Kerangka spreadsheet ekspor generik: judul, baris konteks (waktu ekspor, filter
 * aktif, jumlah baris), lalu tabel data dengan header tebal dan lebar kolom
 * otomatis. Dipakai oleh setiap tombol "Ekspor" di panel admin (arsip laporan,
 * log aktivitas, dst.) supaya berkas yang dihasilkan konsisten satu sama lain.
 */
trait BuildsExportSpreadsheet
{
    /**
     * @param  array<int, string>  $contextLines  baris info di bawah judul, mis. "Diekspor: ...", "Filter aktif: ..."
     * @param  array<int, string>  $headers  label kolom
     * @param  Collection<int, array<int, string|int>>  $rows  tiap baris = nilai kolom terurut sesuai $headers
     */
    protected function buildExportSpreadsheet(string $title, array $contextLines, array $headers, Collection $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $line = 1;
        $sheet->setCellValue("A{$line}", $title);
        $sheet->getStyle("A{$line}")->getFont()->setBold(true)->setSize(14);
        $line++;

        foreach ($contextLines as $contextLine) {
            $sheet->setCellValue("A{$line}", $contextLine);
            $sheet->getStyle("A{$line}")->getFont()->setItalic(true)->setSize(10);
            $line++;
        }

        $line++; // baris kosong pemisah antara konteks dan tabel

        $headerRow = $line;
        foreach ($headers as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}{$headerRow}", $label);
        }
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5F1FF');
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
            $dataRow++;
        }

        foreach (range(1, count($headers)) as $index) {
            $column = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
