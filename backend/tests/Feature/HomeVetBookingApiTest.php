<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomeVetBookingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    public function test_step_two_saves_visit_date_and_time(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('home_service_required_by_pet')->insert([
            'id' => 123,
            'user_id' => 1,
            'latest_completed_step' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/home-vet-bookings/step-2', [
            'booking_id' => 123,
            'pet_name' => 'Bruno',
            'breed' => 'Labrador',
            'pet_dob' => '2022-06-10',
            'pet_sex' => 'Male',
            'date_of_visit' => '2026-04-20',
            'time_of_visit' => '18:30',
            'issue_description' => 'Vomiting since morning',
            'symptoms' => ['Vomiting', 'Low energy'],
            'vaccination_status' => 'Up to date',
            'last_deworming' => '2026-03-01',
            'past_illnesses_or_surgeries' => 'None',
            'current_medications' => '',
            'known_allergies' => '',
            'vet_notes' => 'Please visit in evening',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.booking_id', 123)
            ->assertJsonPath('data.latest_completed_step', 2);

        $booking = DB::table('home_service_required_by_pet')->where('id', 123)->first();

        $this->assertNotNull($booking);
        $this->assertStringStartsWith('2026-04-20', (string) $booking->date_of_visit);
        $this->assertSame('18:30:00', $booking->time_of_visit);
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('home_service_required_by_pet');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->date('pet_dob')->nullable();
            $table->string('pet_gender')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('vaccination_log')->nullable();
            $table->timestamps();
        });

        Schema::create('home_service_required_by_pet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedTinyInteger('latest_completed_step')->default(1);
            $table->date('date_of_visit')->nullable();
            $table->time('time_of_visit')->nullable();
            $table->text('concern_description')->nullable();
            $table->json('symptoms')->nullable();
            $table->string('vaccination_status')->nullable();
            $table->string('last_deworming')->nullable();
            $table->text('past_illnesses_or_surgeries')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('known_allergies')->nullable();
            $table->text('vet_notes')->nullable();
            $table->timestamp('step2_completed_at')->nullable();
            $table->timestamps();
        });
    }
}
