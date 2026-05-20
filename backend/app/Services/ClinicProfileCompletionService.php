<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ClinicProfileCompletionService
{
    /**
     * Calculate completion across the clinic onboarding blocks shown in full onboarding.
     */
    public function calculate(
        mixed $clinic,
        iterable $doctors = [],
        iterable $services = [],
        iterable $specializedPackages = [],
        iterable $vetAtHomeServices = [],
        iterable $clinicAvailability = [],
        iterable $videoSchedules = []
    ): array {
        $doctors = $this->toCollection($doctors);
        $services = $this->toCollection($services);
        $specializedPackages = $this->toCollection($specializedPackages);
        $vetAtHomeServices = $this->toCollection($vetAtHomeServices);
        $clinicAvailability = $this->toCollection($clinicAvailability);
        $videoSchedules = $this->toCollection($videoSchedules);

        $checks = [
            'clinic_name' => [
                'label' => 'Clinic name',
                'complete' => $this->filled(data_get($clinic, 'name')),
            ],
            'clinic_mobile' => [
                'label' => 'Clinic mobile',
                'complete' => $this->filled(data_get($clinic, 'mobile')),
            ],
            'clinic_city' => [
                'label' => 'Clinic city',
                'complete' => $this->filled(data_get($clinic, 'city')),
            ],
            'clinic_pincode' => [
                'label' => 'Clinic pincode',
                'complete' => $this->filled(data_get($clinic, 'pincode')),
            ],
            'clinic_image' => [
                'label' => 'Clinic image',
                'complete' => $this->filled(data_get($clinic, 'clinic_image'))
                    || $this->filled(data_get($clinic, 'clinic_image_url')),
            ],
            'clinic_video' => [
                'label' => 'Clinic video',
                'complete' => $this->filled(data_get($clinic, 'clinic_video'))
                    || $this->filled(data_get($clinic, 'clinic_video_url')),
            ],
            'doctor' => [
                'label' => 'Doctor profile',
                'complete' => $doctors->contains(fn ($doctor) => $this->filled(data_get($doctor, 'doctor_name'))
                    && $this->filled(data_get($doctor, 'doctor_mobile'))),
            ],
            'services' => [
                'label' => 'Services',
                'complete' => $services->isNotEmpty(),
            ],
            'clinic_hours' => [
                'label' => 'Clinic hours',
                'complete' => $clinicAvailability->isNotEmpty(),
            ],
            'video_hours' => [
                'label' => 'Video hours',
                'complete' => $this->hasVideoScheduleRows($videoSchedules),
            ],
            'packages' => [
                'label' => 'Packages',
                'complete' => $this->hasPackagePricing($specializedPackages),
            ],
            'vet_at_home' => [
                'label' => 'Vet at home',
                'complete' => $this->hasVetAtHomeSetup($vetAtHomeServices),
            ],
        ];

        $total = count($checks);
        $completed = collect($checks)->where('complete', true)->count();
        $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'completed_fields' => $completed,
            'total_fields' => $total,
            'missing_fields' => collect($checks)
                ->reject(fn (array $check) => (bool) $check['complete'])
                ->map(fn (array $check, string $key) => [
                    'key' => $key,
                    'label' => $check['label'],
                ])
                ->values()
                ->all(),
            'checks' => collect($checks)
                ->map(fn (array $check, string $key) => [
                    'key' => $key,
                    'label' => $check['label'],
                    'complete' => (bool) $check['complete'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function toCollection(iterable $items): Collection
    {
        return $items instanceof Collection ? $items->values() : collect($items)->values();
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value) || $value instanceof Collection) {
            return count($value) > 0;
        }

        return true;
    }

    private function hasVideoScheduleRows(Collection $videoSchedules): bool
    {
        return $videoSchedules->contains(function ($schedule): bool {
            $availability = data_get($schedule, 'availability');
            if ($availability !== null) {
                return $this->toCollection($availability)->isNotEmpty();
            }

            return $this->filled(data_get($schedule, 'doctor_id'));
        });
    }

    private function hasPackagePricing(Collection $packages): bool
    {
        $priceFields = [
            'dog_vaccination_package_price',
            'cat_vaccination_package_price',
            'dog_neutering_price',
            'cat_neutering_price',
            'dog_vaccination_male_package_price',
            'dog_vaccination_female_package_price',
            'cat_vaccination_male_package_price',
            'cat_vaccination_female_package_price',
            'dog_neutering_male_price',
            'dog_neutering_female_price',
            'cat_neutering_male_price',
            'cat_neutering_female_price',
        ];

        return $packages->contains(function ($package) use ($priceFields): bool {
            foreach ($priceFields as $field) {
                if ($this->filled(data_get($package, $field))) {
                    return true;
                }
            }

            return false;
        });
    }

    private function hasVetAtHomeSetup(Collection $vetAtHomeServices): bool
    {
        return $vetAtHomeServices->contains(fn ($service) => (bool) data_get($service, 'is_enabled')
            || $this->filled(data_get($service, 'service_hours'))
            || $this->filled(data_get($service, 'response_time'))
            || $this->filled(data_get($service, 'base_payout')));
    }
}
