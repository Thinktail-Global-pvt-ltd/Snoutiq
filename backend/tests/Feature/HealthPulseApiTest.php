<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HealthPulseApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    public function test_entry_returns_symptom_trend_analysis_from_previous_and_current_symptoms(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->geminiJson([
                    'short_summary' => 'Care update done for Sheriff.',
                    'pattern_observation' => 'Today looks steady.',
                    'flag_level' => 'None',
                    'recommended_action' => 'Keep tracking changes.',
                ]))
                ->push($this->geminiJson([
                    'analysis_text' => 'Sheriff has repeated itching notes across recent entries, so it is worth monitoring.',
                    'flag_level' => 'Watch',
                    'recommended_action' => 'If itching continues or gets worse, consider a vet check.',
                ])),
        ]);

        DB::table('users')->insert([
            'id' => 1436,
            'name' => 'Pet Parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1318,
            'user_id' => 1436,
            'name' => 'Sheriff',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_pulse_entries')->insert([
            'id' => 10,
            'user_id' => 1436,
            'pet_id' => 1318,
            'entry_date' => '2026-05-28',
            'food' => 'good',
            'energy' => 'active',
            'water' => 'normal',
            'symptoms' => 'itching near ear',
            'digestion_issue' => false,
            'ai_flag_level' => 'None',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/pulse/entry', [
            'user_id' => 1436,
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'food' => 'good',
            'energy' => 'active',
            'water' => 'normal',
            'symptoms' => 'itching near ear again',
            'digestion_issue' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symptom_analysis.symptom_entry_count', 2)
            ->assertJsonPath('data.symptom_analysis.flag_level', 'Watch')
            ->assertJsonPath('data.symptom_analysis.analysis_text', 'Sheriff has repeated itching notes across recent entries, so it is worth monitoring.');

        $this->assertDatabaseHas('health_pulse_symptom_analyses', [
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'symptom_entry_count' => 2,
            'flag_level' => 'Watch',
        ]);
    }

    private function geminiJson(array $payload): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode($payload),
                    ]],
                ],
            ]],
        ];
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('health_pulse_symptom_analyses');
        Schema::dropIfExists('health_pulse_entries');
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
            $table->timestamps();
        });

        Schema::create('health_pulse_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id');
            $table->date('entry_date');
            $table->string('food', 40)->nullable();
            $table->string('energy', 40)->nullable();
            $table->string('water', 40)->nullable();
            $table->text('symptoms')->nullable();
            $table->boolean('digestion_issue')->nullable();
            $table->string('digestion_note', 255)->nullable();
            $table->string('ai_flag_level', 20)->default('None');
            $table->text('ai_short_summary')->nullable();
            $table->text('ai_pattern_observation')->nullable();
            $table->text('ai_recommended_action')->nullable();
            $table->json('ai_payload')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->timestamps();
            $table->unique(['pet_id', 'entry_date'], 'health_pulse_pet_date_unique');
        });

        Schema::create('health_pulse_symptom_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id');
            $table->unsignedBigInteger('health_pulse_entry_id');
            $table->date('entry_date');
            $table->unsignedInteger('symptom_entry_count')->default(0);
            $table->json('symptoms_snapshot')->nullable();
            $table->text('analysis_text')->nullable();
            $table->string('flag_level', 20)->default('None');
            $table->text('recommended_action')->nullable();
            $table->json('ai_payload')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            $table->unique('health_pulse_entry_id', 'health_pulse_symptom_analysis_entry_unique');
        });
    }
}
