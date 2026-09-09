@extends('layouts.app')

@section('page-title', 'Buat Transaksi Penjualan')

@section('content')
<div
    x-data="soCreateForm(
        {{ Illuminate\Support\Js::from($customers) }},
        {{ Illuminate\Support\Js::from($products) }},
        {{ Illuminate\Support\Js::from($sources) }},
        {{ Illuminate\Support\Js::from(old('items', [['product_id' => '', 'qty' => 1, 'sell_price' => '']])) }}
    )"
    x-cloak
>
    <div class="mb-6">
        <a href="{{ route('sales-orders.index') }}" class="text-sm text-ink/50 hover:text-ink inline-flex items-center gap-1 mb-2">
            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali ke daftar SO
        </a>
        <h2 class="text-2xl font-display font-semibold tracking-tight">Buat Transaksi Penjualan</h2>
        <p class="text-sm text-ink/50 mt-1">Stok akan otomatis dipotong FIFO (batch tertua duluan) begitu transaksi disimpan.</p>
        <p class="text-xs text-ink/40 mt-1">Kolom bertanda <span class="text-red-600 font-medium">*</span> wajib diisi.</p>
    </div>

    <form method="POST" action="{{ route('sales-orders.store') }}" @submit="onSubmit">
        @csrf

        {{-- Info utama --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6 mb-6">
            <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="block text-sm font-medium">Customer</label>
                        <button type="button" @click="openCustomerModal()"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            Customer Baru
                        </button>
                    </div>
                    <div class="relative">
                        <select x-ref="customerSelect" name="customer_id" x-init="initCustomerSelect($el)">
                            <option value="">— Pilih customer (opsional) —</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                    @error('customer_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Tanggal Transaksi <span class="text-red-600">*</span></label>
                    <input type="date" name="so_date" value="{{ old('so_date', now()->toDateString()) }}"
                           class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                    @error('so_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 @4xl:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Catatan</label>
                    <textarea name="note" rows="2" placeholder="Opsional"
                              class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">{{ old('note') }}</textarea>
                    @error('note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <label class="block text-sm font-medium">Asal Penjualan <span class="text-red-600">*</span></label>
                        <button type="button" @click="openSourceModal()"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            Asal Baru
                        </button>
                    </div>
                    <select name="source_id" x-model="sourceId"
                            class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <template x-for="s in sources" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
                    </select>
                    @error('source_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Item barang --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-ink/10 flex items-center gap-2">
                <h3 class="font-display font-semibold">Item Barang <span class="text-red-600">*</span></h3>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 hover:text-amber-800">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah Baris
                </button>
            </div>

            <div class="divide-y divide-ink/[0.06]">
                <template x-for="(item, index) in items" :key="item.key">
                    <div class="p-5 grid grid-cols-1 @4xl:grid-cols-12 gap-3 sm:items-start">
                        <div class="@4xl:col-span-5">
                            <label class="block text-xs font-medium text-ink/50 mb-1.5" x-show="index === 0">Produk <span class="text-red-600">*</span></label>
                            <div class="relative">
                                <select :name="'items['+index+'][product_id]'" x-init="initProductSelect($el, item)"></select>
                            </div>
                            <p class="text-xs mt-1" x-show="item.product_id"
                               :class="exceedsStock(item) ? 'text-red-600 font-medium' : 'text-ink/40'">
                                Stok tersedia: <span x-text="currentStock(item.product_id)"></span>
                                <span x-show="exceedsStock(item)"> — melebihi stok!</span>
                            </p>
                        </div>

                        <div class="@4xl:col-span-2">
                            <label class="block text-xs font-medium text-ink/50 mb-1.5" x-show="index === 0">Qty <span class="text-red-600">*</span></label>
                            <input type="number" min="1" :name="'items['+index+'][qty]'" x-model.number="item.qty"
                                   class="w-full rounded-xl border px-3.5 py-2.5 text-sm tnum focus:outline-none focus:ring-4 transition-shadow"
                                   :class="exceedsStock(item)
                                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500/15'
                                        : 'border-ink/12 focus:border-amber-500 focus:ring-amber-500/15'">
                        </div>

                        <div class="@4xl:col-span-3">
                            <label class="block text-xs font-medium text-ink/50 mb-1.5" x-show="index === 0">Harga Jual / Unit <span class="text-red-600">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-ink/40">Rp</span>
                                <input type="text" inputmode="numeric"
                                       :value="formatRupiah(item.sell_price)"
                                       @input="item.sell_price = parseRupiah($event.target.value); $event.target.value = formatRupiah(item.sell_price)"
                                       class="w-full rounded-xl border pl-9 pr-3.5 py-2.5 text-sm tnum focus:outline-none focus:ring-4 transition-shadow"
                                       :class="belowCost(item)
                                            ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/15'
                                            : 'border-ink/12 focus:border-amber-500 focus:ring-amber-500/15'">
                                <input type="hidden" :name="'items['+index+'][sell_price]'" :value="item.sell_price">
                            </div>
                            <p class="text-xs text-amber-700 font-medium mt-1" x-show="belowCost(item)" x-cloak>
                                Di bawah harga beli (Rp <span x-text="formatRupiah(buyPriceFor(item.product_id))"></span>)
                            </p>
                        </div>

                        <div class="@4xl:col-span-1">
                            <label class="block text-xs font-medium text-ink/50 mb-1.5" x-show="index === 0">Subtotal</label>
                            <p class="text-sm font-medium tnum py-2.5" x-text="formatRupiah((item.qty || 0) * (item.sell_price || 0))"></p>
                        </div>

                        <div class="@4xl:col-span-1 flex sm:justify-end sm:pt-6">
                            <button type="button" @click="removeItem(item.key)" x-show="items.length > 1"
                                    class="text-red-600/70 hover:text-red-700 p-1.5" title="Hapus baris">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 bg-ink/[0.02] flex items-center justify-between">
                <span class="text-sm font-medium text-ink/60">Total Transaksi</span>
                <span class="font-display font-semibold text-lg tnum" x-text="'Rp ' + formatRupiah(total)"></span>
            </div>
        </div>
        @error('items')<p class="text-xs text-red-600 -mt-4 mb-6">{{ $message }}</p>@enderror
        <p class="text-xs text-red-600 -mt-4 mb-6" x-show="hasStockError" x-cloak>
            Ada baris dengan qty melebihi stok tersedia — perbaiki dulu sebelum menyimpan.
        </p>

        {{-- Pembayaran awal --}}
        <div class="rounded-2xl border border-ink/10 bg-white shadow-card p-6 mb-6">
            <h3 class="font-display font-semibold mb-1">Pembayaran Awal</h3>
            <p class="text-xs text-ink/50 mb-4">Opsional — kosongkan kalau belum ada pembayaran sama sekali (status akan "Belum Bayar").</p>
            <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Jumlah Dibayar</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-ink/40">Rp</span>
                        <input type="text" inputmode="numeric"
                               :value="formatRupiah(initialPayment)"
                               @input="initialPayment = parseRupiah($event.target.value); $event.target.value = formatRupiah(initialPayment)"
                               class="w-full rounded-xl border border-ink/12 pl-9 pr-3.5 py-2.5 text-sm tnum focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <input type="hidden" name="initial_payment" :value="initialPayment">
                    </div>
                    @error('initial_payment')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Metode Pembayaran</label>
                    <select name="payment_method" x-model="paymentMethod"
                            class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('sales-orders.index') }}"
               class="text-sm font-medium px-5 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">Batal</a>
            <button type="submit"
                    class="text-sm font-semibold px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 active:scale-[0.98] transition-all">
                Simpan Transaksi
            </button>
        </div>
    </form>

    {{-- Modal Tambah Customer Baru --}}
    <div
        x-show="customerModalOpen"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center px-4"
    >
        <div x-show="customerModalOpen" x-transition.opacity @click="customerModalOpen = false" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

        <div
            x-show="customerModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white w-full max-w-md rounded-2xl shadow-panel max-h-[90vh] overflow-y-auto scroll-thin-light"
        >
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-500 rounded-t-2xl"></div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-6 9v-1c0-2.5 2.5-4.5 6-4.5s6 2 6 4.5v1"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg">Tambah Customer Baru</h2>
                </div>

                <p class="text-xs text-ink/40 -mt-2 mb-4">Kolom bertanda <span class="text-red-600 font-medium">*</span> wajib diisi, sisanya boleh dikosongkan.</p>

                <form @submit.prevent="submitCustomer()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Nama <span class="text-red-600">*</span></label>
                            <input type="text" x-model="customerForm.name" x-ref="customerNameInput"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="customerErrors.name?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Telepon</label>
                            <input type="text" x-model="customerForm.phone" placeholder="Opsional"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="customerErrors.phone?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Email</label>
                            <input type="email" x-model="customerForm.email" placeholder="Opsional"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="customerErrors.email?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Alamat</label>
                            <textarea x-model="customerForm.address" rows="3" placeholder="Opsional"
                                      class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"></textarea>
                            <p class="text-xs text-red-600 mt-1" x-text="customerErrors.address?.[0]"></p>
                        </div>
                    </div>

                    <p class="text-xs text-red-600 mt-3" x-show="customerFlashError" x-text="customerFlashError"></p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="customerModalOpen = false"
                                class="text-sm font-medium px-4 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">Batal</button>
                        <button type="submit" :disabled="customerSaving"
                                class="text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 disabled:opacity-50 transition-all">
                            <span x-text="customerSaving ? 'Menyimpan...' : 'Simpan & Pilih'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Asal Penjualan Baru --}}
    <div
        x-show="sourceModalOpen"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center px-4"
    >
        <div x-show="sourceModalOpen" x-transition.opacity @click="sourceModalOpen = false" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

        <div
            x-show="sourceModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white w-full max-w-md rounded-2xl shadow-panel max-h-[90vh] overflow-y-auto scroll-thin-light"
        >
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-500 rounded-t-2xl"></div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 3H4v7.5L14 20.5l7.5-7.5L11.5 3Z"/><path stroke-linecap="round" d="M8 8h.01"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg">Tambah Asal Penjualan Baru</h2>
                </div>

                <form @submit.prevent="submitSource()">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Nama</label>
                        <input type="text" x-model="sourceForm.name" x-ref="sourceNameInput" placeholder="Instagram, TikTok Shop, dll"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-red-600 mt-1" x-text="sourceErrors.name?.[0]"></p>
                    </div>

                    <p class="text-xs text-red-600 mt-3" x-show="sourceFlashError" x-text="sourceFlashError"></p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="sourceModalOpen = false"
                                class="text-sm font-medium px-4 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">Batal</button>
                        <button type="submit" :disabled="sourceSaving"
                                class="text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 disabled:opacity-50 transition-all">
                            <span x-text="sourceSaving ? 'Menyimpan...' : 'Simpan & Pilih'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function soCreateForm(customers, products, sources, initialItems) {
        return {
            customers,
            products,
            sources,
            sourceId: {{ (int) old('source_id', 0) }} || (sources[0]?.id ?? ''),
            items: initialItems.map(i => ({
                key: Math.random().toString(36).slice(2),
                product_id: i.product_id || '',
                qty: i.qty || 1,
                sell_price: i.sell_price || '',
            })),
            initialPayment: {{ (int) old('initial_payment', 0) }} || '',
            paymentMethod: '{{ old('payment_method', 'cash') }}',

            customerModalOpen: false,
            customerSaving: false,
            customerErrors: {},
            customerFlashError: null,
            customerForm: { name: '', phone: '', email: '', address: '' },

            openCustomerModal() {
                this.customerForm = { name: '', phone: '', email: '', address: '' };
                this.customerErrors = {};
                this.customerFlashError = null;
                this.customerModalOpen = true;
                this.$nextTick(() => this.$refs.customerNameInput?.focus());
            },

            async submitCustomer() {
                this.customerSaving = true;
                this.customerErrors = {};
                this.customerFlashError = null;

                const { ok, status, data } = await window.ajaxSend(`{{ route('customers.store') }}`, 'POST', this.customerForm);
                this.customerSaving = false;

                if (ok) {
                    const newCustomer = data.data;
                    this.customers.push(newCustomer);
                    this.customerModalOpen = false;

                    // Pilih otomatis customer yang baru dibuat di select2, tanpa reload
                    // halaman — supaya item barang yang sudah diisi tidak ikut hilang.
                    this.$nextTick(() => {
                        $(this.$refs.customerSelect).val(String(newCustomer.id)).trigger('change.select2');
                    });
                    return;
                }

                if (status === 422) {
                    this.customerErrors = data.errors || {};
                    return;
                }

                this.customerFlashError = data.message || 'Gagal menambahkan customer.';
            },

            sourceModalOpen: false,
            sourceSaving: false,
            sourceErrors: {},
            sourceFlashError: null,
            sourceForm: { name: '' },

            openSourceModal() {
                this.sourceForm = { name: '' };
                this.sourceErrors = {};
                this.sourceFlashError = null;
                this.sourceModalOpen = true;
                this.$nextTick(() => this.$refs.sourceNameInput?.focus());
            },

            async submitSource() {
                this.sourceSaving = true;
                this.sourceErrors = {};
                this.sourceFlashError = null;

                const { ok, status, data } = await window.ajaxSend(`{{ route('sale-sources.store') }}`, 'POST', this.sourceForm);
                this.sourceSaving = false;

                if (ok) {
                    const newSource = data.data;
                    this.sources.push(newSource);
                    this.sourceId = newSource.id;
                    this.sourceModalOpen = false;
                    return;
                }

                if (status === 422) {
                    this.sourceErrors = data.errors || {};
                    return;
                }

                this.sourceFlashError = data.message || 'Gagal menambahkan asal penjualan.';
            },

            addItem() {
                this.items.push({ key: Math.random().toString(36).slice(2), product_id: '', qty: 1, sell_price: '' });
            },

            removeItem(key) {
                if (this.items.length === 1) return;
                this.items = this.items.filter(i => i.key !== key);
            },

            currentStock(productId) {
                const p = this.products.find(p => p.id == productId);
                return p ? p.qty_on_hand + ' ' + p.unit : '—';
            },

            exceedsStock(item) {
                if (!item.product_id) return false;
                const p = this.products.find(p => p.id == item.product_id);
                if (!p) return false;
                return (parseInt(item.qty) || 0) > p.qty_on_hand;
            },

            get hasStockError() {
                return this.items.some(i => this.exceedsStock(i));
            },

            // Harga beli acuan = harga beli batch tertua yang masih ada stok (batch yang
            // bakal benar-benar kepakai duluan kalau produk ini dijual sekarang, sesuai FIFO).
            buyPriceFor(productId) {
                const p = this.products.find(p => p.id == productId);
                return p && p.next_buy_price !== null && p.next_buy_price !== undefined
                    ? parseFloat(p.next_buy_price)
                    : null;
            },

            belowCost(item) {
                if (!item.product_id || !item.sell_price) return false;
                const buyPrice = this.buyPriceFor(item.product_id);
                if (buyPrice === null) return false;
                return parseFloat(item.sell_price) < buyPrice;
            },

            get total() {
                return this.items.reduce((sum, i) => sum + ((parseInt(i.qty) || 0) * (parseFloat(i.sell_price) || 0)), 0);
            },

            onSubmit(e) {
                if (this.hasStockError) {
                    e.preventDefault();
                    return;
                }

                const belowCostItems = this.items.filter(i => this.belowCost(i));
                if (belowCostItems.length > 0) {
                    const names = belowCostItems
                        .map(i => (this.products.find(p => p.id == i.product_id) || {}).name)
                        .filter(Boolean)
                        .join(', ');
                    const ok = confirm(
                        'Harga jual untuk ' + names + ' ditulis di bawah harga beli, transaksi ini akan rugi. ' +
                        'Lanjutkan simpan transaksi ini?'
                    );
                    if (!ok) {
                        e.preventDefault();
                        return;
                    }
                }
            },

            initCustomerSelect(el) {
                $(el).select2({
                    placeholder: '— Pilih customer —',
                    width: '100%',
                    // dropdownParent HARUS body, bukan wrapper lokal — beberapa select
                    // (mis. produk di "Item Barang") ada di dalam card yang overflow-hidden
                    // (buat kliping sudut rounded), jadi dropdown select2 ikut kepotong
                    // kalau dropdownParent-nya masih di dalam card itu.
                    dropdownParent: $('body'),
                });
                const old = {{ Illuminate\Support\Js::from(old('customer_id', '')) }};
                if (old) this.$nextTick(() => $(el).val(String(old)).trigger('change.select2'));
            },

            initProductSelect(el, item) {
                const self = this;
                $(el).select2({
                    placeholder: '— Pilih produk —',
                    width: '100%',
                    dropdownParent: $('body'),
                    // Opsi kosong WAJIB ada duluan di data array. Tanpa ini, select2
                    // (dibangun murni dari `data`, bukan <option> statis) otomatis
                    // memilih item pertama di data sebagai default alih-alih kosong —
                    // ini yang bikin baris baru sering "kebawa" produk baris sebelumnya
                    // dan fitur pembatasan 1-produk-1-baris jadi kurang optimal.
                    data: [
                        { id: '', text: '— Pilih produk —' },
                        ...this.products.map(p => ({
                            id: p.id,
                            text: p.name + ' (' + p.unit + ') — stok ' + p.qty_on_hand,
                            disabled: p.qty_on_hand <= 0,
                        })),
                    ],
                }).on('change', function () {
                    const newVal = $(this).val();

                    // 1 produk cuma boleh dipilih di 1 baris. Kalau produk yang baru dipilih
                    // sudah dipakai di baris lain, batalkan pilihan & balikkan ke nilai
                    // sebelumnya — tambah qty di baris yang sudah ada saja.
                    if (newVal && self.items.some(i => i.key !== item.key && i.product_id == newVal)) {
                        alert('Produk ini sudah dipilih di baris lain. Ubah qty di baris tersebut, atau pilih produk lain.');
                        $(this).val(item.product_id || null).trigger('change.select2');
                        return;
                    }

                    item.product_id = newVal;
                });

                if (item.product_id) {
                    this.$nextTick(() => $(el).val(String(item.product_id)).trigger('change.select2'));
                }
            },
        };
    }
</script>
@endpush
