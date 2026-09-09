<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = ['income_category_id', 'income_date', 'amount', 'description'];

    protected $casts = [
        'income_date' => 'date',
        'amount'      => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }
}
