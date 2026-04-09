<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorEarningsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    public function test_excell_export_transactions_returns_actual_earnings_breakdown(): void
    {
        $clinicId = 501;
        $doctorId = 701;

        $this->createClinic($clinicId);
        $this->createDoctor($doctorId, $clinicId);

        $this->createTransaction($clinicId, $doctorId, 'TXN-A', 120000, 100000);
        $this->createTransaction($clinicId, $doctorId, 'TXN-B', 240000, 200000);
        $this->createTransaction($clinicId, $doctorId, 'TXN-IGNORE', 50000, 40000, 'subscription');

        $response = $this->getJson("/api/excell-export/transactions?doctor_id={$doctorId}&clinic_id={$clinicId}");

        $response->assertOk()
            ->assertJsonPath('total_transactions', 2)
            ->assertJsonPath('total_current_payment_inr', 3000)
            ->assertJsonPath('total_gst_deduction_inr', 540)
            ->assertJsonPath('total_flat_deduction_inr', 300)
            ->assertJsonPath('actual_earnings_inr', 2160)
            ->assertJsonPath('total_amount_after_deduction_inr', 2160);

        $transactions = collect($response->json('transactions'))->keyBy('payment_to_doctor_inr');

        $this->assertSame(1000, $transactions[1000]['payment_to_doctor_inr']);
        $this->assertSame(180, $transactions[1000]['gst_deduction_inr']);
        $this->assertSame(150, $transactions[1000]['flat_deduction_inr']);
        $this->assertSame(670, $transactions[1000]['actual_earnings_inr']);

        $this->assertSame(2000, $transactions[2000]['payment_to_doctor_inr']);
        $this->assertSame(360, $transactions[2000]['gst_deduction_inr']);
        $this->assertSame(150, $transactions[2000]['flat_deduction_inr']);
        $this->assertSame(1490, $transactions[2000]['actual_earnings_inr']);
    }

    public function test_clinic_financials_includes_actual_earnings_breakdown(): void
    {
        $clinicId = 601;
        $doctorId = 801;

        $this->createClinic($clinicId);
        $this->createDoctor($doctorId, $clinicId);

        $this->createTransaction($clinicId, $doctorId, 'FIN-A', 120000, 100000);
        $this->createTransaction($clinicId, $doctorId, 'FIN-B', 240000, 200000);

        $response = $this->getJson("/api/financials?clinic_id={$clinicId}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('kpi.current_payment', 3000)
            ->assertJsonPath('kpi.gst_deduction', 540)
            ->assertJsonPath('kpi.flat_deduction', 300)
            ->assertJsonPath('kpi.actual_earnings', 2160)
            ->assertJsonPath('earnings_rules.gst_rate', 0.18)
            ->assertJsonPath('earnings_rules.flat_deduction_inr', 150);

        $transactions = collect($response->json('transactions'))->keyBy('current_payment');

        $this->assertSame(1000, $transactions[1000]['current_payment']);
        $this->assertSame(180, $transactions[1000]['gst_deduction']);
        $this->assertSame(150, $transactions[1000]['flat_deduction']);
        $this->assertSame(670, $transactions[1000]['actual_earnings']);

        $this->assertSame(2000, $transactions[2000]['current_payment']);
        $this->assertSame(360, $transactions[2000]['gst_deduction']);
        $this->assertSame(150, $transactions[2000]['flat_deduction']);
        $this->assertSame(1490, $transactions[2000]['actual_earnings']);
    }

    private function createClinic(int $id): void
    {
        DB::table('vet_registerations_temp')->insert([
            'id' => $id,
            'name' => 'Clinic '.$id,
            'city' => 'Mumbai',
            'pincode' => '400001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDoctor(int $id, int $clinicId): void
    {
        DB::table('doctors')->insert([
            'id' => $id,
            'vet_registeration_id' => $clinicId,
            'doctor_name' => 'Doctor '.$id,
            'doctor_email' => "doctor{$id}@example.test",
            'doctor_mobile' => '9000012345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTransaction(
        int $clinicId,
        int $doctorId,
        string $reference,
        int $grossPaise,
        int $currentPaymentPaise,
        string $type = 'video_consult'
    ): void {
        Transaction::query()->create([
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId,
            'amount_paise' => $grossPaise,
            'payment_to_doctor_paise' => $currentPaymentPaise,
            'status' => 'completed',
            'type' => $type,
            'payment_method' => 'UPI',
            'reference' => $reference,
            'metadata' => [
                'order_type' => $type,
            ],
        ]);
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('vet_registerations_temp');
    }

    private function createSchema(): void
    {
        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('license_no')->nullable();
            $table->text('address')->nullable();
            $table->text('formatted_address')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->string('doctor_name');
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_license')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->unsignedBigInteger('payment_to_doctor_paise')->nullable();
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
