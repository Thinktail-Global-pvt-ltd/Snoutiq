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
                    'overall_assessment' => 'Sheriff has a paw or ear itching theme worth watching.',
                    'current_status' => 'Latest active note mentions itching near the ear again.',
                    'timeline_summary' => 'Two symptom rows were reviewed and both contain meaningful symptom detail.',
                    'recent_window_summary' => 'The latest two entries both mention itching.',
                    'key_patterns' => ['Itching appears in more than one entry.'],
                    'watch_points' => ['Watch if the itching repeats tomorrow.'],
                    'reassuring_signals' => [],
                    'recent_symptom_notes' => ['2026-05-28: itching near ear', '2026-05-29: itching near ear again'],
                    'latest_symptom_note' => 'itching near ear again',
                    'repeated_symptoms' => ['itching'],
                    'possible_pattern' => 'Itching appears in more than one entry.',
                    'flag_level' => 'Watch',
                    'recommended_action' => 'If itching continues or gets worse, consider a vet check.',
                    'next_steps' => [
                        'Watch whether the itching improves or repeats.',
                        'Note any redness, discharge, or change in energy.',
                    ],
                    'disclaimer' => 'This is not a diagnosis.',
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
            ->assertJsonPath('data.symptom_analysis.analysis_text', 'Sheriff has repeated itching notes across recent entries, so it is worth monitoring.')
            ->assertJsonPath('data.symptom_analysis.details.timeline_summary', 'Two symptom rows were reviewed and both contain meaningful symptom detail.')
            ->assertJsonPath('data.symptom_analysis.details.recent_window_summary', 'The latest two entries both mention itching.')
            ->assertJsonPath('data.symptom_analysis.details.recent_symptom_notes.0', '2026-05-28: itching near ear')
            ->assertJsonPath('data.symptom_analysis.details.key_patterns.0', 'Itching appears in more than one entry.')
            ->assertJsonPath('data.symptom_analysis.details.repeated_symptoms.0', 'itching')
            ->assertJsonPath('data.symptom_analysis.details.next_steps.0', 'Watch whether the itching improves or repeats.');

        $this->assertDatabaseHas('health_pulse_symptom_analyses', [
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'symptom_entry_count' => 2,
            'flag_level' => 'Watch',
        ]);
    }

    public function test_no_symptoms_is_not_treated_as_a_symptom_pattern(): void
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
                    'analysis_text' => 'Sheriff has two symptom rows and both indicate no active symptoms.',
                    'overall_assessment' => 'No active symptom pattern is visible.',
                    'current_status' => 'Latest entry says no symptoms.',
                    'timeline_summary' => 'Two symptom rows were reviewed; both are no-symptom entries.',
                    'recent_window_summary' => 'The latest two entries are no-symptom entries.',
                    'key_patterns' => [],
                    'watch_points' => [],
                    'reassuring_signals' => ['Both entries say no symptoms.'],
                    'recent_symptom_notes' => [],
                    'latest_symptom_note' => null,
                    'repeated_symptoms' => [],
                    'possible_pattern' => 'No repeated active symptom pattern is available.',
                    'flag_level' => 'None',
                    'recommended_action' => 'Keep adding notes if anything changes.',
                    'next_steps' => ['Continue daily updates.'],
                    'disclaimer' => 'This is not a diagnosis.',
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
            'symptoms' => 'No symptoms',
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
            'symptoms' => 'No symptoms',
            'digestion_issue' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.ai.flag_level', 'None')
            ->assertJsonPath('data.symptom_analysis.symptom_entry_count', 2)
            ->assertJsonPath('data.symptom_analysis.flag_level', 'None')
            ->assertJsonPath('data.symptom_analysis.details.meaningful_symptom_entry_count', 0)
            ->assertJsonPath('data.symptom_analysis.details.no_symptom_entry_count', 2)
            ->assertJsonPath('data.symptom_analysis.details.recent_meaningful_symptom_entry_count', 0)
            ->assertJsonPath('data.symptom_analysis.details.recent_no_symptom_entry_count', 2)
            ->assertJsonPath('data.symptom_analysis.details.latest_symptom_note', null)
            ->assertJsonPath('data.symptom_analysis.details.repeated_symptoms', []);

        $this->assertDatabaseHas('health_pulse_symptom_analyses', [
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'symptom_entry_count' => 2,
            'flag_level' => 'None',
        ]);
    }

    public function test_recent_clean_symptom_entries_reduce_fallback_flag_even_when_old_symptoms_exist(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->geminiJson([
                    'short_summary' => 'Care update done for Sheriff.',
                    'pattern_observation' => 'Today looks steady.',
                    'flag_level' => 'None',
                    'recommended_action' => 'Keep tracking changes.',
                ]))
                ->push(['bad' => true]),
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

        foreach ([
            ['id' => 10, 'entry_date' => '2026-05-20', 'symptoms' => 'Dead skin on toe'],
            ['id' => 11, 'entry_date' => '2026-05-21', 'symptoms' => 'Licking his paws'],
            ['id' => 12, 'entry_date' => '2026-05-22', 'symptoms' => 'Panting a bit more'],
            ['id' => 13, 'entry_date' => '2026-05-27', 'symptoms' => 'No'],
            ['id' => 14, 'entry_date' => '2026-05-28', 'symptoms' => 'Na'],
        ] as $row) {
            DB::table('health_pulse_entries')->insert([
                'id' => $row['id'],
                'user_id' => 1436,
                'pet_id' => 1318,
                'entry_date' => $row['entry_date'],
                'food' => 'good',
                'energy' => 'active',
                'water' => 'normal',
                'symptoms' => $row['symptoms'],
                'digestion_issue' => false,
                'ai_flag_level' => 'None',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->postJson('/api/v1/pulse/entry', [
            'user_id' => 1436,
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'food' => 'good',
            'energy' => 'active',
            'water' => 'normal',
            'symptoms' => 'No symptoms',
            'digestion_issue' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.symptom_analysis.symptom_entry_count', 6)
            ->assertJsonPath('data.symptom_analysis.flag_level', 'None')
            ->assertJsonPath('data.symptom_analysis.details.meaningful_symptom_entry_count', 3)
            ->assertJsonPath('data.symptom_analysis.details.recent_meaningful_symptom_entry_count', 0)
            ->assertJsonPath('data.symptom_analysis.details.recent_no_symptom_entry_count', 3)
            ->assertJsonPath('data.symptom_analysis.details.current_status', 'Latest entry does not report an active symptom.');
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
