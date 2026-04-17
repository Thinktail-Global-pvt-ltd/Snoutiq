<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadManagementTimelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('fcm_notifications');
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

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->string('type')->nullable();
            $table->text('title')->nullable();
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('channel')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fcm_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('data_payload')->nullable();
            $table->string('call_session')->nullable();
            $table->string('notification_type')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->text('title')->nullable();
            $table->text('notification_text')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('clicked')->default(false);
            $table->timestamp('clicked_at')->nullable();
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

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'vaccination_milestone',
            'title' => 'Vaccination Milestone Test',
            'body' => 'Vaccination due soon.',
            'payload' => [
                'type' => 'vaccination_milestone',
                'pet_name' => 'Ulu',
            ],
            'status' => 'sent',
            'sent_at' => now()->subHour(),
        ]);

        DB::table('fcm_notifications')->insert([
            'user_id' => $user->id,
            'data_payload' => json_encode([
                'type' => 'pet_neutering_reminder',
                'pet_id' => '42',
                'pet_name' => 'Ulu',
            ]),
            'call_session' => null,
            'notification_type' => 'pet_neutering_reminder',
            'status' => 'sent',
            'title' => 'Neutering Reminder Test',
            'notification_text' => 'Please schedule the neutering consultation.',
            'sent_at' => now()->subMinutes(40),
            'clicked' => false,
            'clicked_at' => null,
            'created_at' => now()->subMinutes(40),
            'updated_at' => now()->subMinutes(40),
        ]);

        DB::table('fcm_notifications')->insert([
            'user_id' => $user->id,
            'data_payload' => json_encode([
                'type' => 'custom_admin_test',
            ]),
            'call_session' => null,
            'notification_type' => 'custom_admin_test',
            'status' => 'sent',
            'title' => 'Custom Admin Test',
            'notification_text' => 'Raw FCM log should be visible in admin panel.',
            'sent_at' => now()->subMinutes(20),
            'clicked' => true,
            'clicked_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(10),
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
        $response->assertSee('"title":"Vaccination Milestone Test"', false);
        $response->assertSee('"title":"Neutering Reminder Test"', false);
        $response->assertSee('"title":"Custom Admin Test"', false);
        $response->assertSee('(lead.related_transactions || []).forEach', false);
        $response->assertSee('(lead.related_prescriptions || []).forEach', false);
        $response->assertDontSee('Notification sent', false);
        $response->assertDontSee('Action logged from CRM panel.', false);
        $response->assertDontSee('Next action saved from CRM panel.', false);
        $response->assertSee('Prescription added', false);
        $response->assertSee((string) $notification->id);
    }
}
