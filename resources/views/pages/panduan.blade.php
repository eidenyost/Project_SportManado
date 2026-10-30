@extends('layouts.app')

@section('title', 'Panduan')

@section('content')
<div class="py-12 bg-dark-bg min-h-screen text-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-14">
            <p class="text-accent font-semibold text-sm tracking-wide">PANDUAN</p>
            <h1 class="text-2xl md:text-3xl font-bold mt-1">Cara mengikuti event</h1>
            <p class="text-slate-500 text-sm mt-1">Tiga langkah mudah dari pendaftaran hingga check-in.</p>
        </div>

        <div class="relative">

            <div class="relative flex gap-6 pb-12">
                <div class="flex flex-col items-center">
                    <div class="w-3 h-3 rounded-full bg-accent ring-[5px] ring-dark-bg flex-shrink-0"></div>
                    <div class="w-px flex-1 bg-slate-800/60 mt-1"></div>
                </div>
                <div class="flex-1 pb-2">
                    <h2 class="text-base font-bold text-white">Cari & Daftar Event</h2>
                    <p class="text-sm text-slate-400 mt-1 leading-relaxed">
                        Telusuri event olahraga aktif di Manado. Pilih event, klik Daftar Sekarang, lalu isi data diri peserta.
                    </p>
                </div>
            </div>

            <div class="relative flex gap-6 pb-12">
                <div class="flex flex-col items-center">
                    <div class="w-3 h-3 rounded-full bg-accent ring-[5px] ring-dark-bg flex-shrink-0"></div>
                    <div class="w-px flex-1 bg-slate-800/60 mt-1"></div>
                </div>
                <div class="flex-1 pb-2">
                    <h2 class="text-base font-bold text-white">Bayar Tiket</h2>
                    <p class="text-sm text-slate-400 mt-1 leading-relaxed">
                        Pembayaran otomatis via Midtrans. Pilih metode pembayaran. Tidak perlu upload bukti transfer.
                    </p>
                </div>
            </div>

            <div class="relative flex gap-6">
                <div class="flex flex-col items-center">
                    <div class="w-3 h-3 rounded-full bg-accent ring-[5px] ring-dark-bg flex-shrink-0"></div>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-bold text-white">Scan QR di Lokasi</h2>
                    <p class="text-sm text-slate-400 mt-1 leading-relaxed">
                        Tampilkan QR Code dari menu Tiket ke panitia di pintu masuk. Validasi instan, langsung masuk venue.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
