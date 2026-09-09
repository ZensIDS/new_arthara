<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya superadmin yang boleh input pembayaran SO. Viewer hanya boleh melihat.
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'method'       => ['required', 'in:cash,transfer,other'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
