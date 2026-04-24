<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceptionistStorePatientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.razorpay.key', '');
        config()->set('services.razorpay.secret', '');

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 25)->nullable();
            $table->string('role', 50)->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('type')->nullable();
            $table->unsignedInteger('pet_age')->nullable();
            $table->string('pet_gender')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->unique(['user_id', 'name', 'breed', 'pet_age', 'pet_gender'], 'uniq_user_pet');
            $table->timestamps();
        });
    }

    public function test_store_patient_updates_existing_patient_when_phone_already_exists(): void
    {
        $existingUser = User::query()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '9999999999',
            'role' => 'pet',
            'password' => 'existing-password',
            'last_vet_id' => 10,
        ]);

        $response = $this->postJson('/api/receptionist/patients', [
            'clinic_id' => 77,
            'name' => 'Rohit Sharma',
            'phone' => '9999999999',
            'email' => 'rohit@example.com',
            'pet_name' => 'Bruno',
            'pet_type' => 'dog',
            'pet_breed' => 'Labrador',
            'amount' => 499,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $existingUser->id)
            ->assertJsonPath('data.user.name', 'Rohit Sharma')
            ->assertJsonPath('data.user.email', 'rohit@example.com')
            ->assertJsonPath('data.user.phone', '9999999999')
            ->assertJsonPath('data.user.last_vet_id', 77)
            ->assertJsonPath('data.pet.name', 'Bruno')
            ->assertJsonPath('data.pet.type', 'dog')
            ->assertJsonPath('data.pet.breed', 'Labrador');

        $this->assertSame(1, User::query()->count());

        $existingUser->refresh();
        $this->assertSame('Rohit Sharma', $existingUser->name);
        $this->assertSame('rohit@example.com', $existingUser->email);
        $this->assertSame('9999999999', $existingUser->phone);
        $this->assertSame('pet', $existingUser->role);
        $this->assertSame(77, $existingUser->last_vet_id);
        $this->assertSame('existing-password', $existingUser->password);

        $pet = DB::table('pets')->first();
        $this->assertNotNull($pet);
        $this->assertSame($existingUser->id, $pet->user_id);
        $this->assertSame('Bruno', $pet->name);
        $this->assertSame('Labrador', $pet->breed);
        $this->assertSame('dog', $pet->type);
    }

    public function test_store_patient_replaces_existing_pets_for_the_user_before_insert(): void
    {
        $existingUser = User::query()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '9999999999',
            'role' => 'pet',
            'password' => 'existing-password',
            'last_vet_id' => 10,
        ]);

        DB::table('pets')->insert([
            [
                'user_id' => $existingUser->id,
                'name' => 'Bruno',
                'breed' => 'Labrador',
                'type' => 'dog',
                'pet_age' => 3,
                'pet_gender' => 'male',
                'weight' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $existingUser->id,
                'name' => 'Lucy',
                'breed' => 'Beagle',
                'type' => 'dog',
                'pet_age' => 5,
                'pet_gender' => 'female',
                'weight' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postJson('/api/receptionist/patients', [
            'clinic_id' => 77,
            'name' => 'Rohit Sharma',
            'phone' => '9999999999',
            'email' => 'rohit@example.com',
            'pet_name' => 'Bruno',
            'pet_type' => 'dog',
            'pet_breed' => 'Labrador',
            'pet_age' => 3,
            'pet_gender' => 'male',
            'weight' => 24,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $existingUser->id)
            ->assertJsonPath('data.pet.name', 'Bruno')
            ->assertJsonPath('data.pet.type', 'dog')
            ->assertJsonPath('data.pet.breed', 'Labrador');

        $pets = DB::table('pets')
            ->where('user_id', $existingUser->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $pets);
        $pet = $pets->first();
        $this->assertSame('Bruno', $pet->name);
        $this->assertSame('Labrador', $pet->breed);
        $this->assertSame('dog', $pet->type);
        $this->assertSame(3, (int) $pet->pet_age);
        $this->assertSame('male', $pet->pet_gender);
        $this->assertSame(24.0, (float) $pet->weight);
    }
}
