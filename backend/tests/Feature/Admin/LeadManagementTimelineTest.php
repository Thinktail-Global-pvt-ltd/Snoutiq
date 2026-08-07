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

        Schema::dropIfExists('pets');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('fcm_notifications');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');

        Schema::create('pets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->binary('pet_doc2_blob_new')->nullable();
            $table->binary('pet_doc2_blob')->nullable();
            $table->string('reported_symptom')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('password')->nullable();
            $table->string('google_token')->nullable();
            $table->string('role')->nullable();
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
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable()->index();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_mobile')->nullable();
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

    public function test_lead_management_full_profile_handles_binary_blobs_and_malformed_utf8_safely(): void
    {
        $user = User::query()->create([
            'name' => "Malformed \xB1\xFE Name",
            'email' => 'binaryuser@example.com',
            'phone' => '916262883732',
            'password' => 'secret',
        ]);

        // Insert binary blob data and invalid utf8 bytes into pet table
        DB::table('pets')->insert([
            'user_id' => $user->id,
            'name' => "Ruby \x80\xFF",
            'pet_doc2_blob_new' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01",
            'pet_doc2_blob' => "\x89PNG\x0D\x0A\x1A\x0A\x00\x00\x00\x0DIHDR",
            'reported_symptom' => "Fever \xE2\x28\xA1",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->get(route('admin.lead-management.users.full-profile', ['user' => $user->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'user_id' => $user->id,
        ]);
        
        $jsonContent = $response->getContent();
        $this->assertNotFalse($jsonContent);
        $this->assertStringNotContainsString('Malformed UTF-8', $jsonContent);
    }

    public function test_lead_management_doctor_mobile_and_copy_appointment_message(): void
    {
        $doctor = DB::table('doctors')->insertGetId([
            'doctor_name' => 'Dr. Ramesh Kumar',
            'doctor_mobile' => '9988776655',
            'exported_from_excell' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Safiya Khan',
            'email' => 'safiya@example.com',
            'phone' => '919169965131',
            'password' => 'secret',
        ]);

        $pet = DB::table('pets')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Aslan',
            'reported_symptom' => 'Fever and Cough',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'doctor_id' => $doctor,
            'amount_paise' => 50000,
            'status' => 'captured',
            'type' => 'video_consult',
            'reference' => 'TXN-TIMELINE-DYN',
        ]);

        // Get main lead-management page
        $response = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->get(route('admin.lead-management'));

        $response->assertOk();
        // Check that pets_details array structure exists in output
        $response->assertSee('pets_details', false);
        $response->assertSee('Aslan', false);
        $response->assertSee('Fever and Cough', false);
        $response->assertSee('9988776655', false);

        // Update doctor via API and verify it returns doctor_mobile
        $anotherDoctor = DB::table('doctors')->insertGetId([
            'doctor_name' => 'Dr. Ramesh Kumar Updated',
            'doctor_mobile' => '8877665544',
            'exported_from_excell' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updateResponse = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->postJson(route('admin.transactions.appointments.doctor', ['transaction' => $transaction->id]), [
            'doctor_id' => $anotherDoctor,
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('transaction.doctor_mobile', '8877665544');
    }

    public function test_google_store_user_creates_or_retrieves_user_with_null_defaults(): void
    {
        // 1. Create a brand new user with google-store-user endpoint
        $email = 'googletestuser@example.com';
        $response = $this->postJson('/api/google-store-user', [
            'email'        => $email,
            'name'         => 'Google Test User',
            'google_token' => 'some_token_123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'User stored successfully in users table.',
        ]);

        $userId = $response->json('user_id');
        $this->assertGreaterThan(0, $userId);

        // Fetch user from DB and assert other fields are null
        $user = User::query()->find($userId);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->email);
        $this->assertEquals('Google Test User', $user->name);
        $this->assertEquals('some_token_123', $user->google_token);
        $this->assertNull($user->phone);
        $this->assertNull($user->password);
        $this->assertNull($user->city);

        // 2. Call the endpoint again with the same email and check it retrieves the existing user
        $responseSecond = $this->postJson('/api/google-store-user', [
            'email'        => $email,
            'name'         => 'Google Test User Updated',
            'google_token' => 'some_token_456',
        ]);

        $responseSecond->assertOk();
        $this->assertEquals($userId, $responseSecond->json('user_id'));

        $userUpdated = User::query()->find($userId);
        $this->assertEquals('Google Test User Updated', $userUpdated->name);
        $this->assertEquals('some_token_456', $userUpdated->google_token);
    }
}
