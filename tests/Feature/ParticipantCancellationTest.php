<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Payment, Ticket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $status='active', bool $paid=false): array
    {
        Storage::fake('public');
        $owner=User::factory()->create(['role'=>'organizer']);
        $organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'Demo','status'=>'approved']);
        $user=User::factory()->create(['role'=>'user']);
        $event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Cancel Test','description'=>'Demo','location'=>'Manado',
            'event_date'=>now()->addDays(10),'registration_deadline'=>now()->addDays(5),'max_participants'=>1,
            'ticket_price'=>$paid?25000:0,'category'=>'Futsal','status'=>'active','auto_approve'=>true]);
        $ticket=Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'CANCEL001','status'=>$status]);
        if($paid) Payment::create(['user_id'=>$user->id,'ticket_id'=>$ticket->id,'amount'=>25000,'method'=>'ewallet','status'=>'confirmed']);
        return [$owner,$user,$event,$ticket];
    }

    private function url(Event $event, Ticket $ticket): string { return "/organizer/events/$event->id/participants/$ticket->id/cancel"; }

    public function test_cancellation_releases_capacity_and_keeps_ticket_account_and_reason(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $this->assertSame(0,$event->remainingCapacity());
        $this->actingAs($owner)->post($this->url($event,$ticket),['cancellation_reason'=>'Peserta berhalangan hadir'])->assertRedirect(route('organizer.events.participants',$event));
        $saved=$ticket->fresh();
        $this->assertSame('cancelled',$saved->status);
        $this->assertSame('Peserta berhalangan hadir',$saved->cancellation_reason);
        $this->assertSame($owner->id,$saved->cancelled_by);
        $this->assertNotNull($saved->cancelled_at);
        $this->assertSame(1,$event->fresh()->remainingCapacity());
        $this->assertNotNull($user->fresh());
        $this->postJson(route('organizer.events.validate-qr',$event),['ticket_code'=>$ticket->ticket_code])->assertStatus(422)->assertJson(['valid'=>false]);
        $this->actingAs($user)->get(route('tickets.show',$ticket))->assertSee('Peserta berhalangan hadir');
    }

    public function test_paid_cancellation_is_blocked_and_payment_is_unchanged(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture('active',true);
        $before=$ticket->payment->getRawOriginal();
        $this->actingAs($owner)->post($this->url($event,$ticket),['cancellation_reason'=>'Dibatalkan atas permintaan peserta'])->assertSessionHasErrors('cancellation_reason');
        $this->assertSame($before,$ticket->payment->fresh()->getRawOriginal());
        $this->assertSame('active',$ticket->fresh()->status);
    }

    public function test_used_ticket_cannot_be_cancelled_and_time_is_preserved(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture('used');
        $time=now()->subHour()->startOfSecond();$ticket->update(['used_at'=>$time,'check_in_method'=>'qr']);
        $this->actingAs($owner)->post($this->url($event,$ticket),['cancellation_reason'=>'Permintaan pembatalan'])->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('used',$ticket->fresh()->status);
        $this->assertTrue($ticket->fresh()->used_at->equalTo($time));
    }

    public function test_reason_is_required_and_foreign_organizer_is_forbidden(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $this->actingAs($owner)->post($this->url($event,$ticket),[])->assertSessionHasErrors('cancellation_reason');
        $other=User::factory()->create(['role'=>'organizer']);
        Organizer::create(['user_id'=>$other->id,'organization_name'=>'Other','status'=>'approved']);
        $this->actingAs($other)->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan'])->assertForbidden();
        $this->actingAs($user)->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan'])->assertForbidden();
        $this->assertSame('active',$ticket->fresh()->status);
    }

    public function test_duplicate_cancellation_keeps_original_actor_reason_and_time(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $this->actingAs($owner)->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan pertama'])->assertRedirect();
        $saved=$ticket->fresh();
        $this->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan kedua'])->assertRedirect();
        $this->assertSame('Alasan pertama',$ticket->fresh()->cancellation_reason);
        $this->assertTrue($saved->cancelled_at->equalTo($ticket->fresh()->cancelled_at));
    }

    public function test_status_dropdown_cannot_bypass_cancellation_reason(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $data=['participant_name'=>$user->name,'participant_email'=>$user->email,'status'=>'cancelled'];
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$data)->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('active',$ticket->fresh()->status);
        $this->patch("/organizer/events/$event->id/participants/$ticket->id",$data+['cancellation_reason'=>'Tidak dapat hadir'])->assertRedirect();
        $this->assertSame('Tidak dapat hadir',$ticket->fresh()->cancellation_reason);
    }

    public function test_pending_rejection_also_saves_the_reason_entered_by_organizer(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture('pending');
        $this->actingAs($owner)->post(route('organizer.events.participants.reject',[$event,$ticket]),['notes'=>'Data pendaftaran tidak sesuai'])->assertRedirect();
        $this->assertSame('Data pendaftaran tidak sesuai',$ticket->fresh()->cancellation_reason);
        $this->assertSame($owner->id,$ticket->fresh()->cancelled_by);
    }

    public function test_paid_dropdown_rejection_and_pending_ticket_cannot_bypass_policy(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture('pending',true);
        $ticket->payment->update(['status'=>'pending']);
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",[
            'participant_name'=>$user->name,'participant_email'=>$user->email,'status'=>'cancelled','cancellation_reason'=>'Alasan',
        ])->assertSessionHasErrors('cancellation_reason');
        $this->post(route('organizer.events.participants.reject',[$event,$ticket]),['notes'=>'Alasan'])->assertSessionHasErrors('notes');
        $this->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan'])->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->get(route('organizer.events.participants.edit',[$event,$ticket]))->assertOk()
            ->assertDontSee('id="cancel-participant-form"',false)->assertDontSee('value="cancelled"',false);
    }

    public function test_payment_record_still_protects_cancellation_when_event_price_is_changed(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture('active',true);
        $event->update(['ticket_price'=>0]);
        $this->actingAs($owner)->post($this->url($event,$ticket),['cancellation_reason'=>'Alasan'])->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('active',$ticket->fresh()->status);
    }
}
