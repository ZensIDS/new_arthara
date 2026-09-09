<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate nomor dokumen format: {PREFIX}/{BULAN_ROMAWI}/{TAHUN}/{URUT}
     * Contoh: PO/IX/2026/001, SO/IX/2026/014
     * Urutan reset setiap bulan (per kombinasi prefix+bulan+tahun).
     *
     * @param string $prefix       'PO' atau 'SO'
     * @param string $modelClass   FQCN model, mis. \App\Models\PurchaseOrder::class
     * @param string $numberColumn nama kolom nomor di tabel, mis. 'po_number'
     */
    public function generate(string $prefix, string $modelClass, string $numberColumn): string
    {
        $now   = now();
        $roman = $this->toRoman((int) $now->format('n'));
        $year  = $now->format('Y');

        $pattern = "{$prefix}/{$roman}/{$year}/";

        // Lock supaya 2 transaksi yang dibuat bersamaan di bulan yang sama
        // tidak dapat nomor urut yang sama (harus dipanggil di dalam DB::transaction()).
        $lastNumber = $modelClass::where($numberColumn, 'like', $pattern . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value($numberColumn);

        $nextSequence = 1;
        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, strlen($pattern));
            $nextSequence = $lastSequence + 1;
        }

        return $pattern . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    protected function toRoman(int $month): string
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }
}
