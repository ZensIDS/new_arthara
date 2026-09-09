<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya superadmin, dan hanya kalau PO ini belum pernah dibayar sama sekali.
        // Kalau sudah ada pembayaran, PO dianggap final — tolak di sini duluan
        // (403) sebelum sempat masuk ke Service.
        return $this->user()->isSuperadmin()
            && $this->route('purchaseOrder')?->canBeModified();
    }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['required', 'exists:suppliers,id'],
            'po_date'       => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:1000'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'exists:products,id'],
            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.buy_price'        => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productIds = collect($this->input('items', []))->pluck('product_id')->filter();

            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('items', 'Satu produk tidak boleh dipilih di lebih dari 1 baris item.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'   => 'Minimal harus ada 1 item barang dalam PO.',
            'items.*.qty.min'  => 'Qty barang minimal 1.',
        ];
    }
}
