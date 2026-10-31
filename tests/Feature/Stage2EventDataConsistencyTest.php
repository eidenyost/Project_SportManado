<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Stage2EventDataConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;
    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->organizerUser = User::factory()->create([
            'role' => 'organizer',
            'email_verified_at' => now(),
        ]);
        $this->organizer = Organizer::create([
            'user_id' => $this->organizerUser->id,
            'organization_name' => 'Stage 2 Organizer',
            'phone' => '08123456789',
            'status' => 'approved',
        ]);
        $this->participant = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

    public function test_create_rejects_fractional_ticket_price_without_creating_an_event(): void
    {
        $response = $this->actingAs($this->organizerUser)->post(route('organizer.events.store'),
            $this->eventPayload(['ticket_price' => '75000.50']));

        $response->assertSessionHasErrors('ticket_price');
        $this->assertDatabaseCount('events', 0);
    }

    public function test_create_accepts_whole_rupiah_price(): void
    {
        $response = $this->actingAs($this->organizerUser)->post(route('organizer.events.store'),
            $this->eventPayload(['ticket_price' => 75000]));

        $response->assertRedirect(route('organizer.dashboard'));
        $this->assertDatabaseHas('events', ['organizer_id' => $this->organizer->id, 'ticket_price' => 75000]);
    }

    public function test_update_rejects_fractional_price_without_mutating_the_event(): void
    {
        $event = $this->event();
        $before = $event->fresh()->getRawOriginal();

        $response = $this->actingAs($this->organizerUser)->patch(route('organizer.events.update', $event),
            $this->updatePayload($event, ['ticket_price' => '75000.50']));

        $response->assertSessionHasErrors('ticket_price');
        $this->assertSame($before, $event->fresh()->getRawOriginal());
    }

    public function test_update_persists_auto_approve_and_custom_fields(): void
    {
        $event = $this->event(['auto_approve' => true]);

        $response = $this->actingAs($this->organizerUser)->patch(route('organizer.events.update', $event),
            $this->updatePayload($event, [
                'auto_approve' => '0',
                'custom_fields' => [[
                    'label' => 'Ukuran Jersey',
                    'type' => 'radio',
                    'options' => 'S, M, L',
                    'required' => '1',
                ]],
            ]));

        $response->assertRedirect(route('organizer.dashboard'));
        $event->refresh();
        $this->assertFalse($event->auto_approve);
        $this->assertSame([['label' => 'Ukuran Jersey', 'type' => 'radio', 'options' => ['S', 'M', 'L'], 'required' => true, 'image' => null]], $event->custom_fields);
    }

    public function test_update_rejects_duplicate_custom_field_labels_without_mutating_the_event(): void
    {
        $event = $this->event();
        $before = $event->fresh()->getRawOriginal();

        $response = $this->actingAs($this->organizerUser)->patch(route('organizer.events.update', $event),
            $this->updatePayload($event, [
                'custom_fields' => [
                    ['label' => 'Nomor Darurat', 'type' => 'text', 'required' => '1'],
                    ['label' => ' nomor darurat ', 'type' => 'text', 'required' => '1'],
                ],
            ]));

        $response->assertSessionHasErrors('custom_fields.1.label');
        $this->assertSame($before, $event->fresh()->getRawOriginal());
    }

    public function test_array_dynamic_answer_is_a_validation_error_and_creates_no_ticket(): void
    {
        $event = $this->event(['status' => 'active', 'custom_fields' => [[
            'label' => 'Nomor Darurat', 'type' => 'text', 'required' => true,
        ]]]);

        $response = $this->actingAs($this->participant)->post(route('tickets.submit-payment', $event), [
            'custom_answers' => ['Nomor Darurat' => ['0812']],
        ]);

        $response->assertSessionHasErrors('custom_answers.0');
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_dynamic_answers_reject_unknown_values_and_accept_an_empty_optional_value(): void
    {
        $event = $this->event(['status' => 'active', 'custom_fields' => [[
            'label' => 'Catatan', 'type' => 'textarea', 'required' => false,
        ]]]);

        $this->actingAs($this->participant)->post(route('tickets.submit-payment', $event), [
            'custom_answers' => ['Catatan' => '', 'Tidak Dikenal' => 'x'],
        ])->assertSessionHasErrors('custom_answers');
        $this->assertDatabaseCount('tickets', 0);

        $response = $this->actingAs($this->participant)->post(route('tickets.submit-payment', $event), [
            'custom_answers' => ['Catatan' => ''],
        ]);
        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame([], $ticket->custom_answers);
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_replace([
            'organizer_id' => $this->organizer->id,
            'title' => 'Stage 2 Event',
            'description' => 'Event description',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'draft',
            'auto_approve' => true,
        ], $overrides));
    }

    private function eventPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'New Stage 2 Event',
            'description' => 'Event description',
            'location' => 'Manado',
            'event_date' => now()->addDays(10)->format('Y-m-d H:i'),
            'registration_deadline' => now()->addDays(5)->format('Y-m-d H:i'),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'auto_approve' => '1',
            'proposal' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'permit' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
            'ktp_image' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
        ], $overrides);
    }

    private function updatePayload(Event $event, array $overrides = []): array
    {
        return array_replace([
            'title' => $event->title,
            'description' => $event->description,
            'location' => $event->location,
            'event_date' => $event->event_date->format('Y-m-d H:i'),
            'registration_deadline' => $event->registration_deadline->format('Y-m-d H:i'),
            'max_participants' => $event->max_participants,
            'ticket_price' => (int) $event->ticket_price,
            'category' => $event->category,
            'status' => $event->status,
            'auto_approve' => $event->auto_approve ? '1' : '0',
        ], $overrides);
    }
}
