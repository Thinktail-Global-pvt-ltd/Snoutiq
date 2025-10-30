<?php

namespace Tests\Feature;

use App\Models\CallSession;
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
        $this->assertNotEmpty($response->json('call_identifier'));
        $this->assertNull($response->json('doctor_join_url'));
        $this->assertNull($response->json('patient_payment_url'));

        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('call_sessions', [
            'id'             => $sessionId,
            'patient_id'     => 1001,
            'doctor_id'      => 2002,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $session = CallSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->call_identifier);
        $this->assertNull($session->doctor_join_url);
        $this->assertNull($session->patient_payment_url);
    }

    public function test_legacy_request_call_endpoint_creates_session_record(): void
    {
        $response = $this->postJson('/api/call/request', [
            'patient_id' => 321,
            'doctor_id'  => 654,
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.call_id'));
        $this->assertNull($response->json('data.doctor_join_url'));
        $this->assertNull($response->json('data.patient_payment_url'));

        $sessionId = $response->json('data.session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('call_sessions', [
            'id'             => $sessionId,
            'patient_id'     => 321,
            'doctor_id'      => 654,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $session = CallSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->call_identifier);
        $this->assertNull($session->doctor_join_url);
        $this->assertNull($session->patient_payment_url);
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

        $session = CallSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->call_identifier);
    }
}
