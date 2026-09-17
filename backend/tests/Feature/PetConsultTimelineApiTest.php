<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PetConsultTimelineApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('pets');

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('pet_name')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_time')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('diagnosis')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('pets');

        parent::tearDown();
    }

    public function test_consult_timeline_includes_multiple_same_pet_bookings_even_when_old_notes_lack_user_id(): void
    {
        DB::table('pets')->insert([
            'id' => 901,
            'user_id' => 701,
            'name' => 'Sheru',
            'created_at' => '2026-09-15 10:00:00',
            'updated_at' => '2026-09-15 10:00:00',
        ]);

        DB::table('appointments')->insert([
            [
                'id' => 1001,
                'pet_id' => 901,
                'doctor_id' => 301,
                'vet_registeration_id' => 201,
                'name' => 'Pet Parent',
                'mobile' => '9000090000',
                'pet_name' => 'Sheru',
                'appointment_date' => '2026-09-16',
                'appointment_time' => '10:30:00',
                'status' => 'confirmed',
                'notes' => json_encode(['text' => 'Old booking symptom without user id']),
                'created_at' => '2026-09-16 08:00:00',
                'updated_at' => '2026-09-16 08:00:00',
            ],
            [
                'id' => 1002,
                'pet_id' => 901,
                'doctor_id' => 302,
                'vet_registeration_id' => 202,
                'name' => 'Pet Parent',
                'mobile' => '9000090000',
                'pet_name' => 'Sheru',
                'appointment_date' => '2026-09-17',
                'appointment_time' => '11:30:00',
                'status' => 'confirmed',
                'notes' => json_encode([
                    'patient_user_id' => 701,
                    'text' => 'New booking symptom with user id',
                ]),
                'created_at' => '2026-09-17 08:00:00',
                'updated_at' => '2026-09-17 08:00:00',
            ],
        ]);

        $response = $this->getJson('/api/pets/consult-timeline?pet_id=901&user_id=701');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.appointments', 2)
            ->assertJsonCount(2, 'data.appointments')
            ->assertJsonCount(2, 'data.timeline')
            ->assertJsonPath('data.appointments.0.notes_decoded.text', 'New booking symptom with user id')
            ->assertJsonPath('data.appointments.1.notes_decoded.text', 'Old booking symptom without user id');
    }

    public function test_consult_timeline_blocks_mismatched_pet_owner(): void
    {
        DB::table('pets')->insert([
            'id' => 902,
            'user_id' => 701,
            'name' => 'Milo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/pets/consult-timeline?pet_id=902&user_id=999')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }
}
