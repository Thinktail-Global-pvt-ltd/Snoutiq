<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorServiceCategoryController extends Controller
{
    private const SERVICE_CATEGORY_CODE_MAP = [
        'boarding' => 'Boarding',
        'cat_neutering' => 'Cat Neutering',
        'cat_package' => 'Cat Package',
        'cat_service' => 'Cat Service',
        'cat_vaccine' => 'Cat Vaccine',
        'dog_neutering' => 'Dog Neutering',
        'dog_package' => 'Dog Package',
        'dog_service' => 'Dog Service',
        'dog_vaccine' => 'Dog Vaccine',
    ];

    // GET /api/doctors/by-service-category?service_category_code=boarding
    // OR  /api/doctors/by-service-category?service_type=Boarding
    public function index(Request $request)
    {
        $payload = $request->validate([
            'service_type' => ['nullable', 'string', 'max:120', 'required_without:service_category_code'],
            'service_category_code' => ['nullable', 'string', 'max:120', 'required_without:service_type'],
        ]);

        $requestedCode = isset($payload['service_category_code'])
            ? $this->normalizeCode((string) $payload['service_category_code'])
            : null;
        $serviceType = trim((string) ($payload['service_type'] ?? ''));

        if ($requestedCode !== null && $requestedCode !== '') {
            $serviceType = self::SERVICE_CATEGORY_CODE_MAP[$requestedCode] ?? $serviceType;
        }

        if ($serviceType === '' && ($requestedCode === null || $requestedCode === '')) {
            return response()->json([
                'success' => false,
                'message' => 'service_type or service_category_code is required.',
            ], 422);
        }

        $normalizedServiceType = $this->normalizeText($serviceType);
        $resolvedCode = $requestedCode ?: ($this->serviceCategoryCodeFromType($serviceType) ?? '');

        // Primary source: doctor_service_master (contains service_category_code + clinic_rate rows)
        $masterRows = $this->fetchDoctorServiceMasterRows($resolvedCode, $normalizedServiceType);
        if ($masterRows->isNotEmpty()) {
            return response()->json($this->formatDoctorServiceMasterResponse($masterRows, $serviceType, $resolvedCode));
        }

        // Fallback source: legacy clinic/groomer services mapping
        return response()->json($this->buildLegacyServiceResponse($serviceType, $resolvedCode, $normalizedServiceType));
    }

    private function fetchDoctorServiceMasterRows(string $resolvedCode, string $normalizedServiceType): Collection
    {
        if (!Schema::hasTable('doctor_service_master')) {
            return collect();
        }

        $query = DB::table('doctor_service_master');
        $hasCategoryCode = Schema::hasColumn('doctor_service_master', 'service_category_code');
        $hasCategoryName = Schema::hasColumn('doctor_service_master', 'service_category_name');
        $hasServiceTypeName = Schema::hasColumn('doctor_service_master', 'service_type_name');

        if (! $hasCategoryCode && ! $hasCategoryName && ! $hasServiceTypeName) {
            return collect();
        }

        $query->where(function ($where) use ($resolvedCode, $normalizedServiceType, $hasCategoryCode, $hasCategoryName, $hasServiceTypeName) {
            if ($hasCategoryCode && $resolvedCode !== '') {
                $where->orWhereRaw('LOWER(TRIM(service_category_code)) = ?', [$resolvedCode]);
            }
            if ($hasCategoryName && $normalizedServiceType !== '') {
                $where->orWhereRaw('LOWER(TRIM(service_category_name)) = ?', [$normalizedServiceType]);
            }
            if ($hasServiceTypeName && $normalizedServiceType !== '') {
                $where->orWhereRaw('LOWER(TRIM(service_type_name)) = ?', [$normalizedServiceType]);
            }
        });

        $select = ['id'];
        foreach ([
            'doctor_id', 'clinic_id', 'doctor_name', 'clinic_name',
            'service_category_code', 'service_type_code',
            'service_category_name', 'service_type_name',
            'clinic_rate', 'snoutiq_commission', 'currency',
            'created_at', 'updated_at',
        ] as $column) {
            if (Schema::hasColumn('doctor_service_master', $column)) {
                $select[] = $column;
            }
        }

        return $query
            ->select($select)
            ->orderBy('doctor_id')
            ->orderBy('id')
            ->get();
    }

    private function formatDoctorServiceMasterResponse(Collection $rows, string $serviceType, string $resolvedCode): array
    {
        $doctorIds = $rows->pluck('doctor_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $clinicIds = $rows->pluck('clinic_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $doctorsById = collect();
        if (Schema::hasTable('doctors') && $doctorIds->isNotEmpty()) {
            $doctorsById = DB::table('doctors')
                ->whereIn('id', $doctorIds->all())
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
                ->get()
                ->keyBy('id');
        }

        $clinicsById = collect();
        if (Schema::hasTable('vet_registerations_temp') && $clinicIds->isNotEmpty()) {
            $clinicsById = DB::table('vet_registerations_temp')
                ->whereIn('id', $clinicIds->all())
                ->select([
                    'id',
                    DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as clinic_name_resolved'),
                    'slug',
                    'city',
                    'mobile',
                    'address',
                ])
                ->get()
                ->keyBy('id');
        }

        $doctorGroups = $rows->groupBy(function ($row) {
            return ((string) ($row->doctor_id ?? '0')) . '|' . ((string) ($row->clinic_id ?? '0'));
        });

        $data = $doctorGroups->map(function (Collection $doctorRows) use ($doctorsById, $clinicsById, $serviceType, $resolvedCode) {
            $first = $doctorRows->first();
            $doctorId = (int) ($first->doctor_id ?? 0);
            $clinicId = (int) ($first->clinic_id ?? 0);
            $doctorMeta = $doctorsById->get($doctorId);
            $clinicMeta = $clinicsById->get($clinicId);

            $services = $doctorRows->map(function ($row) {
                return [
                    'service_master_id' => (int) ($row->id ?? 0),
                    'service_category_code' => $row->service_category_code ?? null,
                    'service_type_code' => $row->service_type_code ?? null,
                    'service_category_name' => $row->service_category_name ?? null,
                    'service_type_name' => $row->service_type_name ?? null,
                    'price' => isset($row->clinic_rate) ? (float) $row->clinic_rate : null,
                    'clinic_rate' => isset($row->clinic_rate) ? (float) $row->clinic_rate : null,
                    'snoutiq_commission' => isset($row->snoutiq_commission) ? (float) $row->snoutiq_commission : null,
                    'currency' => $row->currency ?? null,
                    'created_at' => $row->created_at ?? null,
                    'updated_at' => $row->updated_at ?? null,
                ];
            })->values();

            return [
                'doctor_id' => $doctorId,
                'doctor_name' => $doctorMeta->doctor_name ?? $first->doctor_name ?? null,
                'doctor_email' => $doctorMeta->doctor_email ?? null,
                'doctor_mobile' => $doctorMeta->doctor_mobile ?? null,
                'doctor_license' => $doctorMeta->doctor_license ?? null,
                'doctor_status' => $doctorMeta->doctor_status ?? null,
                'toggle_availability' => $doctorMeta->toggle_availability ?? null,
                'consultation_price' => isset($doctorMeta->doctors_price) ? (float) $doctorMeta->doctors_price : null,
                'video_day_rate' => isset($doctorMeta->video_day_rate) ? (float) $doctorMeta->video_day_rate : null,
                'video_night_rate' => isset($doctorMeta->video_night_rate) ? (float) $doctorMeta->video_night_rate : null,
                'service_category_code' => $resolvedCode !== '' ? $resolvedCode : ($first->service_category_code ?? null),
                'service_type' => $serviceType,
                'clinic' => [
                    'id' => $clinicId,
                    'name' => $clinicMeta->clinic_name_resolved ?? $first->clinic_name ?? null,
                    'slug' => $clinicMeta->slug ?? null,
                    'city' => $clinicMeta->city ?? null,
                    'mobile' => $clinicMeta->mobile ?? null,
                    'address' => $clinicMeta->address ?? null,
                ],
                'services' => $services,
            ];
        })->values();

        return [
            'success' => true,
            'source' => 'doctor_service_master',
            'service_category_code' => $resolvedCode !== '' ? $resolvedCode : null,
            'service_type' => $serviceType,
            'clinic_count' => $clinicIds->count(),
            'doctor_count' => $data->count(),
            'data' => $data,
        ];
    }

    private function buildLegacyServiceResponse(string $serviceType, string $resolvedCode, string $normalizedServiceType): array
    {
        if (!Schema::hasTable('groomer_services')) {
            return [
                'success' => true,
                'source' => 'legacy_fallback',
                'service_category_code' => $resolvedCode !== '' ? $resolvedCode : $this->serviceCategoryCodeFromType($serviceType),
                'service_type' => $serviceType,
                'doctor_count' => 0,
                'clinic_count' => 0,
                'data' => [],
            ];
        }

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
            return [
                'success' => true,
                'source' => 'legacy_fallback',
                'service_category_code' => $resolvedCode !== '' ? $resolvedCode : $this->serviceCategoryCodeFromType($serviceType),
                'service_type' => $serviceType,
                'doctor_count' => 0,
                'clinic_count' => 0,
                'data' => [],
            ];
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

        $data = $doctorRows->map(function ($doctor) use ($clinicRows, $servicesByClinic, $serviceType, $resolvedCode) {
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
                'service_category_code' => $resolvedCode !== '' ? $resolvedCode : null,
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

        return [
            'success' => true,
            'source' => 'legacy_fallback',
            'service_category_code' => $resolvedCode !== '' ? $resolvedCode : $this->serviceCategoryCodeFromType($serviceType),
            'service_type' => $serviceType,
            'clinic_count' => $clinicIds->count(),
            'doctor_count' => $data->count(),
            'data' => $data,
        ];
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtolower($value);
    }

    private function normalizeCode(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', '_', $value) ?? $value;

        return $value;
    }

    private function serviceCategoryCodeFromType(string $serviceType): ?string
    {
        $normalized = $this->normalizeText($serviceType);
        foreach (self::SERVICE_CATEGORY_CODE_MAP as $code => $label) {
            if ($this->normalizeText($label) === $normalized) {
                return $code;
            }
        }

        return null;
    }
}
