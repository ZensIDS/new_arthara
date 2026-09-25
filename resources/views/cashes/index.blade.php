@extends('layouts.app')

@section('page-title', 'Kas')

@section('content')
<div
    x-data="cashPage()"
    x-cloak
>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div class="flex gap-8">
            <div>
                <p class="text-2xl font-display font-semibold tracking-tight">{{ $cashes->total() }}</p>
                <p class="text-sm text-ink/50">akun kas</p>
            </div>
            <div>
                <p class="text-2xl font-display font-semibold tracking-tight tnum">Rp {{ number_format($totalBalance, 0, ',', '.') }}</p>
                <p class="text-sm text-ink/50">total saldo aktif</p>
            </div>
        </div>

        @if (auth()->user()->isSuperadmin())
            <button
                @click="openCreate()"
                class="inline-flex items-center gap-2 text-sm font-semibold bg-gradient-to-r from-amber-400 to-amber-500 text-ink px-5 py-2.5 rounded-xl shadow-glow hover:brightness-105 active:scale-[0.98] transition-all"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Kas
            </button>
        @endif
    </div>

    <div x-show="flash" x-cloak x-transition
         class="mb-4 rounded-xl text-sm px-4 py-3 shadow-card"
         :class="flashType === 'error' ? 'bg-red-50 text-red-900 border border-red-600/15' : 'bg-emerald-50 text-emerald-900 border border-emerald-600/15'">
        <span x-text="flash"></span>
    </div>

    <div class="rounded-2xl border border-ink/10 bg-white shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink/[0.03] text-left text-ink/50">
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Nama Kas</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Jenis</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">No. Rekening</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Saldo Awal</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Saldo Berjalan</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 font-semibold text-xs uppercase tracking-wide text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/[0.06]">
                    @forelse ($cashes as $cash)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium">{{ $cash->name }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-ink/[0.05] px-2.5 py-1 text-xs font-semibold text-ink/70">
                                    {{ $cash->type === 'bank' ? 'Bank' : 'Tunai' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-ink/60">{{ $cash->account_number ?: '-' }}</td>
                            <td class="px-5 py-3.5 text-right tnum">Rp {{ number_format($cash->initial_balance, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right tnum font-semibold">Rp {{ number_format($cash->current_balance, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5">
                                @if ($cash->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-ink/[0.05] px-2.5 py-1 text-xs font-semibold text-ink/50">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->isSuperadmin())
                                    <button
                                        @click="openEdit({{ Illuminate\Support\Js::from($cash) }})"
                                        class="text-ink/60 hover:text-ink font-medium mr-3 transition-colors"
                                    >Edit</button>
                                    <button
                                        @click="remove({{ $cash->id }})"
                                        class="text-red-600/80 hover:text-red-700 font-medium transition-colors"
                                    >Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-ink/40">Belum ada kas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $cashes->links() }}
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
            class="relative bg-white w-full max-w-md rounded-2xl shadow-panel"
        >
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-500 rounded-t-2xl"></div>

            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h18M3 7.5v9a1.5 1.5 0 0 0 1.5 1.5h15A1.5 1.5 0 0 0 21 16.5v-9M3 7.5 5.5 4h13L21 7.5"/><path stroke-linecap="round" d="M15.5 12h1.5"/></svg>
                    </span>
                    <h2 class="font-display font-semibold text-lg" x-text="editing ? 'Edit Kas' : 'Tambah Kas'"></h2>
                </div>

                <form @submit.prevent="submit()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Nama Kas</label>
                        <input type="text" x-model="form.name" placeholder="Kas Toko, BCA Utama, dll"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.name?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5">Jenis</label>
                        <select x-model="form.type"
                                class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                            <option value="cash">Tunai</option>
                            <option value="bank">Bank</option>
                        </select>
                        <p class="text-xs text-red-600 mt-1" x-text="errors.type?.[0]"></p>
                    </div>

                    <div x-show="form.type === 'bank'" x-cloak>
                        <label class="block text-sm font-medium mb-1.5">No. Rekening</label>
                        <input type="text" x-model="form.account_number" placeholder="1234567890"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.account_number?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5">Saldo Awal</label>
                        <input type="number" step="0.01" min="0" x-model="form.initial_balance" placeholder="0"
                               class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow">
                        <p class="text-xs text-red-600 mt-1" x-text="errors.initial_balance?.[0]"></p>
                    </div>

                    <div x-show="editing" x-cloak class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.is_active" id="is_active"
                               class="rounded border-ink/20 text-amber-500 focus:ring-amber-500/30">
                        <label for="is_active" class="text-sm font-medium">Kas aktif</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5">Keterangan</label>
                        <textarea x-model="form.description" rows="2" placeholder="Opsional"
                                  class="w-full rounded-xl border border-ink/12 px-3.5 py-2.5 text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/15 transition-shadow"></textarea>
                        <p class="text-xs text-red-600 mt-1" x-text="errors.description?.[0]"></p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
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
    function cashPage() {
        return {
            modalOpen: false,
            editing: null,
            saving: false,
            errors: {},
            flash: null,
            flashType: 'success',
            form: { name: '', type: 'cash', account_number: '', initial_balance: 0, description: '', is_active: true },

            openCreate() {
                this.editing = null;
                this.form = { name: '', type: 'cash', account_number: '', initial_balance: 0, description: '', is_active: true };
                this.errors = {};
                this.modalOpen = true;
            },

            openEdit(cash) {
                this.editing = cash;
                this.form = {
                    name: cash.name,
                    type: cash.type,
                    account_number: cash.account_number,
                    initial_balance: cash.initial_balance,
                    description: cash.description,
                    is_active: !!cash.is_active,
                };
                this.errors = {};
                this.modalOpen = true;
            },

            async submit() {
                this.saving = true;
                this.errors = {};

                const url = this.editing
                    ? `{{ url('cashes') }}/${this.editing.id}`
                    : `{{ route('cashes.store') }}`;
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
                    if (!this.errors || Object.keys(this.errors).length === 0) {
                        this.flashType = 'error';
                        this.flash = data.message || 'Terjadi kesalahan.';
                    }
                    return;
                }

                this.flashType = 'error';
                this.flash = data.message || 'Terjadi kesalahan.';
            },

            async remove(id) {
                if (!confirm('Hapus kas ini?')) return;

                const { ok, data } = await window.ajaxSend(`{{ url('cashes') }}/${id}`, 'DELETE');

                this.flashType = ok ? 'success' : 'error';
                this.flash = data.message || (ok ? 'Berhasil dihapus.' : 'Gagal menghapus.');

                if (ok) setTimeout(() => window.location.reload(), 500);
            },
        };
    }
</script>
@endpush