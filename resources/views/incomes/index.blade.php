@extends('layouts.app')

@section('page-title', 'Pemasukan Lain')

@section('content')
<div
    x-data="incomePage({{ Illuminate\Support\Js::from($incomeCategories) }})"
    x-cloak
>
    <div class="flex items-center justify-between mb-6">
        <div>
            <p id="income-total" class="text-2xl font-display font-semibold tracking-tight">{{ $incomes->total() }}</p>
            <p class="text-sm text-ink/50">catatan pemasukan</p>
        </div>

        @if (auth()->user()->isSuperadmin())
            <button
                @click="openCreate()"
                class="inline-flex items-center gap-2 text-sm font-semibold bg-gradient-to-r from-amber-400 to-amber-500 text-ink px-5 py-2.5 rounded-xl shadow-glow hover:brightness-105 active:scale-[0.98] transition-all"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Pemasukan
            </button>
        @endif
    </div>

    <p class="text-xs text-ink/40 -mt-4 mb-6">
        Untuk pemasukan di luar penjualan — misalnya suntikan modal, pinjaman, atau pendapatan lain-lain.
        Setiap catatan di sini otomatis tercatat sebagai kas masuk di laporan Arus Kas &amp; Dashboard.
    </p>

    <div x-show="flash" x-cloak x-transition
         class="mb-4 rounded-xl text-sm px-4 py-3 shadow-card"
         :class="flashType === 'error' ? 'bg-red-50 text-red-900 border border-red-600/15' : 'bg-emerald-50 text-emerald-900 border border-emerald-600/15'">
        <span x-text="flash"></span>
    </div>

    @include('partials.date-range-filter', ['routeName' => 'incomes.index', 'dateLabel' => 'Tgl Pemasukan', 'extraParams' => ['search' => $search]])

    {{-- Search: AJAX, tidak perlu tekan Enter, mencari di semua kolom yang tampil di tabel --}}
    <div class="relative mb-4 max-w-sm">
        <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
        <input
            id="income-search"
            type="text"
            value="{{ $search }}"
            placeholder="Cari kategori, keterangan, jumlah..."
            autocomplete="off"
            class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
        >
    </div>

    <div id="income-table-container">
        @include('incomes._table')
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
                    <span class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg" x-text="editing ? 'Edit Pemasukan' : 'Tambah Pemasukan'"></h2>
                </div>

                <p class="text-xs text-ink/40 -mt-2 mb-4">Semua kolom di bawah wajib diisi, kecuali Keterangan.</p>

                <form @submit.prevent="submit()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Kategori Pemasukan <span class="text-red-600">*</span></label>
                            <select x-ref="categorySelect" x-init="initCategorySelect($el)">
                                <option value="">— Pilih kategori —</option>
                                <template x-for="cat in incomeCategories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name + (cat.affects_profit_loss ? '' : ' (di luar laba rugi)')"></option>
                                </template>
                            </select>
                            <p class="text-xs text-red-600 mt-1" x-text="errors.income_category_id?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Tanggal <span class="text-red-600">*</span></label>
                            <input type="date" x-model="form.income_date"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.income_date?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Jumlah <span class="text-red-600">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink/40">Rp</span>
                                <input type="text" inputmode="numeric"
                                       :value="formatRupiah(form.amount)"
                                       @input="form.amount = parseRupiah($event.target.value); $event.target.value = formatRupiah(form.amount)"
                                       placeholder="0"
                                       class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm tnum focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            </div>
                            <p class="text-xs text-red-600 mt-1" x-text="errors.amount?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5">Keterangan</label>
                            <input type="text" x-model="form.description" placeholder="Opsional"
                                   class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.description?.[0]"></p>
                        </div>
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
    function incomePage(initialCategories) {
        return {
            incomeCategories: initialCategories,
            modalOpen: false,
            editing: null,
            saving: false,
            errors: {},
            flash: null,
            flashType: 'success',
            form: { income_category_id: '', income_date: '', amount: '', description: '' },
            _select2El: null,

            initCategorySelect(el) {
                this._select2El = el;
                const self = this;
                $(el).select2({
                    placeholder: '— Pilih kategori —',
                    width: '100%',
                    dropdownParent: $(el).closest('.relative'),
                }).on('change', function () {
                    self.form.income_category_id = $(this).val();
                });
            },

            syncCategorySelect() {
                this.$nextTick(() => {
                    if (this._select2El) {
                        $(this._select2El).val(this.form.income_category_id || null).trigger('change.select2');
                    }
                });
            },

            openCreate() {
                this.editing = null;
                this.form = { income_category_id: '', income_date: '', amount: '', description: '' };
                this.errors = {};
                this.modalOpen = true;
                this.syncCategorySelect();
            },

            openEdit(income) {
                this.editing = income;
                this.form = {
                    income_category_id: income.income_category_id,
                    income_date: income.income_date?.substring(0, 10),
                    amount: Math.round(parseFloat(income.amount)) || '',
                    description: income.description,
                };
                this.errors = {};
                this.modalOpen = true;
                this.syncCategorySelect();
            },

            async submit() {
                this.saving = true;
                this.errors = {};

                const url = this.editing
                    ? `{{ url('incomes') }}/${this.editing.id}`
                    : `{{ route('incomes.store') }}`;
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
                if (!confirm('Hapus catatan pemasukan ini?')) return;

                const { ok, data } = await window.ajaxSend(`{{ url('incomes') }}/${id}`, 'DELETE');

                this.flashType = ok ? 'success' : 'error';
                this.flash = data.message || (ok ? 'Berhasil dihapus.' : 'Gagal menghapus.');

                if (ok) setTimeout(() => window.location.reload(), 500);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.initAjaxListSearch({
            inputEl: document.getElementById('income-search'),
            containerEl: document.getElementById('income-table-container'),
            baseUrl: '{{ route('incomes.index') }}',
            getExtraParams: () => ({
                start_date: document.querySelector('input[name="start_date"]')?.value || '',
                end_date: document.querySelector('input[name="end_date"]')?.value || '',
            }),
            onSwap: () => {
                const total = document.querySelector('#income-table-container [data-total]')?.dataset.total;
                if (total !== undefined) document.getElementById('income-total').textContent = total;
            },
        });
    });
</script>
@endpush
