<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'unit',
        'description',
        'is_active',
        'qty_on_hand',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'qty_on_hand' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    // Hanya batch yang masih ada sisa qty, urut FIFO (paling lama duluan)
    public function availableBatches()
    {
        return $this->hasMany(StockBatch::class)
            ->where('qty_remaining', '>', 0)
            ->orderBy('batch_date')
            ->orderBy('id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
