<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSpecializedPackage;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\GroomerService;
use App\Models\GroomerServiceCategory;
use App\Models\VetAtHomeService;
use App\Models\VetRegisterationTemp;
use App\Services\ClinicProfileCompletionService;
use App\Services\Push\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClinicFullOnboardingController extends Controller
{
    public function __construct(
        private readonly ClinicProfileCompletionService $clinicProfileCompletionService,
        private readonly FcmService $fcm,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min($perPage, 100));
        $fromDate = $filters['from_date'] ?? '2026-05-10';

        $clinics = VetRegisterationTemp::query()
            ->where('created_at', '>=', $fromDate.' 00:00:00')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $clinics->getCollection()->transform(fn (VetRegisterationTemp $clinic) => $this->fullPayloadForClinic($clinic));

        return response()->json([
            'success' => true,
            'data' => $clinics,
        ]);
    }

    public function show(Request $request, string $clinicId)
    {
        $clinic = VetRegisterationTemp::query()->find((int) $clinicId);
        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->fullPayloadForClinic($clinic),
        ]);
    }

    public function sendProfileCompletionNotification(Request $request, string $clinicId)
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer'],
            'force' => ['nullable', 'boolean'],
        ]);

        $clinic = VetRegisterationTemp::query()->find((int) $clinicId);
        if (! $clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found.',
            ], 404);
        }

        $doctor = Doctor::query()
            ->whereKey((int) $validated['doctor_id'])
            ->where('vet_registeration_id', (int) $clinic->id)
            ->first();

        if (! $doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found for this clinic.',
            ], 404);
        }

        if (! Schema::hasTable('device_tokens')) {
            return response()->json([
                'success' => false,
                'message' => 'Device token storage is not available.',
            ], 503);
        }

        $tokens = DeviceToken::query()
            ->where('user_id', (int) $doctor->id)
            ->whereNotNull('token')
            ->pluck('token')
            ->filter()
            ->map(fn ($token) => trim(trim((string) $token), "\"'"))
            ->filter(fn (string $token) => $token !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'No FCM token found for this doctor.',
            ], 404);
        }

        $payload = $this->fullPayloadForClinic($clinic);
        $completion = $payload['profile_completion'] ?? [];
        $completionPercent = (int) ($completion['percentage'] ?? 0);
        $missingFields = collect($completion['missing_fields'] ?? [])
            ->pluck('label')
            ->filter()
            ->values()
            ->all();

        $force = filter_var($validated['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($completionPercent >= 100 && ! $force) {
            return response()->json([
                'success' => true,
                'sent' => false,
                'message' => 'Clinic profile is already complete.',
                'data' => [
                    'clinic_id' => (int) $clinic->id,
                    'doctor_id' => (int) $doctor->id,
                    'profile_completion_percentage' => $completionPercent,
                    'missing_fields' => $missingFields,
                ],
            ]);
        }

        $title = 'Complete your Snoutiq profile';
        $body = sprintf(
            '%s is %d%% complete. Add missing details to get more profile views and bookings.',
            $clinic->name ?: 'Your clinic profile',
            $completionPercent
        );

        $data = [
            'type' => 'clinic_profile_completion',
            'clinic_id' => (string) $clinic->id,
            'doctor_id' => (string) $doctor->id,
            'completion_percent' => (string) $completionPercent,
            'missing_fields' => $missingFields,
            'deepLink' => '/vet-dashboard',
        ];

        try {
            $fcmResult = $this->fcm->sendMulticast($tokens, $title, $body, $data);
        } catch (Throwable $e) {
            Log::error('clinic.profile_completion_push.failed', [
                'clinic_id' => (int) $clinic->id,
                'doctor_id' => (int) $doctor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send profile completion notification.',
            ], 500);
        }

        $fcmSuccess = (int) ($fcmResult['success'] ?? 0);
        $fcmFailure = (int) ($fcmResult['failure'] ?? count($tokens));
        $fcmErrors = collect($fcmResult['results'] ?? [])
            ->filter(fn ($result) => ! (bool) ($result['ok'] ?? false))
            ->map(fn ($result) => [
                'code' => $result['code'] ?? null,
                'error' => $result['error'] ?? null,
            ])
            ->values()
            ->all();

        if ($fcmSuccess <= 0) {
            Log::warning('clinic.profile_completion_push.no_successful_tokens', [
                'clinic_id' => (int) $clinic->id,
                'doctor_id' => (int) $doctor->id,
                'token_count' => count($tokens),
                'fcm_success' => $fcmSuccess,
                'fcm_failure' => $fcmFailure,
                'fcm_errors' => $fcmErrors,
            ]);

            return response()->json([
                'success' => false,
                'sent' => false,
                'message' => 'FCM notification failed for all doctor tokens.',
                'data' => [
                    'clinic_id' => (int) $clinic->id,
                    'doctor_id' => (int) $doctor->id,
                    'profile_completion_percentage' => $completionPercent,
                    'missing_fields' => $missingFields,
                    'token_count' => count($tokens),
                    'fcm_success' => $fcmSuccess,
                    'fcm_failure' => $fcmFailure,
                    'fcm_errors' => $fcmErrors,
                ],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'sent' => true,
            'message' => 'Profile completion notification sent to doctor.',
            'data' => [
                'clinic_id' => (int) $clinic->id,
                'doctor_id' => (int) $doctor->id,
                'profile_completion_percentage' => $completionPercent,
                'missing_fields' => $missingFields,
                'token_count' => count($tokens),
                'fcm_success' => $fcmSuccess,
                'fcm_failure' => $fcmFailure,
                'fcm_errors' => $fcmErrors,
            ],
        ]);
    }

    private function fullPayloadForClinic(VetRegisterationTemp $clinic): array
    {
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

        $vetAtHomeServices = VetAtHomeService::query()
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

        $profileCompletion = $this->clinicProfileCompletionService->calculate(
            $clinic,
            $doctors,
            $services,
            $specializedPackages,
            $vetAtHomeServices,
            $clinicAvailability,
            $videoSchedules
        );

        return [
            'clinic' => $this->clinicPayloadWithMediaUrls($clinic),
            'profile_completion_percentage' => $profileCompletion['percentage'],
            'profile_completion' => $profileCompletion,
            'doctors' => $doctors,
            'services' => $services,
            'specialized_packages' => $specializedPackages,
            'vet_at_home_services' => $vetAtHomeServices,
            'clinic_availability' => $clinicAvailability,
            'video_schedules' => $videoSchedules,
        ];
    }

    private function clinicPayloadWithMediaUrls(VetRegisterationTemp $clinic): array
    {
        $payload = $clinic->toArray();

        $payload['clinic_image_url'] = ! empty($clinic->clinic_image)
            ? route('clinics.media.image', ['clinic' => $clinic->id])
            : null;
        $payload['clinic_video_url'] = ! empty($clinic->clinic_video)
            ? route('clinics.media.video', ['clinic' => $clinic->id])
            : null;

        return $payload;
    }

    public function store(Request $request)
    {
        $request->merge($this->normalizeVaccinationPackageAliases($request->all()));

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
            'doctors.*.years_of_experience' => ['nullable', 'string', 'max:255'],
            'doctors.*.degree' => ['nullable', 'string', 'max:255'],
            'doctors.*.specialization_select_all_that_apply' => ['nullable'],
            'doctors.*.doctor_image' => ['nullable'],

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

            'machinery' => ['nullable', 'array'],
            'machinery.*.name' => ['required_with:machinery', 'string', 'max:255'],
            'machinery.*.image' => ['nullable'],

            'specialized_package' => ['nullable', 'array'],
            'specialized_package.doctor_index' => ['nullable', 'integer', 'min:0'],
            'specialized_package.dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.puppy_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.adult_dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.kitten_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.adult_cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_vaccination_male_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_vaccination_female_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_vaccination_male_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_vaccination_female_package_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_neutering_male_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.dog_neutering_female_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_neutering_male_price' => ['nullable', 'numeric', 'min:0'],
            'specialized_package.cat_neutering_female_price' => ['nullable', 'numeric', 'min:0'],

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

            'vet_at_home_service' => ['nullable', 'array'],
            'vet_at_home_service.doctor_index' => ['nullable', 'integer', 'min:0'],
            'vet_at_home_service.is_enabled' => ['nullable', 'boolean'],
            'vet_at_home_service.service_hours' => ['nullable', 'string', 'max:255'],
            'vet_at_home_service.response_time' => ['nullable', 'string', 'max:255'],
            'vet_at_home_service.base_payout' => ['nullable', 'numeric', 'min:0'],
            'vet_at_home_service.protocol_label' => ['nullable', 'string', 'max:255'],

            'dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'dog_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'cat_neutering_price' => ['nullable', 'numeric', 'min:0'],
            'puppy_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'adult_dog_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'kitten_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'adult_cat_vaccination_package_price' => ['nullable', 'numeric', 'min:0'],
            'dog_vaccination_male_package_price' => ['nullable', 'numeric', 'min:0'],
            'dog_vaccination_female_package_price' => ['nullable', 'numeric', 'min:0'],
            'cat_vaccination_male_package_price' => ['nullable', 'numeric', 'min:0'],
            'cat_vaccination_female_package_price' => ['nullable', 'numeric', 'min:0'],
            'dog_neutering_male_price' => ['nullable', 'numeric', 'min:0'],
            'dog_neutering_female_price' => ['nullable', 'numeric', 'min:0'],
            'cat_neutering_male_price' => ['nullable', 'numeric', 'min:0'],
            'cat_neutering_female_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = DB::transaction(function () use ($request, $data) {
            $clinic = $this->createClinic($request, $data);
            $doctors = $this->createDoctors($request, $clinic, $data['doctors']);
            $services = $this->createServices((int) $clinic->id, $data['services'] ?? []);
            $machinery = $this->createMachineryServices($request, (int) $clinic->id, $data['machinery'] ?? []);
            $specializedPackage = $this->createSpecializedPackage((int) $clinic->id, $doctors, $data);
            $clinicAvailabilityCount = $this->createClinicAvailability($doctors, $data['clinic_availability'] ?? []);
            $videoAvailabilityCount = $this->createVideoAvailability($doctors, $data['video_schedule'] ?? null);
            $vetAtHomeService = $this->createVetAtHomeService((int) $clinic->id, $doctors, $data['vet_at_home_service'] ?? null);

            return [
                'clinic' => $clinic->fresh(),
                'doctors' => $doctors,
                'services' => $services,
                'machinery' => $machinery,
                'specialized_package' => $specializedPackage,
                'vet_at_home_service' => $vetAtHomeService,
                'clinic_availability_rows' => $clinicAvailabilityCount,
                'video_availability_rows' => $videoAvailabilityCount,
            ];
        });

        $result['pet_parent_notification'] = $this->sendNewDoctorNotificationToPetParents(
            $result['clinic'],
            $result['doctors']
        );

        return response()->json([
            'success' => true,
            'message' => 'Clinic, doctors, services and specialized package prices created successfully.',
            'data' => $result,
        ], 201);
    }

    private function sendNewDoctorNotificationToPetParents(VetRegisterationTemp $clinic, $doctors): array
    {
        if (! Schema::hasTable('device_tokens') || ! Schema::hasTable('users')) {
            return [
                'sent' => false,
                'reason' => 'Device token or user storage is not available.',
                'targeted' => 0,
                'success' => 0,
                'failure' => 0,
            ];
        }

        $doctor = $doctors->first();
        if (! $doctor) {
            return [
                'sent' => false,
                'reason' => 'No doctor was created.',
                'targeted' => 0,
                'success' => 0,
                'failure' => 0,
            ];
        }

        $doctor = $doctor->fresh() ?: $doctor;

        $title = "Snoutiq's verified network just got stronger";
        $body = $this->newDoctorNotificationBody($clinic, $doctor, (int) $doctors->count());
        $data = [
            'type' => 'new_doctor_added',
            'clinic_id' => (string) $clinic->id,
            'clinic_name' => (string) ($clinic->name ?? ''),
            'doctor_id' => (string) $doctor->id,
            'doctor_name' => (string) ($doctor->doctor_name ?? ''),
            'specialization' => $this->doctorSpecializationForMessage($doctor),
            'experience' => $this->doctorExperienceForMessage($doctor),
            'deepLink' => '/clinics/'.$clinic->id,
            'cta' => 'Book Now',
        ];

        $summary = [
            'sent' => false,
            'targeted' => 0,
            'success' => 0,
            'failure' => 0,
            'errors' => [],
        ];

        try {
            DeviceToken::query()
                ->select(['device_tokens.id', 'device_tokens.token', 'device_tokens.meta'])
                ->join('users', 'users.id', '=', 'device_tokens.user_id')
                ->whereNotNull('device_tokens.token')
                ->when(Schema::hasColumn('users', 'role'), function ($query) {
                    $query->where(function ($roleQuery) {
                        $roleQuery
                            ->whereNull('users.role')
                            ->orWhereIn('users.role', ['pet', 'pet_owner', 'pet_parent', 'user']);
                    });
                })
                ->orderBy('device_tokens.id')
                ->chunkById(500, function ($deviceTokens) use ($title, $body, $data, &$summary) {
                    $tokens = $deviceTokens
                        ->filter(fn (DeviceToken $deviceToken) => $this->isPetParentDeviceToken($deviceToken))
                        ->pluck('token')
                        ->map(fn ($token) => trim(trim((string) $token), "\"'"))
                        ->filter(fn (string $token) => $token !== '')
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($tokens)) {
                        return;
                    }

                    $summary['targeted'] += count($tokens);

                    try {
                        $result = $this->fcm->sendMulticast($tokens, $title, $body, $data);
                        $summary['success'] += (int) ($result['success'] ?? 0);
                        $summary['failure'] += (int) ($result['failure'] ?? 0);
                    } catch (Throwable $e) {
                        $summary['failure'] += count($tokens);
                        if (count($summary['errors']) < 5) {
                            $summary['errors'][] = $e->getMessage();
                        }
                    }
                }, 'device_tokens.id', 'id');
        } catch (Throwable $e) {
            Log::error('clinic.new_doctor_pet_parent_push.failed', [
                'clinic_id' => (int) $clinic->id,
                'doctor_id' => (int) $doctor->id,
                'error' => $e->getMessage(),
            ]);

            $summary['errors'][] = $e->getMessage();
        }

        $summary['sent'] = $summary['success'] > 0;

        Log::info('clinic.new_doctor_pet_parent_push.completed', [
            'clinic_id' => (int) $clinic->id,
            'doctor_id' => (int) $doctor->id,
            'targeted' => $summary['targeted'],
            'success' => $summary['success'],
            'failure' => $summary['failure'],
        ]);

        return $summary;
    }

    private function newDoctorNotificationBody(VetRegisterationTemp $clinic, Doctor $doctor, int $doctorCount): string
    {
        $doctorName = trim((string) ($doctor->doctor_name ?? 'a new veterinarian'));
        $experience = $this->doctorExperienceForMessage($doctor);
        $clinicName = trim((string) ($clinic->name ?? 'a trusted Snoutiq clinic'));
        $city = trim((string) ($clinic->city ?? ''));
        $clinicLine = $clinicName.($city !== '' ? ', '.$city : '');
        $consultationPrice = $this->doctorConsultationPriceForMessage($doctor);

        return sprintf(
            "%s · %s\nSnoutiq-verified · %s · Consults from %s\nBook Now",
            $doctorName,
            $clinicLine,
            $experience,
            $consultationPrice
        );
    }

    private function doctorSpecializationForMessage(Doctor $doctor): string
    {
        $specialization = trim((string) ($doctor->specialization_select_all_that_apply ?? ''));
        if ($specialization !== '' && str_starts_with($specialization, '[')) {
            $decoded = json_decode($specialization, true);
            if (is_array($decoded)) {
                $specialization = implode(', ', array_filter(array_map(static function ($item) {
                    return is_scalar($item) ? trim((string) $item) : null;
                }, $decoded)));
            }
        }

        return $specialization !== '' ? $specialization : 'General veterinary care';
    }

    private function doctorExperienceForMessage(Doctor $doctor): string
    {
        $experience = trim((string) ($doctor->years_of_experience ?? ''));
        if ($experience === '') {
            return 'Experienced vet care';
        }

        if (preg_match('/experience/i', $experience)) {
            return $experience;
        }

        if (preg_match('/yr|year/i', $experience)) {
            return $experience.' experience';
        }

        return $experience.' yrs experience';
    }

    private function doctorConsultationPriceForMessage(Doctor $doctor): string
    {
        $price = $doctor->video_day_rate ?? $doctor->doctors_price ?? null;
        if ($price === null || $price === '') {
            return '₹499';
        }

        $price = (float) $price;
        $formatted = floor($price) === $price ? (string) (int) $price : number_format($price, 2);

        return '₹'.$formatted;
    }

    private function isPetParentDeviceToken(DeviceToken $deviceToken): bool
    {
        $ownerModel = data_get($deviceToken->meta ?? [], 'owner_model');
        if (! is_string($ownerModel) || trim($ownerModel) === '') {
            return true;
        }

        $ownerModel = strtolower($ownerModel);

        return str_contains($ownerModel, 'user')
            && ! str_contains($ownerModel, 'doctor')
            && ! str_contains($ownerModel, 'vet')
            && ! str_contains($ownerModel, 'clinic');
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

    private function createDoctors(Request $request, VetRegisterationTemp $clinic, array $doctorRows)
    {
        $hasDoctorImageBlob = Schema::hasColumn('doctors', 'doctor_image_blob');
        $hasDoctorImageMime = Schema::hasColumn('doctors', 'doctor_image_mime');
        $hasYearsOfExperience = Schema::hasColumn('doctors', 'years_of_experience');
        $hasDegree = Schema::hasColumn('doctors', 'degree');
        $hasSpecialization = Schema::hasColumn('doctors', 'specialization_select_all_that_apply');

        return collect($doctorRows)->map(function (array $doctorRow, int $index) use (
            $request,
            $clinic,
            $hasDoctorImageBlob,
            $hasDoctorImageMime,
            $hasYearsOfExperience,
            $hasDegree,
            $hasSpecialization
        ) {
            $doctorData = [
                'vet_registeration_id' => $clinic->id,
                'doctor_name' => $doctorRow['doctor_name'],
                'doctor_email' => $doctorRow['doctor_email'] ?? null,
                'doctor_mobile' => $doctorRow['doctor_mobile'] ?? null,
                'doctor_license' => $doctorRow['doctor_license'] ?? null,
            ];

            if ($hasYearsOfExperience && array_key_exists('years_of_experience', $doctorRow)) {
                $doctorData['years_of_experience'] = $doctorRow['years_of_experience'];
            }
            if ($hasDegree && array_key_exists('degree', $doctorRow)) {
                $doctorData['degree'] = $doctorRow['degree'];
            }
            if ($hasSpecialization && array_key_exists('specialization_select_all_that_apply', $doctorRow)) {
                $doctorData['specialization_select_all_that_apply'] = $this->stringifyDoctorSpecialization(
                    $doctorRow['specialization_select_all_that_apply']
                );
            }

            if ($hasDoctorImageBlob) {
                [$blob, $mime] = $this->decodeBlobInputWithMime($request, "doctors.{$index}.doctor_image");
                if ($blob !== null) {
                    $doctorData['doctor_image_blob'] = $blob;
                    if ($hasDoctorImageMime) {
                        $doctorData['doctor_image_mime'] = $mime;
                    }
                }
            }

            return Doctor::create($doctorData);
        })->values();
    }

    private function stringifyDoctorSpecialization($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return implode(', ', array_values(array_filter(array_map(static function ($item) {
                return is_scalar($item) ? trim((string) $item) : null;
            }, $value))));
        }

        return (string) $value;
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

    private function createMachineryServices(Request $request, int $clinicId, array $machineryRows)
    {
        if (empty($machineryRows)) {
            return collect();
        }

        $hasPriceMin = Schema::hasColumn('groomer_services', 'price_min');
        $hasPriceMax = Schema::hasColumn('groomer_services', 'price_max');
        $hasPriceAfterService = Schema::hasColumn('groomer_services', 'price_after_service');
        $hasCategoryColumn = Schema::hasColumn('groomer_services', 'groomer_service_category_id');
        $hasMainService = Schema::hasColumn('groomer_services', 'main_service');
        $hasMachineryImageBlob = Schema::hasColumn('groomer_services', 'machinery_image_blob');
        $hasMachineryImageMime = Schema::hasColumn('groomer_services', 'machinery_image_mime');

        return collect($machineryRows)->map(function (array $machineryRow, int $index) use (
            $request,
            $clinicId,
            $hasPriceMin,
            $hasPriceMax,
            $hasPriceAfterService,
            $hasCategoryColumn,
            $hasMainService,
            $hasMachineryImageBlob,
            $hasMachineryImageMime
        ) {
            $serviceData = [
                'user_id' => $clinicId,
                'name' => $machineryRow['name'],
                'description' => $machineryRow['description'] ?? null,
                'pet_type' => $machineryRow['petType'] ?? 'all',
                'price' => 0,
                'duration' => $machineryRow['duration'] ?? 1,
                'status' => $machineryRow['status'] ?? 'Active',
            ];

            if ($hasMainService) {
                $serviceData['main_service'] = 'machinery';
            }
            if ($hasCategoryColumn) {
                $serviceData['groomer_service_category_id'] = $this->resolveServiceCategoryId($clinicId, null);
            }
            if ($hasPriceMin) {
                $serviceData['price_min'] = 0;
            }
            if ($hasPriceMax) {
                $serviceData['price_max'] = 0;
            }
            if ($hasPriceAfterService) {
                $serviceData['price_after_service'] = false;
            }
            if ($hasMachineryImageBlob) {
                [$blob, $mime] = $this->decodeBlobInputWithMime($request, "machinery.{$index}.image");
                if ($blob !== null) {
                    $serviceData['machinery_image_blob'] = $blob;
                    if ($hasMachineryImageMime) {
                        $serviceData['machinery_image_mime'] = $mime;
                    }
                }
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
            'puppy_vaccination_package_price' => $data['puppy_vaccination_package_price'] ?? null,
            'adult_dog_vaccination_package_price' => $data['adult_dog_vaccination_package_price'] ?? null,
            'kitten_vaccination_package_price' => $data['kitten_vaccination_package_price'] ?? null,
            'adult_cat_vaccination_package_price' => $data['adult_cat_vaccination_package_price'] ?? null,
            'dog_vaccination_male_package_price' => $data['dog_vaccination_male_package_price'] ?? null,
            'dog_vaccination_female_package_price' => $data['dog_vaccination_female_package_price'] ?? null,
            'cat_vaccination_male_package_price' => $data['cat_vaccination_male_package_price'] ?? null,
            'cat_vaccination_female_package_price' => $data['cat_vaccination_female_package_price'] ?? null,
            'dog_neutering_male_price' => $data['dog_neutering_male_price'] ?? null,
            'dog_neutering_female_price' => $data['dog_neutering_female_price'] ?? null,
            'cat_neutering_male_price' => $data['cat_neutering_male_price'] ?? null,
            'cat_neutering_female_price' => $data['cat_neutering_female_price'] ?? null,
        ];

        $priceKeys = [
            'dog_vaccination_package_price',
            'cat_vaccination_package_price',
            'dog_neutering_price',
            'cat_neutering_price',
            'puppy_vaccination_package_price',
            'adult_dog_vaccination_package_price',
            'kitten_vaccination_package_price',
            'adult_cat_vaccination_package_price',
            'dog_vaccination_male_package_price',
            'dog_vaccination_female_package_price',
            'cat_vaccination_male_package_price',
            'cat_vaccination_female_package_price',
            'dog_neutering_male_price',
            'dog_neutering_female_price',
            'cat_neutering_male_price',
            'cat_neutering_female_price',
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
            'puppy_vaccination_package_price' => $package['puppy_vaccination_package_price'] ?? $package['dog_vaccination_male_package_price'] ?? $package['dog_vaccination_package_price'] ?? null,
            'adult_dog_vaccination_package_price' => $package['adult_dog_vaccination_package_price'] ?? $package['dog_vaccination_female_package_price'] ?? $package['dog_vaccination_package_price'] ?? null,
            'kitten_vaccination_package_price' => $package['kitten_vaccination_package_price'] ?? $package['cat_vaccination_male_package_price'] ?? $package['cat_vaccination_package_price'] ?? null,
            'adult_cat_vaccination_package_price' => $package['adult_cat_vaccination_package_price'] ?? $package['cat_vaccination_female_package_price'] ?? $package['cat_vaccination_package_price'] ?? null,
            'dog_vaccination_male_package_price' => $package['dog_vaccination_male_package_price'] ?? $package['dog_vaccination_package_price'] ?? null,
            'dog_vaccination_female_package_price' => $package['dog_vaccination_female_package_price'] ?? $package['dog_vaccination_package_price'] ?? null,
            'cat_vaccination_male_package_price' => $package['cat_vaccination_male_package_price'] ?? $package['cat_vaccination_package_price'] ?? null,
            'cat_vaccination_female_package_price' => $package['cat_vaccination_female_package_price'] ?? $package['cat_vaccination_package_price'] ?? null,
            'dog_neutering_male_price' => $package['dog_neutering_male_price'] ?? $package['dog_neutering_price'] ?? null,
            'dog_neutering_female_price' => $package['dog_neutering_female_price'] ?? $package['dog_neutering_price'] ?? null,
            'cat_neutering_male_price' => $package['cat_neutering_male_price'] ?? $package['cat_neutering_price'] ?? null,
            'cat_neutering_female_price' => $package['cat_neutering_female_price'] ?? $package['cat_neutering_price'] ?? null,
        ]);
    }

    private function normalizeVaccinationPackageAliases(array $input): array
    {
        $package = $input['specialized_package'] ?? [];
        if (is_string($package)) {
            $decoded = json_decode($package, true);
            $package = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($package)) {
            $package = [];
        }

        $aliasMap = [
            'puppy_vaccination_package_price' => [
                'puppy_package_price',
                'puppy_vaccine_package_price',
                'puppy_vaccination_price',
                'puppy vaccination package',
            ],
            'adult_dog_vaccination_package_price' => [
                'adult_dog_package_price',
                'adult_dog_vaccine_package_price',
                'adult_dog_vaccination_price',
                'adult dog package',
            ],
            'kitten_vaccination_package_price' => [
                'kitten_package_price',
                'kitten_vaccine_package_price',
                'kitten_vaccination_price',
                'kitten vaccine package',
            ],
            'adult_cat_vaccination_package_price' => [
                'adult_cat_package_price',
                'adult_cat_vaccine_package_price',
                'adult_cat_vaccination_price',
                'adult cat vaccination package',
            ],
        ];

        foreach ($aliasMap as $canonical => $aliases) {
            $value = $package[$canonical] ?? $input[$canonical] ?? null;

            foreach ($aliases as $alias) {
                if ($value !== null && $value !== '') {
                    break;
                }

                $value = $package[$alias] ?? $input[$alias] ?? null;
            }

            if ($value !== null && $value !== '') {
                $package[$canonical] = $value;
                $input[$canonical] = $value;
            }
        }

        if (! empty($package)) {
            $input['specialized_package'] = $package;
        }

        return $input;
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

    private function createVetAtHomeService(int $clinicId, $doctors, ?array $serviceData): ?VetAtHomeService
    {
        if (empty($serviceData)) {
            return null;
        }

        $doctor = null;
        if (array_key_exists('doctor_index', $serviceData)) {
            $doctor = $doctors->get((int) $serviceData['doctor_index']);
            if (! $doctor) {
                throw ValidationException::withMessages([
                    'vet_at_home_service.doctor_index' => ['The selected doctor index does not exist in doctors array.'],
                ]);
            }
        }

        return VetAtHomeService::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $doctor?->id,
            'is_enabled' => (bool) ($serviceData['is_enabled'] ?? false),
            'service_hours' => $serviceData['service_hours'] ?? null,
            'response_time' => $serviceData['response_time'] ?? null,
            'base_payout' => $serviceData['base_payout'] ?? null,
            'protocol_label' => $serviceData['protocol_label'] ?? 'Doorstep Protocol',
        ]);
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
        if (Schema::hasColumn('doctors', 'exported_from_excell')) {
            $rateUpdates['exported_from_excell'] = 1;
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
        return $this->decodeBlobInputWithMime($request, $field)[0];
    }

    private function decodeBlobInputWithMime(Request $request, string $field): array
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            return [$file->get(), $file->getMimeType()];
        }

        if (! $request->filled($field)) {
            return [null, null];
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

        return [$binary, $matches[1] ?? null];
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
