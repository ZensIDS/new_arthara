<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helper styling Excel yang dipakai bareng oleh semua export laporan,
 * supaya semua sheet punya tampilan yang konsisten ("brand" ink/amber
 * yang sama dengan tampilan web) tanpa perlu nulis ulang style di
 * tiap-tiap export.
 *
 * Alur pemakaian umum di satu worksheet:
 *   $row = ExcelStyler::title($sheet, 'Laporan Stok', 'Periode ...', $colSpan);
 *   $row = ExcelStyler::summaryBlock($sheet, $row, [...], $colSpan);
 *   $row = ExcelStyler::header($sheet, $row, ['Produk', 'Qty', ...]);
 *   $row = ExcelStyler::rows($sheet, $row, $data, currencyCols: [3]);
 *   ExcelStyler::totalsRow($sheet, $row, ['Total', '', 123456], currencyCols: [3]);
 *   ExcelStyler::autosize($sheet, $colSpan);
 *   return ExcelStyler::download($spreadsheet, 'laporan-stok.xlsx');
 */
class ExcelStyler
{
    public const COLOR_INK = '18181B';
    public const COLOR_INK_SOFT = '3F3F46';
    public const COLOR_AMBER = 'F59E0B';
    public const COLOR_AMBER_LIGHT = 'FEF3C7';
    public const COLOR_GRAY_LIGHT = 'F4F4F5';
    public const COLOR_BORDER = 'E4E4E7';
    public const COLOR_WHITE = 'FFFFFF';
    public const COLOR_RED = 'B91C1C';
    public const COLOR_GREEN = '047857';

    public const FMT_RP = '"Rp" #,##0;[RED]-"Rp" #,##0';
    public const FMT_NUMBER = '#,##0';

    /**
     * Menulis judul + subjudul (mis. periode / tanggal cetak), merge di sepanjang $colSpan kolom.
     * Mengembalikan nomor baris berikutnya yang masih kosong.
     */
    public static function title(Worksheet $sheet, string $title, ?string $subtitle, int $colSpan, int $startRow = 1): int
    {
        $lastCol = self::colLetter($colSpan);

        $sheet->mergeCells("A{$startRow}:{$lastCol}{$startRow}");
        $sheet->setCellValue("A{$startRow}", $title);
        $sheet->getStyle("A{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => self::COLOR_INK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $row = $startRow + 1;

        if ($subtitle) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("A{$row}", $subtitle);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => self::COLOR_INK_SOFT]],
            ]);
            $row++;
        }

        return $row + 1; // baris kosong sebagai jarak
    }

    /**
     * Blok ringkasan label -> nilai (mis. Total Stok, Laba Bersih, dst).
     * $pairs = [['label' => ..., 'value' => ..., 'format' => self::FMT_RP, 'highlight' => bool], ...]
     */
    public static function summaryBlock(Worksheet $sheet, int $startRow, array $pairs, int $colSpan): int
    {
        $row = $startRow;
        $lastCol = self::colLetter($colSpan);

        foreach ($pairs as $pair) {
            $highlight = $pair['highlight'] ?? false;

            $sheet->setCellValue("A{$row}", $pair['label']);
            $sheet->mergeCells("A{$row}:" . self::colLetter(max(1, $colSpan - 1)) . $row);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => $highlight, 'color' => ['rgb' => self::COLOR_INK_SOFT]],
            ]);

            $sheet->setCellValue("{$lastCol}{$row}", $pair['value']);
            $sheet->getStyle("{$lastCol}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_INK]],
                'numberFormat' => ['formatCode' => $pair['format'] ?? self::FMT_NUMBER],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);

            if ($highlight) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_AMBER_LIGHT],
                    ],
                ]);
            }

            $row++;
        }

        return $row + 1;
    }

    /**
     * Header tabel dengan background gelap & teks putih (senada dengan tampilan web).
     */
    public static function header(Worksheet $sheet, int $row, array $headers): int
    {
        $col = 1;
        foreach ($headers as $label) {
            $letter = self::colLetter($col);
            $sheet->setCellValue("{$letter}{$row}", $label);
            $col++;
        }

        $lastCol = self::colLetter(count($headers));
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_WHITE], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_INK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_INK]]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $sheet->freezePane('A' . ($row + 1));

        return $row + 1;
    }

    /**
     * Menulis baris-baris data dengan zebra striping + border tipis.
     * $currencyCols / $numberCols pakai index kolom mulai dari 1 (A=1).
     * Mengembalikan nomor baris kosong berikutnya (setelah data).
     */
    public static function rows(Worksheet $sheet, int $startRow, iterable $data, array $currencyCols = [], array $numberCols = [], array $centerCols = []): int
    {
        $row = $startRow;

        foreach ($data as $record) {
            $col = 1;
            foreach ($record as $value) {
                $letter = self::colLetter($col);
                $sheet->setCellValue("{$letter}{$row}", $value);

                if (in_array($col, $currencyCols, true)) {
                    $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode(self::FMT_RP);
                    $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($col, $numberCols, true)) {
                    $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode(self::FMT_NUMBER);
                    $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($col, $centerCols, true)) {
                    $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $col++;
            }

            $lastCol = self::colLetter($col - 1);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            ]);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_GRAY_LIGHT]],
                ]);
            }

            $row++;
        }

        return $row;
    }

    /**
     * Baris total di akhir tabel, ditonjolkan dengan warna amber muda.
     * $values sejumlah kolom, isi '' untuk sel yang dikosongkan.
     */
    public static function totalsRow(Worksheet $sheet, int $row, array $values, array $currencyCols = [], array $numberCols = []): void
    {
        $col = 1;
        foreach ($values as $value) {
            $letter = self::colLetter($col);
            $sheet->setCellValue("{$letter}{$row}", $value);

            if (in_array($col, $currencyCols, true)) {
                $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode(self::FMT_RP);
                $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif (in_array($col, $numberCols, true)) {
                $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode(self::FMT_NUMBER);
                $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $col++;
        }

        $lastCol = self::colLetter($col - 1);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_INK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_AMBER_LIGHT]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::COLOR_AMBER]]],
        ]);
    }

    /**
     * Tulis judul section kecil (mis. "Detail Batch", "Riwayat Pembayaran") di antara blok tabel.
     */
    public static function sectionTitle(Worksheet $sheet, int $row, string $text, int $colSpan): int
    {
        $lastCol = self::colLetter($colSpan);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", $text);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::COLOR_INK]],
        ]);

        return $row + 2;
    }

    public static function autosize(Worksheet $sheet, int $colSpan, int $minWidth = 12, int $maxWidth = 48): void
    {
        for ($i = 1; $i <= $colSpan; $i++) {
            $letter = self::colLetter($i);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        // setAutoSize butuh kalkulasi ulang; batasi biar tidak kelewat lebar/sempit.
        foreach ($sheet->getColumnDimensions() as $dimension) {
            $sheet->calculateColumnWidths();
        }
    }

    public static function setColumnWidths(Worksheet $sheet, array $widths): void
    {
        $col = 1;
        foreach ($widths as $width) {
            $sheet->getColumnDimension(self::colLetter($col))->setWidth($width);
            $col++;
        }
    }

    public static function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    /**
     * Stream file .xlsx sebagai response download, sekaligus bersihkan memory spreadsheet.
     */
    public static function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $spreadsheet->setActiveSheetIndex(0);

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
