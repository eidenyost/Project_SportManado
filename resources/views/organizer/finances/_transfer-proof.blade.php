@if($wd->transfer_proof_path)
    <a href="{{ route('withdrawals.proof',$wd) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center text-xs font-semibold text-orange-400 hover:text-orange-300">Lihat bukti transfer</a>
    @if($wd->transferred_at)<p class="mt-1 text-xs text-slate-400">{{ $wd->transferred_at->timezone('Asia/Makassar')->format('d/m/Y H:i') }} WITA</p>@endif
    @if($wd->transfer_reference)<p class="mt-1 break-words text-xs text-slate-400">Referensi: {{ $wd->transfer_reference }}</p>@endif
@elseif($wd->status === 'approved')
    <p class="text-xs text-slate-500">Bukti transfer belum tersedia untuk riwayat ini.</p>
@endif
@if($wd->display_notes)<p class="mt-2 whitespace-pre-wrap break-words text-xs text-slate-400">{{ $wd->display_notes }}</p>@endif
