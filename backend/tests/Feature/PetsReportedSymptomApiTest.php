<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PetsReportedSymptomApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    public function test_it_updates_reported_symptom_for_the_owners_pet(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Ananya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 11,
            'user_id' => 1,
            'name' => 'Milo',
            'reported_symptom' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson('/api/users/1/pets/11/reported-symptom', [
            'reported_symptom' => 'Vomiting since morning',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 1)
            ->assertJsonPath('data.pet.id', 11)
            ->assertJsonPath('data.pet.reported_symptom', 'Vomiting since morning');

        $this->assertSame(
            'Vomiting since morning',
            DB::table('pets')->where('id', 11)->value('reported_symptom')
        );
    }

    public function test_it_returns_404_when_pet_does_not_belong_to_user(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Owner A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 2,
            'name' => 'Owner B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 22,
            'user_id' => 1,
            'name' => 'Bruno',
            'reported_symptom' => 'Old symptom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson('/api/users/2/pets/22/reported-symptom', [
            'reported_symptom' => 'New symptom',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);

        $this->assertSame(
            'Old symptom',
            DB::table('pets')->where('id', 22)->value('reported_symptom')
        );
    }

    private function resetSchema(): void
    {
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
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->timestamps();
        });
    }
}
