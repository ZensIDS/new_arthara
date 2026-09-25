<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sama seperti input SO: hanya superadmin yang boleh input retur.
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:1000'],

            // Kas sumber refund ke customer — hanya benar-benar dipakai kalau retur
            // ini membuat SO overpaid (lihat SalesReturnService::create). Kalau
            // ternyata overpaid tapi ini kosong, service akan menolak dengan
            // RuntimeException yang jelas, bukan gagal diam-diam.
            'cash_id' => ['nullable', 'exists:cashes,id'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.sale_item_id'     => ['required', 'exists:sale_items,id'],
            'items.*.qty'               => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        // Satu baris sale item cuma boleh muncul 1 kali dalam 1 retur.
        $validator->after(function ($validator) {
            $itemIds = collect($this->input('items', []))->pluck('sale_item_id')->filter();

            if ($itemIds->count() !== $itemIds->unique()->count()) {
                $validator->errors()->add('items', 'Satu item transaksi tidak boleh diretur 2 kali dalam 1 form yang sama.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'  => 'Minimal harus ada 1 item barang yang diretur.',
            'items.*.qty.min' => 'Qty retur minimal 1.',
        ];
    }
}