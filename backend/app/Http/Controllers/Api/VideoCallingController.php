<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoCallingController extends Controller
{
    public function nearbyVets(Request $request)
    {
        $userId = $request->query('user_id');
        $date   = (string) $request->query('date', now('Asia/Kolkata')->toDateString());

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 422);
        }

        // 1) User lat/lng lao
        $user = DB::table('users')
            ->select('latitude', 'longitude')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->latitude === null || $user->longitude === null || $user->latitude === '' || $user->longitude === '') {
            return response()->json(['status' => 'error', 'message' => 'User lat/long missing'], 422);
        }

        // 2) Numbers ensure karo
        $lat = (float) $user->latitude;
        $lng = (float) $user->longitude;
        $radiusKm = 5;

        // 3) Haversine with bindings
        $vets = DB::table('vet_registerations_temp')
            ->select('vet_registerations_temp.*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(vet_registerations_temp.lat)) *
                    cos(radians(vet_registerations_temp.lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(vet_registerations_temp.lat))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->whereNotNull('vet_registerations_temp.lat')
            ->whereNotNull('vet_registerations_temp.lng')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->get();

        if ($vets->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date,
                'data'   => $vets,
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $vetIds = $vets->pluck('id')->all();

        $doctors = DB::table('doctors')
            ->select('id', 'vet_registeration_id')
            ->whereIn('vet_registeration_id', $vetIds)
            ->get();

        if ($doctors->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date,
                'data'   => [],
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $doctorIds = $doctors->pluck('id')->all();

        $activeDoctorIds = DB::table('doctor_video_availability')
            ->whereIn('doctor_id', $doctorIds)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('doctor_id');

        if ($activeDoctorIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date,
                'data'   => [],
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $utcNightHours = array_merge(range(13, 23), range(0, 6));

        $busyDoctorIds = DB::table('video_slots')
            ->whereIn('committed_doctor_id', $activeDoctorIds->all())
            ->where('slot_date', $date)
            ->whereIn('hour_24', $utcNightHours)
            ->whereIn('status', ['committed', 'in_progress', 'done'])
            ->distinct()
            ->pluck('committed_doctor_id')
            ->all();

        $activeDoctorSet = array_fill_keys($activeDoctorIds->all(), true);
        $busyDoctorSet   = array_fill_keys($busyDoctorIds, true);

        $doctorsByVet = $doctors->groupBy('vet_registeration_id');
        $availableDoctorsByVet = [];

        $filteredVets = $vets->filter(function ($vet) use ($doctorsByVet, $activeDoctorSet, $busyDoctorSet, &$availableDoctorsByVet) {
            $doctorsForVet = $doctorsByVet->get($vet->id);

            if (!$doctorsForVet) {
                return false;
            }

            $qualified = collect($doctorsForVet)
                ->pluck('id')
                ->filter(function ($doctorId) use ($activeDoctorSet, $busyDoctorSet) {
                    if (!isset($activeDoctorSet[$doctorId])) {
                        return false;
                    }

                    if (isset($busyDoctorSet[$doctorId])) {
                        return false;
                    }

                    return true;
                })
                ->values()
                ->all();

            if (empty($qualified)) {
                return false;
            }

            $availableDoctorsByVet[$vet->id] = $qualified;

            return true;
        });

        return response()->json([
            'status' => 'success',
            'date'   => $date,
            'data'   => $filteredVets->values(),
            'available_doctors_by_vet' => (object) $availableDoctorsByVet,
        ]);
    }
}

