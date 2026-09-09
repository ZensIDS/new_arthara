<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_order_item_id',
        'product_id',
        'qty',
        'buy_price',
        'subtotal',
    ];

    protected $casts = [
        'qty'       => 'integer',
        'buy_price' => 'decimal:2',
        'subtotal'  => 'decimal:2',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
