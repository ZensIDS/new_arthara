<?php

namespace App\Http\Requests;

use App\Models\ProfitShare;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfitShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        $profitShare = $this->route('profitShare') ?? $this->route('profit_share');

        return [
            'name'       => ['required', 'string', 'max:255', Rule::unique('profit_shares', 'name')->ignore($profitShare)],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Sama seperti StoreProfitShareRequest, tapi baris yang sedang diedit
     * dikeluarkan dulu dari total sebelum ditambah nilai barunya.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $profitShare = $this->route('profitShare') ?? $this->route('profit_share');
            $isActive = $this->boolean('is_active', true);

            if (! $isActive) {
                return;
            }

            $existingTotal = ProfitShare::where('is_active', true)
                ->when($profitShare, fn($q) => $q->whereKeyNot($profitShare))
                ->sum('percentage');

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
