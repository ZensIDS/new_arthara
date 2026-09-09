@extends('layouts.app')

@section('page-title', 'Purchase Order')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <p id="po-total" class="text-2xl font-display font-semibold tracking-tight">{{ $purchaseOrders->total() }}</p>
            <p class="text-sm text-ink/50">purchase order tercatat</p>
        </div>

        @if (auth()->user()->isSuperadmin())
            <a
                href="{{ route('purchase-orders.create') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold bg-gradient-to-r from-amber-400 to-amber-500 text-ink px-5 py-2.5 rounded-xl shadow-glow hover:brightness-105 active:scale-[0.98] transition-all"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Buat PO
            </a>
        @endif
    </div>

    @include('partials.date-range-filter', ['routeName' => 'purchase-orders.index', 'dateLabel' => 'Tgl PO', 'extraParams' => ['search' => $search]])

    {{-- Search: AJAX, tidak perlu tekan Enter, mencari di semua kolom yang tampil di tabel --}}
    <div class="relative mb-4 max-w-sm">
        <svg viewBox="0 0 24 24" class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.3-4.3"/></svg>
        <input
            id="po-search"
            type="text"
            value="{{ $search }}"
            placeholder="Cari No. PO, supplier, status..."
            autocomplete="off"
            class="w-full rounded-xl border border-ink/12 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"
        >
    </div>

    <div id="po-table-container">
        @include('purchase-orders._table')
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.initAjaxListSearch({
            inputEl: document.getElementById('po-search'),
            containerEl: document.getElementById('po-table-container'),
            baseUrl: '{{ route('purchase-orders.index') }}',
            getExtraParams: () => ({
                start_date: document.querySelector('input[name="start_date"]')?.value || '',
                end_date: document.querySelector('input[name="end_date"]')?.value || '',
            }),
            onSwap: () => {
                const total = document.querySelector('#po-table-container [data-total]')?.dataset.total;
                if (total !== undefined) document.getElementById('po-total').textContent = total;
            },
        });
    });
</script>
@endpush
