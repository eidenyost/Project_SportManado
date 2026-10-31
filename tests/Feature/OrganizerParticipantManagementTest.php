<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Payment, Ticket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizerParticipantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function registration(string $status = 'active', int $price = 0): array
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role'=>'organizer']);
        $organizer = Organizer::create(['user_id'=>$owner->id,'organization_name'=>'EO Demo','status'=>'approved']);
        $event = Event::create(['organizer_id'=>$organizer->id,'title'=>'Demo','description'=>'Demo','location'=>'Manado',
            'event_date'=>now()->addDays(10),'registration_deadline'=>now()->addDays(5),'max_participants'=>2,
            'ticket_price'=>$price,'category'=>'Futsal','status'=>'active','auto_approve'=>true,
            'custom_fields'=>[['label'=>'Nomor Tim','type'=>'text','required'=>true]]]);
        $participant = User::factory()->create(['role'=>'user']);
        $ticket = Ticket::create(['event_id'=>$event->id,'user_id'=>$participant->id,'ticket_code'=>'EOEDIT001',
            'status'=>$status,'custom_answers'=>['Nomor Tim'=>'Tim Lama']]);
        if ($price) Payment::create(['ticket_id'=>$ticket->id,'user_id'=>$participant->id,'amount'=>$price,'method'=>'ewallet','status'=>'pending']);
        return [$owner,$event,$participant,$ticket];
    }

    private function payload(string $status = 'active'): array
    {
        return ['participant_name'=>'Peserta Dikoreksi','participant_email'=>'corrected@example.test','status'=>$status,
            'custom_answers'=>['Nomor Tim'=>'Tim Baru'],'notes'=>'Catatan EO'];
    }

    public function test_owner_can_edit_registration_without_changing_the_login_account(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $original = $participant->only(['name','email','password','role']);
        $this->actingAs($owner)->get("/organizer/events/$event->id/participants/$ticket->id/edit")->assertOk();
        $this->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertRedirect(route('organizer.events.participants',$event));
        $this->assertSame($original,$participant->fresh()->only(['name','email','password','role']));
        $this->assertSame('Peserta Dikoreksi',$ticket->fresh()->participant_name);
        $this->assertSame(['Nomor Tim'=>'Tim Baru'],$ticket->fresh()->custom_answers);
        $this->actingAs($participant)->get(route('tickets.show',$ticket))->assertSee('Peserta Dikoreksi')->assertSee('corrected@example.test');
    }

    public function test_foreign_organizer_and_mismatched_event_are_rejected(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $foreign = User::factory()->create(['role'=>'organizer']);
        Organizer::create(['user_id'=>$foreign->id,'organization_name'=>'Other','status'=>'approved']);
        $this->actingAs($foreign)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertForbidden();
        $this->actingAs($participant)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertForbidden();
        $other = $event->replicate(); $other->save();
        $this->actingAs($owner)->patch("/organizer/events/$other->id/participants/$ticket->id",$this->payload())->assertNotFound();
    }

    public function test_unpaid_ticket_cannot_be_activated_by_an_organizer(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration('pending',25000);
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertSessionHasErrors('status');
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->assertSame('pending',$ticket->payment->fresh()->status);
    }

    public function test_used_ticket_cannot_be_reopened_and_original_scan_time_is_preserved(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $time = now()->subHour()->startOfSecond();
        $ticket->update(['status'=>'used','used_at'=>$time]);
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertSessionHasErrors('status');
        $this->assertTrue($ticket->fresh()->used_at->equalTo($time));
        $this->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload('used'))->assertRedirect();
        $this->assertTrue($ticket->fresh()->used_at->equalTo($time));
        $this->get(route('organizer.events.participants',$event))->assertSee($time->format('d/m/Y H:i:s'))->assertSee('WITA');
    }

    public function test_manual_attendance_records_its_source_and_time(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload('used'))->assertRedirect();
        $this->assertSame('manual',$ticket->fresh()->check_in_method);
        $this->assertNotNull($ticket->fresh()->used_at);
    }

    public function test_cancelled_ticket_cannot_reclaim_a_full_event(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration('cancelled');
        $event->update(['max_participants'=>1]);
        Ticket::create(['event_id'=>$event->id,'user_id'=>$owner->id,'ticket_code'=>'OTHER001','status'=>'active']);
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertSessionHasErrors('status');
        $this->assertSame('cancelled',$ticket->fresh()->status);
    }

    public function test_invalid_answers_and_status_do_not_change_registration(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $data=$this->payload(); $data['custom_answers']=['Nomor Tim'=>['invalid']];
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$data)->assertSessionHasErrors('custom_answers.0');
        $this->assertSame(['Nomor Tim'=>'Tim Lama'],$ticket->fresh()->custom_answers);
        $this->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload('confirmed'))->assertSessionHasErrors('status');
    }
    public function test_qr_validation_exposes_the_original_timestamp_and_corrected_identity(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $ticket->update(['participant_name'=>'Nama Pendaftaran','participant_email'=>'registration@example.test']);
        $this->actingAs($owner)->postJson(route('organizer.events.validate-qr',$event),['ticket_code'=>$ticket->ticket_code])
            ->assertOk()->assertJson(['valid'=>true,'attendee'=>'Nama Pendaftaran','email'=>'registration@example.test']);
        $used=$ticket->fresh();
        $this->assertSame('qr',$used->check_in_method);
        $this->get(route('organizer.events.participants',$event))->assertSee('Validasi QR')->assertSee($used->used_at->format('d/m/Y H:i:s'));
        $this->postJson(route('organizer.events.validate-qr',$event),['ticket_code'=>$ticket->ticket_code])->assertStatus(422);
        $this->assertTrue($used->used_at->equalTo($ticket->fresh()->used_at));
    }

    public function test_paid_confirmed_ticket_can_be_activated_without_changing_payment(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration('pending',25000);
        $ticket->payment->update(['status'=>'confirmed']);
        $this->actingAs($owner)->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertRedirect();
        $this->assertSame('active',$ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->qr_code_path);
        $this->assertSame('confirmed',$ticket->payment->fresh()->status);
    }

    public function test_pending_organizer_cannot_edit_and_status_filter_limits_results(): void
    {
        [$owner,$event,$participant,$ticket] = $this->registration();
        $this->actingAs($owner)->get(route('organizer.events.participants',$event).'?filter=cancelled')
            ->assertOk()->assertDontSee($ticket->ticket_code);
        $owner->organizer->update(['status'=>'pending']);
        $this->get("/organizer/events/$event->id/participants/$ticket->id/edit")->assertForbidden();
        $this->patch("/organizer/events/$event->id/participants/$ticket->id",$this->payload())->assertForbidden();
    }

}
