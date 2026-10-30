@php
    $wc = $wdCounts instanceof \Illuminate\Support\Collection ? $wdCounts->toArray() : (array) $wdCounts;
    $transferRows = $withdrawalRequests->mapWithKeys(fn ($row) => [$row->id => [
        'amount' => 'Rp '.number_format($row->amount,0,',','.'), 'event'=>$row->event?->title,
        'bank'=>$row->bank_name, 'account'=>$row->account_number, 'holder'=>$row->account_holder,
    ]]);
    $openApproval = $errors->any() && $transferRows->has((int) old('withdrawal_id')) ? (int) old('withdrawal_id') : null;
@endphp
<div class="space-y-4" x-data="{ rejectModal: null, approveModal: @js($openApproval), transfers: @js($transferRows) }"
     @keydown.window.escape="rejectModal = null; approveModal = null">
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach([''=>'Total Pengajuan','pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak'] as $status=>$label)
        <div class="rounded-xl border border-slate-800 bg-dark-card p-4">
            <p class="text-xs text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold text-white">{{ $status === '' ? array_sum($wc) : ($wc[$status] ?? 0) }}</p>
        </div>
        @endforeach
    </div>
    <p class="text-sm text-slate-400">Periksa rekening tujuan dan lakukan transfer sesuai pengajuan Penyelenggara/EO. Unggah bukti transfer untuk menyetujui dan mencatat pengiriman dana.</p>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-800 p-3 text-sm text-red-300">{{ $errors->first() }}</div>
    @endif
    <x-search-form :action="route('admin.dashboard')" placeholder="Cari event, Penyelenggara/EO, bank, atau pemilik rekening..." />
    <nav aria-label="Filter penarikan" class="flex gap-2 overflow-x-auto pb-1">
        @foreach([''=>'Semua','pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak'] as $value=>$label)
        <a class="inline-flex min-h-11 shrink-0 items-center rounded-lg border border-slate-700 px-3 py-2 text-xs text-white {{ request('status','') === $value ? 'bg-orange-600' : '' }}"
           href="{{ route('admin.dashboard',array_merge(request()->except(['status','page']),['tab'=>'withdrawal_requests','status'=>$value])) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-dark-card">
        @if($withdrawalRequests->isEmpty())
        <p class="p-8 text-center text-sm text-slate-400">Tidak ada pengajuan penarikan yang cocok.</p>
        @else
        <p class="px-4 pt-3 text-xs text-slate-500 md:hidden">Geser tabel untuk melihat rekening, bukti transfer dan tindakan.</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-xs">
                <thead class="border-b border-slate-800 text-slate-400"><tr>
                    @foreach(['Tanggal','Event / Penyelenggara/EO','Jumlah','Rekening Tujuan','Status','Bukti / Catatan','Tindakan'] as $heading)<th class="p-4">{{ $heading }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-slate-800 text-slate-200">
                    @foreach($withdrawalRequests as $req)
                    <tr>
                        <td class="whitespace-nowrap p-4">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td class="max-w-xs p-4"><p class="break-words font-semibold text-white">{{ $req->event?->title }}</p><p class="mt-1 text-slate-400">{{ $req->organizer?->organization_name }}</p></td>
                        <td class="whitespace-nowrap p-4 font-bold text-orange-400">Rp {{ number_format($req->amount,0,',','.') }}</td>
                        <td class="p-4"><p class="font-semibold">{{ $req->bank_name }}</p><p class="mt-1 whitespace-nowrap">{{ $req->account_number }}</p><p class="mt-1 text-slate-400">a/n {{ $req->account_holder }}</p></td>
                        <td class="p-4"><span class="whitespace-nowrap rounded-full border border-slate-700 px-2 py-1">{{ $req->status === 'approved' ? ($req->transfer_proof_path ? 'Dana dikirim' : 'Disetujui') : ($req->status === 'rejected' ? 'Ditolak' : 'Menunggu') }}</span></td>
                        <td class="max-w-xs p-4">@include('organizer.finances._transfer-proof',['wd'=>$req])</td>
                        <td class="p-4">
                            @if($req->status === 'pending')
                                @if($req->payout_requested_at || ($req->iris_reference_no && !str_starts_with($req->iris_reference_no,'IRIS-MOCK-')))
                                    <p class="max-w-48 text-slate-400">Periksa proses pencairan sebelumnya sebelum melakukan transfer manual.</p>
                                @else
                                <div class="flex flex-col gap-2">
                                    <button type="button" @click="approveModal = {{ $req->id }}; $nextTick(() => $refs.transferProof.focus())" class="min-h-11 whitespace-nowrap rounded-lg bg-emerald-600 px-3 py-2 font-semibold text-white">Unggah Bukti</button>
                                    <button type="button" @click="rejectModal = {{ $req->id }}" class="min-h-11 rounded-lg border border-red-500/40 px-3 py-2 text-red-400">Tolak</button>
                                </div>
                                @endif
                            @else <span class="text-slate-500">Selesai diproses</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($withdrawalRequests->hasPages())<div class="border-t border-slate-800 p-4">{{ $withdrawalRequests->links() }}</div>@endif
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="approveModal" style="display:none" @click.self="approveModal = null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <section role="dialog" aria-modal="true" aria-labelledby="approve-title" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-700 bg-dark-card p-5 shadow-xl">
                <div class="flex items-center justify-between gap-3"><h2 id="approve-title" class="text-lg font-bold text-white">Konfirmasi Transfer Dana</h2><button type="button" @click="approveModal = null" aria-label="Tutup konfirmasi" class="min-h-11 min-w-11 text-slate-300">&times;</button></div>
                <div class="my-4 space-y-1 rounded-lg bg-slate-900 p-3 text-sm text-slate-300">
                    <p class="break-words" x-text="transfers[approveModal]?.event"></p>
                    <p class="font-bold text-orange-400" x-text="transfers[approveModal]?.amount"></p>
                    <p class="break-words" x-text="(transfers[approveModal]?.bank || '') + ' / ' + (transfers[approveModal]?.account || '')"></p>
                    <p class="break-words" x-text="'a/n ' + (transfers[approveModal]?.holder || '')"></p>
                </div>
                @php $approveRoute=route('admin.withdrawals.approve',['withdrawal'=>'__ID__']); @endphp
                <form method="POST" :action="'{{ $approveRoute }}'.replace('__ID__', approveModal)" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="withdrawal_id" :value="approveModal">
                    <label class="block text-sm text-slate-300">Bukti transfer <span class="text-red-400">*</span>
                        <input x-ref="transferProof" type="file" name="transfer_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="mt-2 block min-h-11 w-full min-w-0 rounded-lg border border-slate-700 p-2 text-xs text-slate-300">
                        <span class="mt-1 block text-xs text-slate-500">JPG, PNG, WebP atau PDF. Maksimal 5 MB.</span>
                    </label>
                    <label class="block text-sm text-slate-300">Nomor referensi transfer (opsional)<input name="transfer_reference" value="{{ old('transfer_reference') }}" maxlength="100" class="mt-2 min-h-11 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 text-sm text-white"></label>
                    <label class="block text-sm text-slate-300">Catatan (opsional)<textarea name="admin_notes" maxlength="500" rows="3" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-sm text-white">{{ old('admin_notes') }}</textarea></label>
                    <p class="text-xs text-slate-400">Pastikan dana sudah ditransfer sesuai rekening dan jumlah pengajuan sebelum menyetujui.</p>
                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end"><button type="button" @click="approveModal = null" class="min-h-11 rounded-lg border border-slate-700 px-4 text-sm text-white">Batal</button><button type="submit" class="min-h-11 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Setujui &amp; Catat Pengiriman</button></div>
                </form>
            </section>
        </div>
    </template>
    <template x-teleport="body">
        <div x-show="rejectModal" style="display:none" @click.self="rejectModal = null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <section role="dialog" aria-modal="true" aria-labelledby="reject-title" class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl border border-slate-700 bg-dark-card p-5">
                <h2 id="reject-title" class="text-lg font-bold text-white">Tolak Pengajuan Penarikan</h2>
                @php $rejectRoute=route('admin.withdrawals.reject',['withdrawal'=>'__ID__']); @endphp
                <form method="POST" :action="'{{ $rejectRoute }}'.replace('__ID__', rejectModal)" class="mt-4 space-y-4">
                    @csrf
                    <label class="block text-sm text-slate-300">Alasan penolakan<textarea name="admin_notes" required maxlength="500" rows="4" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 p-3 text-sm text-white"></textarea></label>
                    <div class="flex justify-end gap-2"><button type="button" @click="rejectModal = null" class="min-h-11 rounded-lg border border-slate-700 px-4 text-white">Batal</button><button type="submit" class="min-h-11 rounded-lg bg-red-600 px-4 text-white">Tolak Pengajuan</button></div>
                </form>
            </section>
        </div>
    </template>
</div>
