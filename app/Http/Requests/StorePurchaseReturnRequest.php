<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sama seperti input PO: hanya superadmin yang boleh input retur.
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:1000'],

            'items'                              => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id'     => ['required', 'exists:purchase_order_items,id'],
            'items.*.qty'                         => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        // Satu baris PO item cuma boleh muncul 1 kali dalam 1 retur.
        $validator->after(function ($validator) {
            $itemIds = collect($this->input('items', []))->pluck('purchase_order_item_id')->filter();

            if ($itemIds->count() !== $itemIds->unique()->count()) {
                $validator->errors()->add('items', 'Satu item PO tidak boleh diretur 2 kali dalam 1 form yang sama.');
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
