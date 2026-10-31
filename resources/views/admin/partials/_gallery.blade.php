<div class="space-y-6 animate-in fade-in duration-300">

    <!-- Upload Form -->
    <div class="bg-dark-card rounded-xl p-6 border border-slate-800/40">
        <h3 class="text-base font-bold text-white flex items-center gap-2 mb-5">
            <svg class="w-5 h-5 text-[#ff6b00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Foto Baru
        </h3>

        <form action="{{ route('admin.gallery.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
            @csrf

            <div class="flex-1 w-full sm:max-w-md">
                <label class="text-xs font-bold text-slate-300 mb-1.5 block">File Gambar <span class="text-red-400">*</span></label>
                <input type="file" name="image" required accept="image/jpeg,image/png,image/jpg,image/webp"
                       class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ff6b00] file:text-white hover:file:bg-[#e05e00] file:cursor-pointer file:transition-colors cursor-pointer">
                <p class="text-[10px] text-slate-500 mt-1">Maks 2MB. Format: JPG, PNG, WebP</p>
            </div>

            <button type="submit" class="shrink-0 py-2.5 px-6 bg-[#ff6b00] hover:bg-[#e05e00] text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Unggah
            </button>
        </form>
    </div>

    <!-- Photo Grid -->
    <div class="bg-dark-card rounded-xl p-6 border border-slate-800/40">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff6b00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                Semua Foto
                @if($galleryPhotos->isNotEmpty())
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-slate-700 text-slate-300 rounded-full">{{ $galleryPhotos->count() }}</span>
                @endif
            </h3>
        </div>

        @if($galleryPhotos->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                <p class="text-sm text-slate-400 font-medium">Belum ada foto di galeri.</p>
                <p class="text-xs text-slate-500 mt-1">Upload foto menggunakan form di atas.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                 x-data="{ lightbox: null }"
                 @keydown.window.escape="lightbox = null">
                @foreach($galleryPhotos as $photo)
                    <div class="group relative bg-[#080c14] rounded-xl overflow-hidden border border-slate-800 cursor-pointer"
                         @click="lightbox = '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}'">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}"
                                 alt="Foto galeri"
                                 class="w-full h-full object-cover">
                        </div>

                        <form action="{{ route('admin.gallery.destroy', $photo) }}" method="POST"
                              onsubmit="return confirm('Hapus foto ini dari galeri?')"
                              class="absolute top-2 right-2 z-10"
                              @click.stop>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 bg-red-500/80 hover:bg-red-600 rounded-lg text-white transition-colors" title="Hapus">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach

                <!-- Lightbox -->
                <template x-teleport="body">
                    <div x-show="lightbox"
                         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                         @click="lightbox = null">
                        <button class="absolute top-5 right-5 text-white/60 hover:text-white text-3xl leading-none" @click="lightbox = null">&times;</button>
                        <div class="max-w-5xl max-h-[90vh]" @click.stop>
                            <img :src="lightbox" alt="Foto galeri" class="max-w-full max-h-[90vh] object-contain rounded-lg">
                        </div>
                    </div>
                </template>
            </div>
        @endif

        @if(method_exists($galleryPhotos, 'links'))
        <div class="mt-4 px-4 py-2.5 border-t border-slate-800/60 bg-dark-card rounded-xl">
            {{ $galleryPhotos->links() }}
        </div>
        @endif
    </div>
</div>
