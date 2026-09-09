<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'stock_batch_id',
        'type',
        'qty',
        'movement_date',
        'reference_type',
        'reference_id',
        'note',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'qty'           => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
