<?php

namespace Tests\Feature;

use App\Models\{Event, Notification, Organizer, Payment, Ticket, User, WithdrawalRequest};
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SearchAndConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        Storage::fake('public');Storage::fake('local');
        $owner=User::factory()->create(['role'=>'organizer']);
        $organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'Sport EO','status'=>'approved']);
        $user=User::factory()->create(['role'=>'user','name'=>'Nama Akun Lama']);
        $event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Event Uji','description'=>'Demo','location'=>'Manado',
            'event_date'=>now()->addDays(20),'registration_deadline'=>now()->addDays(10),'max_participants'=>100,
            'ticket_price'=>100000,'category'=>'Futsal','status'=>'active']);
        $ticket=Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'SEARCH-001','status'=>'active',
            'participant_name'=>'Peserta Koreksi','participant_email'=>'koreksi@example.test']);
        $payment=Payment::create(['ticket_id'=>$ticket->id,'user_id'=>$user->id,'amount'=>100000,'method'=>'ewallet','status'=>'confirmed']);
        return [$owner,$user,$event,$ticket,$payment];
    }

    public function test_participant_search_combines_status_identity_and_event_scope(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $other=User::factory()->create(['role'=>'user']);
        Ticket::create(['user_id'=>$other->id,'event_id'=>$event->id,'ticket_code'=>'UNRELATED','status'=>'pending']);
        $response=$this->actingAs($owner)->get(route('organizer.events.participants',$event).'?search=Koreksi&filter=active');
        $response->assertOk()->assertViewHas('tickets',fn($tickets)=>$tickets->total()===1 && $tickets->first()->id===$ticket->id);
        $this->get(route('organizer.events.participants',$event).'?search=SEARCH-001&filter=pending')
            ->assertViewHas('tickets',fn($tickets)=>$tickets->total()===0);
        $this->get(route('organizer.events.participants',$event).'?search=')
            ->assertViewHas('tickets',fn($tickets)=>$tickets->total()===2);
    }

    public function test_participant_pagination_keeps_search_and_status_and_status_links_reset_page(): void
    {
        [$owner,$user,$event]=$this->fixture();
        for($i=0;$i<31;$i++) Ticket::create(['user_id'=>User::factory()->create()->id,'event_id'=>$event->id,
            'ticket_code'=>'MATCH-'.$i,'status'=>'pending','participant_name'=>'Banyak Peserta']);
        $this->actingAs($owner)->get(route('organizer.events.participants',$event).'?search=Banyak&filter=pending')
            ->assertViewHas('tickets',fn($tickets)=>str_contains($tickets->url(2),'search=Banyak') && str_contains($tickets->url(2),'filter=pending'));
    }

    public function test_finance_search_does_not_change_event_balance_totals(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $this->actingAs($owner)->get(route('organizer.finances.finance',$event).'?search=missing&filter=confirmed')
            ->assertViewHas('payments',fn($payments)=>$payments->count()===0)
            ->assertViewHas('grossRevenue',100000)->assertViewHas('availableBalance',100000);
    }

    public function test_pending_approval_and_rejection_keep_balances_consistent(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $finance=app(FinanceService::class);
        $this->actingAs($owner)->post(route('organizer.finances.withdraw',$event),[
            'amount'=>25000,'bank_name'=>'BCA','account_number'=>'1234567890','account_holder'=>'EO Uji',
        ])->assertSessionHasNoErrors();
        $first=WithdrawalRequest::firstOrFail();
        $this->assertSame(75000,$finance->availableBalance($event));
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$first),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertSessionHas('success');
        $this->assertSame(75000,$finance->availableBalance($event));
        $this->assertSame(25000,$finance->withdrawn($event));
        $this->post(route('admin.withdrawals.reject',$first),['admin_notes'=>'Permintaan ulang'])->assertStatus(400);
        $this->assertSame('approved',$first->fresh()->status);
        $this->actingAs($owner)->get(route('organizer.dashboard'))->assertViewHas('availableBalance',75000);
    }

    public function test_unread_notification_count_uses_all_pages_and_owner_scope(): void
    {
        [$owner]=$this->fixture();
        Notification::query()->delete();
        for($i=0;$i<17;$i++) Notification::create(['user_id'=>$owner->id,'type'=>'test','title'=>'Notice '.$i,'message'=>'Example']);
        $this->actingAs($owner)->get(route('organizer.notifications.index').'?page=2')->assertViewHas('totalUnread',17);
        $this->post(route('organizer.notifications.read-all'))->assertRedirect();
        $this->getJson(route('organizer.notifications.unread-count'))->assertJson(['count'=>0]);
    }

    public function test_notifications_follow_ticket_transitions_once_and_are_available_to_participant(): void
    {
        [$owner,$user,$event,$ticket]=$this->fixture();
        $this->assertTrue(Notification::where('user_id',$owner->id)->where('type','registration_received')->exists());
        $ticket->update(['status'=>'used','used_at'=>now(),'check_in_method'=>'qr']);
        $ticket->refresh()->update(['notes'=>'Hanya catatan']);
        $this->assertSame(1,Notification::where('user_id',$user->id)->where('type','ticket_used')->count());
        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('Kehadiran Tercatat');
    }

    public function test_admin_payment_status_filter_and_withdrawal_search_work(): void
    {
        [$owner,$user,$event,$ticket,$payment]=$this->fixture();
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard',['tab'=>'payment_monitoring','status'=>'pending']))
            ->assertViewHas('payments',fn($payments)=>$payments->total()===0);
        WithdrawalRequest::create(['event_id'=>$event->id,'organizer_id'=>$event->organizer_id,'amount'=>25000,
            'bank_name'=>'BCA','account_number'=>'123','account_holder'=>'EO Uji','status'=>'pending']);
        $this->get(route('admin.dashboard',['tab'=>'withdrawal_requests','search'=>'missing']))
            ->assertViewHas('withdrawalRequests',fn($withdrawals)=>$withdrawals->total()===0);
    }

    public function test_rejecting_pending_withdrawal_releases_reservation_once(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $withdrawal=WithdrawalRequest::create(['event_id'=>$event->id,'organizer_id'=>$event->organizer_id,'amount'=>25000,
            'bank_name'=>'BCA','account_number'=>'123','account_holder'=>'EO Uji','status'=>'pending']);
        $finance=app(FinanceService::class);
        $this->assertSame(75000,$finance->availableBalance($event));
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post(route('admin.withdrawals.reject',$withdrawal),['admin_notes'=>'Data rekening perlu diperiksa'])->assertSessionHas('success');
        $this->assertSame(100000,$finance->availableBalance($event));
        $this->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertStatus(400);
        $this->assertSame(1,Notification::where('type','withdrawal_rejected')->where('notifiable_id',$withdrawal->id)->count());
    }

    public function test_unfunded_approval_and_fractional_withdrawal_are_rejected(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $this->actingAs($owner)->post(route('organizer.finances.withdraw',$event),[
            'amount'=>'10000.50','bank_name'=>'BCA','account_number'=>'123','account_holder'=>'EO Uji',
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawal_requests',0);
        $withdrawal=WithdrawalRequest::create(['event_id'=>$event->id,'organizer_id'=>$event->organizer_id,'amount'=>150000,
            'bank_name'=>'BCA','account_number'=>'123','account_holder'=>'EO Uji','status'=>'pending']);
        $this->actingAs(User::factory()->create(['role'=>'admin']))->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertStatus(422);
        $this->assertSame('pending',$withdrawal->fresh()->status);
        $this->assertNull($withdrawal->fresh()->iris_reference_no);
    }

    public function test_export_uses_the_current_participant_search_and_status(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $response=$this->actingAs($owner)->get(route('organizer.events.participants.export',$event).'?search=missing&filter=active');
        $response->assertOk();
        $this->assertStringNotContainsString('SEARCH-001',$response->streamedContent());
        $response=$this->get(route('organizer.events.participants.export',$event).'?search=Koreksi&filter=active');
        $this->assertStringContainsString('SEARCH-001',$response->streamedContent());
    }

    public function test_notification_write_rolls_back_and_foreign_notifications_are_private(): void
    {
        [$owner,$user]=$this->fixture();
        $notice=Notification::where('user_id',$owner->id)->firstOrFail();
        $this->actingAs($user)->post('/notifications/'.$notice->id.'/read')->assertForbidden();
        $this->assertFalse($notice->fresh()->is_read);
        $count=Notification::count();
        \Illuminate\Support\Facades\DB::beginTransaction();
        $event=Event::firstOrFail();
        Ticket::create(['user_id'=>User::factory()->create()->id,'event_id'=>$event->id,'ticket_code'=>'ROLLBACK-NOTICE','status'=>'pending']);
        \Illuminate\Support\Facades\DB::rollBack();
        $this->assertSame($count,Notification::count());
    }

    public function test_scheduled_completion_notifies_owner_and_participant_only_once(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $event->update(['event_date'=>now()->subHours(2),'event_end_at'=>now()->subMinute()]);
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->artisan('events:auto-complete')->assertSuccessful();
        $this->assertSame(1,Notification::where('user_id',$owner->id)->where('type','event_completed')->count());
        $this->assertSame(1,Notification::where('user_id',$user->id)->where('type','event_completed')->count());
    }

    public function test_duplicate_event_approval_does_not_send_duplicate_notifications(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $event->update(['status'=>'pending']);
        $this->actingAs(User::factory()->create(['role'=>'admin']))->post(route('admin.events.approve',$event))->assertRedirect();
        $this->post(route('admin.events.approve',$event))->assertStatus(400);
        $this->assertSame(1,Notification::where('user_id',$owner->id)->where('type','event_approved')->count());
    }

    public function test_registration_and_withdrawal_notification_targets_are_private_to_their_roles(): void
    {
        [$owner,$user,$event]=$this->fixture();
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($owner)->post(route('organizer.finances.withdraw',$event),[
            'amount'=>10000,'bank_name'=>'BCA','account_number'=>'123','account_holder'=>'EO Uji',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1,Notification::where('user_id',$admin->id)->where('type','withdrawal_submitted')->count());
        $this->assertSame(1,Notification::where('user_id',$owner->id)->where('type','withdrawal_pending')->count());
        $this->actingAs($admin)->get('/notifications')->assertOk()->assertSee('Pengajuan Penarikan Baru');
        $this->actingAs($user)->get('/notifications')->assertOk()->assertDontSee('Pengajuan Penarikan Baru');
    }

}
