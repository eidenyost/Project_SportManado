<?php

namespace Tests\Feature;

use Tests\TestCase;

class MidtransRouteTest extends TestCase
{
    public function test_notification_route_exists_and_bypasses_csrf(): void
    {
        $response = $this->postJson(route('payments.notification'), [
            'order_id' => 'TEST-123',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'signature_key' => 'invalidsignature',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'midtrans-tx-123'
        ]);

        // Expecting 403 Forbidden because of invalid signature, NOT 419 Token Mismatch
        $response->assertStatus(403);
    }
}
