@extends('layouts.app')

@section('title', 'Status Pengajuan Event – SportManado')

@section('content')
<span hidden data-refresh-on-return></span>
<div class="min-h-screen bg-[#080c14]">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-12">

        @if(!$registrationRequest)
        <div class="bg-[#0d1527] border border-[#1e293b] rounded-xl p-10 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-[#1e293b] flex items-center justify-center text-3xl">🏟️</div>
            <h2 class="text-xl font-extrabold text-white font-display mb-2">Belum Ada Pengajuan</h2>
            <p class="text-sm text-slate-400 mb-6">Anda belum mengajukan pendaftaran event. Isi formulir dan lampirkan dokumen yang diperlukan.</p>
            <a href="{{ route('event-registration.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white text-sm bg-[#ff6b00] hover:bg-[#e05e00] transition-all shadow">
                + Daftarkan Event Sekarang
            </a>
        </div>

        @elseif($registrationRequest->status === 'pending')
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-4xl">⏳</div>
            <h1 class="text-2xl font-extrabold text-white font-display mb-2">Pengajuan Sedang Diproses</h1>
            <p class="text-slate-400 text-sm max-w-md mx-auto">Admin sedang mereview dokumen Anda. Proses verifikasi berlangsung dalam <strong class="text-amber-400">1×24 jam</strong> hari kerja.</p>
        </div>

        <div class="bg-[#0d1527] border border-[#1e293b] rounded-xl p-6 mb-6">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-5">Tahapan Proses</p>

            <div class="flex items-start gap-4">
                <div class="w-9 h-9 rounded-full bg-emerald-500/10 border-2 border-emerald-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="pt-1.5">
                    <p class="text-sm font-bold text-emerald-400">Pengajuan Diterima</p>
                    <p class="text-xs text-slate-500">{{ $registrationRequest->created_at->translatedFormat('d F Y, H:i') }} WITA</p>
                </div>
            </div>
            <div class="w-[2px] h-10 bg-gradient-to-b from-emerald-500/50 to-transparent ml-[17px]"></div>

            <div class="flex items-start gap-4">
                <div class="w-9 h-9 rounded-full bg-amber-500/10 border-2 border-amber-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div class="pt-1.5">
                    <p class="text-sm font-bold text-amber-400">Verifikasi Dokumen oleh Admin</p>
                    <p class="text-xs text-slate-500">Admin sedang memeriksa Proposal & Surat Izin Anda...</p>
                </div>
            </div>
            <div class="w-[2px] h-10 bg-gradient-to-b from-amber-500/50 to-transparent ml-[17px]"></div>

            <div class="flex items-start gap-4">
                <div class="w-9 h-9 rounded-full bg-[#1e293b] border-2 border-slate-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="pt-1.5">
                    <p class="text-sm font-bold text-slate-500">Persetujuan & Aktivasi Event</p>
                    <p class="text-xs text-slate-600">Menunggu keputusan Admin...</p>
                </div>
            </div>
        </div>

        <div class="bg-[#0d1527] border border-[#1e293b] rounded-xl p-6 mb-6">
            <p class="text-xs font-bold text-[#ff6b00] uppercase tracking-wider mb-4">📋 Ringkasan Pengajuan Anda</p>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-start gap-4">
                    <span class="text-slate-400 flex-shrink-0 font-medium">Event</span>
                    <span class="text-white font-semibold text-right">{{ $registrationRequest->event_title }}</span>
                </div>
                <div class="flex justify-between items-center gap-4">
                    <span class="text-slate-400 font-medium">Komunitas / Penyelenggara/EO</span>
                    <span class="text-slate-300">{{ $registrationRequest->organization_name }}</span>
                </div>
                <div class="flex justify-between items-center gap-4">
                    <span class="text-slate-400 font-medium">Kategori</span>
                    <span class="text-slate-300">{{ $registrationRequest->category }}</span>
                </div>
                <div class="flex justify-between items-center gap-4">
                    <span class="text-slate-400 font-medium">Tanggal Event</span>
                    <span class="text-slate-300">{{ $registrationRequest->event_date->translatedFormat('d F Y, H:i') }}</span>
                </div>
                <div class="flex justify-between items-center gap-4 pt-2 border-t border-[#1e293b]">
                    <span class="text-slate-400 font-medium">Proposal</span>
                    <a href="{{ route('event-registration.documents', [$registrationRequest, 'type' => 'proposal']) }}" target="_blank"
                       class="text-[#ff6b00] hover:text-[#e05e00] text-xs font-bold flex items-center gap-1">
                        📥 Lihat File
                    </a>
                </div>
                <div class="flex justify-between items-center gap-4">
                    <span class="text-slate-400 font-medium">Surat Izin</span>
                    <a href="{{ route('event-registration.documents', [$registrationRequest, 'type' => 'permit']) }}" target="_blank"
                       class="text-[#ff6b00] hover:text-[#e05e00] text-xs font-bold flex items-center gap-1">
                        📥 Lihat File
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-xs text-slate-500">Setelah disetujui, akun Anda akan otomatis di-upgrade menjadi <strong class="text-white">Penyelenggara/EO</strong> dan Anda bisa langsung mengelola event dari menu <strong class="text-white">Management Event</strong>.</p>
        </div>

        @elseif($registrationRequest->status === 'rejected')
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-4xl">❌</div>
            <h1 class="text-2xl font-extrabold text-white font-display mb-2">Pengajuan Ditolak</h1>
            <p class="text-slate-400 text-sm max-w-md mx-auto">Maaf, pengajuan event Anda tidak dapat disetujui. Silakan baca catatan dari Admin dan coba lagi.</p>
        </div>

        @if($registrationRequest->admin_notes)
        <div class="bg-[#0d1527] border border-red-500/20 rounded-xl p-5 mb-6">
            <p class="text-xs font-bold text-red-400 uppercase tracking-wider mb-2">📋 Catatan dari Admin</p>
            <p class="text-sm text-red-300 leading-relaxed font-medium">{{ $registrationRequest->admin_notes }}</p>
        </div>
        @endif

        <div class="bg-[#0d1527] border border-[#1e293b] rounded-xl p-6 mb-6">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Detail Pengajuan yang Ditolak</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-slate-400">Event</span>
                    <span class="text-slate-500 font-semibold text-right line-through">{{ $registrationRequest->event_title }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-slate-400">Tanggal Pengajuan</span>
                    <span class="text-slate-300 font-medium">{{ $registrationRequest->created_at->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>

        <div class="text-center space-y-3">
            <a href="{{ route('event-registration.create') }}"
               class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-bold text-white text-sm bg-[#ff6b00] hover:bg-[#e05e00] transition-all shadow">
                🔄 Ajukan Pendaftaran Event Baru
            </a>
            <a href="{{ route('events.index') }}"
               class="w-full block text-center py-3 rounded-xl text-sm text-slate-400 hover:text-white border border-slate-700 hover:border-slate-500 transition-all">
                Kembali ke Jelajahi Event
            </a>
        </div>

        @elseif($registrationRequest->status === 'approved')
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-4xl">🎉</div>
            <h1 class="text-2xl font-extrabold text-white font-display mb-2">Pengajuan Disetujui!</h1>
            <p class="text-slate-400 text-sm max-w-md mx-auto">Selamat! Akun Anda kini telah di-upgrade menjadi Penyelenggara/EO. Kelola event Anda sekarang.</p>
        </div>
        <div class="text-center">
            <a href="{{ route('organizer.dashboard') }}"
               class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-bold text-white text-sm bg-emerald-600 hover:bg-emerald-700 transition-all shadow">
                🚀 Buka Dashboard Management Event
            </a>
        </div>
        @endif

    </div>
</div>
@endsection
