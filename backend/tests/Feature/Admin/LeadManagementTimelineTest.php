<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\Pet;
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
        Schema::dropIfExists('otps');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('doctor_video_availability');
        Schema::dropIfExists('groomer_services');

        Schema::create('groomer_services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('main_service')->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('place_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('user_ratings_total')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_video_availability', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->integer('day_of_week');
            $table->integer('is_active')->default(0);
            $table->timestamps();
        });

        Schema::create('otps', function (Blueprint $table): void {
            $table->id();
            $table->string("token");
            $table->string("type");
            $table->string("value");
            $table->string('otp');
            $table->integer("is_verified")->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_type')->nullable();
            $table->string('type')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('gender')->nullable();
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
            $table->unsignedBigInteger('last_vet_id')->nullable()->index();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
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
        // 1. Create a brand new user along with a pet
        $email = 'googletestuser@example.com';
        $response = $this->postJson('/api/google-store-user', [
            'email'             => $email,
            'name'              => 'Google Test User',
            'google_token'      => 'some_token_123',
            'pet_name'          => 'Simba',
            'pet_breed'         => 'Persian Cat',
            'pet_type'          => 'cat',
            'pet_gender'        => 'male',
            'reported_symptom'  => 'Sneezing',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'User and pet details stored successfully.',
        ]);
        $this->assertNotNull($response->json('pet_id'));
        $this->assertNotNull($response->json('pet'));
        $this->assertIsArray($response->json('pets'));
        $this->assertIsArray($response->json('user_pets'));

        $userId = $response->json('user_id');
        $petId = $response->json('pet_id');
        $this->assertGreaterThan(0, $userId);
        $this->assertGreaterThan(0, $petId);

        // Fetch user from DB and assert other fields are null
        $user = User::query()->find($userId);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->email);
        $this->assertEquals('Google Test User', $user->name);
        $this->assertNull($user->phone);
        $this->assertNull($user->password);
        $this->assertNull($user->city);

        // Fetch pet from DB and verify values
        $pet = Pet::query()->find($petId);
        $this->assertNotNull($pet);
        $this->assertEquals('Simba', $pet->name);
        $this->assertEquals('Persian Cat', $pet->breed);
        $this->assertEquals('cat', $pet->pet_type);
        $this->assertEquals('male', $pet->pet_gender);
        $this->assertEquals('Sneezing', $pet->reported_symptom);

        // 2. Call the endpoint again with updated pet details
        $responseSecond = $this->postJson('/api/google-store-user', [
            'email'             => $email,
            'name'              => 'Google Test User Updated',
            'google_token'      => 'some_token_456',
            'pet_name'          => 'Simba', // same name to retrieve and update Simba
            'pet_breed'         => 'Persian Cat Updated',
            'pet_gender'        => 'female',
        ]);

        $responseSecond->assertOk();
        $this->assertEquals($userId, $responseSecond->json('user_id'));
        $this->assertEquals($petId, $responseSecond->json('pet_id'));

        $userUpdated = User::query()->find($userId);
        $this->assertEquals('Google Test User Updated', $userUpdated->name);

        $petUpdated = Pet::query()->find($petId);
        $this->assertEquals('Persian Cat Updated', $petUpdated->breed);
        $this->assertEquals('female', $petUpdated->pet_gender);
    }

    public function test_google_merge_user_when_phone_exists(): void
    {
        // 1. Create a Phone User (B) in DB with a +91 prefix phone and a pet
        $phoneUser = User::query()->create([
            'phone' => '+919988776655',
            'name'  => 'Old Phone User',
            'email' => null,
        ]);
        $phonePet = Pet::query()->create([
            'user_id'          => $phoneUser->id,
            'name'             => 'Simba',
            'breed'            => 'Persian Cat',
            'reported_symptom' => 'Lethargy',
        ]);

        // 2. Create a Google User (A) (simulate googleStoreUser)
        $googleUser = User::query()->create([
            'email'        => 'newgoogle@example.com',
            'name'         => 'New Google User',
            'google_token' => 'google_token_123',
        ]);
        $googlePet = Pet::query()->create([
            'user_id' => $googleUser->id,
            'name'    => 'Simba',
            'breed'   => 'Persian Cat',
        ]);

        // Create matching OTP in DB
        DB::table('otps')->insert([
            'token'      => 'token_match_xyz',
            'type'       => 'whatsapp',
            'value'      => '919988776655',
            'otp'        => '1122',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Call merge API with matching name and breed -> symptom should update, Google user & pet deleted
        // Note: we pass ONLY name, phone, otp and token to verify session lookup
        $responseMatch = $this->withSession([
            'user_id' => $googleUser->id,
        ])->postJson('/api/google-merge-user', [
            'phone'            => '9988776655',
            'name'             => 'Old Phone User Updated',
            'google_token'     => 'google_token_123_updated',
            'otp'              => '1122',
            'token'            => 'token_match_xyz',
            'pet_name'         => 'Simba',
            'pet_breed'        => 'Persian Cat',
            'reported_symptom' => 'Sneezing',
        ]);

        $responseMatch->assertOk();
        $responseMatch->assertJsonPath('success', true);
        $responseMatch->assertJsonPath('message', 'Data merged successfully. Temporary Google user deleted.');
        $this->assertNotNull($responseMatch->json('pet_id'));
        $this->assertIsArray($responseMatch->json('pets'));
        $this->assertIsArray($responseMatch->json('user_pets'));

        // Assert Phone User now has email, name, and google_token
        $phoneUserMerged = User::query()->find($phoneUser->id);
        $this->assertEquals('newgoogle@example.com', $phoneUserMerged->email);
        $this->assertEquals('google_token_123_updated', $phoneUserMerged->google_token);
        $this->assertEquals('Old Phone User Updated', $phoneUserMerged->name);

        // Assert Google User A has been deleted
        $this->assertNull(User::query()->find($googleUser->id));
        $this->assertNull(Pet::query()->find($googlePet->id));

        // Assert Phone User's Simba pet reported symptom updated to 'Sneezing'
        $simbaPet = Pet::query()->find($phonePet->id);
        $this->assertEquals('Sneezing', $simbaPet->reported_symptom);

        // Create new OTP for the second call
        DB::table('otps')->insert([
            'token'      => 'token_new_xyz',
            'type'       => 'whatsapp',
            'value'      => '919988776655',
            'otp'        => '3344',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Call merge API with non-matching breed -> should insert a new pet
        $responseNewPet = $this->withSession([
            'user_id' => $phoneUserMerged->id, // logged in as merged user now
        ])->postJson('/api/google-merge-user', [
            'phone'      => '9988776655',
            'name'       => 'Old Phone User Updated',
            'otp'        => '3344',
            'token'      => 'token_new_xyz',
            'pet_name'   => 'Oscar',
            'pet_breed'  => 'German Shepherd',
            'pet_type'   => 'dog',
        ]);

        $responseNewPet->assertOk();
        
        // Assert a new pet was created for the phone user
        $oscarPet = Pet::query()->where('user_id', $phoneUser->id)->where('name', 'Oscar')->first();
        $this->assertNotNull($oscarPet);
        $this->assertEquals('German Shepherd', $oscarPet->breed);
        $this->assertEquals('dog', $oscarPet->pet_type);
    }

    public function test_exported_from_excell_doctors_rating_and_sorting(): void
    {
        // 1. Setup doctor video availability table
        if (Schema::hasTable('doctor_video_availability')) {
            DB::table('doctor_video_availability')->truncate();
        }

        // Create 2 clinics
        $clinicFar = DB::table('vet_registerations_temp')->insertGetId([
            'name' => 'Far Clinic',
            'address' => 'Far Address',
            'lat' => 28.6139, // Delhi
            'lng' => 77.2090,
            'rating' => 4.5,
            'user_ratings_total' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clinicNear = DB::table('vet_registerations_temp')->insertGetId([
            'name' => 'Near Clinic',
            'address' => 'Near Address',
            'lat' => 26.2185, // Close to Gwalior (26.2181)
            'lng' => 78.2246,
            'rating' => 4.9,
            'user_ratings_total' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create 2 Doctors
        $doctorFarId = DB::table('doctors')->insertGetId([
            'doctor_name' => 'Dr. Far',
            'vet_registeration_id' => $clinicFar,
            'exported_from_excell' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctorNearId = DB::table('doctors')->insertGetId([
            'doctor_name' => 'Dr. Near',
            'vet_registeration_id' => $clinicNear,
            'exported_from_excell' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentDayOfWeek = (int) \Illuminate\Support\Carbon::now('Asia/Kolkata')->dayOfWeek;
        if (Schema::hasTable('doctor_video_availability')) {
            DB::table('doctor_video_availability')->insert([
                ['doctor_id' => $doctorFarId, 'day_of_week' => $currentDayOfWeek, 'is_active' => 1],
                ['doctor_id' => $doctorNearId, 'day_of_week' => $currentDayOfWeek, 'is_active' => 1],
            ]);
        }

        // Hit route without coordinates
        $responseNoCoords = $this->getJson('/api/exported_from_excell_doctors');
        $responseNoCoords->assertOk();
        $dataNoCoords = $responseNoCoords->json('data');

        // Locate far doctor entry
        $farDoctorResult = collect($dataNoCoords)->firstWhere('id', $doctorFarId);
        $this->assertEquals('Far Clinic', $farDoctorResult['clinic_name']);
        $this->assertEquals(4.5, $farDoctorResult['google_rating']);
        $this->assertEquals(120, $farDoctorResult['google_user_ratings_total']);

        // Hit route with Gwalior coordinates -> Near Doctor should sort first
        $responseWithCoords = $this->getJson('/api/exported_from_excell_doctors?lat=26.2181&lng=78.2245');
        $responseWithCoords->assertOk();
        $dataWithCoords = $responseWithCoords->json('data');

        $this->assertEquals($doctorNearId, $dataWithCoords[0]['id']); // Near Doctor is first
        $this->assertEquals($doctorFarId, $dataWithCoords[1]['id']); // Far Doctor is second
        $this->assertLessThan($dataWithCoords[1]['distance_km'], $dataWithCoords[0]['distance_km']);
    }

    public function test_last_vet_details_and_inclinic_lists_ratings(): void
    {
        // Create user with last_vet_id
        $clinicId = DB::table('vet_registerations_temp')->insertGetId([
            'name' => 'Review Clinic',
            'address' => 'Gwalior Center',
            'lat' => 26.2181,
            'lng' => 78.2245,
            'rating' => 4.7,
            'user_ratings_total' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Pet Parent',
            'email' => 'parent@example.com',
            'last_vet_id' => $clinicId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Hit /users/last-vet-details
        $responseLastVet = $this->getJson("/api/users/last-vet-details?user_id={$userId}");
        $responseLastVet->assertOk();
        $this->assertEquals(4.7, $responseLastVet->json('data.clinic.google_rating'));
        $this->assertEquals(99, $responseLastVet->json('data.clinic.google_user_ratings_total'));

        // Seed doctor for this clinic to show in /inclinic-lists-new-after-10th-may-registerations
        DB::table('doctors')->insert([
            'vet_registeration_id' => $clinicId,
            'doctor_name' => 'Dr. Reviewer',
            'exported_from_excell' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Hit /inclinic-lists-new-after-10th-may-registerations
        $responseInClinic = $this->getJson('/api/inclinic-lists-new-after-10th-may-registerations?from_date=2026-05-10');
        $responseInClinic->assertOk();
        
        $clinicResult = collect($responseInClinic->json('data'))->firstWhere('id', $clinicId);
        $this->assertNotNull($clinicResult);
        $this->assertEquals(4.7, $clinicResult['google_rating']);
        $this->assertEquals(99, $clinicResult['google_user_ratings_total']);
    }
}
