<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorFollowUpUsersRevenueFilterTest extends TestCase
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

    public function test_follow_up_users_revenue_counts_only_captured_excel_export_transactions(): void
    {
        DB::table('doctors')->insert([
            'id' => 10,
            'vet_registeration_id' => 241,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 20,
            'name' => 'Pet Parent',
            'last_vet_id' => 241,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'id' => 30,
            'doctor_id' => 10,
            'user_id' => 20,
            'follow_up_date' => '2026-04-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            [
                'doctor_id' => 10,
                'amount_paise' => 49900,
                'status' => 'captured',
                'type' => 'excell_export_campaign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 10,
                'amount_paise' => 69900,
                'status' => 'pending',
                'type' => 'excell_export_campaign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 10,
                'amount_paise' => 89900,
                'status' => 'captured',
                'type' => 'video_consult',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 11,
                'amount_paise' => 99900,
                'status' => 'captured',
                'type' => 'excell_export_campaign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/doctor/follow-up-users?doctor_id=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('matched_user_count', 1)
            ->assertJsonPath('vet_registeration_id', 241)
            ->assertJsonPath('total_earnings_sum', 499);
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('doctors');
    }

    private function createSchema(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }
}
