<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number',
        'customer_id',
        'so_date',
        'total_amount',
        'total_hpp',
        'paid_amount',
        'payment_status',
        'note',
        'source_id',
        'estimated_packing_cost',
    ];

    protected $casts = [
        'so_date'                => 'date',
        'total_amount'           => 'decimal:2',
        'total_hpp'              => 'decimal:2',
        'paid_amount'            => 'decimal:2',
        'estimated_packing_cost' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function source()
    {
        return $this->belongsTo(SaleSource::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    // Biaya lainnya (packing, ongkir, dll) yang diinput lewat form SO ini dan
    // otomatis tercatat sebagai Expense — TIDAK ikut menambah total_amount/paid_amount,
    // karena itu murni biaya operasional terkait transaksi ini, bukan bagian dari
    // harga jual ke customer. Pola sama seperti PurchaseOrder::otherCosts().
    public function otherCosts()
    {
        return $this->hasMany(Expense::class);
    }

    public function getOtherCostsTotalAttribute(): float
    {
        return (float) $this->otherCosts()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->total_hpp;
    }

    // Estimasi untung bersih = Total Penjualan - Total HPP - Biaya Lainnya
    // (tercatat sebagai Expense) - Estimasi Biaya Packing (diinput manual saat
    // create/edit). Murni gambaran, TIDAK mempengaruhi angka keuangan apapun
    // (bukan dipakai di laporan/cash_flow), makanya bukan bagian dari
    // total_amount/paid_amount.
    public function getEstimatedNetProfitAttribute(): float
    {
        return (float) $this->total_amount
            - (float) $this->total_hpp
            - $this->other_costs_total
            - (float) $this->estimated_packing_cost;
    }

    // Kebijakan: edit & hapus SO TETAP diperbolehkan meskipun sudah ada
    // pembayaran (partial maupun lunas). Satu-satunya hal yang benar-benar
    // memblokir edit/hapus adalah kalau ada item dari SO ini yang sudah
    // terlanjur diretur customer (lihat SalesOrderService::guardCanModify)
    // — itu dicek terpisah di service karena butuh query ke tabel lain,
    // bukan lewat method ini.
    public function canBeModified(): bool
    {
        return true;
    }
}
