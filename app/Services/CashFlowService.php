<?php

namespace App\Services;

use App\Models\CashFlow;
use Illuminate\Database\Eloquent\Model;

class CashFlowService
{
    public function recordIn(string $date, float $amount, Model $source, string $description): CashFlow
    {
        return CashFlow::create([
            'transaction_date' => $date,
            'direction'        => 'in',
            'amount'           => $amount,
            'source_type'      => get_class($source),
            'source_id'        => $source->getKey(),
            'description'      => $description,
        ]);
    }

    public function recordOut(string $date, float $amount, Model $source, string $description): CashFlow
    {
        return CashFlow::create([
            'transaction_date' => $date,
            'direction'        => 'out',
            'amount'           => $amount,
            'source_type'      => get_class($source),
            'source_id'        => $source->getKey(),
            'description'      => $description,
        ]);
    }

    /**
     * Sinkronkan ulang entry cash_flow milik satu source (mis. PurchasePayment/SalesPayment)
     * yang datanya baru saja diedit — dipakai saat payment di-edit supaya ledger arus kas
     * ikut berubah, bukan dobel/nyisa entry lama.
     */
    public function updateForSource(Model $source, string $date, float $amount): void
    {
        CashFlow::where('source_type', get_class($source))
            ->where('source_id', $source->getKey())
            ->update([
                'transaction_date' => $date,
                'amount'           => $amount,
            ]);
    }

    /**
     * Hapus semua entry cash_flow milik satu source (mis. PurchasePayment) —
     * dipakai saat PO/SO dihapus padahal sudah punya pembayaran, supaya
     * ledger arus kas tidak menyisakan entry "hantu" untuk transaksi yang
     * sumbernya sudah tidak ada.
     */
    public function deleteForSource(Model $source): void
    {
        CashFlow::where('source_type', get_class($source))
            ->where('source_id', $source->getKey())
            ->delete();
    }
}
