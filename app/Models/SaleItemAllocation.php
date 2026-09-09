<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItemAllocation extends Model
{
    protected $fillable = [
        'sale_item_id',
        'stock_batch_id',
        'qty_taken',
        'buy_price_at_time',
        'hpp_subtotal',
    ];

    protected $casts = [
        'qty_taken'         => 'integer',
        'buy_price_at_time' => 'decimal:2',
        'hpp_subtotal'      => 'decimal:2',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function returnAllocations()
    {
        return $this->hasMany(SaleReturnItemAllocation::class);
    }
}
