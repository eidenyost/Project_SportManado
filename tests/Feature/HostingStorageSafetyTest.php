<?php

namespace Tests\Feature;

use App\Models\{Event, Organizer, Ticket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class HostingStorageSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_qr_write_rolls_back_registration_without_saving_a_false_path(): void
    {
        [$owner, $event] = $this->event();
        $participant = User::factory()->create(['role'=>'user']);
        $disk = \Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($participant)->post(route('tickets.submit-payment', $event));
            $this->fail('A failed QR write must interrupt the registration transaction.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('QR', $exception->getMessage());
        }
        $this->assertDatabaseMissing('tickets', ['event_id' => $event->id, 'user_id' => $participant->id]);
    }

    public function test_failed_banner_replacement_keeps_the_old_file_and_database_reference(): void
    {
        [$owner, $event] = $this->event();
        Storage::fake('public');
        Storage::disk('public')->put('event-banners/original.png', 'original');
        $event->update(['banner' => 'event-banners/original.png']);
        $disk = Storage::disk('public');
        $failing = \Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('public', $failing);
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($owner)->patch(route('organizer.events.update', $event), [
                'title'=>$event->title, 'description'=>'Replacement test', 'location'=>'Manado',
                'event_date'=>now()->addDays(10)->format('Y-m-d H:i'),
                'registration_deadline'=>now()->addDays(5)->format('Y-m-d H:i'),
                'max_participants'=>30, 'ticket_price'=>0, 'category'=>'Futsal', 'status'=>'active',
                'banner'=>UploadedFile::fake()->create('replacement.png', 1, 'image/png'),
            ]);
            $this->fail('A failed upload must stop the replacement.');
        } catch (RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertSame('event-banners/original.png', $event->fresh()->banner);
        $this->assertTrue($disk->exists('event-banners/original.png'));
    }

    public function test_failed_organizer_registration_does_not_leave_an_orphan_user(): void
    {
        $disk = \Mockery::mock();
        $disk->shouldReceive('putFileAs')->once()->andThrow(new RuntimeException('Upload unavailable'));
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->withoutExceptionHandling();
        try {
            $this->post('/register', ['name'=>'Organizer','email'=>'new-organizer@example.test',
                'password'=>'Strong-password-2026!', 'password_confirmation'=>'Strong-password-2026!',
                'role'=>'organizer','organization_name'=>'Demo','phone'=>'081234567890',
                'ktp'=>UploadedFile::fake()->create('ktp.png',1,'image/png'),
                'selfie'=>UploadedFile::fake()->create('selfie.png',1,'image/png')]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Upload unavailable', $exception->getMessage());
        }
        $this->assertDatabaseMissing('users',['email'=>'new-organizer@example.test']);
    }

    public function test_ticket_page_recreates_a_missing_active_qr(): void
    {
        [$owner, $event] = $this->event();
        Storage::fake('public');
        $participant = User::factory()->create(['role'=>'user']);
        $ticket = Ticket::create(['user_id'=>$participant->id,'event_id'=>$event->id,'ticket_code'=>'RESTORE01','status'=>'active']);
        $path = $ticket->qr_code_path;
        Storage::disk('public')->delete($path);
        $this->actingAs($participant)->get(route('tickets.show',$ticket))->assertOk();
        Storage::disk('public')->assertExists($path);
        $this->assertSame('active',$ticket->fresh()->status);
    }

    public function test_participant_notes_are_saved_and_shown_on_the_ticket(): void
    {
        [$owner, $event] = $this->event();
        Storage::fake('public');
        $participant = User::factory()->create(['role'=>'user']);
        $this->actingAs($participant)->post(route('tickets.submit-payment',$event), ['notes'=>'Mohon informasi akses kursi roda.'])->assertRedirect();
        $ticket = Ticket::where('user_id',$participant->id)->firstOrFail();
        $this->assertSame('Mohon informasi akses kursi roda.',$ticket->notes);
        $this->get(route('tickets.show',$ticket))->assertOk()->assertSee('Mohon informasi akses kursi roda.');
    }

    public function test_excessive_notes_do_not_create_a_ticket(): void
    {
        [$owner, $event] = $this->event();
        $participant = User::factory()->create(['role'=>'user']);
        $this->actingAs($participant)->post(route('tickets.submit-payment',$event), ['notes'=>str_repeat('x',2001)])->assertSessionHasErrors('notes');
        $this->assertDatabaseMissing('tickets',['user_id'=>$participant->id]);
    }

    private function event(): array
    {
        $owner = User::factory()->create(['role'=>'organizer']);
        $organizer = Organizer::create(['user_id'=>$owner->id, 'organization_name'=>'Demo', 'status'=>'approved']);
        $event = Event::create(['organizer_id'=>$organizer->id, 'title'=>'Storage test', 'description'=>'Demo',
            'location'=>'Manado', 'event_date'=>now()->addDays(10), 'registration_deadline'=>now()->addDays(5),
            'max_participants'=>30, 'ticket_price'=>0, 'category'=>'Futsal', 'status'=>'active', 'auto_approve'=>true]);
        return [$owner, $event];
    }
}
