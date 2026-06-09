<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SymptomPricingTest extends TestCase
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

    public function test_symptom_check_returns_default_pricing_for_no_user_context(): void
    {
        // Unconscious triggers the emergency red flag, bypassing Gemini call
        $response = $this->postJson('/api/symptom-check', [
            'message' => 'My dog is unconscious',
            'pet_name' => 'Bruno',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'emergency')
            ->assertJsonPath('ui.service_cards.0.price', '₹999') // Vet at Home default
            ->assertJsonPath('ui.service_cards.1.price', '₹350'); // Confirmed Clinic Booking default
    }

    public function test_symptom_check_returns_dynamic_pricing_for_user_with_last_vet_id(): void
    {
        // Setup Clinic with fee
        DB::table('vet_registerations_temp')->insert([
            'id' => 20,
            'name' => 'Happy Pets Clinic',
            'clinic_day_fee' => 600.00,
            'clinic_night_fee' => 800.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup Doctor with rate under the clinic
        DB::table('doctors')->insert([
            'id' => 201,
            'vet_registeration_id' => 20,
            'doctor_name' => 'Dr. Smith',
            'video_day_rate' => 750.00,
            'video_night_rate' => 900.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup User linked to the clinic
        DB::table('users')->insert([
            'id' => 5001,
            'name' => 'John Doe',
            'last_vet_id' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup Pet
        DB::table('pets')->insert([
            'id' => 6001,
            'user_id' => 5001,
            'name' => 'Max',
            'species' => 'dog',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call symptom check for this user (not breathing triggers emergency red-flag bypass)
        $response = $this->postJson('/api/symptom-check', [
            'user_id' => 5001,
            'pet_id' => 6001,
            'message' => 'My dog is not breathing',
            'pet_name' => 'Max',
            'species' => 'dog',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('routing', 'emergency')
            // For emergency, it returns Vet at Home (fixed 999) and Confirmed Clinic Booking (dynamic: clinic_day_fee = 600)
            ->assertJsonPath('ui.service_cards.1.price', '₹600');

        // Let's check non-emergency/mild routing (default) to verify video call rate
        // We'll call /api/chat/send legacy/compat endpoint where we mock/greeting to trigger default routing
        // Wait, greeting 'hello' triggers softReset and default routing or default card output
        // Let's call /api/chat/send
        $responseCompat = $this->postJson('/api/chat/send', [
            'user_id' => 5001,
            'pet_id' => 6001,
            'question' => 'hello',
            'pet_name' => 'Max',
            'species' => 'dog',
        ]);

        $responseCompat->assertOk()
            ->assertJsonPath('success', true)
            // default view is video_consult + vet_at_home
            // Video consultation price should be dynamic: video_day_rate = 750
            ->assertJsonPath('ui.service_cards.0.price', '₹750')
            ->assertJsonPath('ui.service_cards.0.orig_price', null); // orig_price is null for dynamic rate
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
