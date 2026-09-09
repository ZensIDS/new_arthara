<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'purchase_order_item_id',
        'batch_date',
        'buy_price',
        'qty_in',
        'qty_remaining',
    ];

    protected $casts = [
        'batch_date'    => 'date',
        'buy_price'     => 'decimal:2',
        'qty_in'        => 'integer',
        'qty_remaining' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function allocations()
    {
        return $this->hasMany(SaleItemAllocation::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
