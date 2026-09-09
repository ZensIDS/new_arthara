<?php

namespace App\Http\Requests;

use App\Models\ProfitShare;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProfitShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:profit_shares,name'],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Total persentase orang yang AKTIF tidak boleh lebih dari 100%,
     * supaya bagi hasil tidak "membagikan" lebih dari Laba Bersih yang ada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isActive = $this->boolean('is_active', true);

            if (! $isActive) {
                return;
            }

            $existingTotal = ProfitShare::where('is_active', true)->sum('percentage');
            $newTotal = $existingTotal + (float) $this->input('percentage', 0);

            if ($newTotal > 100) {
                $validator->errors()->add(
                    'percentage',
                    "Total persentase bagi hasil aktif akan menjadi {$newTotal}%, melebihi 100%."
                );
            }
        });
    }
}
