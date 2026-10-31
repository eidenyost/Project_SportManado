<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Notification;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Stage1OrganizerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function account(?string $status = 'approved', string $role = 'organizer'): User
    {
        $user = User::factory()->create(['role' => $role]);
        if ($status !== null) {
            Organizer::create([
                'user_id' => $user->id,
                'organization_name' => 'Stage 1 Organizer',
                'phone' => '081234567890',
                'status' => $status,
                'admin_notes' => $status === 'rejected' ? 'Documents unclear' : null,
                'ktp_path' => 'organizer-ktps/existing-ktp.jpg',
                'selfie_path' => 'organizer-ktps/existing-selfie.jpg',
            ]);
        }

        return $user;
    }

    private function resources(User $owner): array
    {
        $event = Event::create([
            'organizer_id' => $owner->organizer->id,
            'title' => 'Stage 1 Protected Event',
            'description' => 'Authorization fixture',
            'location' => 'Manado',
            'event_date' => now()->addDays(10),
            'registration_deadline' => now()->addDays(5),
            'max_participants' => 20,
            'ticket_price' => 0,
            'category' => 'Futsal',
            'status' => 'active',
        ]);
        $ticket = Ticket::create([
            'user_id' => User::factory()->create()->id,
            'event_id' => $event->id,
            'ticket_code' => 'STAGE1-PROTECTED',
            'status' => 'pending',
        ]);
        $notification = Notification::create([
            'user_id' => $owner->id,
            'type' => 'event',
            'title' => 'Protected notification',
            'message' => 'Must remain unread',
            'is_read' => false,
        ]);

        return ['event' => $event->id, 'ticket' => $ticket->id, 'notification' => $notification->id];
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['events', 'tickets', 'notifications', 'payments', 'withdrawal_requests', 'organizers'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $snapshot;
    }

    public function test_auth01_guests_cannot_access_organizer_operations(): void
    {
        $this->get(route('organizer.events.index'))->assertRedirect(route('login'));
        $this->postJson(route('organizer.events.store'), [])->assertUnauthorized();
    }

    public function test_auth02_regular_user_with_approved_profile_does_not_gain_organizer_role(): void
    {
        $this->actingAs($this->account('approved', 'user'));
        $this->get(route('organizer.events.index'))->assertForbidden();
        $this->postJson(route('organizer.events.store'), [])->assertForbidden();
    }

    public function test_auth03_admin_does_not_bypass_organizer_role(): void
    {
        $this->actingAs($this->account(null, 'admin'));
        $this->get(route('organizer.events.index'))->assertForbidden();
    }

    public function test_auth04_pending_dashboard_redirects_to_accessible_pending_page(): void
    {
        $this->actingAs($this->account('pending'));
        $this->get(route('organizer.dashboard'))->assertRedirect(route('organizer.pending'));
        $this->get(route('dashboard'))->assertRedirect(route('organizer.pending'));
        $this->get(route('organizer.pending'))->assertOk();
    }

    public function test_auth05_rejected_dashboard_redirects_to_accessible_setup(): void
    {
        $this->actingAs($this->account('rejected'));
        $this->get(route('organizer.dashboard'))->assertRedirect(route('organizer.setup'));
        $this->get(route('dashboard'))->assertRedirect(route('organizer.setup'));
        $this->get(route('organizer.setup'))->assertOk();
    }

    public function test_auth06_approved_organizer_retains_dashboard_and_operation_access(): void
    {
        $this->actingAs($this->account());
        $this->get(route('organizer.dashboard'))->assertOk();
        $this->get(route('organizer.events.index'))->assertOk();
        $this->getJson(route('organizer.notifications.unread-count'))->assertOk()->assertJsonPath('count', 0);
    }

    public function test_auth07_missing_profile_redirect_has_no_setup_loop_and_store_reaches_validation(): void
    {
        $this->actingAs($this->account(null));
        $this->get(route('dashboard'))->assertRedirect(route('organizer.setup'));
        $this->get(route('organizer.dashboard'))->assertRedirect(route('organizer.setup'));
        $this->get(route('organizer.setup'))->assertOk();
        $this->postJson(route('organizer.store-setup'), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_name', 'phone', 'ktp', 'selfie']);
    }

    public function test_auth08_pending_setup_and_store_remain_status_exempt(): void
    {
        $this->actingAs($this->account('pending'));
        $this->get(route('organizer.setup'))->assertRedirect(route('organizer.pending'));
        $this->post(route('organizer.store-setup'), [])->assertRedirect(route('organizer.pending'));
        $this->get(route('organizer.pending'))->assertOk();
    }

    public function test_auth09_rejected_profile_can_resubmit_existing_documents_for_review(): void
    {
        $user = $this->account('rejected');
        $this->actingAs($user);
        $this->post(route('organizer.store-setup'), [
            'organization_name' => 'Corrected organizer',
            'phone' => '081234567890',
            'description' => 'Corrected application',
        ])->assertRedirect(route('organizer.pending'));
        $this->assertDatabaseHas('organizers', [
            'user_id' => $user->id,
            'organization_name' => 'Corrected organizer',
            'status' => 'pending',
            'admin_notes' => null,
        ]);
        $this->get(route('organizer.pending'))->assertOk();
    }

    public function test_auth10_pending_rejected_and_missing_profile_accounts_can_logout(): void
    {
        foreach (['pending', 'rejected', null] as $status) {
            $this->actingAs($this->account($status));
            $this->post(route('logout'))->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_auth11_approved_organizer_cannot_access_or_mutate_foreign_resources(): void
    {
        $owner = $this->account();
        $parameters = $this->resources($owner);
        $this->actingAs($this->account());
        $before = $this->snapshot();
        foreach ([
            ['GET', 'organizer.events.edit'],
            ['PATCH', 'organizer.events.update'],
            ['DELETE', 'organizer.events.destroy'],
            ['GET', 'organizer.events.participants'],
            ['GET', 'organizer.events.participants.export'],
            ['GET', 'organizer.events.manage'],
            ['POST', 'organizer.events.participants.approve'],
            ['POST', 'organizer.events.participants.reject'],
            ['GET', 'organizer.events.scan'],
            ['POST', 'organizer.events.validate-qr'],
            ['GET', 'organizer.finances.finance'],
            ['GET', 'organizer.finances.export'],
            ['GET', 'organizer.finances.withdraw.page'],
            ['POST', 'organizer.finances.withdraw'],
            ['POST', 'organizer.notifications.read'],
        ] as [$method, $name]) {
            $this->json($method, route($name, $parameters), [
                'ticket_code' => 'STAGE1-PROTECTED',
                'qr_data' => json_encode(['event_id' => $parameters['event'], 'ticket_code' => 'STAGE1-PROTECTED']),
            ])->assertForbidden();
        }
        $this->assertSame($before, $this->snapshot(), 'Foreign resources must not change.');
    }

    public function test_auth12_missing_profile_operations_are_forbidden_not_redirected_or_crashed(): void
    {
        $this->actingAs($this->account(null));
        $this->get(route('organizer.events.index'))->assertForbidden();
        $this->postJson(route('organizer.events.store'), [])->assertForbidden();
        $this->getJson(route('organizer.notifications.unread-count'))->assertForbidden();
    }

    public static function blockedOperations(): iterable
    {
        $operations = [
            ['GET', 'organizer.events.index'],
            ['GET', 'organizer.events.create'],
            ['POST', 'organizer.events.store'],
            ['GET', 'organizer.events.edit'],
            ['PATCH', 'organizer.events.update'],
            ['DELETE', 'organizer.events.destroy'],
            ['GET', 'organizer.events.participants'],
            ['GET', 'organizer.events.participants.export'],
            ['POST', 'organizer.events.participants.approve'],
            ['POST', 'organizer.events.participants.reject'],
            ['GET', 'organizer.events.manage'],
            ['GET', 'organizer.events.scan'],
            ['POST', 'organizer.events.validate-qr'],
            ['GET', 'organizer.finances.finance'],
            ['GET', 'organizer.finances.export'],
            ['GET', 'organizer.finances.withdraw.page'],
            ['POST', 'organizer.finances.withdraw'],
            ['GET', 'organizer.notifications.index'],
            ['POST', 'organizer.notifications.read'],
            ['POST', 'organizer.notifications.read-all'],
            ['GET', 'organizer.notifications.unread-count'],
        ];
        foreach (['pending', 'rejected'] as $status) {
            foreach (['html', 'json'] as $format) {
                foreach ($operations as [$method, $name]) {
                    yield "$status $format $method $name" => [$status, $format, $method, $name];
                }
            }
        }
    }

    #[DataProvider('blockedOperations')]
    public function test_all_sensitive_operations_require_approved_profile_before_mutation(
        string $status, string $format, string $method, string $name
    ): void {
        $user = $this->account($status);
        $parameters = $this->resources($user);
        $this->actingAs($user);
        $before = $this->snapshot();
        $payload = [
            'title' => 'Unauthorized mutation',
            'amount' => 1,
            'bank_name' => 'Unauthorized bank',
            'account_number' => '1234567890',
            'account_name' => 'Unauthorized holder',
            'ticket_code' => 'STAGE1-PROTECTED',
                'qr_data' => json_encode(['event_id' => $parameters['event'], 'ticket_code' => 'STAGE1-PROTECTED']),
        ];
        $url = route($name, $parameters);
        $response = $format === 'json'
            ? $this->json($method, $url, $payload)
            : $this->call($method, $url, $payload);

        $response->assertForbidden();
        if ($format === 'json') {
            $response->assertHeader('content-type', 'application/json');
        }
        $this->assertSame($before, $this->snapshot(), "$name must not mutate application state.");
    }
}
