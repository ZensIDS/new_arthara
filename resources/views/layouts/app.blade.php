<!DOCTYPE html>
<html lang="id" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — BerlianzStore</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Space Grotesk"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        ink: '#111214',
                        paper: '#FFFFFF',
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(17,18,20,0.04), 0 8px 24px -12px rgba(17,18,20,0.10)',
                        glow: '0 10px 30px -10px rgba(245,158,11,0.45)',
                        panel: '0 24px 60px -20px rgba(17,18,20,0.35)',
                    },
                    keyframes: {
                        'fade-slide': {
                            '0%':   { opacity: 0, transform: 'translateY(6px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' },
                        },
                    },
                    animation: {
                        'fade-slide': 'fade-slide .25s ease-out',
                    },
                },
            },
        }
    </script>

    {{-- jQuery + Select2, dimuat SEBELUM Alpine supaya select2 siap saat x-init jalan --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        html { scrollbar-gutter: stable; }
        body {
            background-color: #FAFAF8;
            background-image:
                radial-gradient(1100px 500px at 100% -10%, rgba(245,158,11,0.08), transparent 60%),
                radial-gradient(800px 400px at -10% 10%, rgba(17,18,20,0.04), transparent 55%);
            background-attachment: fixed;
        }

        /* Angka finansial rapi sejajar */
        .tnum { font-variant-numeric: tabular-nums; }

        /* Scrollbar halus untuk sidebar & tabel */
        .scroll-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 999px; }
        .scroll-thin-light::-webkit-scrollbar-thumb { background: rgba(17,18,20,0.15); border-radius: 999px; }

        /* ===== Select2 — retema supaya menyatu dengan desain BerlianzStore ===== */
        .select2-container { width: 100% !important; font-family: 'Inter', sans-serif; }
        .select2-container--default .select2-selection--single {
            height: 42px;
            display: flex;
            align-items: center;
            border-radius: 0.75rem;
            border: 1px solid rgba(17,18,20,0.12);
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.18);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 14px;
            padding-right: 28px;
            font-size: 0.875rem;
            color: #111214;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder { color: rgba(17,18,20,0.4); }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; right: 8px; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #F59E0B; color: #111214; }
        .select2-container--default .select2-results__option[aria-selected=true] { background-color: rgba(245,158,11,0.12); }
        .select2-dropdown { border-radius: 0.75rem; border-color: rgba(17,18,20,0.12); overflow: hidden; box-shadow: 0 20px 40px -16px rgba(17,18,20,0.25); }
        .select2-search--dropdown { padding: 8px; }
        .select2-search--dropdown .select2-search__field {
            border-radius: 0.5rem; border: 1px solid rgba(17,18,20,0.12); padding: 6px 10px; font-size: 0.875rem;
        }
        .select2-results__option { font-size: 0.875rem; padding: 8px 12px; }
    </style>

    <script>
        // Helper AJAX kecil dipakai semua modal CRUD (products, categories, dst).
        // Mengembalikan { ok, status, data } supaya gampang dicek di Alpine.
        window.ajaxSend = async function (url, method, payload) {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: payload ? JSON.stringify(payload) : undefined,
            });

            let data = {};
            try { data = await res.json(); } catch (e) { /* no body */ }

            return { ok: res.ok, status: res.status, data };
        };

        // Helper format Rupiah — dipakai di SEMUA input nominal (biaya, harga beli/jual, dst)
        // supaya user langsung lihat "1.000.000" saat mengetik, bukan angka mentah.
        window.formatRupiah = function (value) {
            if (value === null || value === undefined || value === '') return '';
            const digits = String(value).replace(/[^0-9]/g, '');
            if (!digits) return '';
            return new Intl.NumberFormat('id-ID').format(parseInt(digits, 10));
        };

        // Kebalikannya: dari string terformat balik ke angka mentah untuk dikirim ke server.
        window.parseRupiah = function (formatted) {
            const digits = String(formatted ?? '').replace(/[^0-9]/g, '');
            return digits ? parseInt(digits, 10) : '';
        };

        // Helper AJAX search untuk halaman listing (PO, SO, Expenses, Incomes, dst).
        // Query dikirim ke server (bukan filter di JS), di-debounce supaya tidak
        // nembak request tiap ketikan, dan request lama di-abort kalau ada request
        // baru menyusul (mencegah race condition/response basi menimpa yang baru).
        //
        // opts:
        // - inputEl       : elemen <input> pencarian
        // - containerEl   : elemen wrapper yang isinya (tabel + pagination) diganti via AJAX
        // - baseUrl       : URL index halaman ini (route(...).index)
        // - getExtraParams: fungsi opsional yang mengembalikan object query tambahan
        //                   (mis. start_date/end_date dari form filter tanggal)
        // - onSwap        : callback opsional dipanggil tiap kali containerEl diganti isinya
        // - delay         : debounce delay ms (default 400)
        window.initAjaxListSearch = function ({ inputEl, containerEl, baseUrl, getExtraParams, onSwap, delay = 400 }) {
            let debounceTimer = null;
            let controller = null;

            async function fetchAndSwap(url) {
                if (controller) controller.abort();
                controller = new AbortController();

                containerEl.classList.add('opacity-50', 'pointer-events-none');

                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal,
                    });
                    if (!res.ok) throw new Error('Request gagal');
                    const html = await res.text();

                    containerEl.innerHTML = html;
                    // Elemen baru hasil swap perlu diinisialisasi ulang oleh Alpine
                    // (misal tombol Edit/Hapus yang pakai @click di dalam tabel).
                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(containerEl);
                    }

                    window.history.replaceState({}, '', url);
                    if (onSwap) onSwap();
                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                } finally {
                    containerEl.classList.remove('opacity-50', 'pointer-events-none');
                }
            }

            function buildUrl(extraQuery = {}) {
                const params = new URLSearchParams(getExtraParams ? getExtraParams() : {});
                params.set('search', inputEl.value.trim());
                Object.entries(extraQuery).forEach(([k, v]) => params.set(k, v));
                return `${baseUrl}?${params.toString()}`;
            }

            inputEl.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchAndSwap(buildUrl()), delay);
            });

            // Klik link pagination di dalam containerEl (elemen .ajax-pagination)
            // ikut lewat AJAX juga, supaya tidak reload full page & tetap bawa
            // search + filter tanggal yang sedang aktif.
            containerEl.addEventListener('click', (e) => {
                const link = e.target.closest('.ajax-pagination a');
                if (!link) return;
                e.preventDefault();
                fetchAndSwap(link.href);
            });

            return { fetchAndSwap, buildUrl };
        };
    </script>

    @stack('styles')
</head>
<body class="h-full bg-paper text-ink font-sans antialiased">
    <div class="min-h-full lg:flex">

        {{-- Overlay untuk mobile saat sidebar terbuka --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
        ></div>

        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            @include('layouts.partials.topbar')

            <main class="flex-1 min-w-0 px-4 sm:px-6 lg:px-10 py-8 @container">
                <div class="max-w-6xl mx-auto animate-fade-slide">

                    @if (session('success'))
                        <div class="mb-6 rounded-xl border border-emerald-600/15 bg-emerald-50 text-emerald-900 text-sm px-4 py-3.5 flex items-center gap-3 shadow-card">
                            <span class="shrink-0 h-6 w-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-red-600/15 bg-red-50 text-red-900 text-sm px-4 py-3.5 shadow-card">
                            <p class="font-semibold mb-1 flex items-center gap-2">
                                <span class="shrink-0 h-5 w-5 rounded-full bg-red-600 text-white flex items-center justify-center text-[11px]">!</span>
                                Terjadi kesalahan
                            </p>
                            <ul class="list-disc list-inside space-y-0.5 pl-7">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')

                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
