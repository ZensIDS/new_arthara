<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya superadmin yang boleh input penjualan. Viewer hanya boleh melihat.
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'customer_id'   => ['nullable', 'exists:customers,id'],
            'so_date'       => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'source_id'     => ['required', 'exists:sale_sources,id'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'exists:products,id'],
            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.sell_price'       => ['required', 'numeric', 'min:0'],

            'initial_payment'          => ['nullable', 'numeric', 'min:0'],
            'payment_method'           => ['nullable', 'in:cash,transfer,other'],
        ];
    }

    public function withValidator($validator): void
    {
        // Satu produk cuma boleh muncul di 1 baris item. Dicek juga di sini (bukan cuma
        // di form JS) supaya request yang dikirim langsung tanpa lewat form tetap ditolak.
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
            'items.required'  => 'Minimal harus ada 1 item barang dalam transaksi.',
            'items.*.qty.min' => 'Qty barang minimal 1.',
        ];
    }
}
