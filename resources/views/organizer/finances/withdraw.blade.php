@extends('layouts.organizer')

@section('title', 'Penarikan Dana – ' . $event->title)
@section('page-title', 'Penarikan Dana')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-1 font-medium">
        <a href="{{ route('organizer.dashboard') }}" class="hover:text-[#ff6b00] transition-colors">Dashboard Penyelenggara/EO</a>
        <span>›</span>
        <a href="{{ route('organizer.finances.finance', $event) }}" class="hover:text-[#ff6b00] transition-colors">{{ $event->title }}</a>
        <span>›</span>
        <span class="text-white font-bold truncate max-w-xs">Penarikan Dana</span>
    </nav>
    <p class="text-sm text-slate-400 mb-6">Ajukan penarikan dana hasil penjualan tiket event Anda.</p>

    <!-- Event + Saldo Info -->
    <div class="flex items-center justify-between">
        <div>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-[#ff6b00]/10 text-[#ff6b00] border border-[#ff6b00]/20 uppercase">{{ $event->category }}</span>
            <h3 class="text-lg font-bold text-white mt-1">{{ $event->title }}</h3>
        </div>
        <div class="text-right">
            <span class="text-xs text-slate-400">Saldo tersedia</span>
            <p class="text-xl font-extrabold text-[#ff6b00] leading-tight">Rp {{ number_format($availableBalance, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Penarikan Dana -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Penarikan Dana</h3>
            <a href="{{ route('organizer.finances.finance', $event) }}" class="text-xs text-slate-400 hover:text-white transition-colors">
                Kembali &rarr;
            </a>
        </div>

        @php $balanceOk = $availableBalance >= 10000; @endphp

        <form method="POST" action="{{ route('organizer.finances.withdraw', $event) }}" class="space-y-4" onsubmit="return confirmWithdraw()">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                <div class="space-y-1.5">
                    <label for="amount" class="text-xs font-bold text-slate-300">Jumlah Penarikan</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">Rp</span>
                        <input type="number" name="amount" id="amount"
                               min="10000" max="{{ $availableBalance }}"
                               class="w-full pl-9 pr-4 py-2.5 bg-[#080c14] border border-slate-800 rounded-xl outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-600 transition-colors"
                               value="{{ old('amount', $balanceOk ? $availableBalance : '') }}" {{ $balanceOk ? '' : 'disabled' }} required>
                    </div>
                    @error('amount')
                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="bank_name" class="text-xs font-bold text-slate-300">Nama Bank</label>
                    <input type="text" name="bank_name" id="bank_name"
                           placeholder="BCA, Mandiri, BNI"
                           class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-800 rounded-xl outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-600 transition-colors"
                           value="{{ old('bank_name') }}" required>
                    @error('bank_name')
                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="account_number" class="text-xs font-bold text-slate-300">Nomor Rekening</label>
                    <input type="text" name="account_number" id="account_number"
                           placeholder="Masukkan nomor rekening"
                           class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-800 rounded-xl outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-600 transition-colors"
                           value="{{ old('account_number') }}" required>
                    @error('account_number')
                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="account_holder" class="text-xs font-bold text-slate-300">Nama Pemilik Rekening</label>
                    <input type="text" name="account_holder" id="account_holder"
                           placeholder="Sesuai KTP"
                           class="w-full px-4 py-2.5 bg-[#080c14] border border-slate-800 rounded-xl outline-none focus:border-[#ff6b00] text-xs text-white placeholder-slate-600 transition-colors"
                           value="{{ old('account_holder') }}" required>
                    @error('account_holder')
                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @if($balanceOk)
                <button type="submit" class="w-full py-2.5 bg-[#ff6b00] hover:bg-[#e05e00] text-white rounded-xl text-xs font-bold transition-all shadow-md mt-4 flex items-center justify-center">
                    Ajukan Pencairan Dana
                </button>
            @else
                <button type="button" disabled class="w-full py-2.5 bg-slate-700 text-slate-500 rounded-xl text-xs font-bold cursor-not-allowed mt-4 flex items-center justify-center">
                    Min. penarikan Rp10.000
                </button>
            @endif
        </form>

        @if($withdrawals->isNotEmpty())
            <div class="mt-6 pt-4 border-t border-slate-800">
                <h4 class="text-xs font-bold text-slate-400 mb-3 uppercase tracking-wider">Riwayat Penarikan</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-800/60 text-slate-400 font-semibold">
                                <th class="pb-3 pr-2">Tanggal</th>
                                <th class="pb-3 px-2">Jumlah</th>
                                <th class="pb-3 px-2">Tujuan Transfer</th>
                                <th class="pb-3 px-2">Status</th>
                                <th class="pb-3 pl-2 text-right">Bukti Transfer / Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-slate-200">
                            @foreach($withdrawals as $wd)
                                <tr class="hover:bg-slate-900/20 transition-colors">
                                    <td class="py-3 pr-2 text-slate-400 font-medium">{{ $wd->created_at->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="py-3 px-2 font-bold">Rp {{ number_format($wd->amount, 0, ',', '.') }}</td>
                                    <td class="py-3 px-2">
                                        <span class="font-bold text-white block">{{ $wd->bank_name }}</span>
                                        <span class="text-slate-400 block text-[10px]">{{ $wd->account_number }} - a/n {{ $wd->account_holder }}</span>
                                    </td>
                                    <td class="py-3 px-2">
                                        @if($wd->status === 'approved')
                                            <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 px-2 py-0.5 rounded-full text-[10px] font-bold">{{ $wd->transfer_proof_path ? 'Dana dikirim' : 'Disetujui' }}</span>
                                        @elseif($wd->status === 'pending')
                                            <span class="bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-[10px] font-bold">Menunggu</span>
                                        @elseif($wd->status === 'rejected')
                                            <span class="bg-red-500/10 text-red-400 border border-red-500/25 px-2 py-0.5 rounded-full text-[10px] font-bold">Ditolak</span>
                                        @else
                                            <span class="bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full text-[10px] font-bold">{{ strtoupper($wd->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pl-2 text-right">
                                        @include('organizer.finances._transfer-proof',['wd'=>$wd])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@push('scripts')
<script>
function confirmWithdraw() {
    const amount = document.getElementById('amount')?.value;
    if (!amount || parseInt(amount) < 10000) return false;
    return confirm('Ajukan penarikan dana sebesar Rp ' + Number(amount).toLocaleString('id-ID') + '?');
}
</script>
@endpush
@endsection