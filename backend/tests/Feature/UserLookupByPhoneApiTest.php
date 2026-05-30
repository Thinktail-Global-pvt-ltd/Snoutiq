<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserLookupByPhoneApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_lookup_by_phone_returns_user_and_pets(): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Pet Parent',
            'phone' => '+91 98765 43210',
            'email' => 'parent@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            [
                'id' => 50,
                'user_id' => 10,
                'name' => 'Milo',
                'breed' => 'Indie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 51,
                'user_id' => 10,
                'name' => 'Bruno',
                'breed' => 'Labrador',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/users/by-phone?phone=919876543210');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('exists', true)
            ->assertJsonPath('data.user.id', 10)
            ->assertJsonPath('data.user.phone', '+91 98765 43210')
            ->assertJsonPath('data.pets.0.id', 50)
            ->assertJsonPath('data.pets.0.name', 'Milo')
            ->assertJsonPath('data.pets.1.id', 51)
            ->assertJsonPath('data.pets.1.name', 'Bruno');
    }

    public function test_user_lookup_by_phone_returns_not_found_when_phone_does_not_exist(): void
    {
        $response = $this->getJson('/api/users/by-phone?phone=9000000000');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('exists', false)
            ->assertJsonPath('data', null);
    }
}
