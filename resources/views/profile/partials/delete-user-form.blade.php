<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-red-400 font-display">
            Hapus Akun secara Permanen
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            Penghapusan hanya tersedia untuk akun tanpa riwayat tiket, pembayaran, atau Penyelenggara/EO. Akun yang memiliki riwayat dilindungi agar data kegiatan tetap tersimpan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun Saya</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-white font-display">
                Apakah Anda yakin ingin menghapus akun?
            </h2>

            <p class="mt-2 text-sm text-slate-400">
                Setelah akun Anda dihapus, semua data akan dihapus secara permanen. Silakan masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Masukkan Password Anda"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun secara Permanen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
