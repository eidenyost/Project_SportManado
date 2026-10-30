<x-guest-layout>
    <header class="mb-8 text-center flex flex-col items-center">
        <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-xl bg-[#ff6b00]/10 border border-[#ff6b00]/30 text-[#ff6b00] shadow-sm">
            @if($organizer->status === 'rejected')
                <span class="material-symbols-outlined text-[32px]">cancel</span>
            @else
                <span class="material-symbols-outlined text-[32px]">hourglass_empty</span>
            @endif
        </div>
        <h1 class="text-headline-md font-headline-md text-white tracking-tight">
            {{ $organizer->status === 'rejected' ? 'Pendaftaran Penyelenggara/EO Ditolak' : 'Verifikasi Akun Penyelenggara/EO' }}
        </h1>
        <p class="text-body-md font-body-md text-slate-400 mt-2">
            {{ $organizer->status === 'rejected' ? 'Profil Penyelenggara/EO Anda tidak disetujui.' : 'Profil Penyelenggara/EO Anda sedang ditinjau.' }}
        </p>
    </header>

    <div class="space-y-5">
        @if($organizer->status === 'rejected')
            <div class="bg-red-950/20 border border-red-900/40 text-red-400 p-4 rounded-xl text-sm text-left">
                <strong class="block mb-1 font-bold text-red-300">Alasan Penolakan:</strong> 
                {{ $organizer->admin_notes ?? 'Dokumen atau informasi profil tidak valid/lengkap.' }}
            </div>
            
            <a href="{{ route('organizer.setup') }}" class="w-full text-center block bg-[#ff6b00] hover:bg-[#e05e00] text-white py-3 px-6 rounded-xl font-bold shadow-lg transition-all">
                Daftar Ulang
            </a>
        @else
            <div class="bg-[#080c14] border border-slate-800/80 p-4 rounded-xl text-sm text-left text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-800/50">
                    <span class="text-slate-500">Nama Organisasi:</span>
                    <span class="font-semibold text-white">{{ $organizer->organization_name }}</span>
                </div>
                <div class="flex justify-between py-1 mt-1">
                    <span class="text-slate-500">Status:</span>
                    <span class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Menunggu Verifikasi
                    </span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">logout</span>
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
