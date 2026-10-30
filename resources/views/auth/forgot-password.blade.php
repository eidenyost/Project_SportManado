<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lupa Password - SportManado</title>
    @include('auth._auth-config')
</head>
<body class="min-h-screen flex flex-col md:flex-row bg-dark-bg text-white">
    {{-- Right Panel: Image + Brand --}}
    <div class="relative w-full md:w-[65%] min-h-[30vh] md:min-h-screen flex items-center justify-center overflow-hidden order-1 md:order-2 bg-dark-bg md:border-l-2 md:border-white/15">
        <img src="{{ asset('images/manado-city.jpg') }}"
             class="absolute inset-0 w-full h-full object-cover blur-sm opacity-80 scale-105"
             alt="Kota Manado">
        <div class="absolute inset-0 bg-gradient-to-r from-black/10 via-transparent to-transparent"></div>
        <div class="relative z-10 text-center px-6">
            <h1 class="text-5xl sm:text-6xl lg:text-7xl xl:text-8xl font-black font-display tracking-tight leading-none">
                <span class="text-accent">SPORT</span><br>
                <span class="text-white">MANADO</span>
            </h1>
        </div>
    </div>

    {{-- Left Panel: Form --}}
    <div class="w-full md:w-[35%] flex items-center justify-center p-8 md:p-12 lg:p-16 order-2 md:order-1 border border-slate-800/50">
        <div class="w-full max-w-[400px] space-y-6">

            {{-- Header --}}
            <div>
                <h2 class="text-2xl font-bold font-display text-accent">Lupa Password?</h2>
                <p class="text-sm text-slate-400 mt-1">Masukkan email Anda untuk mereset password.</p>
            </div>

            {{-- Session Status --}}
            @if(session('status'))
                <div class="p-3 rounded-lg bg-success-bg border border-success-border text-success-DEFAULT text-sm font-semibold">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5" id="forgotForm">
                @csrf

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="email">
                        Email
                    </label>
                    <input class="w-full px-4 py-2.5 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white placeholder-slate-600"
                           id="email"
                           name="email"
                           type="email"
                           value="{{ old('email') }}"
                           placeholder="nama@email.com"
                           required
                           autofocus />
                    @error('email')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button class="w-full bg-accent hover:bg-accent-hover text-white py-2.5 px-6 rounded-xl font-display font-bold text-sm tracking-wider transition-colors" type="submit">
                    Kirim Tautan Reset Password
                </button>
            </form>

            <p class="text-sm text-slate-400 text-center">
                <a class="text-accent hover:text-accent-hover font-bold hover:underline transition-colors inline-flex items-center gap-1" href="{{ route('login') }}">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Kembali ke Login
                </a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', (e) => {
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
