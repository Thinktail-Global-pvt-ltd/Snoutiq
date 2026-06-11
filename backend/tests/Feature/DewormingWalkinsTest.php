<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Prescription;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DewormingWalkinsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->resetSchema();

        parent::tearDown();
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('pet_vaccination_documents');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('users');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('pet_age')->nullable();
            $table->string('pet_age_months')->nullable();
            $table->date('pet_dob')->nullable();
            $table->date('dob')->nullable();
            $table->boolean('deworming_yes_no')->nullable();
            $table->date('last_deworming_date')->nullable();
            $table->string('deworming_status', 50)->nullable();
            $table->date('next_deworming_date')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('pet_vaccination_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->unique();
            $table->binary('document_blob')->nullable();
            $table->string('document_mime', 120)->nullable();
            $table->string('document_name')->nullable();
            $table->unsignedInteger('document_size')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medical_record_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('visit_category', 255)->nullable();
            $table->string('case_severity', 255)->nullable();
            $table->text('visit_notes')->nullable();
            $table->text('doctor_treatment')->nullable();
            $table->text('content_html')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->json('medications_json')->nullable();
            $table->string('deworming', 50)->nullable();
            $table->date('last_deworming_date')->nullable();
            $table->string('deworming_status', 50)->nullable();
            $table->date('next_deworming_date')->nullable();
            
            // Other fields to prevent QueryException during fill/create/update
            $table->float('temperature')->nullable();
            $table->float('weight')->nullable();
            $table->float('heart_rate')->nullable();
            $table->text('exam_notes')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('diagnosis_status')->nullable();
            $table->boolean('is_chronic')->default(false);
            $table->string('disease_name')->nullable();
            $table->text('prognosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('home_care')->nullable();
            $table->text('history_snapshot')->nullable();
            $table->string('video_inclinic')->nullable();
            $table->string('call_session')->nullable();
            $table->boolean('follow_up_required')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('follow_up_type')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->string('system_affected')->nullable();
            $table->unsignedBigInteger('system_affected_id')->nullable();
            $table->string('mucous_membrane')->nullable();
            $table->string('dehydration_level')->nullable();
            $table->string('abdominal_pain_reaction')->nullable();
            $table->string('auscultation')->nullable();
            $table->text('physical_exam_other')->nullable();
            $table->unsignedBigInteger('video_appointment_id')->nullable();
            $table->unsignedBigInteger('in_clinic_appointment_id')->nullable();
            $table->string('vaccination_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('vaccination_date')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    private function assertDateEquals(string $expected, $actual): void
    {
        $this->assertNotNull($actual);
        $this->assertEquals($expected, \Carbon\Carbon::parse($actual)->toDateString());
    }

    public function test_store_medical_record_with_deworming_no(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => true,
            'last_deworming_date' => '2025-06-01',
            'deworming_status' => 'every_3_months',
            'next_deworming_date' => '2025-09-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'no',
            'notes' => 'Some deworming visit notes',
        ]);

        $response->assertStatus(201);

        $prescription = Prescription::where('pet_id', 1357)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('no', $prescription->deworming);
        $this->assertNull($prescription->last_deworming_date);
        $this->assertNull($prescription->deworming_status);
        $this->assertNull($prescription->next_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertFalse((bool)$pet->deworming_yes_no);
        $this->assertNull($pet->last_deworming_date);
        $this->assertNull($pet->deworming_status);
        $this->assertNull($pet->next_deworming_date);
    }

    public function test_store_medical_record_with_deworming_yes(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'yes',
            'last_deworming_date' => '2026-06-01',
            'notes' => 'Deworming completed today',
        ]);

        $response->assertStatus(201);

        $prescription = Prescription::where('pet_id', 1357)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('yes', $prescription->deworming);
        $this->assertDateEquals('2026-06-01', $prescription->last_deworming_date);
        $this->assertEquals('every_3_months', $prescription->deworming_status);
        $this->assertDateEquals('2026-09-01', $prescription->next_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertTrue((bool)$pet->deworming_yes_no);
        $this->assertDateEquals('2026-06-01', $pet->last_deworming_date);
        $this->assertEquals('every_3_months', $pet->deworming_status);
        $this->assertDateEquals('2026-09-01', $pet->next_deworming_date);
    }

    public function test_update_medical_record_with_deworming(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('medical_records')->insert([
            'id' => 99,
            'user_id' => 1479,
            'doctor_id' => 40,
            'vet_registeration_id' => 22,
            'notes' => 'initial visit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'id' => 77,
            'medical_record_id' => 99,
            'user_id' => 1479,
            'doctor_id' => 40,
            'pet_id' => 1357,
            'visit_category' => 'consultation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson('/api/medical-records/99', [
            'clinic_id' => 22,
            'doctor_id' => 40,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'yes',
            'last_deworming_date' => '2026-06-02',
            'notes' => 'updated to deworming',
        ]);

        $response->assertOk();

        $prescription = Prescription::find(77);
        $this->assertNotNull($prescription);
        $this->assertEquals('yes', $prescription->deworming);
        $this->assertDateEquals('2026-06-02', $prescription->last_deworming_date);
        $this->assertEquals('every_3_months', $prescription->deworming_status);
        $this->assertDateEquals('2026-09-02', $prescription->next_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertTrue((bool)$pet->deworming_yes_no);
        $this->assertDateEquals('2026-06-02', $pet->last_deworming_date);
        $this->assertEquals('every_3_months', $pet->deworming_status);
        $this->assertDateEquals('2026-09-02', $pet->next_deworming_date);
    }

    public function test_parse_vaccination_certificate_success(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"vaccination":{"dhppil":{"date":"2026-06-05","next_due":"2026-06-05"}}}'
                        ]]
                    ]
                ]]
            ], 200)
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('cert.png');

        $response = $this->postJson('/api/medical-records/parse-vaccination-certificate', [
            'document' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'vaccination' => [
                        'dhppil' => [
                            'date' => '2026-06-05',
                            'next_due' => '2026-06-05',
                        ]
                    ]
                ]
            ]);
    }

    public function test_store_medical_record_with_vaccination_certificate_json(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'dog_disease_payload' => json_encode([
                'vaccination' => [
                    'rabies' => [
                        'date' => '2025-05-10',
                        'next_due' => '2026-05-10',
                    ]
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'vaccination',
            'notes' => 'Vaccination cert upload test',
            'vaccination_certificate_json' => json_encode([
                'vaccination' => [
                    'dhppil' => [
                        'date' => '2026-06-05',
                        'next_due' => '2026-06-05',
                    ]
                ]
            ]),
        ]);

        $response->assertStatus(201);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $payload = is_string($pet->dog_disease_payload) ? json_decode($pet->dog_disease_payload, true) : $pet->dog_disease_payload;
        
        $this->assertArrayHasKey('vaccination', $payload);
        $this->assertArrayHasKey('rabies', $payload['vaccination']);
        $this->assertArrayHasKey('dhppil', $payload['vaccination']);
        
        $this->assertEquals('2026-06-05', $payload['vaccination']['dhppil'][0]['date']);
        $this->assertEquals('2026-06-05', $payload['vaccination']['dhppil'][0]['next_due']);
        
        $this->assertEquals('2025-05-10', $payload['vaccination']['rabies'][0]['date']);
        $this->assertEquals('2026-05-10', $payload['vaccination']['rabies'][0]['next_due']);
    }

    public function test_store_medical_record_with_multiple_doses_vaccination_certificate_json(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Start with an existing multi-dose array for dhppil (with one dose)
        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'dog_disease_payload' => json_encode([
                'vaccination' => [
                    'dhppil' => [
                        [
                            'date' => '2025-04-10',
                            'next_due' => '2025-05-10',
                        ]
                    ]
                ]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Post multiple doses for dhppil (one duplicate, one newer, one older)
        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'vaccination',
            'notes' => 'Vaccination cert upload test',
            'vaccination_certificate_json' => json_encode([
                'vaccination' => [
                    'dhppil' => [
                        [
                            'date' => '2025-04-10', // duplicate, should be ignored
                            'next_due' => '2025-05-10',
                            'batch_no' => 'B-DUP',
                        ],
                        [
                            'date' => '2026-06-05', // newer, should be sorted last
                            'next_due' => '2027-06-05',
                            'batch_no' => 'B-NEW',
                        ],
                        [
                            'date' => '2024-03-08', // older, should be sorted first
                            'next_due' => '2024-04-08',
                            'batch_no' => 'B-OLD',
                        ]
                    ]
                ]
            ]),
        ]);

        $response->assertStatus(201);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $payload = is_string($pet->dog_disease_payload) ? json_decode($pet->dog_disease_payload, true) : $pet->dog_disease_payload;
        
        $this->assertArrayHasKey('vaccination', $payload);
        $this->assertArrayHasKey('dhppil', $payload['vaccination']);
        
        // Assert that there are exactly 3 doses (duplicate is deduplicated)
        $doses = $payload['vaccination']['dhppil'];
        $this->assertCount(3, $doses);

        // Assert chronological order and batch numbers
        $this->assertEquals('2024-03-08', $doses[0]['date']);
        $this->assertEquals('2024-04-08', $doses[0]['next_due']);
        $this->assertEquals('B-OLD', $doses[0]['batch_no']);

        $this->assertEquals('2025-04-10', $doses[1]['date']);
        $this->assertEquals('2025-05-10', $doses[1]['next_due']);
        $this->assertEquals('B-DUP', $doses[1]['batch_no']);

        $this->assertEquals('2026-06-05', $doses[2]['date']);
        $this->assertEquals('2027-06-05', $doses[2]['next_due']);
        $this->assertEquals('B-NEW', $doses[2]['batch_no']);
    }

    public function test_upload_and_retrieve_vaccination_image_blob(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('vaccination_cert.png');

        // Post request to upload the vaccination document and save its image blob
        $response = $this->postJson('/api/vaccination-records/analyze-document', [
            'pet_id' => 1357,
            'user_id' => 1479,
            'document' => $file,
            'save_vaccination_image' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.document.vaccination_image_blob_saved', true);

        // Verify it was saved in the pet_vaccination_documents table and NOT pets table
        $this->assertDatabaseHas('pet_vaccination_documents', [
            'pet_id' => 1357,
            'document_mime' => 'image/png',
            'document_name' => 'vaccination_cert.png',
        ]);

        // Get the binary blob from the DB and make sure it has the same size
        $doc = DB::table('pet_vaccination_documents')->where('pet_id', 1357)->first();
        $this->assertNotNull($doc);
        $this->assertNotNull($doc->document_blob);

        // Check retrieval endpoint
        $retrieveResponse = $this->get('/api/vaccination-records/pets/1357/image');
        $retrieveResponse->assertStatus(200);
        $retrieveResponse->assertHeader('Content-Type', 'image/png');
        $retrieveResponse->assertHeader('Content-Disposition', 'inline; filename="vaccination_cert.png"');
        $this->assertEquals($doc->document_blob, $retrieveResponse->getContent());

        // Check retrieval endpoint for non-existing pet
        $nonExistingResponse = $this->get('/api/vaccination-records/pets/99999/image');
        $nonExistingResponse->assertStatus(404);

        // Check retrieval endpoint for pet without document
        DB::table('pets')->insert([
            'id' => 8888,
            'user_id' => 1479,
            'name' => 'NoDocPet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $noDocResponse = $this->get('/api/vaccination-records/pets/8888/image');
        $noDocResponse->assertStatus(404);
    }
}
