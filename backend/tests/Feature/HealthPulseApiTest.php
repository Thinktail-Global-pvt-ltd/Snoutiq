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
            ->assertJsonPath('data.symptom_analysis.analysis_text', "Sheriff's latest updates look more reassuring. Older notes about skin/nail/toe changes, paw licking or paw discomfort, panting are still useful background, but the recent symptom entries do not strongly point to an active issue right now.")
            ->assertJsonPath('data.symptom_analysis.details.meaningful_symptom_entry_count', 3)
            ->assertJsonPath('data.symptom_analysis.details.recent_meaningful_symptom_entry_count', 0)
            ->assertJsonPath('data.symptom_analysis.details.recent_no_symptom_entry_count', 3)
            ->assertJsonPath('data.symptom_analysis.details.current_status', 'Latest entry does not report an active symptom.');
    }

    public function test_report_returns_latest_ai_symptom_summary(): void
    {
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
            'id' => 39,
            'user_id' => 1436,
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'food' => 'good',
            'energy' => 'active',
            'water' => 'normal',
            'symptoms' => 'No symptoms',
            'digestion_issue' => false,
            'ai_flag_level' => 'None',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_pulse_symptom_analyses')->insert([
            'id' => 7,
            'user_id' => 1436,
            'pet_id' => 1318,
            'health_pulse_entry_id' => 39,
            'entry_date' => '2026-05-29',
            'symptom_entry_count' => 11,
            'symptoms_snapshot' => json_encode([]),
            'analysis_text' => 'Recent symptom entries are clean, while older notes remain useful context.',
            'flag_level' => 'None',
            'recommended_action' => 'Keep tracking any new changes.',
            'ai_payload' => json_encode([
                'overall_assessment' => 'Recent clean entries reduce concern from older symptom notes.',
                'current_status' => 'Latest entry does not report an active symptom.',
                'timeline_summary' => '11 symptom rows reviewed.',
                'recent_window_summary' => 'Last 3 entries: 0 active symptom notes, 3 no-symptom notes.',
                'key_patterns' => ['Older notes mention skin and paw themes.'],
                'watch_points' => ['Watch if paw licking returns.'],
                'reassuring_signals' => ['Recent entries are no-symptom entries.'],
                'recent_symptom_notes' => [],
                'latest_symptom_note' => null,
                'repeated_symptoms' => [],
                'possible_pattern' => 'No recent active symptom pattern.',
                'total_symptom_entries' => 11,
                'meaningful_symptom_entry_count' => 5,
                'no_symptom_entry_count' => 6,
                'recent_meaningful_symptom_entry_count' => 0,
                'recent_no_symptom_entry_count' => 3,
                'next_steps' => ['Continue daily updates.'],
                'disclaimer' => 'This is not a diagnosis.',
            ]),
            'analyzed_at' => '2026-05-29 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/pulse/report/1318?user_id=1436');

        $response->assertOk()
            ->assertJsonPath('data.analysis_text', 'Recent symptom entries are clean, while older notes remain useful context.')
            ->assertJsonPath('data.ai_symptom_summary.id', 7)
            ->assertJsonPath('data.ai_symptom_summary.analysis_text', 'Recent symptom entries are clean, while older notes remain useful context.')
            ->assertJsonPath('data.ai_symptom_summary.details.current_status', 'Latest entry does not report an active symptom.')
            ->assertJsonPath('data.ai_symptom_summary.details.recent_no_symptom_entry_count', 3)
            ->assertJsonPath('data.entries.0.symptom_analysis.id', 7);
    }

    public function test_report_generates_ai_symptom_summary_when_table_has_no_row(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->geminiJson([
                    'analysis_text' => "Sheriff's recent updates look more reassuring, while older paw notes remain useful context.",
                    'overall_assessment' => 'Recent clean entries reduce concern from older symptom notes.',
                    'current_status' => 'Latest entry does not report an active symptom.',
                    'timeline_summary' => 'Three symptom rows were reviewed.',
                    'recent_window_summary' => 'The latest entries are mostly clean.',
                    'key_patterns' => ['Older notes mention paw licking.'],
                    'watch_points' => ['Watch if paw licking returns.'],
                    'reassuring_signals' => ['Latest entry says no symptoms.'],
                    'recent_symptom_notes' => [],
                    'latest_symptom_note' => null,
                    'repeated_symptoms' => [],
                    'possible_pattern' => 'No recent active symptom pattern.',
                    'flag_level' => 'None',
                    'recommended_action' => 'Keep tracking any new changes.',
                    'next_steps' => ['Continue daily updates.'],
                    'disclaimer' => 'This is not a diagnosis.',
                ]))
                ->push($this->geminiJson(['summary' => 'Overall health trend looks steady.'])),
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
            ['id' => 39, 'entry_date' => '2026-05-27', 'symptoms' => 'Licking his paws'],
            ['id' => 40, 'entry_date' => '2026-05-28', 'symptoms' => 'No'],
            ['id' => 41, 'entry_date' => '2026-05-29', 'symptoms' => 'No symptoms'],
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

        $response = $this->getJson('/api/v1/pulse/report/1318?user_id=1436');

        $response->assertOk()
            ->assertJsonPath('data.analysis_text', "Sheriff's recent updates look more reassuring, while older paw notes remain useful context.")
            ->assertJsonPath('data.ai_symptom_summary.entry_id', 41)
            ->assertJsonPath('data.ai_symptom_summary.details.current_status', 'Latest entry does not report an active symptom.')
            ->assertJsonPath('data.entries.2.symptom_analysis.entry_id', 41);

        $this->assertDatabaseHas('health_pulse_symptom_analyses', [
            'pet_id' => 1318,
            'health_pulse_entry_id' => 41,
            'symptom_entry_count' => 3,
            'flag_level' => 'None',
        ]);
    }

    public function test_report_returns_generic_symptom_text_when_no_symptoms_exist(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->geminiJson(['summary' => 'Overall health trend looks steady.'])),
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
            'name' => 'Rocko',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_pulse_entries')->insert([
            'id' => 41,
            'user_id' => 1436,
            'pet_id' => 1318,
            'entry_date' => '2026-05-29',
            'food' => 'good',
            'energy' => 'normal',
            'water' => 'normal',
            'symptoms' => null,
            'digestion_issue' => true,
            'ai_flag_level' => 'None',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/pulse/report/1318?user_id=1436');

        $response->assertOk()
            ->assertJsonPath('data.analysis_text', "Rocko's symptom updates look clear so far. No symptoms have been added yet, so keep using the daily check-in to note any new change if it appears.")
            ->assertJsonPath('data.ai_symptom_summary.symptom_entry_count', 0)
            ->assertJsonPath('data.ai_symptom_summary.flag_level', 'None');
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
