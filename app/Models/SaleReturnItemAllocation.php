<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItemAllocation extends Model
{
    protected $fillable = [
        'sale_return_item_id',
        'sale_item_allocation_id',
        'stock_batch_id',
        'qty',
        'buy_price_at_time',
        'hpp_subtotal',
    ];

    protected $casts = [
        'qty'               => 'integer',
        'buy_price_at_time' => 'decimal:2',
        'hpp_subtotal'      => 'decimal:2',
    ];

    public function saleReturnItem()
    {
        return $this->belongsTo(SaleReturnItem::class);
    }

    public function saleItemAllocation()
    {
        return $this->belongsTo(SaleItemAllocation::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}
