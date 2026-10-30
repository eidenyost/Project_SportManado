<x-guest-layout>
    <div class="text-center mb-6">
        <span class="material-symbols-outlined text-5xl text-[#ff6b00]">shield_lock</span>
        <h2 class="mt-3 text-2xl font-bold text-white font-display">
            Konfirmasi Password
        </h2>
        <p class="mt-2 text-sm text-slate-400">
            Ini adalah area aman aplikasi. Harap konfirmasi password Anda sebelum melanjutkan.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Konfirmasi') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
