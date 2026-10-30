<x-guest-layout>
    <div class="text-center mb-6">
        <span class="material-symbols-outlined text-5xl text-[#ff6b00]">mark_email_read</span>
        <h2 class="mt-3 text-2xl font-bold text-white font-display">
            Verifikasi Email
        </h2>
        <p class="mt-2 text-sm text-slate-400">
            Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan. Jika tidak menerimanya, kami akan mengirimkan ulang.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 font-medium text-sm text-emerald-400 bg-emerald-950/40 rounded-xl p-4 border border-emerald-500/30">
            {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.') }}
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                {{ __('Kirim Ulang Email Verifikasi') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center mt-2">
            @csrf
            <button type="submit" class="underline text-sm text-slate-400 hover:text-[#ff6b00] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#ff6b00]">
                {{ __('Keluar (Log Out)') }}
            </button>
        </form>
    </div>
</x-guest-layout>
