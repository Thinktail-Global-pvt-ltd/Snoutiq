<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSpecializedPackage;
use App\Models\Doctor;
use App\Models\GroomerService;
use App\Models\GroomerServiceCategory;
use App\Models\HomeServiceRequiredByPet;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClinicFullOnboardingController extends Controller
{
    public function show(Request $request, string $clinicId)
    {
        $clinic = VetRegisterationTemp::query()->find((int) $clinicId);
        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        $doctors = Doctor::query()
            ->where('vet_registeration_id', (int) $clinic->id)
            ->orderBy('id')
            ->get();

        $doctorIds = $doctors->pluck('id')->map(fn ($id) => (int) $id)->all();

        $services = GroomerService::query()
            ->where('user_id', (int) $clinic->id)
            ->orderBy('id')
            ->get();

        $specializedPackages = ClinicSpecializedPackage::query()
            ->where('clinic_id', (int) $clinic->id)
            ->orderBy('doctor_id')
            ->get();

        $clinicAvailability = empty($doctorIds)
            ? collect()
            : DB::table('doctor_availability')
                ->whereIn('doctor_id', $doctorIds)
                ->orderBy('doctor_id')
                ->orderBy('service_type')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

        $videoSchedules = empty($doctorIds)
            ? collect()
            : DB::table('doctor_video_availability')
                ->whereIn('doctor_id', $doctorIds)
                ->where('is_active', 1)
                ->orderBy('doctor_id')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('doctor_id')
                ->map(function ($rows, $doctorId) use ($doctors) {
                    $doctor = $doctors->firstWhere('id', (int) $doctorId);

                    return [
                        'doctor_id' => (int) $doctorId,
                        'doctor_name' => $doctor?->doctor_name,
                        'day_rate' => $doctor?->video_day_rate === null ? null : (float) $doctor->video_day_rate,
                        'night_rate' => $doctor?->video_night_rate === null ? null : (float) $doctor->video_night_rate,
                        'availability' => $rows->values(),
                    ];
                })
                ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => $clinic,
                'doctors' => $doctors,
                'services' => $services,
                'specialized_packages' => $specializedPackages,
                'clinic_availability' => $clinicAvailability,
                'video_schedules' => $videoSchedules,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:15'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:20'],
            'coordinates' => ['nullable', 'array'],
            'address' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6'],
            'hospital_profile' => ['nullable', 'string'],
            'clinic_profile' => ['nullable', 'string'],
            'clinic_image' => ['nullable'],
            'clinic_video' => ['nullable'],

            'doctors' => ['required', 'array', 'min:1'],
            'doctors.*.doctor_name' => ['required', 'string', 'max:255'],
            'doctors.*.doctor_email' => ['nullable', 'email', 'max:255'],
            'doctors.*.doctor_mobile' => ['nullable', 'string', 'max:15'],
            'doctors.*.doctor_license' => ['nullable', 'string', 'max:255'],

            'services' => ['nullable', 'array'],
            'services.*.serviceName' => ['required_with:services', 'string', 'max:255'],
            'services.*.description' => ['nullable', 'string'],
            'services.*.petType' => ['nullable', 'string', 'max:255'],
            'services.*.price' => ['nullable', 'numeric', 'min:0'],
            'services.*.price_min' => ['nullable', 'numeric', 'min:0'],
            'services.*.price_max' => ['nullable', 'numeric', 'min:0'],
            'services.*.price_after_service' => ['nullable', 'boolean'],
            'services.*.duration' => ['nullable', 'integer', 'min:1'],
            'services.*.main_service' => ['nullable', 'string', 'max:255'],
            'services.*.status' => ['nullable', 'string', 'max:255'],
            'services.*.serviceCategory' => ['nullable', 'integer', 'exists:groomer_service_categories,id'],

            'specialized_package' => ['nullable', 'array'],
            'specialized_package.doctor_index' => ['nullable', 'integer', 'min:0'],
            'specialized_package.dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_neutering_price' => ['nullable', 'numeric', 'min:0'],

            'clinic_availability' => ['nullable', 'array'],
            'clinic_availability.*.service_type' => ['nullable', 'string', 'in:video,in_clinic,home_visit'],
            'clinic_availability.*.day_of_week' => ['required_with:clinic_availability', 'integer', 'min:0', 'max:6'],
            'clinic_availability.*.start_time' => ['required_with:clinic_availability'],
            'clinic_availability.*.end_time' => ['required_with:clinic_availability'],
            'clinic_availability.*.break_start' => ['nullable'],
            'clinic_availability.*.break_end' => ['nullable'],
            'clinic_availability.*.avg_consultation_mins' => ['nullable', 'integer'],
            'clinic_availability.*.max_bookings_per_hour' => ['nullable', 'integer'],

            'video_schedule' => ['nullable', 'array'],
            'video_schedule.doctor_index' => ['nullable', 'integer', 'min:0'],
            'video_schedule.day_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'video_schedule.night_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'video_schedule.availability' => ['nullable', 'array'],
            'video_schedule.availability.*.day_of_week' => ['required_with:video_schedule.availability', 'integer', 'min:0', 'max:6'],
            'video_schedule.availability.*.start_time' => ['required_with:video_schedule.availability'],
            'video_schedule.availability.*.end_time' => ['required_with:video_schedule.availability'],
            'video_schedule.availability.*.break_start' => ['nullable'],
            'video_schedule.availability.*.break_end' => ['nullable'],
            'video_schedule.availability.*.avg_consultation_mins' => ['nullable', 'integer'],
            'video_schedule.availability.*.max_bookings_per_hour' => ['nullable', 'integer'],

            'home_service_required_by_pet' => ['nullable', 'array'],
            'home_service_required_by_pet.user_id' => ['required_with:home_service_required_by_pet', 'integer', 'exists:users,id'],
            'home_service_required_by_pet.pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'home_service_required_by_pet.latest_completed_step' => ['nullable', 'integer', 'min:1', 'max:3'],
            'home_service_required_by_pet.owner_name' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.owner_phone' => ['nullable', 'string', 'max:30'],
            'home_service_required_by_pet.pet_type' => ['nullable', 'string', 'max:50'],
            'home_service_required_by_pet.area' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.reason_for_visit' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.date_of_visit' => ['nullable', 'date'],
            'home_service_required_by_pet.time_of_visit' => ['nullable', 'date_format:H:i'],
            'home_service_required_by_pet.concern_description' => ['nullable', 'string'],
            'home_service_required_by_pet.symptoms' => ['nullable', 'array'],
            'home_service_required_by_pet.symptoms.*' => ['string', 'max:120'],
            'home_service_required_by_pet.vaccination_status' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.last_deworming' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.past_illnesses_or_surgeries' => ['nullable', 'string'],
            'home_service_required_by_pet.current_medications' => ['nullable', 'string'],
            'home_service_required_by_pet.known_allergies' => ['nullable', 'string'],
            'home_service_required_by_pet.vet_notes' => ['nullable', 'string'],
            'home_service_required_by_pet.payment_status' => ['nullable', 'string', 'max:20'],
            'home_service_required_by_pet.amount_payable' => ['nullable', 'numeric', 'min:0'],
            'home_service_required_by_pet.amount_paid' => ['nullable', 'numeric', 'min:0'],
            'home_service_required_by_pet.payment_provider' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.payment_reference' => ['nullable', 'string', 'max:255'],
            'home_service_required_by_pet.booking_reference' => ['nullable', 'string', 'max:255', 'unique:home_service_required_by_pet,booking_reference'],

            'dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'dog_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'cat_neutering_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = DB::transaction(function () use ($request, $data) {
            $clinic = $this->createClinic($request, $data);
            $doctors = $this->createDoctors($clinic, $data['doctors']);
            $services = $this->createServices((int) $clinic->id, $data['services'] ?? []);
            $specializedPackage = $this->createSpecializedPackage((int) $clinic->id, $doctors, $data);
            $clinicAvailabilityCount = $this->createClinicAvailability($doctors, $data['clinic_availability'] ?? []);
            $videoAvailabilityCount = $this->createVideoAvailability($doctors, $data['video_schedule'] ?? null);
            $homeServiceRequiredByPet = $this->createHomeServiceRequiredByPet($data['home_service_required_by_pet'] ?? null);

            return [
                'clinic' => $clinic->fresh(),
                'doctors' => $doctors,
                'services' => $services,
                'specialized_package' => $specializedPackage,
                'clinic_availability_rows' => $clinicAvailabilityCount,
                'video_availability_rows' => $videoAvailabilityCount,
                'home_service_required_by_pet' => $homeServiceRequiredByPet,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Clinic, doctors, services and specialized package prices created successfully.',
            'data' => $result,
        ], 201);
    }

    private function createClinic(Request $request, array $data): VetRegisterationTemp
    {
        $clinicData = collect($data)->only([
            'email',
            'mobile',
            'employee_id',
            'name',
            'city',
            'pincode',
            'coordinates',
            'address',
            'bio',
            'password',
            'hospital_profile',
            'clinic_profile',
        ])->toArray();

        $clinicData['password'] = $clinicData['password'] ?? '123456';
        $clinicData['slug'] = $this->makeUniqueSlug($clinicData['name']);
        $clinicData['status'] = 'active';
        $clinicData['claim_token'] = null;
        $clinicData['draft_expires_at'] = null;
        $clinicData['claimed_at'] = now();

        if (isset($clinicData['coordinates'])) {
            $clinicData['coordinates'] = json_encode($clinicData['coordinates']);
        }

        foreach (['clinic_image', 'clinic_video'] as $field) {
            if (! Schema::hasColumn('vet_registerations_temp', $field)) {
                continue;
            }

            $blob = $this->decodeBlobInput($request, $field);
            if ($blob !== null) {
                $clinicData[$field] = $blob;
            }
        }

        $clinic = VetRegisterationTemp::create($clinicData);
        if (! $clinic->owner_user_id) {
            $clinic->owner_user_id = $clinic->id;
            $clinic->save();
        }

        return $clinic;
    }

    private function createDoctors(VetRegisterationTemp $clinic, array $doctorRows)
    {
        return collect($doctorRows)->map(function (array $doctorRow) use ($clinic) {
            return Doctor::create([
                'vet_registeration_id' => $clinic->id,
                'doctor_name' => $doctorRow['doctor_name'],
                'doctor_email' => $doctorRow['doctor_email'] ?? null,
                'doctor_mobile' => $doctorRow['doctor_mobile'] ?? null,
                'doctor_license' => $doctorRow['doctor_license'] ?? null,
            ]);
        })->values();
    }

    private function createServices(int $clinicId, array $serviceRows)
    {
        if (empty($serviceRows)) {
            return collect();
        }

        $hasPriceMin = Schema::hasColumn('groomer_services', 'price_min');
        $hasPriceMax = Schema::hasColumn('groomer_services', 'price_max');
        $hasPriceAfterService = Schema::hasColumn('groomer_services', 'price_after_service');
        $hasCategoryColumn = Schema::hasColumn('groomer_services', 'groomer_service_category_id');

        return collect($serviceRows)->map(function (array $serviceRow) use (
            $clinicId,
            $hasPriceMin,
            $hasPriceMax,
            $hasPriceAfterService,
            $hasCategoryColumn
        ) {
            $priceAfterService = filter_var($serviceRow['price_after_service'] ?? false, FILTER_VALIDATE_BOOLEAN);
            [$priceMin, $priceMax, $priceValue] = $this->resolvePriceRange($serviceRow, $priceAfterService);

            $serviceData = [
                'user_id' => $clinicId,
                'name' => $serviceRow['serviceName'],
                'description' => $serviceRow['description'] ?? null,
                'pet_type' => $serviceRow['petType'] ?? 'all',
                'price' => $priceAfterService ? null : $priceValue,
                'duration' => $serviceRow['duration'] ?? 30,
                'main_service' => $serviceRow['main_service'] ?? 'vet',
                'status' => $serviceRow['status'] ?? 'Active',
            ];

            if ($hasCategoryColumn) {
                $serviceData['groomer_service_category_id'] = $this->resolveServiceCategoryId(
                    $clinicId,
                    $serviceRow['serviceCategory'] ?? null
                );
            }
            if ($hasPriceMin) {
                $serviceData['price_min'] = $priceAfterService ? null : $priceMin;
            }
            if ($hasPriceMax) {
                $serviceData['price_max'] = $priceAfterService ? null : $priceMax;
            }
            if ($hasPriceAfterService) {
                $serviceData['price_after_service'] = $priceAfterService;
            }

            return GroomerService::create($serviceData);
        })->values();
    }

    private function createSpecializedPackage(int $clinicId, $doctors, array $data): ?ClinicSpecializedPackage
    {
        $package = $data['specialized_package'] ?? [
            'dog_vaccination_package_price' => $data['dog_vaccination_package_price'] ?? null,
            'cat_vaccination_package_price' => $data['cat_vaccination_package_price'] ?? null,
            'dog_neutering_price' => $data['dog_neutering_price'] ?? null,
            'cat_neutering_price' => $data['cat_neutering_price'] ?? null,
        ];

        $priceKeys = [
            'dog_vaccination_package_price',
            'cat_vaccination_package_price',
            'dog_neutering_price',
            'cat_neutering_price',
        ];

        $hasAnyPrice = collect($priceKeys)->contains(fn (string $key) => array_key_exists($key, $package) && $package[$key] !== null && $package[$key] !== '');
        if (! $hasAnyPrice) {
            return null;
        }

        $doctorIndex = (int) ($package['doctor_index'] ?? 0);
        $doctor = $doctors->get($doctorIndex);
        if (! $doctor) {
            throw ValidationException::withMessages([
                'specialized_package.doctor_index' => ['The selected doctor index does not exist in doctors array.'],
            ]);
        }

        return ClinicSpecializedPackage::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $doctor->id,
            'dog_vaccination_package_price' => $package['dog_vaccination_package_price'] ?? null,
            'cat_vaccination_package_price' => $package['cat_vaccination_package_price'] ?? null,
            'dog_neutering_price' => $package['dog_neutering_price'] ?? null,
            'cat_neutering_price' => $package['cat_neutering_price'] ?? null,
        ]);
    }

    private function createClinicAvailability($doctors, array $availabilityRows): int
    {
        if (empty($availabilityRows)) {
            return 0;
        }

        $doctorIds = $doctors->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($doctorIds)) {
            return 0;
        }

        $insertRows = [];
        foreach ($doctorIds as $doctorId) {
            foreach ($availabilityRows as $row) {
                $insertRows[] = [
                    'doctor_id' => $doctorId,
                    'service_type' => $row['service_type'] ?? 'in_clinic',
                    'day_of_week' => $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'break_start' => $row['break_start'] ?? null,
                    'break_end' => $row['break_end'] ?? null,
                    'avg_consultation_mins' => $row['avg_consultation_mins'] ?? 20,
                    'max_bookings_per_hour' => $row['max_bookings_per_hour'] ?? 3,
                    'is_active' => 1,
                ];
            }
        }

        DB::table('doctor_availability')->insert($insertRows);

        return count($insertRows);
    }

    private function createHomeServiceRequiredByPet(?array $homeServiceData): ?HomeServiceRequiredByPet
    {
        if (empty($homeServiceData)) {
            return null;
        }

        $data = collect($homeServiceData)->only([
            'user_id',
            'pet_id',
            'latest_completed_step',
            'owner_name',
            'owner_phone',
            'pet_type',
            'area',
            'reason_for_visit',
            'date_of_visit',
            'time_of_visit',
            'concern_description',
            'symptoms',
            'vaccination_status',
            'last_deworming',
            'past_illnesses_or_surgeries',
            'current_medications',
            'known_allergies',
            'vet_notes',
            'payment_status',
            'amount_payable',
            'amount_paid',
            'payment_provider',
            'payment_reference',
            'booking_reference',
        ])->toArray();

        $data['latest_completed_step'] = $data['latest_completed_step'] ?? 1;
        $data['payment_status'] = $data['payment_status'] ?? 'pending';
        $data['booking_reference'] = $data['booking_reference'] ?? 'HSV-'.Str::upper(Str::random(10));

        if (! empty($data['time_of_visit']) && preg_match('/^\d{2}:\d{2}$/', (string) $data['time_of_visit'])) {
            $data['time_of_visit'] .= ':00';
        }

        if (! empty($data['date_of_visit']) || ! empty($data['time_of_visit'])) {
            $data['latest_completed_step'] = max((int) $data['latest_completed_step'], 2);
            $data['step2_completed_at'] = now();
        }

        if ((int) $data['latest_completed_step'] >= 1) {
            $data['step1_completed_at'] = now();
        }
        if ((int) $data['latest_completed_step'] >= 3) {
            $data['step3_completed_at'] = now();
            $data['confirmed_at'] = now();
        }

        return HomeServiceRequiredByPet::create($data);
    }

    private function createVideoAvailability($doctors, ?array $videoSchedule): int
    {
        $availabilityRows = $videoSchedule['availability'] ?? [];
        if (empty($videoSchedule) || empty($availabilityRows)) {
            return 0;
        }

        $doctorIndex = (int) ($videoSchedule['doctor_index'] ?? 0);
        $doctor = $doctors->get($doctorIndex);
        if (! $doctor) {
            throw ValidationException::withMessages([
                'video_schedule.doctor_index' => ['The selected doctor index does not exist in doctors array.'],
            ]);
        }

        $rateUpdates = [];
        if (array_key_exists('day_rate', $videoSchedule)) {
            $rateUpdates['video_day_rate'] = $videoSchedule['day_rate'] === null ? null : (float) $videoSchedule['day_rate'];
        }
        if (array_key_exists('night_rate', $videoSchedule)) {
            $rateUpdates['video_night_rate'] = $videoSchedule['night_rate'] === null ? null : (float) $videoSchedule['night_rate'];
        }
        if (! empty($rateUpdates)) {
            DB::table('doctors')->where('id', (int) $doctor->id)->update($rateUpdates);
        }

        $insertRows = [];
        foreach ($availabilityRows as $row) {
            $insertRows[] = [
                'doctor_id' => (int) $doctor->id,
                'day_of_week' => $row['day_of_week'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'break_start' => $row['break_start'] ?? null,
                'break_end' => $row['break_end'] ?? null,
                'avg_consultation_mins' => $row['avg_consultation_mins'] ?? 20,
                'max_bookings_per_hour' => $row['max_bookings_per_hour'] ?? 3,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('doctor_video_availability')->insert($insertRows);

        return count($insertRows);
    }

    private function decodeBlobInput(Request $request, string $field): ?string
    {
        if ($request->hasFile($field)) {
            return $request->file($field)->get();
        }

        if (! $request->filled($field)) {
            return null;
        }

        $value = (string) $request->input($field);
        if (! preg_match('/^data:([^;]+);base64,(.*)$/s', $value, $matches)) {
            throw ValidationException::withMessages([
                $field => ['The '.$field.' field must be a file upload or a base64 data URI.'],
            ]);
        }

        $binary = base64_decode(str_replace(' ', '+', $matches[2]), true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                $field => ['The '.$field.' field contains invalid base64 data.'],
            ]);
        }

        return $binary;
    }

    private function resolvePriceRange(array $serviceRow, bool $priceAfterService): array
    {
        if ($priceAfterService) {
            return [null, null, null];
        }

        $price = $serviceRow['price'] ?? null;
        $priceMin = $serviceRow['price_min'] ?? $price;
        $priceMax = $serviceRow['price_max'] ?? $price;

        if ($priceMin === null && $priceMax === null) {
            throw ValidationException::withMessages([
                'services' => ['Price is required unless price_after_service is true.'],
            ]);
        }

        $priceMin = $priceMin !== null && $priceMin !== '' ? (float) $priceMin : null;
        $priceMax = $priceMax !== null && $priceMax !== '' ? (float) $priceMax : null;

        if ($priceMin !== null && $priceMax === null) {
            $priceMax = $priceMin;
        } elseif ($priceMax !== null && $priceMin === null) {
            $priceMin = $priceMax;
        }

        if ($priceMin !== null && $priceMax !== null && $priceMax < $priceMin) {
            throw ValidationException::withMessages([
                'services' => ['Max price must be greater than or equal to min price.'],
            ]);
        }

        return [$priceMin, $priceMax, $priceMax ?? $priceMin ?? 0];
    }

    private function resolveServiceCategoryId(int $clinicId, ?int $serviceCategoryId): int
    {
        if ($serviceCategoryId) {
            return $serviceCategoryId;
        }

        $existing = GroomerServiceCategory::where('user_id', $clinicId)->value('id')
            ?: GroomerServiceCategory::value('id');

        if ($existing) {
            return (int) $existing;
        }

        $safeUserId = DB::table('users')->min('id');
        if (! $safeUserId) {
            throw ValidationException::withMessages([
                'services' => ['No users available to assign a default service category.'],
            ]);
        }

        return GroomerServiceCategory::firstOrCreate(
            ['user_id' => $safeUserId, 'name' => 'Default'],
            ['name' => 'Default']
        )->id;
    }

    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::random(6);
        $slug = $base;
        $i = 1;

        while (VetRegisterationTemp::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
