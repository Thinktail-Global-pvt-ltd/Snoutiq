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
        if (!Schema::hasTable('clinic_service_presets')) {
            $this->markTestSkipped('clinic_service_presets table does not exist.');
        }

        $this->createClinic(101);

        $now = now();
        DB::table('clinic_service_presets')->insert([
            [
                'clinic_id' => 101,
                'name' => 'Vaccination',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => 101,
                'name' => 'Deworming',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => 101,
                'name' => 'X-Ray',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => 101,
                'name' => 'Physiotherapy',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $response = $this->getJson('/api/clinic-service-presets?clinic_id=101');

        $response->assertOk()
            ->assertJsonPath('status', true);

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertSame(['Physiotherapy', 'X-Ray'], $names);
    }

    public function test_index_resolves_clinic_by_owner_user_id_when_user_id_is_provided(): void
    {
        if (!Schema::hasTable('clinic_service_presets')) {
            $this->markTestSkipped('clinic_service_presets table does not exist.');
        }

        if (!Schema::hasColumn('vet_registerations_temp', 'owner_user_id')) {
            $this->markTestSkipped('owner_user_id column does not exist on vet_registerations_temp.');
        }

        $this->createClinic(202, ['owner_user_id' => 9002]);

        DB::table('clinic_service_presets')->insert([
            'clinic_id' => 202,
            'name' => 'Laser Therapy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/clinic-service-presets?user_id=9002');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 202)
            ->assertJsonPath('data.0.name', 'Laser Therapy');
    }

    public function test_index_resolves_clinic_by_doctor_id_when_user_id_is_provided(): void
    {
        if (!Schema::hasTable('clinic_service_presets')) {
            $this->markTestSkipped('clinic_service_presets table does not exist.');
        }

        $this->createClinic(303);

        DB::table('doctors')->insert([
            'id' => 77,
            'vet_registeration_id' => 303,
            'doctor_name' => 'Dr Resolve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('clinic_service_presets')->insert([
            'clinic_id' => 303,
            'name' => 'Nutrition Plan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/clinic-service-presets?user_id=77');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 303)
            ->assertJsonPath('data.0.name', 'Nutrition Plan');
    }

    public function test_index_prefers_direct_clinic_id_when_user_id_collides_with_doctor_id(): void
    {
        if (!Schema::hasTable('clinic_service_presets')) {
            $this->markTestSkipped('clinic_service_presets table does not exist.');
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

        $now = now();
        DB::table('clinic_service_presets')->insert([
            [
                'clinic_id' => 1,
                'name' => 'Clinic One Service',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clinic_id' => 2,
                'name' => 'Clinic Two Service',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $response = $this->getJson('/api/clinic-service-presets?user_id=1');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.clinic_id', 1)
            ->assertJsonPath('data.0.name', 'Clinic One Service');
    }

    private function createClinic(int $id, array $overrides = []): void
    {
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
}
