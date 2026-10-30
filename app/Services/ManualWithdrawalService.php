<?php

namespace App\Services;

use App\Models\{Event, Notification, Organizer, WithdrawalRequest};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class ManualWithdrawalService
{
    public function approve(WithdrawalRequest $withdrawal, int $adminId, UploadedFile $proof, ?string $reference, ?string $notes): void
    {
        $storedPath=null;
        try {
            DB::transaction(function () use ($withdrawal,$adminId,$proof,$reference,$notes,&$storedPath) {
                Organizer::whereKey($withdrawal->organizer_id)->lockForUpdate()->firstOrFail();
                $event=Event::whereKey($withdrawal->event_id)->lockForUpdate()->firstOrFail();
                $locked=WithdrawalRequest::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status==='pending',400,'Pengajuan penarikan tidak dalam status menunggu.');
                abort_if($locked->organizer_id!==$event->organizer_id,422,'Pemilik saldo tidak sesuai.');
                abort_if($locked->payout_requested_at || ($locked->iris_reference_no && !str_starts_with($locked->iris_reference_no,'IRIS-MOCK-')),
                    422,'Pengajuan memiliki proses pencairan sebelumnya. Periksa riwayatnya sebelum konfirmasi transfer manual.');
                abort_if(app(FinanceService::class)->availableBalance($event)<0,422,'Saldo kegiatan tidak mencukupi untuk seluruh pengajuan.');

                $storedPath=$proof->store('withdrawal-proofs','local');
                if (!$storedPath) throw new \RuntimeException('Bukti transfer gagal disimpan. Pengajuan belum disetujui.');
                $locked->update(['status'=>'approved','transfer_proof_path'=>$storedPath,
                    'transfer_reference'=>$reference,'transferred_at'=>now(),'approved_by'=>$adminId,
                    'admin_notes'=>$notes ?: 'Transfer manual telah dikonfirmasi Admin dan bukti transfer tersedia.']);

                Notification::create(['user_id'=>$locked->organizer->user_id,'type'=>'withdrawal_approved',
                    'title'=>'Dana Penarikan Telah Dikirim',
                    'message'=>'Admin telah mengonfirmasi transfer Rp '.number_format($locked->amount,0,',','.').' untuk '.$event->title.'. Bukti transfer tersedia pada riwayat penarikan.',
                    'notifiable_type'=>WithdrawalRequest::class,'notifiable_id'=>$locked->id]);
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                try { Storage::disk('local')->delete($storedPath); } catch (\Throwable $cleanup) { report($cleanup); }
            }
            throw $exception;
        }
    }
}
