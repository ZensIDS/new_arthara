<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        $saleSource = $this->route('saleSource') ?? $this->route('sale_source');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('sale_sources', 'name')->ignore($saleSource)],
        ];
    }
}
