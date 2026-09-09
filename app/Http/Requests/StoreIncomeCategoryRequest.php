<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255', 'unique:income_categories,name'],
            'affects_profit_loss'  => ['sometimes', 'boolean'],
        ];
    }
}
