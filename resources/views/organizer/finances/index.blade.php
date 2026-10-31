@extends('layouts.organizer')

@section('title', 'Keuangan – ' . $event->title)

@push('styles')
<style>
    tr.payment-row:hover td { background: rgba(255,107,0,.04); }
</style>
@endpush

@section('content')
<span hidden data-refresh-on-return></span>
<div class="max-w-6xl mx-auto space-y-5">

    <!-- Header -->
    <div>
        <h1 class="text-lg font-bold text-white">{{ $event->title }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $event->event_date->translatedFormat('l, d F Y · H:i') }} WITA &middot;
            {{ $event->location }}
        </p>
    </div>

    <!-- Tab Bar -->
    @include('organizer.events.tabs._tab_bar', ['event' => $event, 'active' => 'finance'])

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="banknote" class="w-4 h-4 text-emerald-400 mx-auto mb-1.5"></i>
            <p class="break-words text-base sm:text-xl font-extrabold text-white">Rp{{ number_format($grossRevenue, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Total Pemasukan</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="clock" class="w-4 h-4 text-amber-400 mx-auto mb-1.5"></i>
            <p class="break-words text-base sm:text-xl font-extrabold text-white">Rp{{ number_format($pendingPaymentTotal, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Belum Dibayar</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="landmark" class="w-4 h-4 text-emerald-400 mx-auto mb-1.5"></i>
            <p class="break-words text-base sm:text-xl font-extrabold text-white">Rp{{ number_format($withdrawn, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Sudah Dicairkan</p>
        </div>
        <div class="bg-dark-card border border-slate-800/40 rounded-xl p-4 text-center">
            <i data-lucide="wallet" class="w-4 h-4 text-[#ff6b00] mx-auto mb-1.5"></i>
            <p class="break-words text-base sm:text-xl font-extrabold text-white">Rp{{ number_format($availableBalance, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5 font-medium uppercase tracking-wider">Tersedia</p>
        </div>
    </div>

    <!-- Withdraw Button -->
    <div>
        <a href="{{ route('organizer.finances.withdraw.page', $event) }}"
           class="inline-flex px-4 py-2.5 bg-[#ff6b00] hover:bg-[#e05e00] text-white rounded-xl text-xs font-bold transition">
            Ajukan Penarikan Dana
        </a>
    </div>

    <p class="text-xs text-slate-400">Saldo tersedia = pemasukan dikurangi dana dicairkan dan pengajuan menunggu (Rp{{ number_format($pendingWithdrawal,0,',','.') }}). Persetujuan tidak mengurangi dana yang sama dua kali.</p>
    <x-search-form :action="route('organizer.finances.finance', $event)" placeholder="Cari nama peserta, email, atau kode tiket..." />
    <!-- Filter + Export -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div aria-label="Filter transaksi" class="flex min-w-0 max-w-full gap-2 overflow-x-auto pb-1">
            @foreach([
                '' => 'Semua',
                'confirmed' => 'Lunas',
                'pending' => 'Pending',
                'rejected' => 'Dibatalkan',
            ] as $f => $label)
            <a href="{{ route('organizer.finances.finance', array_merge(['event'=>$event], request()->except(['filter','page']), ['filter'=>$f])) }}"
               class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap px-3 py-2 text-xs font-bold rounded-lg transition-all
                   {{ request('filter', '') === $f
                       ? 'bg-[#ff6b00] text-white shadow-sm'
                       : 'text-slate-400 border border-slate-800 hover:bg-slate-800/40 hover:text-white' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
        <a href="{{ route('organizer.finances.export', array_merge(['event'=>$event],request()->except('page'))) }}"
           class="px-3 py-1.5 text-[10px] font-bold text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition flex-shrink-0">
            Export CSV
        </a>
    </div>

    <!-- Pemasukan Tiket -->
    <div class="bg-dark-card border border-slate-800/40 rounded-xl overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-800/60 flex items-center justify-between">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Pemasukan Tiket</h3>
        </div>

        @if($payments->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="banknote" class="w-8 h-8 text-slate-600 mx-auto mb-3"></i>
            <p class="text-slate-400 font-semibold text-sm">Belum ada pemasukan tiket.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-slate-800 text-slate-500 text-[10px] font-bold bg-[#080c14] uppercase tracking-wider">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Tiket / Peserta</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($payments as $pm)
                        <tr class="payment-row transition-colors">
                            <td class="px-4 py-3.5 text-slate-400 text-xs">{{ $pm->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-white block text-xs">#{{ $pm->ticket->ticket_code ?? '-' }}</span>
                                <span class="text-slate-500 block text-[10px]">{{ $pm->ticket?->participant_name ?? $pm->user?->name ?? 'Peserta' }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-white text-xs">Rp{{ number_format($pm->amount, 0, ',', '.') }}</td>
                            @php $methodLabels = ['bank_transfer' => 'Transfer Bank', 'credit_card' => 'Kartu Kredit', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay', 'qris' => 'QRIS', 'mandiri_clickpay' => 'Mandiri Clickpay', 'bca_klikbca' => 'BCA KlikBCA', 'bca_klikpay' => 'BCA KlikPay', 'bri_epay' => 'BRI Epay', 'cimb_clicks' => 'CIMB Clicks', 'danamon_online' => 'Danamon Online', 'indomaret' => 'Indomaret', 'alfamart' => 'Alfamart', 'akulaku' => 'Akulaku']; @endphp
                            <td class="px-4 py-3.5 text-xs text-slate-300">{{ $methodLabels[$pm->method] ?? ucwords(str_replace('_', ' ', $pm->method)) }}</td>
                            <td class="px-4 py-3.5 text-right">
                                @php
                                    $pClass = match($pm->status) {
                                        'confirmed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'pending'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        default     => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    };
                                    $pLabel = match($pm->status) {
                                        'confirmed' => 'Lunas',
                                        'pending'   => 'Pending',
                                        default     => 'Dibatalkan',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $pClass }}">
                                    {{ $pLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @endif
    </div>

</div>
@endsection
