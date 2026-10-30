<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Atur Ulang Password - SportManado</title>
    @include('auth._auth-config')
</head>
<body class="sports-gradient min-h-screen flex items-center justify-center p-4 md:p-8 overflow-x-hidden text-white">
<main class="w-full max-w-[440px]">
    <div class="auth-card rounded-3xl p-8 md:p-10 flex flex-col items-center auth-stagger">
        <header class="mb-8 text-center">
            <span class="material-symbols-outlined text-5xl text-accent">lock_reset</span>
            <h2 class="mt-3 text-2xl font-bold text-white font-display">
                Atur Ulang Password
            </h2>
            <p class="mt-2 text-body-md font-body-md text-slate-400">
                Masukkan password baru Anda untuk mengamankan kembali akun Anda.
            </p>
        </header>

        <form method="POST" action="{{ route('password.store') }}" class="w-full space-y-5" id="resetForm">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="space-y-2">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2" for="email">
                    Email
                </label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-accent transition-colors">mail</span>
                    <input class="w-full pl-10 pr-4 py-3 bg-dark-bg rounded-xl border border-slate-800/80 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-body-md font-body-md text-white placeholder-slate-600"
                           id="email"
                           name="email"
                           type="email"
                           value="{{ old('email', $request->email) }}"
                           required
                           autofocus
                           autocomplete="username" />
                </div>
                @error('email')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2" for="password">
                    Password Baru
                </label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-accent transition-colors">lock</span>
                    <input class="w-full pl-10 pr-12 py-3 bg-dark-bg rounded-xl border border-slate-800/80 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-body-md font-body-md text-white"
                           id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="new-password" />
                    <button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" onclick="togglePassword('password', 'passwordToggleIcon')" type="button">
                        <span class="material-symbols-outlined" id="passwordToggleIcon">visibility</span>
                    </button>
                </div>
                @error('password')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2" for="password_confirmation">
                    Konfirmasi Password Baru
                </label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-accent transition-colors">lock</span>
                    <input class="w-full pl-10 pr-12 py-3 bg-dark-bg rounded-xl border border-slate-800/80 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-body-md font-body-md text-white"
                           id="password_confirmation"
                           type="password"
                           name="password_confirmation"
                           required
                           autocomplete="new-password" />
                    <button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" onclick="togglePassword('password_confirmation', 'confirmToggleIcon')" type="button">
                        <span class="material-symbols-outlined" id="confirmToggleIcon">visibility</span>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button class="w-full bg-accent hover:bg-accent-hover text-white py-3.5 px-6 rounded-xl font-display text-label-md tracking-wider flex items-center justify-center gap-2 transition-colors font-bold mt-2" type="submit" id="submitBtn">
                Atur Ulang Password
                <span class="material-symbols-outlined text-[18px]">lock_reset</span>
            </button>
        </form>

        <footer class="mt-8 text-center space-y-4 w-full">
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-800/80"></div>
                <span class="flex-shrink mx-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-widest">Atau</span>
                <div class="flex-grow border-t border-slate-800/80"></div>
            </div>
            <p class="text-body-md font-body-md text-slate-400">
                <a class="text-accent hover:text-accent-hover font-bold hover:underline transition-colors inline-flex items-center gap-1" href="{{ route('login') }}">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Kembali ke Login
                </a>
            </p>
        </footer>
    </div>
</main>

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

    document.getElementById('resetForm')?.addEventListener('submit', (e) => {
        const btn = e.target.querySelector('button[type="submit"]');
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Memproses...';
        btn.disabled = true;
    });
</script>
</body>
</html>
