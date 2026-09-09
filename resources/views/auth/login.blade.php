<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — BerlianZStore</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Space Grotesk"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: { ink: '#111214' },
                },
            },
        }
    </script>
</head>
<body class="h-full bg-[#fafaf9] font-sans text-ink antialiased">
    <div class="relative min-h-full flex items-center justify-center overflow-hidden px-6 py-12">

        {{-- Aksen dekoratif tipis, tidak membagi layar — cuma nuansa di belakang card --}}
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 h-[420px] w-[420px] rounded-full bg-amber-400/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.04]"
                 style="background-image: radial-gradient(#111214 1px, transparent 1px); background-size: 22px 22px;"></div>
        </div>

        <div class="w-full max-w-sm">

            {{-- Brand mark --}}
            <div class="flex flex-col items-center text-center mb-8">
                {{--  <div class="h-11 w-11 rounded-xl bg-ink flex items-center justify-center mb-4 shadow-lg shadow-ink/10">
                    <span class="font-display font-bold text-amber-400 text-lg">B</span>
                </div>  --}}
                <div class="font-display font-semibold text-xl tracking-tight">
                    BerlianZ<span class="text-ink/40">Store</span>
                </div>
            </div>

            {{-- Card login --}}
            <div class="bg-white border border-ink/10 rounded-2xl shadow-card p-7 sm:p-8">
                <h1 class="font-display font-semibold text-xl mb-1">Masuk</h1>
                <p class="text-ink/50 text-sm mb-7">Masukkan username dan kata sandi akunmu.</p>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-900/10 bg-red-50 text-red-800 text-sm px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="username" class="block text-sm font-medium mb-1.5">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            class="w-full rounded-lg border border-ink/15 focus:border-ink focus:outline-none focus:ring-2 focus:ring-amber-400/30 px-3.5 py-2.5 text-sm transition-colors"
                            placeholder="cth. budi.admin"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium mb-1.5">Kata sandi</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full rounded-lg border border-ink/15 focus:border-ink focus:outline-none focus:ring-2 focus:ring-amber-400/30 px-3.5 py-2.5 text-sm transition-colors"
                            placeholder="••••••••"
                        >
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink/60">
                        <input type="checkbox" name="remember" class="rounded border-ink/30">
                        Ingat saya
                    </label>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-ink text-white py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors"
                    >
                        Masuk
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-ink/35 mt-6">&copy; {{ date('Y') }} BerlianZStore</p>
        </div>
    </div>
</body>
</html>
