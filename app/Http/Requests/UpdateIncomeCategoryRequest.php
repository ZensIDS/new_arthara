<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncomeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        $incomeCategory = $this->route('incomeCategory') ?? $this->route('income_category');

        return [
            'name'                => ['required', 'string', 'max:255', Rule::unique('income_categories', 'name')->ignore($incomeCategory)],
            'affects_profit_loss' => ['sometimes', 'boolean'],
        ];
    }
}
