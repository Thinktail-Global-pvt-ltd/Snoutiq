<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorServiceCategoryController extends Controller
{
    // GET /api/doctors/by-service-category?service_type=Dog%20Vaccine
    public function index(Request $request)
    {
        $payload = $request->validate([
            'service_type' => ['required', 'string', 'max:120'],
        ]);

        $serviceType = trim((string) $payload['service_type']);
        if ($serviceType === '') {
            return response()->json([
                'success' => false,
                'message' => 'service_type is required.',
            ], 422);
        }

        $normalizedServiceType = $this->normalizeServiceType($serviceType);

        $serviceQuery = DB::table('groomer_services as gs')
            ->select([
                'gs.id',
                'gs.user_id as clinic_id',
                'gs.name',
                'gs.pet_type',
                'gs.price',
                'gs.duration',
                'gs.status',
            ]);

        $hasMainService = Schema::hasColumn('groomer_services', 'main_service');
        $hasPriceMin = Schema::hasColumn('groomer_services', 'price_min');
        $hasPriceMax = Schema::hasColumn('groomer_services', 'price_max');
        $hasPriceAfterService = Schema::hasColumn('groomer_services', 'price_after_service');

        if ($hasMainService) {
            $serviceQuery->addSelect('gs.main_service');
        }
        if ($hasPriceMin) {
            $serviceQuery->addSelect('gs.price_min');
        }
        if ($hasPriceMax) {
            $serviceQuery->addSelect('gs.price_max');
        }
        if ($hasPriceAfterService) {
            $serviceQuery->addSelect('gs.price_after_service');
        }

        $serviceQuery->where(function ($query) use ($normalizedServiceType, $hasMainService) {
            $query->whereRaw('LOWER(TRIM(gs.name)) = ?', [$normalizedServiceType]);
            if ($hasMainService) {
                $query->orWhereRaw('LOWER(TRIM(gs.main_service)) = ?', [$normalizedServiceType]);
            }
        });

        $serviceRows = $serviceQuery
            ->orderBy('gs.user_id')
            ->orderBy('gs.name')
            ->get();

        $presetClinicIds = collect();
        if (Schema::hasTable('clinic_service_presets')) {
            $presetClinicIds = DB::table('clinic_service_presets')
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedServiceType])
                ->pluck('clinic_id')
                ->map(fn ($id) => (int) $id);
        }

        $serviceClinicIds = $serviceRows->pluck('clinic_id')->map(fn ($id) => (int) $id);
        $clinicIds = $serviceClinicIds
            ->merge($presetClinicIds)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($clinicIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'service_type' => $serviceType,
                'doctor_count' => 0,
                'clinic_count' => 0,
                'data' => [],
            ]);
        }

        $clinicRows = DB::table('vet_registerations_temp')
            ->whereIn('id', $clinicIds->all())
            ->select([
                'id',
                DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as clinic_name'),
                'slug',
                'city',
                'mobile',
                'address',
            ])
            ->get()
            ->keyBy('id');

        $doctorRows = DB::table('doctors')
            ->whereIn('vet_registeration_id', $clinicIds->all())
            ->select([
                'id',
                'vet_registeration_id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'doctor_license',
                'doctor_status',
                'toggle_availability',
                'doctors_price',
                'video_day_rate',
                'video_night_rate',
            ])
            ->orderBy('doctor_name')
            ->get();

        $servicesByClinic = $serviceRows
            ->groupBy('clinic_id')
            ->map(function ($rows) {
                return $rows->map(function ($row) {
                    return [
                        'service_id' => (int) $row->id,
                        'name' => $row->name,
                        'pet_type' => $row->pet_type,
                        'status' => $row->status,
                        'duration' => $row->duration !== null ? (int) $row->duration : null,
                        'price' => $row->price !== null ? (float) $row->price : null,
                        'price_min' => property_exists($row, 'price_min') && $row->price_min !== null ? (float) $row->price_min : null,
                        'price_max' => property_exists($row, 'price_max') && $row->price_max !== null ? (float) $row->price_max : null,
                        'price_after_service' => property_exists($row, 'price_after_service') ? (bool) $row->price_after_service : null,
                        'main_service' => property_exists($row, 'main_service') ? $row->main_service : null,
                    ];
                })->values();
            });

        $data = $doctorRows->map(function ($doctor) use ($clinicRows, $servicesByClinic, $serviceType) {
            $clinicId = (int) $doctor->vet_registeration_id;
            $clinic = $clinicRows->get($clinicId);
            $services = $servicesByClinic->get($clinicId, collect())->values();

            return [
                'doctor_id' => (int) $doctor->id,
                'doctor_name' => $doctor->doctor_name,
                'doctor_email' => $doctor->doctor_email,
                'doctor_mobile' => $doctor->doctor_mobile,
                'doctor_license' => $doctor->doctor_license,
                'doctor_status' => $doctor->doctor_status,
                'toggle_availability' => $doctor->toggle_availability,
                'consultation_price' => $doctor->doctors_price !== null ? (float) $doctor->doctors_price : null,
                'video_day_rate' => $doctor->video_day_rate !== null ? (float) $doctor->video_day_rate : null,
                'video_night_rate' => $doctor->video_night_rate !== null ? (float) $doctor->video_night_rate : null,
                'service_type' => $serviceType,
                'clinic' => [
                    'id' => $clinicId,
                    'name' => $clinic->clinic_name ?? null,
                    'slug' => $clinic->slug ?? null,
                    'city' => $clinic->city ?? null,
                    'mobile' => $clinic->mobile ?? null,
                    'address' => $clinic->address ?? null,
                ],
                'services' => $services,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'service_type' => $serviceType,
            'clinic_count' => $clinicIds->count(),
            'doctor_count' => $data->count(),
            'data' => $data,
        ]);
    }

    private function normalizeServiceType(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtolower($value);
    }
}
