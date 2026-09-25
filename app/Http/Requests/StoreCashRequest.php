<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255', 'unique:cashes,name'],
            'type'            => ['required', Rule::in(['cash', 'bank'])],
            'account_number'  => ['nullable', 'string', 'max:100'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'description'     => ['nullable', 'string'],
        ];
    }
}