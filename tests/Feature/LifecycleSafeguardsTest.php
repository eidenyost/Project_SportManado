<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Payment, Ticket, User};
use App\Services\{PaymentReservationService, ReservationGateway};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Storage};
use Tests\TestCase;

class LifecycleSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(bool $paid = true): array
    {
        Storage::fake('public');
        config(['services.midtrans.server_key'=>'sandbox-test-key']);
        Http::preventStrayRequests();
        $owner=User::factory()->create(['role'=>'organizer']);
        $organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'Lifecycle EO','status'=>'approved']);
        $user=User::factory()->create(['role'=>'user']);
        $event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Lifecycle','description'=>'Description','location'=>'Manado',
            'event_date'=>now()->addDays(2),'registration_deadline'=>now()->addDay(),'max_participants'=>2,
            'ticket_price'=>$paid?25000:0,'category'=>'Futsal','status'=>'active','auto_approve'=>true]);
        $ticket=Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'LIFECYCLE-01','status'=>'pending']);
        $payment=$paid?Payment::create(['ticket_id'=>$ticket->id,'user_id'=>$user->id,'amount'=>25000,'method'=>'ewallet','status'=>'pending',
            'reservation_expires_at'=>now()->subMinute()]):null;
        return [$owner,$user,$event,$ticket,$payment];
    }

    private function payload(string $status): array
    {
        return ['order_id'=>'LIFECYCLE-01','gross_amount'=>'25000.00','transaction_status'=>$status,
            'status_code'=>$status==='settlement'?'200':'201','transaction_id'=>'provider-01','payment_type'=>'bank_transfer'];
    }

    private function eventData(Event $event, string $status): array
    {
        return ['title'=>$event->title,'description'=>$event->description,'location'=>$event->location,
            'event_date'=>$event->event_date->format('Y-m-d H:i'),'registration_deadline'=>$event->registration_deadline->format('Y-m-d H:i'),
            'max_participants'=>2,'ticket_price'=>(int) $event->ticket_price,'category'=>'Futsal','status'=>$status];
    }

    public function test_completion_uses_end_time_and_legacy_end_of_day(): void
    {
        [$owner,$user,$event]=$this->fixture(false);
        $this->travelTo(now()->startOfDay()->addHours(12));
        $event->update(['event_date'=>now()->subHours(2),'event_end_at'=>now()->addHours(3)]);
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->assertSame('active',$event->fresh()->status);
        $event->update(['event_end_at'=>now()->subMinute()]);
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->assertSame('completed',$event->fresh()->status);
        $event->refresh()->update(['status'=>'active','event_end_at'=>null]);
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->assertSame('active',$event->fresh()->status);
        $this->travel(1)->days();
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->assertSame('completed',$event->fresh()->status);
    }

    public function test_completed_event_keeps_archives_and_exports_but_cannot_scan_or_edit_participants(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $event->update(['status'=>'completed']);
        $this->actingAs($owner)->get(route('organizer.events.participants',$event))->assertOk()->assertSee('Arsip peserta')->assertDontSee('Edit Peserta');
        $this->get(route('organizer.events.participants.export',$event))->assertOk();
        $this->get(route('organizer.finances.finance',$event))->assertOk();
        $this->get(route('organizer.finances.export',$event))->assertOk();
        $this->get(route('organizer.finances.withdraw.page',$event))->assertOk();
        $this->get(route('organizer.events.scan',$event))->assertStatus(422);
        $this->postJson(route('organizer.events.validate-qr',$event),['ticket_code'=>$ticket->ticket_code])->assertStatus(422);
        $this->patch(route('organizer.events.participants.update',[$event,$ticket]),
            ['participant_name'=>'Correction','participant_email'=>$user->email,'status'=>'active'])->assertForbidden();
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->actingAs($user)->get(route('organizer.events.participants',$event))->assertForbidden();
    }

    public function test_check_in_closes_at_end_time_even_without_scheduler(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture(false);
        $event->update(['event_date'=>now()->subHours(2),'event_end_at'=>now()->subMinute()]);
        $ticket->update(['status'=>'active']);
        $this->actingAs($owner)->postJson(route('organizer.events.validate-qr',$event),['ticket_code'=>$ticket->ticket_code])->assertStatus(422);
        $this->assertSame('active',$ticket->fresh()->status);
    }

    public function test_event_end_time_is_saved_and_must_follow_start_time(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $end=$event->event_date->copy()->addHours(4)->startOfMinute();
        $this->actingAs($owner)->patch(route('organizer.events.update',$event),
            $this->eventData($event,'active')+['event_end_at'=>$end->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $this->assertTrue($event->fresh()->event_end_at->equalTo($end));
        $this->patch(route('organizer.events.update',$event),
            $this->eventData($event,'active')+['event_end_at'=>$event->event_date->copy()->subHour()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('event_end_at');
        $this->assertTrue($event->fresh()->event_end_at->equalTo($end));
    }

    public function test_paid_event_cannot_be_cancelled_or_unpublished_by_any_organizer_update_path(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $this->actingAs($owner)->delete(route('organizer.events.destroy',$event))->assertSessionHasErrors('status');
        foreach (['cancelled','draft','pending'] as $status) {
            $this->patch(route('organizer.events.update',$event),$this->eventData($event,$status))->assertSessionHasErrors('status');
            $this->assertSame('active',$event->fresh()->status);
        }
        $event->update(['ticket_price'=>0]);
        $this->delete(route('organizer.events.destroy',$event))->assertSessionHasErrors('status');
        $this->assertSame('pending',$payment->fresh()->status);
    }

    public function test_empty_paid_event_can_be_cancelled(): void
    {
        [$owner,$user,$event]=$this->fixture(false);
        $event->tickets()->delete();
        $event->update(['ticket_price'=>25000]);
        $this->actingAs($owner)->delete(route('organizer.events.destroy',$event))->assertRedirect();
        $this->assertSame('cancelled',$event->fresh()->status);
    }

    public function test_admin_cannot_delete_or_reject_an_event_with_participant_history(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->delete(route('admin.events.delete',$event))->assertSessionHas('error');
        $this->post(route('admin.events.reject',$event))->assertSessionHas('error');
        $this->assertNull($event->fresh()->deleted_at);
        $this->assertNotNull($ticket->fresh());
    }

    public function test_self_deletion_preserves_history_and_session(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $this->actingAs($user)->from('/profile')->delete('/profile',['password'=>'password'])
            ->assertSessionHasErrorsIn('userDeletion','password')->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
        $this->assertNotNull($ticket->fresh());
        $this->assertNotNull($payment->fresh());
        $this->actingAs($owner)->delete('/profile',['password'=>'password'])->assertSessionHasErrorsIn('userDeletion','password');
        $this->assertNotNull($event->fresh());
    }

    public function test_database_rejects_cascading_user_deletion_even_if_model_guard_is_bypassed(): void
    {
        [$owner,$user]=$this->fixture();
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('users')->where('id',$user->id)->delete();
    }

    public function test_new_paid_registration_has_a_thirty_minute_reservation(): void
    {
        [$owner,$user,$event]=$this->fixture(false);
        $event->tickets()->delete();$event->update(['ticket_price'=>25000]);
        $this->actingAs($user)->post(route('tickets.submit-payment',$event))->assertRedirect();
        $payment=Payment::firstOrFail();
        $this->assertEqualsWithDelta(1800,now()->diffInSeconds($payment->reservation_expires_at),2);
    }

    public function test_unopened_expired_payment_releases_capacity_without_provider_request(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        $this->assertSame('cancelled',$ticket->fresh()->status);
        $this->assertSame('rejected',$payment->fresh()->status);
        $this->assertSame(2,$event->remainingCapacity());
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_successful_payment_is_reconciled_instead_of_released(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::response($this->payload('settlement'))]);
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        $this->assertSame('active',$ticket->fresh()->status);
        $this->assertSame('confirmed',$payment->fresh()->status);
    }

    public function test_pending_provider_payment_is_expired_then_verified_before_release(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::sequence()->push($this->payload('pending'))->push($this->payload('expire'))->push($this->payload('expire'))]);
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        Http::assertSentCount(3);
        $this->assertSame('cancelled',$ticket->fresh()->status);
        $this->assertSame('rejected',$payment->fresh()->status);
    }

    public function test_provider_failure_or_unverifiable_payment_keeps_reserved_capacity(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::response(['error'=>'unavailable'],503)]);
        $this->artisan('payments:expire-reservations')->assertFailed();
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->assertSame('pending',$payment->fresh()->status);
        $this->assertSame(1,$event->remainingCapacity());
    }

    public function test_provider_payload_with_wrong_amount_never_releases_or_activates_ticket(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::response(array_replace($this->payload('settlement'),['gross_amount'=>'1.00']))]);
        $this->artisan('payments:expire-reservations')->assertFailed();
        $this->assertSame('pending',$ticket->fresh()->status);
    }

    public function test_provider_404_requires_absolute_expiry_and_grace_period(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::response(['status_code'=>'404'],404)]);
        $service=app(PaymentReservationService::class);
        $service->reconcile($payment);
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->travel(16)->minutes();
        $service->reconcile($payment);
        $this->assertSame('cancelled',$ticket->fresh()->status);
    }

    public function test_legacy_snap_404_keeps_capacity_until_maximum_provider_lifetime(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['reservation_expires_at'=>null,'snap_token'=>'legacy']);
        Http::fake(['*'=>Http::response(['status_code'=>'404'],404)]);
        $service=app(PaymentReservationService::class);
        $service->reconcile($payment);
        $this->assertSame('pending',$ticket->fresh()->status);
        $this->travel(8)->days();
        $service->reconcile($payment);
        $this->assertSame('cancelled',$ticket->fresh()->status);
    }

    public function test_checkout_claim_prevents_local_expiry_and_uses_absolute_provider_deadline(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['reservation_expires_at'=>now()->addMinutes(30)]);
        $service=app(PaymentReservationService::class);
        $claimed=$service->beginCheckout($ticket);
        $this->assertNotNull($claimed->snap_requested_at);
        $expiry=$service->snapExpiry($claimed);
        $this->assertSame(30,$expiry['duration']);
        $this->assertSame('+0700',substr($expiry['start_time'],-5));
        $this->travel(31)->minutes();
        $this->assertNull($service->beginCheckout($ticket));
        Http::fake(['*'=>Http::response(['message'=>'unauthorized'],401)]);
        $this->artisan('payments:expire-reservations')->assertFailed();
        $this->assertSame('pending',$ticket->fresh()->status);
    }

    public function test_expired_checkout_does_not_create_a_snap_token_or_modify_payment(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $before=$payment->fresh()->getRawOriginal();
        $this->actingAs($user)->get(route('tickets.show',$ticket))->assertOk()
            ->assertViewHas('snapToken',null)->assertSee('Batas waktu pembayaran telah berakhir');
        $this->assertSame($before,$payment->fresh()->getRawOriginal());
        Http::assertNothingSent();
    }

    public function test_confirmed_payment_and_free_approval_queue_are_not_expired(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['status'=>'confirmed']);
        $ticket->update(['status'=>'active']);
        $free=Ticket::create(['user_id'=>User::factory()->create()->id,'event_id'=>$event->id,'ticket_code'=>'FREE-APPROVAL','status'=>'pending']);
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        $this->assertSame('active',$ticket->fresh()->status);
        $this->assertSame('pending',$free->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_provider_expiry_race_with_settlement_keeps_successful_ticket(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $payment->update(['snap_token'=>'snap','snap_requested_at'=>now()->subHour()]);
        Http::fake(['*'=>Http::sequence()->push($this->payload('pending'))->push($this->payload('expire'))->push($this->payload('settlement'))]);
        $this->artisan('payments:expire-reservations')->assertSuccessful();
        $this->assertSame('active',$ticket->fresh()->status);
        $this->assertSame('confirmed',$payment->fresh()->status);
    }

    public function test_completed_event_can_request_payout_from_its_preserved_balance(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $event->update(['status'=>'completed']);$payment->update(['status'=>'confirmed']);
        $this->actingAs($owner)->post(route('organizer.finances.withdraw',$event),[
            'amount'=>10000,'bank_name'=>'Bank Test','account_number'=>'1234567890','account_holder'=>'EO Test',
        ])->assertSessionHasNoErrors()->assertRedirect(route('organizer.finances.finance',$event));
        $this->assertDatabaseHas('withdrawal_requests',['event_id'=>$event->id,'amount'=>10000,'status'=>'pending']);
    }
}
