<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClinicServicePresetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_custom_presets_for_clinic_id(): void
    {
        if (!Schema::hasTable('groomer_services')) {
            $this->markTestSkipped('groomer_services table does not exist.');
        }

        $this->createClinic(101);
        $this->createGroomerService(101, 'Vaccination');
        $this->createGroomerService(101, 'Deworming');
        $this->createGroomerService(101, 'X-Ray');
        $this->createGroomerService(101, 'Physiotherapy');

        $response = $this->getJson('/api/clinic-service-presets?clinic_id=101');

        $response->assertOk()
            ->assertJsonPath('status', true);

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertSame(['Physiotherapy', 'X-Ray', 'Deworming', 'Vaccination'], $names);
    }

    public function test_index_resolves_clinic_by_owner_user_id_when_user_id_is_provided(): void
    {
        if (!Schema::hasTable('groomer_services')) {
            $this->markTestSkipped('groomer_services table does not exist.');
        }

        if (!Schema::hasColumn('vet_registerations_temp', 'owner_user_id')) {
            $this->markTestSkipped('owner_user_id column does not exist on vet_registerations_temp.');
        }

        $this->createClinic(202, ['owner_user_id' => 9002]);
        $this->createUser(9002);
        $this->createGroomerService(202, 'Laser Therapy');

        $response = $this->getJson('/api/clinic-service-presets?user_id=9002');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 202)
            ->assertJsonPath('data.0.name', 'Laser Therapy');
    }

    public function test_index_resolves_clinic_by_doctor_id_when_user_id_is_provided(): void
    {
        if (!Schema::hasTable('groomer_services')) {
            $this->markTestSkipped('groomer_services table does not exist.');
        }

        $this->createClinic(303);

        DB::table('doctors')->insert([
            'id' => 77,
            'vet_registeration_id' => 303,
            'doctor_name' => 'Dr Resolve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createGroomerService(303, 'Nutrition Plan');

        $response = $this->getJson('/api/clinic-service-presets?user_id=77');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 303)
            ->assertJsonPath('data.0.name', 'Nutrition Plan');
    }

    public function test_index_prefers_direct_clinic_id_when_user_id_collides_with_doctor_id(): void
    {
        if (!Schema::hasTable('groomer_services')) {
            $this->markTestSkipped('groomer_services table does not exist.');
        }

        $this->createClinic(1);
        $this->createClinic(2);

        DB::table('doctors')->insert([
            'id' => 1,
            'vet_registeration_id' => 2,
            'doctor_name' => 'Dr Collision',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createGroomerService(1, 'Clinic One Service');
        $this->createGroomerService(2, 'Clinic Two Service');

        $response = $this->getJson('/api/clinic-service-presets?user_id=1');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 1)
            ->assertJsonPath('data.0.name', 'Clinic One Service');
    }

    private function createClinic(int $id, array $overrides = []): void
    {
        $this->createUser($id);

        $payload = array_merge([
            'id' => $id,
            'name' => 'Clinic '.$id,
            'city' => 'Mumbai',
            'pincode' => '400001',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        DB::table('vet_registerations_temp')->insert($payload);
    }

    private function createUser(int $id): void
    {
        if (DB::table('users')->where('id', $id)->exists()) {
            return;
        }

        DB::table('users')->insert([
            'id' => $id,
            'name' => 'User '.$id,
            'email' => "user{$id}@example.test",
            'phone' => '90000'.str_pad((string) $id, 5, '0', STR_PAD_LEFT),
            'role' => 'vet',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCategory(int $clinicId): int
    {
        $existing = DB::table('groomer_service_categories')
            ->where('user_id', $clinicId)
            ->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $id = (int) DB::table('groomer_service_categories')->insertGetId([
            'name' => 'General',
            'user_id' => $clinicId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createGroomerService(int $clinicId, string $name): void
    {
        $categoryId = $this->createCategory($clinicId);

        $payload = [
            'user_id' => $clinicId,
            'groomer_service_category_id' => $categoryId,
            'name' => $name,
            'description' => null,
            'pet_type' => 'Dog',
            'price' => 100,
            'duration' => 30,
            'status' => 'Active',
            'service_pic' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('groomer_services', 'price_min')) {
            $payload['price_min'] = 100;
        }
        if (Schema::hasColumn('groomer_services', 'price_max')) {
            $payload['price_max'] = 100;
        }
        if (Schema::hasColumn('groomer_services', 'price_after_service')) {
            $payload['price_after_service'] = 0;
        }
        if (Schema::hasColumn('groomer_services', 'main_service')) {
            $payload['main_service'] = 'custom_service';
        }

        DB::table('groomer_services')->insert($payload);
    }
}
