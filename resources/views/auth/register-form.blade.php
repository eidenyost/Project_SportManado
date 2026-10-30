<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Daftar - SportManado</title>
    @include('auth._auth-config')
</head>
<body class="min-h-screen flex flex-col md:flex-row bg-dark-bg text-white">
    {{-- Right Panel: Image + Brand --}}
    <div class="relative w-full md:w-[50%] min-h-[30vh] md:min-h-screen flex items-center justify-center overflow-hidden order-1 md:order-2 bg-dark-bg md:border-l-2 md:border-white/15">
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
    <div class="w-full md:w-[50%] flex items-start justify-center p-8 md:p-12 lg:p-16 order-2 md:order-1 border border-slate-800/50 overflow-y-auto">
        <div class="w-full max-w-[480px] space-y-6 py-4">

            {{-- Header --}}
            <div>
                <h2 class="text-2xl font-bold font-display text-accent">Daftar Akun</h2>
                <p class="text-sm text-slate-400 mt-1">
                    Mendaftar sebagai <strong class="text-accent font-bold">{{ $role === 'organizer' ? 'Penyelenggara/EO' : 'Peserta' }}</strong>
                </p>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-4" enctype="multipart/form-data" id="registerForm">
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">

                {{-- Nama --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="name">
                        Nama Lengkap
                    </label>
                    <input class="w-full px-4 py-2.5 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white placeholder-slate-600"
                           id="name"
                           type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Nama Anda"
                           required
                           autofocus
                           autocomplete="name" />
                    @error('name')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="email">
                        Email
                    </label>
                    <input class="w-full px-4 py-2.5 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white placeholder-slate-600"
                           id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="nama@email.com"
                           required
                           autocomplete="username" />
                    @error('email')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <input class="w-full px-4 py-2.5 pr-10 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white"
                               id="password"
                               type="password"
                               name="password"
                               required
                               autocomplete="new-password" />
                        <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" onclick="togglePassword('password', 'passwordToggleIcon')" type="button">
                            <span class="material-symbols-outlined text-[18px]" id="passwordToggleIcon">visibility</span>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="password_confirmation">
                        Konfirmasi Password
                    </label>
                    <div class="relative">
                        <input class="w-full px-4 py-2.5 pr-10 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white"
                               id="password_confirmation"
                               type="password"
                               name="password_confirmation"
                               required
                               autocomplete="new-password" />
                        <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors" onclick="togglePassword('password_confirmation', 'confirmToggleIcon')" type="button">
                            <span class="material-symbols-outlined text-[18px]" id="confirmToggleIcon">visibility</span>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Organizer Fields --}}
                @if($role === 'organizer')
                    {{-- Organization Name --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="organization_name">
                            Nama Komunitas/Organisasi <span class="text-error">*</span>
                        </label>
                        <input class="w-full px-4 py-2.5 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white placeholder-slate-600"
                               id="organization_name"
                               type="text"
                               name="organization_name"
                               value="{{ old('organization_name') }}"
                               placeholder="mis. Kawanua Sports Club"
                               required />
                        @error('organization_name')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="phone">
                            Nomor WhatsApp Aktif <span class="text-error">*</span>
                        </label>
                        <input class="w-full px-4 py-2.5 bg-dark-bg rounded-xl border border-slate-800 focus:border-accent focus:ring-1 focus:ring-accent/20 transition-all outline-none text-sm text-white placeholder-slate-600"
                               id="phone"
                               type="text"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="mis. 081234567890"
                               required />
                        @error('phone')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Logo (Opsional) --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="logo">
                            Logo Komunitas/Organisasi <span class="text-slate-500 font-normal">(Opsional)</span>
                        </label>
                        <input type="file" name="logo" id="logo" accept="image/*"
                               class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 file:cursor-pointer bg-dark-bg border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition" />
                        @error('logo')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- KTP --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="ktp">
                            Foto KTP Asli <span class="text-error">*</span>
                        </label>
                        <input type="file" name="ktp" id="ktp" accept="image/*"
class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 file:cursor-pointer bg-dark-bg border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition"
                                required />
                        @error('ktp')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Selfie --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="selfie">
                            Swafoto dengan KTP <span class="text-error">*</span>
                        </label>
                        <input type="file" name="selfie" id="selfie" accept="image/*"
class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 file:cursor-pointer bg-dark-bg border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition"
                                required />
                        @error('selfie')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300" for="description">
                            Deskripsi Singkat Komunitas/Organisasi <span class="text-slate-500 font-normal">(Opsional)</span>
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Jelaskan secara singkat latar belakang atau kegiatan utama komunitas/organisasi Anda..."
                                   class="w-full px-4 py-2.5 bg-dark-bg border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-accent/20 focus:border-accent transition"></textarea>
                        @error('description')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Submit --}}
                <button class="w-full bg-accent hover:bg-accent-hover text-white py-2.5 px-6 rounded-xl font-display font-bold text-sm tracking-wider transition-colors mt-6" type="submit">
                    Daftar Sekarang
                </button>
            </form>

            {{-- Footer --}}
            <p class="text-sm text-slate-400 text-center pt-2">
                Sudah punya akun?
                <a class="text-accent hover:text-accent-hover font-bold hover:underline transition-colors" href="{{ route('login') }}">Masuk</a>
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

        document.getElementById('registerForm')?.addEventListener('submit', (e) => {
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
