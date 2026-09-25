<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = ['income_category_id', 'cash_id', 'income_date', 'amount', 'description'];

    protected $casts = [
        'income_date' => 'date',
        'amount'      => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    // Kas yang saldonya bertambah akibat pemasukan ini. Null kalau income ini
    // dibuat sebelum fitur kas ada, atau kas sumbernya sudah dihapus.
    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }
}