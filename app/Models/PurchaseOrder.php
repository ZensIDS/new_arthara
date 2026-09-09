<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'po_date',
        'total_amount',
        'paid_amount',
        'payment_status',
        'note',
    ];

    protected $casts = [
        'po_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    // Kebijakan: edit & hapus PO TETAP diperbolehkan meskipun sudah ada
    // pembayaran (partial maupun lunas). Satu-satunya hal yang benar-benar
    // memblokir edit/hapus adalah kalau stok dari PO ini sudah terlanjur
    // terjual (lihat PurchaseOrderService::guardCanModify) — itu dicek
    // terpisah di service karena butuh query ke tabel lain, bukan lewat
    // method ini.
    public function canBeModified(): bool
    {
        return true;
    }
}
