@extends('layouts.app')

@section('page-title', 'Produk')

@section('content')
<div
    x-data="productPage({{ Illuminate\Support\Js::from($categories) }})"
    x-cloak
>
    <div class="flex items-center justify-between mb-6">
        <div>
            <p id="product-total" class="text-2xl font-display font-semibold tracking-tight">{{ $products->total() }}</p>
            <p class="text-sm text-ink/50">produk terdaftar</p>
        </div>

        @if (auth()->user()->isSuperadmin())
            <button
                @click="openCreate()"
                class="inline-flex items-center gap-2 text-sm font-semibold bg-gradient-to-r from-amber-400 to-amber-500 text-ink px-5 py-2.5 rounded-xl shadow-glow hover:brightness-105 active:scale-[0.98] transition-all"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Produk
            </button>
        @endif
    </div>

    <div x-show="flash" x-cloak x-transition
         class="mb-4 rounded-xl text-sm px-4 py-3 shadow-card"
         :class="flashType === 'error' ? 'bg-red-50 text-red-900 border border-red-600/15' : 'bg-emerald-50 text-emerald-900 border border-emerald-600/15'">
        <span x-text="flash"></span>
    </div>

    @include('partials.date-range-filter', ['routeName' => 'products.index', 'dateLabel' => 'Tgl Dibuat', 'extraParams' => ['search' => $search]])

    {{-- Search: AJAX, tidak perlu tekan Enter, mencari di semua kolom yang tampil di tabel --}}
    <div class="relative mb-4 max-w-sm">
        <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
        <input
            id="product-search"
            type="text"
            value="{{ $search }}"
            placeholder="Cari nama produk, kategori, satuan, status..."
            autocomplete="off"
            class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
        >
    </div>

    <div id="product-table-container">
        @include('products._table')
    </div>

    {{-- Modal Create/Edit --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
    >
        <div x-show="modalOpen" x-transition.opacity @click="modalOpen = false" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white w-full max-w-md rounded-2xl shadow-panel max-h-[90vh] overflow-y-auto scroll-thin-light"
        >
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-500 rounded-t-2xl"></div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 7.5 12 3l8.5 4.5M3.5 7.5 12 12m-8.5-4.5v9L12 21m0-9 8.5-4.5m-8.5 4.5v9m8.5-9v9L12 21"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg" x-text="editing ? 'Edit Produk' : 'Tambah Produk'"></h2>
                </div>

                <form @submit.prevent="submit()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Kategori</label>
                            <select x-ref="categorySelect" x-init="initCategorySelect($el)">
                                <option value="">— Pilih kategori —</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                            <p class="text-xs text-red-600 mt-1" x-text="errors.category_id?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Nama Produk</label>
                            <input type="text" x-model="form.name" placeholder="Mis. Beras Premium 5kg"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.name?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Satuan</label>
                            <input type="text" x-model="form.unit" placeholder="pcs, kg, dus, dll"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.unit?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Deskripsi</label>
                            <textarea x-model="form.description" rows="3" placeholder="Opsional"
                                      class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"></textarea>
                            <p class="text-xs text-red-600 mt-1" x-text="errors.description?.[0]"></p>
                        </div>

                        <label class="flex items-center gap-2.5 text-sm rounded-xl border border-ink/10 px-3.5 py-2.5 bg-ink/[0.02] cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="h-4 w-4 rounded border-ink/20 text-amber-500 focus:ring-amber-500/40">
                            Produk aktif
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false"
                                class="text-sm font-medium px-4 py-2.5 rounded-xl border border-ink/12 hover:bg-ink/[0.03] transition-colors">Batal</button>
                        <button type="submit" :disabled="saving"
                                class="text-sm font-semibold px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 text-ink shadow-glow hover:brightness-105 disabled:opacity-50 transition-all">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
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
    function productPage(initialCategories) {
        return {
            categories: initialCategories,
            modalOpen: false,
            editing: null,
            saving: false,
            errors: {},
            flash: null,
            flashType: 'success',
            form: { category_id: '', name: '', unit: 'pcs', description: '', is_active: true },
            _select2El: null,

            // Select2 dipasang sekali ke elemen <select> asli, lalu disinkronkan manual
            // ke state Alpine (form.category_id) tiap kali user memilih opsi.
            initCategorySelect(el) {
                this._select2El = el;
                const self = this;
                $(el).select2({
                    placeholder: '— Pilih kategori —',
                    width: '100%',
                    dropdownParent: $(el).closest('.relative'),
                }).on('change', function () {
                    self.form.category_id = $(this).val();
                });
            },

            syncCategorySelect() {
                this.$nextTick(() => {
                    if (this._select2El) {
                        $(this._select2El).val(this.form.category_id || null).trigger('change.select2');
                    }
                });
            },

            openCreate() {
                this.editing = null;
                this.form = { category_id: '', name: '', unit: 'pcs', description: '', is_active: true };
                this.errors = {};
                this.modalOpen = true;
                this.syncCategorySelect();
            },

            openEdit(product) {
                this.editing = product;
                this.form = {
                    category_id: product.category_id,
                    name: product.name,
                    unit: product.unit,
                    description: product.description,
                    is_active: !!product.is_active,
                };
                this.errors = {};
                this.modalOpen = true;
                this.syncCategorySelect();
            },

            async submit() {
                this.saving = true;
                this.errors = {};

                const url = this.editing
                    ? `{{ url('products') }}/${this.editing.id}`
                    : `{{ route('products.store') }}`;
                const method = this.editing ? 'PUT' : 'POST';

                const { ok, status, data } = await window.ajaxSend(url, method, this.form);
                this.saving = false;

                if (ok) {
                    this.modalOpen = false;
                    this.flashType = 'success';
                    this.flash = data.message;
                    setTimeout(() => window.location.reload(), 500);
                    return;
                }

                if (status === 422) {
                    this.errors = data.errors || {};
                    return;
                }

                this.flashType = 'error';
                this.flash = data.message || 'Terjadi kesalahan.';
            },

            async remove(id) {
                if (!confirm('Hapus produk ini?')) return;

                const { ok, data } = await window.ajaxSend(`{{ url('products') }}/${id}`, 'DELETE');

                this.flashType = ok ? 'success' : 'error';
                this.flash = data.message || (ok ? 'Berhasil dihapus.' : 'Gagal menghapus.');

                if (ok) setTimeout(() => window.location.reload(), 500);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.initAjaxListSearch({
            inputEl: document.getElementById('product-search'),
            containerEl: document.getElementById('product-table-container'),
            baseUrl: '{{ route('products.index') }}',
            getExtraParams: () => ({
                start_date: document.querySelector('input[name="start_date"]')?.value || '',
                end_date: document.querySelector('input[name="end_date"]')?.value || '',
            }),
            onSwap: () => {
                const total = document.querySelector('#product-table-container [data-total]')?.dataset.total;
                if (total !== undefined) document.getElementById('product-total').textContent = total;
            },
        });
    });
</script>
@endpush
