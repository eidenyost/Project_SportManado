<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Daftar Akun - SportManado</title>
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

    {{-- Left Panel: List --}}
    <div class="w-full md:w-[35%] flex items-center justify-center p-8 md:p-12 lg:p-16 order-2 md:order-1 border border-slate-800/50">
        <div class="w-full max-w-[400px] space-y-6">

            {{-- Header --}}
            <div>
                <h2 class="text-2xl font-bold font-display text-accent">Daftar</h2>
                <p class="text-sm text-slate-400 mt-1">Pilih peran Anda</p>
            </div>

            {{-- Role Grid --}}
            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('register.form', ['role' => 'user']) }}"
                   class="group p-4 rounded-xl border-2 border-accent/50 hover:border-accent transition-colors">
                    <span class="text-sm font-bold font-display text-white group-hover:text-accent transition-colors">Peserta</span>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Cari event, daftarkan diri, dan dapatkan e-ticketing berupa QR code.</p>
                </a>

                <a href="{{ route('register.form', ['role' => 'organizer']) }}"
                   class="group p-4 rounded-xl border-2 border-accent/50 hover:border-accent transition-colors">
                    <span class="text-sm font-bold font-display text-white group-hover:text-accent transition-colors">Penyelenggara/EO</span>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Buat dan kelola event serta e-ticketing QR code.</p>
                    <p class="text-xs font-bold text-slate-400 mt-1.5 leading-relaxed">Proses pendaftaran Penyelenggara/EO memerlukan persetujuan Admin.</p>
                </a>
            </div>

            {{-- Footer --}}
            <p class="text-sm text-slate-400 text-center pt-1">
                Sudah punya akun?
                <a class="text-accent hover:text-accent-hover font-bold hover:underline transition-colors" href="{{ route('login') }}">Masuk</a>
            </p>
        </div>
    </div>
</body>
</html>
