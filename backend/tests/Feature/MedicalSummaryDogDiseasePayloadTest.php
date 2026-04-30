<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MedicalSummaryDogDiseasePayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->text('summary')->nullable();
            $table->string('pet_name')->nullable();
            $table->string('pet_gender')->nullable();
            $table->unsignedInteger('pet_age')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->unsignedInteger('pet_age')->nullable();
            $table->unsignedInteger('pet_age_months')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('pet_type')->nullable();
            $table->date('pet_dob')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->string('pic_link')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('image_path')->nullable();
            $table->date('next_medicine_day')->nullable();
            $table->date('next_visit_day')->nullable();
            $table->timestamps();
        });
    }

    public function test_medical_summary_merges_dog_disease_payload_without_wiping_existing_keys(): void
    {
        DB::table('users')->insert([
            'id' => 1421,
            'name' => 'Test User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1283,
            'user_id' => 1421,
            'name' => 'Bruno',
            'dog_disease_payload' => json_encode([
                'user_id' => 1421,
                'pet_id' => 1283,
                'question' => 'Vaccination history confirmation',
                'vaccination' => [
                    'p1|dog|dhppi|series|1|2024-05-16' => [
                        'status' => 'done',
                        'date' => '2024-05-16',
                    ],
                ],
                'pet_card_for_ai' => null,
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->patchJson('/api/users/medical-summary', [
            'user_id' => 1421,
            'pet_id' => 1283,
            'dog_disease_payload' => [
                'question' => 'Fhh',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $stored = json_decode(DB::table('pets')->where('id', 1283)->value('dog_disease_payload'), true);

        $this->assertSame('Fhh', $stored['question']);
        $this->assertSame(1421, $stored['user_id']);
        $this->assertSame(1283, $stored['pet_id']);
        $this->assertSame('done', $stored['vaccination']['p1|dog|dhppi|series|1|2024-05-16']['status']);
        $this->assertArrayHasKey('pet_card_for_ai', $stored);
    }
}
