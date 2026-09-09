<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'income_category_id' => ['required', 'exists:income_categories,id'],
            'income_date'         => ['required', 'date'],
            'amount'              => ['required', 'numeric', 'min:0'],
            'description'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
