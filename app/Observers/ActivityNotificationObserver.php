<?php

namespace App\Observers;

use App\Models\{Event, EventRegistrationRequest, Notification, Organizer, Ticket, User, WithdrawalRequest};
use Illuminate\Database\Eloquent\Model;

class ActivityNotificationObserver
{
    private function send(int $userId, string $type, string $title, string $message, Model $model): void
    {
        Notification::create(['user_id'=>$userId,'type'=>$type,'title'=>$title,'message'=>$message,
            'notifiable_type'=>$model::class,'notifiable_id'=>$model->id]);
    }

    private function admins(string $type, string $title, string $message, Model $model): void
    {
        User::where('role','admin')->pluck('id')->each(fn ($id) => $this->send($id,$type,$title,$message,$model));
    }

    public function created(Model $model): void
    {
        if ($model instanceof Ticket) {
            $event=$model->event;
            if ($event?->organizer) $this->send($event->organizer->user_id,'registration_received','Pendaftaran Peserta Baru',
                $model->participant_name.' terdaftar pada '.$event->title.'.',$model);
            $this->ticketStatus($model);
        } elseif ($model instanceof WithdrawalRequest && $model->status==='pending') {
            $message='Pengajuan Rp '.number_format($model->amount,0,',','.').' untuk '.$model->event?->title.' menunggu pemeriksaan Admin.';
            $this->send($model->organizer->user_id,'withdrawal_pending','Pengajuan Penarikan Diterima',$message,$model);
            $this->admins('withdrawal_submitted','Pengajuan Penarikan Baru',$message,$model);
        } elseif ($model instanceof Event && $model->status==='pending') {
            $this->admins('event_submitted','Event Menunggu Persetujuan',$model->title.' menunggu pemeriksaan.',$model);
        } elseif ($model instanceof Organizer && $model->status==='pending') {
            $this->admins('organizer_submitted','Pendaftaran Penyelenggara/EO Baru',$model->organization_name.' menunggu pemeriksaan.',$model);
        } elseif ($model instanceof EventRegistrationRequest && $model->status==='pending') {
            $this->admins('event_request_submitted','Permohonan Event Baru',$model->event_title.' menunggu pemeriksaan.',$model);
        }
    }

    public function updated(Model $model): void
    {
        if (!$model->wasChanged('status')) return;
        if ($model instanceof Ticket) $this->ticketStatus($model);
        if ($model instanceof Event && in_array($model->status,['completed','cancelled'],true)) {
            $title=$model->status==='completed'?'Event Selesai':'Event Dibatalkan';
            $recipients=$model->tickets()->whereIn('status',['pending','active','used'])->pluck('user_id');
            if ($model->organizer) $recipients->push($model->organizer->user_id);
            foreach ($recipients->unique() as $userId) $this->send($userId,'event_'.$model->status,$title,$model->title.'.',$model);
        }
        if ($model instanceof EventRegistrationRequest && in_array($model->status,['approved','rejected'],true)) {
            $title=$model->status==='approved'?'Permohonan Event Disetujui':'Permohonan Event Ditolak';
            $this->send($model->user_id,'event_request_'.$model->status,$title,
                $model->event_title.($model->admin_notes ? ': '.$model->admin_notes : '.'),$model);
        }
    }

    private function ticketStatus(Ticket $ticket): void
    {
        $title=match($ticket->status) {
            'active'=>'Tiket Aktif','pending'=>'Pendaftaran Diterima','used'=>'Kehadiran Tercatat',
            'cancelled'=>'Kepesertaan Dibatalkan',default=>null,
        };
        if (!$title) return;
        $message=$ticket->ticket_code.' untuk '.$ticket->event?->title.'.';
        if ($ticket->status==='pending') $message.=' Periksa halaman tiket untuk pembayaran atau persetujuan Penyelenggara/EO.';
        if ($ticket->status==='used' && $ticket->used_at) $message.=' Kehadiran: '.$ticket->used_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s').' WITA.';
        if ($ticket->status==='cancelled' && $ticket->cancellation_reason) $message.=' '.$ticket->cancellation_reason;
        $this->send($ticket->user_id,'ticket_'.$ticket->status,$title,$message,$ticket);
    }
}
