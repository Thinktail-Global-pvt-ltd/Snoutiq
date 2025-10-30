<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_call_creation_persists_session_record(): void
    {
        $response = $this->postJson('/api/call/create', [
            'patient_id' => 1001,
            'doctor_id'  => 2002,
        ]);

        $response->assertStatus(200);

        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('call_sessions', [
            'id'             => $sessionId,
            'patient_id'     => 1001,
            'doctor_id'      => 2002,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_payment_success_creates_payment_and_updates_session(): void
    {
        $createResponse = $this->postJson('/api/call/create', [
            'patient_id' => 412,
        ])->assertStatus(200);

        $sessionId = $createResponse->json('session_id');

        $this->postJson("/api/call/{$sessionId}/payment-success", [
            'payment_id'          => 'pay_test_123',
            'razorpay_order_id'   => 'order_test_456',
            'razorpay_signature'  => 'signature_test_789',
            'amount'              => 49900,
            'currency'            => 'inr',
            'status'              => 'captured',
            'method'              => 'upi',
            'email'               => 'user@example.com',
            'contact'             => '9999999999',
        ])->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'razorpay_payment_id' => 'pay_test_123',
            'razorpay_order_id'   => 'order_test_456',
            'razorpay_signature'  => 'signature_test_789',
            'amount'              => 49900,
            'currency'            => 'INR',
            'status'              => 'captured',
            'method'              => 'upi',
        ]);

        $this->assertDatabaseHas('call_sessions', [
            'id'             => $sessionId,
            'payment_status' => 'paid',
            'amount_paid'    => 49900,
            'currency'       => 'INR',
        ]);

        $payment = Payment::where('razorpay_payment_id', 'pay_test_123')->first();
        $this->assertNotNull($payment);
        $this->assertEquals((string) $sessionId, data_get($payment->notes, 'call_session_id'));
    }
}
