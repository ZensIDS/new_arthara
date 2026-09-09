@extends('layouts.app')

@section('page-title', 'Detail Purchase Order')

@php
    $statusStyle = [
        'paid'    => 'bg-emerald-100 text-emerald-700',
        'partial' => 'bg-amber-100 text-amber-800',
        'unpaid'  => 'bg-red-100 text-red-700',
    ][$purchaseOrder->payment_status] ?? 'bg-ink/[0.06] text-ink/60';
    $statusLabel = [
        'paid'    => 'Lunas',
        'partial' => 'Sebagian',
        'unpaid'  => 'Belum Bayar',
    ][$purchaseOrder->payment_status] ?? $purchaseOrder->payment_status;
@endphp

@section('content')
<div>
    <a href="{{ route('purchase-orders.index') }}" class="text-sm text-ink/50 hover:text-ink inline-flex items-center gap-1 mb-4">
        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Kembali ke daftar PO
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1.5">
                <h2 class="text-2xl font-display font-semibold tracking-tight tnum">{{ $purchaseOrder->po_number }}</h2>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <p class="text-sm text-ink/50">
                {{ $purchaseOrder->po_date->format('d M Y') }} &middot; {{ $purchaseOrder->supplier->name }}
            </p>
        </div>

        @if ($purchaseOrder->canBeModified() && auth()->user()->isSuperadmin())
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold px-4 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('purchase-orders.destroy', $purchaseOrder) }}"
                      onsubmit="return confirm('Hapus PO {{ $purchaseOrder->po_number }}? Stok yang sudah masuk dari PO ini akan dikembalikan. Aksi ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold px-4 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                        Hapus
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 @4xl:grid-cols-3 gap-6">

        <div class="@4xl:col-span-2 space-y-6">

            {{-- Item barang --}}
            <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
                <div class="px-6 py-4 border-b border-ink/10">
                    <h3 class="font-display font-semibold">Item Barang</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-ink/[0.03] text-left text-ink/50">
                                <th class="px-6 py-3 font-semibold text-xs uppercase tracking-wide">Produk</th>
                                <th class="px-6 py-3 font-semibold text-xs uppercase tracking-wide text-right">Qty</th>
                                <th class="px-6 py-3 font-semibold text-xs uppercase tracking-wide text-right">Harga Beli</th>
                                <th class="px-6 py-3 font-semibold text-xs uppercase tracking-wide text-right">Subtotal</th>
                                <th class="px-6 py-3 font-semibold text-xs uppercase tracking-wide text-right">Sisa Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/[0.06]">
                            @foreach ($purchaseOrder->items as $item)
                                <tr>
                                    <td class="px-6 py-3.5">
                                        <p class="font-medium">{{ $item->product->name }}</p>
                                        <p class="text-xs text-ink/40">{{ $item->product->unit }}</p>
                                    </td>
                                    <td class="px-6 py-3.5 text-right tnum">{{ $item->qty }}</td>
                                    <td class="px-6 py-3.5 text-right tnum">Rp {{ number_format($item->buy_price, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3.5 text-right tnum font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3.5 text-right tnum text-ink/50">
                                        {{ $item->stockBatch->qty_remaining ?? 0 }} / {{ $item->stockBatch->qty_in ?? $item->qty }}
                                        @if ($item->qty_returned > 0)
                                            <span class="block text-xs text-red-600 font-normal">Diretur: {{ $item->qty_returned }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-ink/[0.02]">
                                <td colspan="3" class="px-6 py-3.5 text-right font-medium text-ink/60">Total PO</td>
                                <td colspan="2" class="px-6 py-3.5 text-right font-display font-semibold tnum">
                                    Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if ($purchaseOrder->note)
                    <div class="px-6 py-4 border-t border-ink/10 text-sm text-ink/60">
                        <span class="font-medium text-ink/70">Catatan:</span> {{ $purchaseOrder->note }}
                    </div>
                @endif
            </div>

            {{-- Retur Pembelian --}}
            <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
                <div class="px-6 py-4 border-b border-ink/10">
                    <h3 class="font-display font-semibold">Retur Pembelian</h3>
                </div>
                @if ($purchaseOrder->returns->isEmpty())
                    <p class="px-6 py-8 text-sm text-ink/40 text-center">Belum ada retur tercatat.</p>
                @else
                    <div class="divide-y divide-ink/[0.06] text-sm">
                        @foreach ($purchaseOrder->returns->sortByDesc('return_date') as $return)
                            <div class="px-6 py-3.5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-medium">{{ $return->return_number }}</p>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <p class="tnum font-medium text-red-700">- Rp {{ number_format($return->total_amount, 0, ',', '.') }}</p>
                                        @if (auth()->user()->isSuperadmin())
                                            <form method="POST" action="{{ route('purchase-orders.returns.destroy', [$purchaseOrder, $return]) }}"
                                                  onsubmit="return confirm('Hapus retur {{ $return->return_number }}? Stok akan dikembalikan, total PO akan ditambah kembali, dan entri kas masuk (refund) terkait (kalau ada) akan dihapus. Aksi ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-ink/40 hover:text-red-600 p-1" title="Hapus retur">
                                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-xs text-ink/40 mt-0.5">
                                    {{ $return->return_date->format('d M Y') }}
                                    @if ($return->note) &middot; {{ $return->note }} @endif
                                </p>
                                <ul class="mt-2 space-y-0.5 text-xs text-ink/60">
                                    @foreach ($return->items as $returnItem)
                                        <li>{{ $returnItem->product->name }} &times; {{ $returnItem->qty }} {{ $returnItem->product->unit }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php
                    $returnableItems = $purchaseOrder->items->filter(fn ($item) => ($item->stockBatch->qty_remaining ?? 0) > 0);
                @endphp

                @if (auth()->user()->isSuperadmin() && $returnableItems->isNotEmpty())
                    <div x-data="poReturnForm()" x-cloak class="px-6 py-4 border-t border-ink/10">
                        <button type="button" @click="open = !open"
                                class="text-sm font-semibold text-amber-700 hover:text-amber-800 inline-flex items-center gap-1.5">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            Buat Retur Baru
                        </button>

                        <form x-show="open" x-cloak method="POST" action="{{ route('purchase-orders.returns.store', $purchaseOrder) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Tanggal Retur</label>
                                <input type="date" name="return_date" value="{{ now()->toDateString() }}"
                                       class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            </div>

                            <div class="space-y-3">
                                @foreach ($returnableItems as $item)
                                    @php $maxQty = $item->stockBatch->qty_remaining; @endphp
                                    <label class="flex items-center gap-3 rounded-xl border border-ink/10 px-3.5 py-2.5">
                                        <input type="checkbox" :value="{{ $item->id }}" @change="toggle({{ $item->id }}, {{ $maxQty }}, $event.target.checked)"
                                               class="h-4 w-4 rounded border-ink/25 text-amber-500 focus:ring-amber-500/40">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $item->product->name }}</p>
                                            <p class="text-xs text-ink/40">Sisa stok dari PO ini: {{ $maxQty }} {{ $item->product->unit }}</p>
                                        </div>
                                        <input type="number" min="1" max="{{ $maxQty }}"
                                               x-show="selected[{{ $item->id }}] !== undefined" x-cloak
                                               x-model.number="selected[{{ $item->id }}]"
                                               @input="clamp({{ $item->id }}, {{ $maxQty }})"
                                               :name="selected[{{ $item->id }}] !== undefined ? 'items[{{ $item->id }}][qty]' : null"
                                               class="w-20 rounded-lg border border-ink/12 px-2 py-1.5 text-sm text-right tnum focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                        <input type="hidden" :name="selected[{{ $item->id }}] !== undefined ? 'items[{{ $item->id }}][purchase_order_item_id]' : null" value="{{ $item->id }}">
                                    </label>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1.5">Catatan</label>
                                <input type="text" name="note" placeholder="Opsional, mis. alasan retur"
                                       class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            </div>

                            <button type="submit" :disabled="Object.keys(selected).length === 0"
                                    class="w-full text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 active:scale-[0.98] transition-all disabled:opacity-40 disabled:pointer-events-none">
                                Simpan Retur
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Riwayat pembayaran --}}
            <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
                <div class="px-6 py-4 border-b border-ink/10">
                    <h3 class="font-display font-semibold">Riwayat Pembayaran</h3>
                </div>
                @if ($purchaseOrder->payments->isEmpty())
                    <p class="px-6 py-8 text-sm text-ink/40 text-center">Belum ada pembayaran tercatat.</p>
                @else
                    <div class="divide-y divide-ink/[0.06] text-sm">
                        @foreach ($purchaseOrder->payments->sortByDesc('payment_date') as $payment)
                            <div x-data="paymentEditRow({{ (int) round($payment->amount) }}, {{ (int) round($payment->amount + $purchaseOrder->remaining_balance) }})" x-cloak>
                                <div class="flex items-center justify-between px-6 py-3.5" x-show="!editing">
                                    <div>
                                        <p class="font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                                        <p class="text-xs text-ink/40">
                                            {{ $payment->payment_date->format('d M Y') }} &middot;
                                            {{ ['cash' => 'Tunai', 'transfer' => 'Transfer', 'other' => 'Lainnya'][$payment->method] ?? $payment->method }}
                                            @if ($payment->note) &middot; {{ $payment->note }} @endif
                                        </p>
                                    </div>
                                    @if (auth()->user()->isSuperadmin())
                                        <button type="button" @click="editing = true"
                                                class="text-ink/40 hover:text-amber-700 p-1.5 shrink-0" title="Edit pembayaran">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                    @endif
                                </div>

                                @if (auth()->user()->isSuperadmin())
                                    <form x-show="editing" x-transition method="POST"
                                          action="{{ route('purchase-orders.payments.update', [$purchaseOrder, $payment]) }}"
                                          class="px-6 py-4 bg-amber-50/40 space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-ink/50 mb-1">Tanggal Bayar</label>
                                                <input type="date" name="payment_date" value="{{ $payment->payment_date->toDateString() }}"
                                                       class="w-full rounded-lg border border-ink/12 px-3 py-2 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-ink/50 mb-1">
                                                    Jumlah
                                                    <button type="button" @click="setAmount(max)" class="text-amber-700 hover:text-amber-800 font-normal text-[11px]">(isi maksimal)</button>
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-ink/40">Rp</span>
                                                    <input type="text" inputmode="numeric"
                                                           :value="formatRupiah(amount)"
                                                           @input="setAmount(parseRupiah($event.target.value)); $event.target.value = formatRupiah(amount)"
                                                           class="w-full rounded-lg border border-ink/12 pl-8 pr-3 py-2 text-sm tnum focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                                    <input type="hidden" name="amount" :value="amount">
                                                </div>
                                                <p class="text-[11px] mt-1" :class="amount === max ? 'text-amber-700 font-medium' : 'text-ink/40'">
                                                    Maks. untuk pembayaran ini: Rp <span x-text="formatRupiah(max)"></span>
                                                </p>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-ink/50 mb-1">Metode</label>
                                                <select name="method" class="w-full rounded-lg border border-ink/12 px-3 py-2 text-sm bg-white focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                                    <option value="cash" @selected($payment->method === 'cash')>Tunai</option>
                                                    <option value="transfer" @selected($payment->method === 'transfer')>Transfer</option>
                                                    <option value="other" @selected($payment->method === 'other')>Lainnya</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-ink/50 mb-1">Catatan</label>
                                                <input type="text" name="note" value="{{ $payment->note }}" placeholder="Opsional"
                                                       class="w-full rounded-lg border border-ink/12 px-3 py-2 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                            </div>
                                        </div>
                                        <p class="text-xs text-ink/40">
                                            Mengubah nominal ini akan otomatis menghitung ulang sisa hutang & catatan arus kas terkait.
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <button type="submit"
                                                    class="text-xs font-semibold px-4 py-2 rounded-lg bg-ink text-white hover:bg-ink/90 transition-colors">
                                                Simpan
                                            </button>
                                            <button type="button" @click="editing = false"
                                                    class="text-xs font-medium px-4 py-2 rounded-lg border border-ink/12 hover:bg-ink/[0.03] transition-colors">
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">

            {{-- Ringkasan pembayaran --}}
            <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
                <div class="px-6 py-4 border-b border-ink/10">
                    <h3 class="font-display font-semibold">Ringkasan</h3>
                </div>
                <div class="divide-y divide-ink/[0.06] text-sm">
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-ink/60">Total PO</span>
                        <span class="tnum font-medium">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-ink/60">Sudah Dibayar</span>
                        <span class="tnum font-medium text-emerald-700">Rp {{ number_format($purchaseOrder->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3.5 font-semibold bg-amber-50/60">
                        <span>Sisa Hutang</span>
                        <span class="tnum {{ $purchaseOrder->remaining_balance > 0 ? 'text-red-700' : '' }}">
                            Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Form tambah pembayaran --}}
            @if (auth()->user()->isSuperadmin() && $purchaseOrder->remaining_balance > 0)
                <div
                    x-data="poPaymentForm({{ (float) $purchaseOrder->remaining_balance }})"
                    x-cloak
                    class="rounded-2xl border border-ink/10 bg-white shadow-card p-6"
                >
                    <h3 class="font-display font-semibold mb-4">Tambah Pembayaran</h3>
                    <form method="POST" action="{{ route('purchase-orders.payments.store', $purchaseOrder) }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Tanggal Bayar</label>
                                <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
                                       class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5">
                                    Jumlah
                                    <button type="button" @click="useMax()" class="text-amber-700 hover:text-amber-800 font-normal text-xs">(isi lunas)</button>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-ink/40">Rp</span>
                                    <input type="text" inputmode="numeric"
                                           :value="formatRupiah(amount)"
                                           @input="setAmount(parseRupiah($event.target.value)); $event.target.value = formatRupiah(amount)"
                                           class="w-full rounded-xl border border-ink/12 pl-9 pr-3.5 py-2.5 text-sm tnum focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                    <input type="hidden" name="amount" :value="amount">
                                </div>
                                <p class="text-xs mt-1" :class="amount === remaining ? 'text-amber-700 font-medium' : 'text-ink/40'">
                                    Sisa hutang: Rp <span x-text="formatRupiah(remaining)"></span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Metode</label>
                                <select name="method" class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                                    <option value="cash">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Catatan</label>
                                <input type="text" name="note" placeholder="Opsional"
                                       class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            </div>
                        </div>
                        <button type="submit"
                                class="mt-5 w-full text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 active:scale-[0.98] transition-all">
                            Simpan Pembayaran
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function poPaymentForm(remaining) {
        return {
            remaining,
            amount: '',
            // Cegah input nominal pembayaran melebihi sisa hutang — kalau user ngetik
            // lebih besar dari sisa, otomatis dipangkas balik ke batas maksimalnya.
            setAmount(value) {
                this.amount = value === '' ? '' : Math.min(value, this.remaining);
            },
            useMax() { this.setAmount(this.remaining); },
        };
    }

    function poReturnForm() {
        return {
            open: false,
            // selected: { [purchase_order_item_id]: qty }
            selected: {},
            toggle(itemId, maxQty, checked) {
                if (checked) {
                    this.selected[itemId] = maxQty;
                } else {
                    delete this.selected[itemId];
                }
            },
            clamp(itemId, maxQty) {
                let qty = this.selected[itemId];
                if (qty === '' || qty === null || isNaN(qty)) {
                    return;
                }
                this.selected[itemId] = Math.max(1, Math.min(qty, maxQty));
            },
        };
    }

    function paymentEditRow(initialAmount, max) {
        return {
            editing: false,
            amount: initialAmount,
            max,
            // Nominal edit tidak boleh melebihi (nominal lama + sisa hutang saat ini) —
            // itu batas maksimal yang mungkin tanpa bikin total pembayaran > total PO,
            // sudah memperhitungkan pembayaran-pembayaran lain yang sudah ada.
            setAmount(value) {
                this.amount = value === '' ? '' : Math.min(value, this.max);
            },
        };
    }
</script>
@endpush
