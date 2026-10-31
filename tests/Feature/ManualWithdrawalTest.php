<?php

namespace Tests\Feature;

use App\Models\{Event, Notification, Organizer, Payment, Ticket, User, WithdrawalRequest};
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

class ManualWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        Storage::fake('public');Storage::fake('local');Http::preventStrayRequests();
        $owner=User::factory()->create(['role'=>'organizer']);
        $organizer=Organizer::create(['user_id'=>$owner->id,'organization_name'=>'EO Manual','status'=>'approved']);
        $user=User::factory()->create(['role'=>'user']);
        $event=Event::create(['organizer_id'=>$organizer->id,'title'=>'Penarikan Manual','description'=>'Demo','location'=>'Manado',
            'event_date'=>now()->addDays(10),'registration_deadline'=>now()->addDays(5),'max_participants'=>50,'ticket_price'=>100000,'category'=>'Futsal','status'=>'active']);
        $ticket=Ticket::create(['user_id'=>$user->id,'event_id'=>$event->id,'ticket_code'=>'MANUAL-WITHDRAWAL','status'=>'active']);
        Payment::create(['ticket_id'=>$ticket->id,'user_id'=>$user->id,'amount'=>100000,'method'=>'ewallet','status'=>'confirmed']);
        $withdrawal=WithdrawalRequest::create(['event_id'=>$event->id,'organizer_id'=>$organizer->id,'amount'=>25000,
            'bank_name'=>'BCA','account_number'=>'0123456789','account_holder'=>'Pemilik EO','status'=>'pending']);
        $admin=User::factory()->create(['role'=>'admin']);
        return [$owner,$user,$event,$withdrawal,$admin];
    }

    public function test_admin_must_upload_transfer_proof_before_approval(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal))->assertSessionHasErrors('transfer_proof');
        $this->assertSame('pending',$withdrawal->fresh()->status);
        $this->assertSame(75000,app(FinanceService::class)->availableBalance($event));
        Http::assertNothingSent();
    }

    public function test_manual_approval_records_private_receipt_actor_time_and_preserves_balance(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        config(['services.midtrans.is_production'=>true]);
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),[
            'transfer_proof'=>\Tests\Support\TransferProof::image('bukti.png'),'transfer_reference'=>'BANK-123','admin_notes'=>'Ditransfer sesuai rekening pengajuan.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $saved=$withdrawal->fresh();
        $this->assertSame('approved',$saved->status);$this->assertSame($admin->id,$saved->approved_by);
        $this->assertNotNull($saved->transferred_at);$this->assertSame('BANK-123',$saved->transfer_reference);
        Storage::disk('local')->assertExists($saved->transfer_proof_path);
        Storage::disk('public')->assertMissing($saved->transfer_proof_path);
        $this->assertSame(75000,app(FinanceService::class)->availableBalance($event));
        $this->assertSame(25000,app(FinanceService::class)->withdrawn($event));
        $this->assertSame(1,Notification::where('type','withdrawal_approved')->where('notifiable_id',$saved->id)->count());
        $this->get(route('withdrawals.proof',$saved))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->actingAs($owner)->get(route('withdrawals.proof',$saved))->assertOk();
        $this->get(route('organizer.finances.withdraw.page',$event))->assertOk()->assertSee('Lihat bukti transfer');
        $this->actingAs($user)->get(route('withdrawals.proof',$saved))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_duplicate_approval_cannot_replace_receipt_or_send_notification_again(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image('first.png')]);
        $saved=$withdrawal->fresh();
        $this->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image('second.png')])->assertStatus(400);
        $this->assertSame($saved->transfer_proof_path,$withdrawal->fresh()->transfer_proof_path);
        $this->assertTrue($saved->transferred_at->equalTo($withdrawal->fresh()->transferred_at));
        $this->assertCount(1,Storage::disk('local')->allFiles('withdrawal-proofs'));
        $this->assertSame(1,Notification::where('type','withdrawal_approved')->where('notifiable_id',$saved->id)->count());
    }

    public function test_invalid_or_oversized_proof_cannot_approve_a_withdrawal(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        foreach([UploadedFile::fake()->create('program.php',1,'text/plain'),UploadedFile::fake()->create('large.pdf',6000,'application/pdf')] as $file) {
            $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>$file])->assertSessionHasErrors('transfer_proof');
        }
        $this->assertSame('pending',$withdrawal->fresh()->status);$this->assertCount(0,Storage::disk('local')->allFiles());
    }

    public function test_eo_keeps_withdrawal_form_and_cannot_approve_or_read_other_eos_proof(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($owner)->get(route('organizer.finances.withdraw.page',$event))->assertOk()
            ->assertSee('name="amount"',false)->assertSee('name="bank_name"',false)->assertSee('name="account_number"',false)->assertSee('name="account_holder"',false);
        $this->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image('proof.png')])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image('proof.png')]);
        $other=User::factory()->create(['role'=>'organizer']);Organizer::create(['user_id'=>$other->id,'organization_name'=>'Other EO','status'=>'approved']);
        $this->actingAs($other)->get(route('withdrawals.proof',$withdrawal))->assertForbidden();
    }

    public function test_admin_notifications_use_admin_sidebar_and_normal_role_label(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($admin)->get('/notifications')->assertOk()->assertSee('Panel Admin')->assertSee('Verifikasi Penarikan')->assertDontSee('Super Admin');
        $this->get(route('admin.dashboard',['tab'=>'withdrawal_requests']))->assertOk()->assertSee('Unggah Bukti')->assertDontSee('Super Admin');
        $this->actingAs($owner)->get(route('organizer.finances.finance',$event))->assertOk()->assertDontSee('Mode Sandbox:');
    }

    public function test_receipt_storage_failure_keeps_request_pending(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $disk=Storage::disk('local');
        Storage::shouldReceive('disk')->with('local')->andThrow(new \RuntimeException('Simulated storage failure'));
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertStatus(500);
        $this->assertSame('pending',$withdrawal->fresh()->status);
        $this->assertNull($withdrawal->fresh()->transfer_proof_path);
        $this->assertCount(0,$disk->allFiles());
        $this->assertSame(0,Notification::where('type','withdrawal_approved')->count());
    }

    public function test_failed_database_transaction_removes_uncommitted_receipt(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        Notification::creating(function ($notice) {
            if ($notice->type==='withdrawal_approved') throw new \RuntimeException('Simulated notification failure');
        });
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertStatus(500);
        $saved=$withdrawal->fresh();
        $this->assertSame('pending',$saved->status);$this->assertNull($saved->transfer_proof_path);$this->assertNull($saved->approved_by);
        $this->assertCount(0,Storage::disk('local')->allFiles());
        $this->assertSame(75000,app(FinanceService::class)->availableBalance($event));
    }

    public function test_pdf_proof_is_supported_and_missing_receipt_returns_not_found(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($owner)->get(route('withdrawals.proof',$withdrawal))->assertNotFound();
        $file=UploadedFile::fake()->createWithContent('receipt.pdf',"%PDF-1.4\n% Local test fixture\n%%EOF");
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>$file])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('withdrawals.proof',$withdrawal))->assertOk()->assertHeader('Content-Type','application/pdf');
    }

    public function test_old_provider_request_cannot_be_confirmed_as_a_new_manual_transfer(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $withdrawal->update(['payout_requested_at'=>now(),'iris_reference_no'=>'EXISTING-REQUEST']);
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()])->assertStatus(422);
        $this->assertSame('pending',$withdrawal->fresh()->status);$this->assertCount(0,Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    public function test_account_of_receipt_approver_cannot_delete_financial_history(): void
    {
        [$owner,$user,$event,$withdrawal,$admin]=$this->fixture();
        $this->actingAs($admin)->post(route('admin.withdrawals.approve',$withdrawal),['transfer_proof'=>\Tests\Support\TransferProof::image()]);
        $this->delete('/profile',['password'=>'password'])->assertSessionHasErrorsIn('userDeletion','password');
        $this->assertNotNull($admin->fresh());$this->assertSame($admin->id,$withdrawal->fresh()->approved_by);
    }
}
