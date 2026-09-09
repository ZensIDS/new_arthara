<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number',
        'sales_order_id',
        'return_date',
        'total_amount',
        'total_hpp',
        'note',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'total_amount' => 'decimal:2',
        'total_hpp'    => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
