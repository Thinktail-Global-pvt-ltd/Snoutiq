<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvidersController extends Controller
{
    // POST /api/providers/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|string|in:vet_clinic,home_service,hybrid',
            'name' => 'required|string',
            'clinic_name' => 'nullable|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'license_number' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $data['status'] = 'registered';
        $data['type'] = $data['type'] ?? 'vet_clinic';

        $id = DB::table('providers')->insertGetId($data);

        return response()->json([
            'provider_id' => $id,
            'message' => 'Provider registered. Please complete profile.',
            'completion_link' => url("/vet/complete/{$id}")
        ]);
    }

    // POST /api/providers/complete-profile
    public function completeProfile(Request $request)
    {
        $payload = $request->validate([
            'provider_id' => 'required|integer|min:1',
            'weekly_commitment_hours' => 'required|integer|min:1',
            'service_radius_km' => 'nullable|numeric',
            'emergency_callable' => 'nullable|boolean',
            'notification_prefs' => 'nullable|array',
            'dnd_periods' => 'nullable|array',
            'team_members' => 'nullable|array',
            'specializations' => 'nullable|array',
            'home_service_equipment' => 'nullable|array',
            'availability' => 'required|array',
            'availability.*.service_type' => 'required|string|in:video,in_clinic,home_visit',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.start_time' => 'required',
            'availability.*.end_time' => 'required',
            'availability.*.break_start' => 'nullable',
            'availability.*.break_end' => 'nullable',
            'availability.*.avg_consultation_mins' => 'nullable|integer',
            'availability.*.max_bookings_per_hour' => 'nullable|integer',
        ]);

        $pid = $payload['provider_id'];

        DB::table('providers')->where('id', $pid)->update([
            'service_radius_km' => $payload['service_radius_km'] ?? config('snoutiq.default_service_radius_km'),
            'emergency_callable' => (bool) ($payload['emergency_callable'] ?? false),
            'notification_prefs' => json_encode($payload['notification_prefs'] ?? []),
            'dnd_periods' => json_encode($payload['dnd_periods'] ?? []),
            'weekly_commitment_hours' => $payload['weekly_commitment_hours'],
            'team_members' => json_encode($payload['team_members'] ?? []),
            'specializations' => json_encode($payload['specializations'] ?? []),
            'home_service_equipment' => json_encode($payload['home_service_equipment'] ?? []),
            'status' => 'active',
        ]);

        // Replace availability
        DB::table('provider_availability')->where('provider_id', $pid)->delete();
        foreach ($payload['availability'] as $a) {
            DB::table('provider_availability')->insert([
                'provider_id' => $pid,
                'service_type' => $a['service_type'],
                'day_of_week' => $a['day_of_week'],
                'start_time' => $a['start_time'],
                'end_time' => $a['end_time'],
                'break_start' => $a['break_start'] ?? null,
                'break_end' => $a['break_end'] ?? null,
                'avg_consultation_mins' => $a['avg_consultation_mins'] ?? 20,
                'max_bookings_per_hour' => $a['max_bookings_per_hour'] ?? 3,
                'is_active' => 1,
            ]);
        }

        return response()->json(['message' => 'Profile activated successfully', 'status' => 'active']);
    }

    // GET /api/providers/{id}/status
    public function status(string $id)
    {
        $provider = DB::table('providers as p')
            ->leftJoin('ml_provider_performance as perf', function ($join) {
                $join->on('p.id', '=', 'perf.provider_id');
            })
            ->where('p.id', $id)
            ->selectRaw('p.*, COALESCE(perf.reliability_score, 50) as reliability_score, COALESCE(perf.total_bookings, 0) as total_bookings, COALESCE(perf.avg_rating, 0) as avg_rating')
            ->first();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found', 'success' => false], 404);
        }
        return response()->json(['provider' => $provider]);
    }

    // PUT /api/providers/{id}/availability
    public function updateAvailability(Request $request, string $id)
    {
        // Validate shape of availability rows
        $payload = $request->validate([
            'availability' => 'required|array|min:1',
            'availability.*.service_type' => 'required|string|in:video,in_clinic,home_visit',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.start_time' => 'required',
            'availability.*.end_time' => 'required',
            'availability.*.break_start' => 'nullable',
            'availability.*.break_end' => 'nullable',
            'availability.*.avg_consultation_mins' => 'nullable|integer',
            'availability.*.max_bookings_per_hour' => 'nullable|integer',
        ]);

        // Ensure provider exists to satisfy FK
        $exists = DB::table('providers')->where('id', (int) $id)->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'error' => 'Provider not found. Register provider first and use its ID.',
            ], 404);
        }

        DB::transaction(function () use ($id, $payload) {
            DB::table('provider_availability')->where('provider_id', (int) $id)->delete();
            foreach ($payload['availability'] as $a) {
                DB::table('provider_availability')->insert([
                    'provider_id' => (int) $id,
                    'service_type' => $a['service_type'],
                    'day_of_week' => $a['day_of_week'],
                    'start_time' => $a['start_time'],
                    'end_time' => $a['end_time'],
                    'break_start' => $a['break_start'] ?? null,
                    'break_end' => $a['break_end'] ?? null,
                    'avg_consultation_mins' => $a['avg_consultation_mins'] ?? 20,
                    'max_bookings_per_hour' => $a['max_bookings_per_hour'] ?? 3,
                    'is_active' => 1,
                ]);
            }
        });

        return response()->json(['message' => 'Availability updated', 'success' => true]);
    }
}
