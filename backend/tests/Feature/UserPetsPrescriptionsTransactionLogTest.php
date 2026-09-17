<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPetsPrescriptionsTransactionLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('reported_symptom_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_gender')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->binary('pet_doc2_blob_new')->nullable();
            $table->binary('pet_doc2_blob')->nullable();
            $table->string('pet_doc2_mime')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->string('pic_link')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('visit_category')->nullable();
            $table->string('case_severity')->nullable();
            $table->text('visit_notes')->nullable();
            $table->text('content_html')->nullable();
            $table->string('image_path')->nullable();
            $table->date('next_medicine_day')->nullable();
            $table->date('next_visit_day')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('reported_symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();
            $table->text('reported_symptom')->nullable();
            $table->binary('image_blob')->nullable();
            $table->string('image_mime')->nullable();
            $table->timestamps();
        });
    }

    public function test_pets_prescriptions_can_overlay_transaction_specific_symptom_and_image(): void
    {
        DB::table('users')->insert([
            'id' => 1387,
            'name' => 'Maayank Pet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 501,
            'user_id' => 1387,
            'name' => 'Bsbsbsb',
            'breed' => 'affenpinscher',
            'pet_gender' => 'male',
            'reported_symptom' => 'latest pet-level symptom',
            'pet_doc2_blob_new' => 'latest-pet-image',
            'pet_doc2_mime' => 'image/png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 902,
            'doctor_id' => 116,
            'user_id' => 1387,
            'pet_id' => 501,
            'type' => 'video_consult',
            'status' => 'captured',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reported_symptom_logs')->insert([
            'id' => 77,
            'pet_id' => 501,
            'doctor_id' => 116,
            'transaction_id' => 902,
            'reported_symptom' => 'Transaction-specific symptom',
            'image_blob' => 'transaction-image',
            'image_mime' => 'image/jpeg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/users/pets-prescriptions?pet_id=501&transaction_id=902');

        $response
            ->assertOk()
            ->assertJsonPath('data.pets.0.reported_symptom', 'Transaction-specific symptom')
            ->assertJsonPath('data.pets.0.reported_symptom_log_id', 77)
            ->assertJsonPath('data.pets.0.reported_symptom_log_transaction_id', 902);

        $imageUrl = $response->json('data.pets.0.pet_doc2_blob_new_url');
        $this->assertStringContainsString('/reported-symptom-logs/77/image', $imageUrl);
        $this->assertSame($imageUrl, $response->json('data.pets.0.pet_image_url'));
    }
}
