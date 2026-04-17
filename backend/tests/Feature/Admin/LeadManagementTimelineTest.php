<?php

namespace Tests\Feature\Admin;

use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadManagementTimelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('type')->nullable();
            $table->string('reference')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable()->index();
            $table->string('doctor_name')->nullable();
            $table->boolean('exported_from_excell')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->longText('content_html');
            $table->text('diagnosis')->nullable();
            $table->string('video_inclinic')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('follow_up_type')->nullable();
            $table->timestamps();
        });
    }

    public function test_lead_management_serializes_transactions_and_prescriptions_for_timeline(): void
    {
        $user = User::query()->create([
            'name' => 'Soumita Chakraborty',
            'email' => 'soumita@example.com',
            'phone' => '917896389114',
            'password' => 'secret',
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'amount_paise' => 250000,
            'status' => 'completed',
            'type' => 'video_consult',
            'reference' => 'TXN-TIMELINE-1',
        ]);

        $prescription = Prescription::query()->create([
            'doctor_id' => null,
            'user_id' => $user->id,
            'content_html' => '<p>Prescription content</p>',
            'diagnosis' => 'Otitis',
            'video_inclinic' => 'video',
            'follow_up_type' => 'recheck',
            'follow_up_date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->get(route('admin.lead-management'));

        $response->assertOk();
        $response->assertSee('Soumita Chakraborty');
        $response->assertSee('"related_transactions":[{"id":'.$transaction->id, false);
        $response->assertSee('"related_prescriptions":[{"id":'.$prescription->id, false);
        $response->assertSee('(lead.related_transactions || []).forEach', false);
        $response->assertSee('(lead.related_prescriptions || []).forEach', false);
        $response->assertSee('Prescription added', false);
    }
}
