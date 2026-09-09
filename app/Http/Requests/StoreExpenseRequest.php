<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'expense_date'        => ['required', 'date'],
            'amount'              => ['required', 'numeric', 'min:0'],
            'description'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
