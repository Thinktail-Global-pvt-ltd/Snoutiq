<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SymptomTriageRoutingTest extends TestCase
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

    public function test_blood_input_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'blood',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today')
            ->assertJsonPath('ui.theme', 'clinic')
            ->assertJsonPath('buttons.primary.label', 'Book Clinic Appointment')
            ->assertJsonPath('buttons.primary.deeplink', 'snoutiq://clinic-booking');

        $cards = $response->json('ui.service_cards');
        $this->assertNotEmpty($cards);
        $this->assertSame('Confirmed Clinic Booking', $cards[0]['title']);
        $this->assertTrue($cards[0]['featured']);
        $this->assertSame('snoutiq://clinic-booking', $cards[0]['cta']['deeplink']);

        $healthScore = (int) $response->json('ui.health_score.value');
        $this->assertGreaterThanOrEqual(31, $healthScore);
        $this->assertLessThanOrEqual(55, $healthScore);
    }

    public function test_severe_bleeding_routes_to_emergency(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'dog was hit by car with severe bleeding',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'emergency')
            ->assertJsonPath('ui.banner.title', 'Go to Emergency Vet Now');
    }

    public function test_inability_to_walk_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'my dog cannot walk and is dragging leg',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today');
    }

    public function test_eye_injury_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'cat scratched his eye and eye is cloudy',
            'pet_name' => 'Simba',
            'species' => 'cat',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today');
    }

    public function test_blood_in_stool_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'there is blood in stool this morning',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today')
            ->assertJsonPath('buttons.primary.label', 'Book Clinic Appointment')
            ->assertJsonPath('buttons.primary.deeplink', 'snoutiq://clinic-booking');
    }

    public function test_limping_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'my dog is limping on his left front leg',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today');
    }

    public function test_foreign_body_routes_to_in_clinic(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'my dog swallowed a plastic toy and chicken bone',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'in_clinic')
            ->assertJsonPath('ui.banner.title', 'Visit a Vet Clinic Today');
    }

    public function test_mild_itching_routes_to_video_consult(): void
    {
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'my dog has a slight itch on his ear since yesterday, mild scratching',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'video_consult')
            ->assertJsonPath('ui.banner.title', 'See a Vet Today via Video');
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('web_chat_campaign');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('chat_rooms');
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
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('clinic_day_fee', 10, 2)->nullable();
            $table->decimal('clinic_night_fee', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->decimal('video_day_rate', 10, 2)->nullable();
            $table->decimal('video_night_rate', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_age')->nullable();
            $table->string('pet_gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('neutered')->nullable();
            $table->string('species')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('chat_room_token', 100)->unique();
            $table->string('name')->nullable();
            $table->string('last_emergency_status', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('chat_room_id');
            $table->string('chat_room_token', 100);
            $table->string('context_token', 100)->nullable();
            $table->string('question', 500)->nullable();
            $table->text('answer')->nullable();
            $table->string('response_tag', 30)->nullable();
            $table->string('emergency_status', 30)->nullable();
            $table->string('pet_name', 120)->nullable();
            $table->string('pet_breed', 120)->nullable();
            $table->string('pet_age', 120)->nullable();
            $table->string('pet_location', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('web_chat_campaign', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedSmallInteger('turn')->default(1)->index();
            $table->string('routing', 30)->nullable()->index();
            $table->string('severity', 30)->nullable()->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('pet_name', 120)->nullable();
            $table->string('species', 30)->nullable();
            $table->string('breed', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->text('user_message')->nullable();
            $table->longText('assistant_message')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->longText('state_payload_json')->nullable();
            $table->timestamps();
        });
    }
}
