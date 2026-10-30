@extends('layouts.app')

@section('title', 'Galeri')

@section('content')
<div class="py-12 bg-dark-bg min-h-screen text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-12">
            <p class="text-accent font-semibold text-sm tracking-wide">GALERI</p>
            <h1 class="text-2xl md:text-3xl font-bold mt-1">Dokumentasi event</h1>
            <p class="text-slate-500 text-sm mt-1">Momen olahraga komunitas di Manado.</p>
        </div>

        @if($photos->isEmpty())
            <p class="text-center text-slate-500 py-20">Belum ada dokumentasi foto.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                 x-data="{ lightbox: null }"
                 @keydown.window.escape="lightbox = null">
                @foreach($photos as $photo)
                    <div class="bg-[#0d1527] rounded-xl overflow-hidden border border-slate-800/40 cursor-pointer group"
                         @click="lightbox = '{{ asset('storage/' . $photo->image_path) }}'">
                        <div class="aspect-[4/3] overflow-hidden bg-slate-900">
                            <img src="{{ asset('storage/' . $photo->image_path) }}"
                                 alt="Foto galeri"
                                 class="w-full h-full object-cover">
                        </div>
                    </div>
                @endforeach

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
    </div>
</div>
@endsection
