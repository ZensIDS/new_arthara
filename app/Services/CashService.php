<?php

namespace App\Services;

use App\Models\Cash;
use RuntimeException;

/**
 * Menangani perubahan current_balance sebuah Cash akibat transaksi (PO, SO,
 * Expense, Retur, dll). Sengaja dipisah dari CashFlowService: CashFlowService
 * hanya mencatat ledger (baris riwayat arus kas), sedangkan service ini yang
 * benar-benar menaikkan/menurunkan saldo berjalan. Setiap transaksi yang
 * memengaruhi kas idealnya memanggil DUA-duanya (lihat PurchaseOrderService
 * & PurchaseReturnService sebagai contoh).
 */
class CashService
{
    /**
     * Kas masuk: saldo bertambah. Dipakai misalnya untuk pembayaran yang
     * diterima, atau refund kelebihan bayar dari supplier.
     */
    public function increase(Cash $cash, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $locked = Cash::where('id', $cash->id)->lockForUpdate()->first();
        $locked->current_balance = (float) $locked->current_balance + $amount;
        $locked->save();

        $cash->current_balance = $locked->current_balance;
    }

    /**
     * Kas keluar: saldo berkurang. Dipakai misalnya untuk pembayaran ke
     * supplier. Ditolak kalau saldo kas yang dipilih tidak cukup, supaya
     * saldo tidak pernah minus.
     *
     * @throws RuntimeException kalau saldo kas tidak cukup
     */
    public function decrease(Cash $cash, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $locked = Cash::where('id', $cash->id)->lockForUpdate()->first();

        if ((float) $locked->current_balance < $amount) {
            throw new RuntimeException(
                "Saldo kas \"{$locked->name}\" tidak mencukupi. Saldo saat ini Rp ".number_format((float) $locked->current_balance, 0, ',', '.').
                ", dibutuhkan Rp ".number_format($amount, 0, ',', '.').'.'
            );
        }

        $locked->current_balance = (float) $locked->current_balance - $amount;
        $locked->save();

        $cash->current_balance = $locked->current_balance;
    }
}