<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleSource extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'source_id');
    }
}
