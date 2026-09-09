<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'affects_profit_loss'];

    protected $casts = [
        'affects_profit_loss' => 'boolean',
    ];

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }
}
