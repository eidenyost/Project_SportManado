<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - SportManado</title>
    @include('auth._auth-config')
</head>
<body class="min-h-screen flex flex-col md:flex-row bg-dark-bg text-white">
    {{-- Right Panel: Image + Brand --}}
    <div class="relative w-full md:w-[65%] min-h-[30vh] md:min-h-screen flex items-center justify-center overflow-hidden order-1 md:order-2 bg-dark-bg md:border-l-2 md:border-white/15">
        <img src="{{ asset('images/manado-city.jpg') }}"
             class="absolute inset-0 w-full h-full object-cover blur-sm opacity-80 scale-105"
             alt="Kota Manado">
        <div class="absolute inset-0 bg-gradient-to-r from-black/10 via-transparent to-transparent"></div>
        <div class="relative z-10 text-center space-y-4 px-6">
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
                <h2 class="text-2xl font-bold font-display text-accent">Masuk</h2>
                <p class="text-sm text-slate-400 mt-1">Masuk ke akun SportManado Anda</p>
            </div>

            {{-- Session Status --}}
            @if(session('status'))
                <div class="p-3 rounded-lg bg-success-bg border border-success-border text-success-DEFAULT text-sm font-semibold">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('login') }}" method="POST" class="space-y-5" id="loginForm">
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
                           required />
                    @error('email')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="password">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-accent hover:text-accent-hover transition-colors" href="{{ route('password.request') }}">Lupa?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <input class="w-full px-4 py-2.5 pr-10 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white"
                               id="password"
                               name="password"
                               type="password"
                               required />
                        <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" onclick="togglePassword('password', 'passwordToggleIcon')" type="button">
                            <span class="material-symbols-outlined text-[18px]" id="passwordToggleIcon">visibility</span>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" id="remember"
                           class="w-4 h-4 rounded border-slate-700 bg-dark-bg text-accent focus:ring-accent/20 cursor-pointer">
                    <span class="text-sm text-slate-400 select-none">Ingat saya</span>
                </label>

                <button class="w-full bg-accent hover:bg-accent-hover text-white py-2.5 px-6 rounded-xl font-display font-bold text-sm tracking-wider transition-colors" type="submit">
                    Masuk
                </button>
            </form>

            <p class="text-sm text-slate-400 text-center">
                Belum punya akun?
                <a class="text-accent hover:text-accent-hover font-bold hover:underline transition-colors" href="{{ route('register') }}">Daftar</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', (e) => {
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
