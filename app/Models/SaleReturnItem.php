<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'sale_item_id',
        'product_id',
        'qty',
        'sell_price',
        'subtotal',
        'hpp_subtotal',
    ];

    protected $casts = [
        'qty'          => 'integer',
        'sell_price'   => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'hpp_subtotal' => 'decimal:2',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations()
    {
        return $this->hasMany(SaleReturnItemAllocation::class);
    }
}
