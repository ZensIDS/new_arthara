<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['expense_category_id', 'purchase_order_id', 'sales_order_id', 'expense_date', 'amount', 'description'];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    // Terisi kalau expense ini otomatis dibuat dari form "Biaya Lainnya" di PO
    // (packing, ongkir, dll) — null kalau input manual dari halaman Pengeluaran.
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Terisi kalau expense ini otomatis dibuat dari form "Biaya Lainnya" di SO
    // (packing, ongkir, dll) — null kalau input manual dari halaman Pengeluaran.
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
