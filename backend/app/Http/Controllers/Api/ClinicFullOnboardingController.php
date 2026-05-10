<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSpecializedPackage;
use App\Models\Doctor;
use App\Models\GroomerService;
use App\Models\GroomerServiceCategory;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClinicFullOnboardingController extends Controller
{
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

            return [
                'clinic' => $clinic->fresh(),
                'doctors' => $doctors,
                'services' => $services,
                'specialized_package' => $specializedPackage,
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
