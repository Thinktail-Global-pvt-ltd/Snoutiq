<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetOverviewController extends Controller
{
    public function show(Request $request, int $petId)
    {
        $pet = DB::table('pets')->where('id', $petId)->first();
        if (!$pet) {
            return response()->json(['success' => false, 'message' => 'Pet not found'], 404);
        }

        $owner = DB::table('users')->where('id', $pet->user_id)->first();

        $prescriptions = $this->fetchPrescriptions($petId);
        $vaccinations  = $this->fetchVaccinations($petId);
        $observation   = $this->fetchLatestObservation($owner?->id);

        $healthSignals = [
            'energy'   => $this->normalizeScore($observation['energy'] ?? null),
            'appetite' => $this->normalizeScore($observation['appetite'] ?? null),
            'mood'     => $this->normalizeScore($observation['mood'] ?? null),
        ];

        $clinicalRoadmap = [
            'condition'    => $pet->suggested_disease ?? null,
            'state'        => $pet->health_state ?? null,
            'next_consult' => $prescriptions['next_follow_up'] ?? null,
            'protocol'     => $prescriptions['protocol'] ?? null,
        ];

        $careRoadmap = $vaccinations['care_roadmap'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'pet' => [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'breed' => $pet->breed,
                    'age_years' => $pet->pet_age,
                    'age_months' => $pet->pet_age_months,
                    'gender' => $pet->pet_gender,
                    'state' => $pet->health_state,
                    'ai_summary' => $pet->ai_summary,
                    'reported_symptom' => $pet->reported_symptom,
                    'suggested_disease' => $pet->suggested_disease,
                ],
                'owner' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'phone' => $owner->phone,
                    'email' => $owner->email,
                ] : null,
                'clinical_roadmap' => $clinicalRoadmap,
                'health_signals' => $healthSignals,
                'latest_observation' => $observation,
                'prescriptions' => $prescriptions['items'],
                'medications' => $prescriptions['medications'],
                'care_roadmap' => $careRoadmap,
                'knowledge_hub' => $this->knowledgeHubSuggestions($pet),
            ],
        ]);
    }

    private function fetchPrescriptions(int $petId): array
    {
        if (!Schema::hasTable('prescriptions')) {
            return ['items' => [], 'medications' => [], 'next_follow_up' => null, 'protocol' => null];
        }

        $items = DB::table('prescriptions')
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'diagnosis',
                'diagnosis_status',
                'treatment_plan',
                'home_care',
                'follow_up_date',
                'follow_up_type',
                'follow_up_notes',
                'visit_notes',
                'exam_notes',
                'case_severity',
                'visit_category',
                'content_html',
                'image_path',
                'created_at',
            ])
            ->where(function ($q) use ($petId) {
                if (Schema::hasColumn('prescriptions', 'pet_id')) {
                    $q->where('pet_id', $petId);
                }
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $nextFollowUp = $items->first()?->follow_up_date;
        $protocol     = $items->first()?->treatment_plan;

        $medications = [];
        foreach ($items as $p) {
            if (!empty($p->treatment_plan)) {
                $medications[] = [
                    'title' => 'Treatment plan',
                    'details' => $p->treatment_plan,
                    'prescription_id' => $p->id,
                ];
            }
            if (!empty($p->home_care)) {
                $medications[] = [
                    'title' => 'Home care',
                    'details' => $p->home_care,
                    'prescription_id' => $p->id,
                ];
            }
        }

        return [
            'items' => $items,
            'medications' => $medications,
            'next_follow_up' => $nextFollowUp,
            'protocol' => $protocol,
        ];
    }

    private function fetchVaccinations(int $petId): array
    {
        if (!Schema::hasTable('pet_vaccination_records')) {
            return ['care_roadmap' => []];
        }

        $records = DB::table('pet_vaccination_records')
            ->select(['id', 'recommendations', 'notes', 'as_of_date', 'life_stage', 'age_display'])
            ->where('pet_id', $petId)
            ->orderByDesc('as_of_date')
            ->limit(3)
            ->get();

        $care = [];
        foreach ($records as $rec) {
            $recs = json_decode($rec->recommendations ?? '[]', true);
            if (is_array($recs)) {
                foreach ($recs as $r) {
                    $care[] = [
                        'title' => $r['title'] ?? 'Care item',
                        'status' => $r['status'] ?? null,
                        'due' => $r['due'] ?? null,
                        'note' => $r['note'] ?? ($rec->notes ?? null),
                    ];
                }
            }
        }

        return ['care_roadmap' => $care];
    }

    private function fetchLatestObservation(?int $userId): ?array
    {
        if (!$userId || !Schema::hasTable('user_observations')) {
            return null;
        }

        $obs = DB::table('user_observations')
            ->where('user_id', $userId)
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->first();

        if (!$obs) {
            return null;
        }

        return [
            'id' => $obs->id,
            'observed_at' => $obs->observed_at,
            'eating' => $obs->eating,
            'appetite' => $obs->appetite,
            'energy' => $obs->energy,
            'mood' => $obs->mood,
            'symptoms' => $obs->symptoms ? json_decode($obs->symptoms, true) : [],
            'notes' => $obs->notes,
        ];
    }

    private function normalizeScore($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            $v = (int)$value;
            return max(0, min(100, $v));
        }

        $map = [
            'low' => 30,
            'med' => 60,
            'medium' => 60,
            'ok' => 70,
            'good' => 80,
            'high' => 90,
            'great' => 95,
        ];
        $key = strtolower(trim((string)$value));
        return $map[$key] ?? null;
    }

    private function knowledgeHubSuggestions(object $pet): array
    {
        $breed = $pet->breed ?: 'dog';
        return [
            [
                'title' => 'Understanding '.$breed.' joint health',
                'tag' => 'Preventative',
                'duration' => '4 min read',
            ],
            [
                'title' => 'Seasonal care tips for active pets',
                'tag' => 'Seasonal',
                'duration' => '6 min read',
            ],
        ];
    }
}
