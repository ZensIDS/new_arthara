<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    protected $fillable = [
        'transaction_date',
        'direction',
        'amount',
        'source_type',
        'source_id',
        'description',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    public function source()
    {
        return $this->morphTo();
    }
}
