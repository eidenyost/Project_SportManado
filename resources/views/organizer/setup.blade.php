@extends('layouts.app')

@section('title', 'Pendaftaran Penyelenggara/EO – SportManado')

@section('content')
<div class="bg-[#080c14] min-h-screen py-12 flex flex-col justify-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 w-full">

        <!-- Back to Home -->
        <div class="mb-6">
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-[#ff6b00] transition-colors group font-semibold">
                <span class="material-symbols-outlined text-lg group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
                Kembali ke Beranda
            </a>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <span class="inline-block px-4 py-1.5 rounded-full text-xs font-bold bg-[#ff6b00]/10 border border-[#ff6b00]/25 text-[#ff6b00] mb-3">
                🎪 Langkah Awal Penyelenggara/EO
            </span>
            <h1 class="text-3xl sm:text-4xl font-display font-extrabold text-white">Daftarkan <span class="text-[#ff6b00]">Komunitas Olahraga</span></h1>
            <p class="text-sm text-slate-400 mt-2 max-w-lg mx-auto leading-relaxed">
                Lengkapi profil organisasi atau klub Anda untuk mulai mempublikasikan event olahraga komunitas, mengelola pendaftaran peserta, dan memvalidasi e-tiket QR-Code secara gratis!
            </p>
        </div>

        <!-- Rejection Alert -->
        @if(isset($organizer) && $organizer->status === 'rejected')
        <div class="mb-6 p-5 rounded-2xl bg-red-950/20 border border-red-900/40 text-red-200">
            <div class="flex gap-3">
                <span class="material-symbols-outlined text-red-500">warning</span>
                <div>
                    <h4 class="font-bold text-sm text-red-400">Pendaftaran Sebelumnya Ditolak</h4>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        Alasan penolakan: <strong class="text-white">"{{ $organizer->admin_notes }}"</strong>.
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        Silakan perbaiki data atau unggah ulang foto KTP / Swafoto yang benar di bawah ini.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Registration Card -->
        <div class="bg-[#0d1527] rounded-2xl border border-slate-800/80 overflow-hidden shadow-2xl relative">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#ff6b00] to-orange-600"></div>

            <form action="{{ route('organizer.store-setup') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                @csrf

                <!-- Organization Name -->
                <div class="space-y-2">
                    <label for="organization_name" class="block text-sm font-bold text-slate-300">Nama Organisasi / Komunitas / Klub <span class="text-red-500">*</span></label>
                    <div class="relative rounded-xl shadow-sm">
                        <input type="text" name="organization_name" id="organization_name" 
                               value="{{ old('organization_name', $organizer->organization_name ?? '') }}"
                               placeholder="mis. Kawanua Sports Club, GOR Sario Basketball"
                               class="w-full px-4 py-3 bg-[#080c14] border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#ff6b00]/30 focus:border-[#ff6b00] transition @error('organization_name') border-red-500 @enderror"
                               required>
                    </div>
                    @error('organization_name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Phone (WhatsApp) -->
                    <div class="space-y-2">
                        <label for="phone" class="block text-sm font-bold text-slate-300">Nomor WhatsApp Aktif <span class="text-red-500">*</span></label>
                        <input type="text" name="phone" id="phone" 
                               value="{{ old('phone', $organizer->phone ?? '') }}"
                               placeholder="mis. 081234567890"
                               class="w-full px-4 py-3 bg-[#080c14] border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#ff6b00]/30 focus:border-[#ff6b00] transition @error('phone') border-red-500 @enderror"
                               required>
                        <p class="text-[10px] text-slate-500">Format nomor WhatsApp Indonesia diawali 08 / 628</p>
                        @error('phone')
                            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Logo -->
                    <div class="space-y-2">
                        <label for="logo" class="block text-sm font-bold text-slate-300">Logo Komunitas <span class="text-slate-500 font-normal">(Opsional)</span></label>
                        <input type="file" name="logo" id="logo" accept="image/*"
                               class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ff6b00]/10 file:text-[#ff6b00] hover:file:bg-[#ff6b00]/20 file:cursor-pointer bg-[#080c14] border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition @error('logo') border-red-500 @enderror">
                        @error('logo')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Verification Documents -->
                <div class="bg-[#080c14]/50 border border-slate-800 p-5 rounded-xl space-y-4">
                    <h3 class="text-xs font-bold text-white ">Dokumen Verifikasi Identitas</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- KTP File Upload -->
                        <div class="space-y-2">
                            <label for="ktp" class="block text-sm font-bold text-slate-300">Foto KTP <span class="text-red-500">*</span></label>
                            <input type="file" name="ktp" id="ktp" accept="image/*"
                                   class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ff6b00]/10 file:text-[#ff6b00] hover:file:bg-[#ff6b00]/20 file:cursor-pointer bg-[#080c14] border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition @error('ktp') border-red-500 @enderror"
                                   @if(!isset($organizer) || !$organizer->ktp_path) required @endif>
                            <p class="text-[10px] text-slate-500">Upload foto KTP asli secara jelas (maks 2MB, JPG/PNG)</p>
                            @error('ktp')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Selfie File Upload -->
                        <div class="space-y-2">
                            <label for="selfie" class="block text-sm font-bold text-slate-300">Swafoto (Selfie) dengan KTP <span class="text-red-500">*</span></label>
                            <input type="file" name="selfie" id="selfie" accept="image/*"
                                   class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ff6b00]/10 file:text-[#ff6b00] hover:file:bg-[#ff6b00]/20 file:cursor-pointer bg-[#080c14] border border-slate-800 rounded-xl py-1.5 px-3 focus:outline-none transition @error('selfie') border-red-500 @enderror"
                                   @if(!isset($organizer) || !$organizer->selfie_path) required @endif>
                            <p class="text-[10px] text-slate-500">Pegang KTP dekat wajah, pastikan wajah & KTP terbaca (maks 2MB)</p>
                            @error('selfie')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <label for="description" class="block text-sm font-bold text-slate-300">Deskripsi Singkat Komunitas <span class="text-slate-500 font-normal">(Opsional)</span></label>
                    <textarea name="description" id="description" rows="4"
                              placeholder="Jelaskan secara singkat latar belakang, jenis cabang olahraga yang difokuskan, atau agenda rutin komunitas Anda..."
                              class="w-full px-4 py-3 bg-[#080c14] border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#ff6b00]/30 focus:border-[#ff6b00] transition @error('description') border-red-500 @enderror">{{ old('description', $organizer->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-[#ff6b00] hover:bg-[#e05e00] text-white py-3.5 rounded-xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        Kirim untuk Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
