<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        // qty_on_hand sengaja TIDAK ada di sini — itu kolom cache yang cuma
        // boleh disinkronkan lewat StockService, bukan diinput manual.
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'unit'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['boolean'],
        ];
    }
}
