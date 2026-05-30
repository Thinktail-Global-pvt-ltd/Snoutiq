<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSpecializedPackage;
use App\Models\Doctor;
use App\Models\VetAtHomeService;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ClinicOnboardingExtrasController extends Controller
{
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],

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

            'vet_at_home_enabled' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'service_hours' => ['nullable', 'string', 'max:255'],
            'response_time' => ['nullable', 'string', 'max:255'],
            'base_payout' => ['nullable', 'numeric', 'min:0'],
            'protocol_label' => ['nullable', 'string', 'max:255'],

            'video_day_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'video_night_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'video_availability' => ['nullable', 'array'],
            'video_availability.*.day_of_week' => ['required_with:video_availability', 'integer', 'min:0', 'max:6'],
            'video_availability.*.start_time' => ['required_with:video_availability'],
            'video_availability.*.end_time' => ['required_with:video_availability'],
            'video_availability.*.break_start' => ['nullable'],
            'video_availability.*.break_end' => ['nullable'],
            'video_availability.*.avg_consultation_mins' => ['nullable', 'integer'],
            'video_availability.*.max_bookings_per_hour' => ['nullable', 'integer'],

            'clinic_image' => ['nullable'],
            'clinic_video' => ['nullable'],
        ]);

        $doctorClinicId = Doctor::query()
            ->whereKey($data['doctor_id'])
            ->value('vet_registeration_id');

        if ((int) $doctorClinicId !== (int) $data['clinic_id']) {
            throw ValidationException::withMessages([
                'doctor_id' => ['The selected doctor does not belong to the selected clinic.'],
            ]);
        }

        $result = DB::transaction(function () use ($request, $data): array {
            $package = ClinicSpecializedPackage::updateOrCreate(
                [
                    'clinic_id' => $data['clinic_id'],
                    'doctor_id' => $data['doctor_id'],
                ],
                [
                    'dog_vaccination_package_price' => $data['dog_vaccination_package_price'] ?? null,
                    'cat_vaccination_package_price' => $data['cat_vaccination_package_price'] ?? null,
                    'dog_neutering_price' => $data['dog_neutering_price'] ?? null,
                    'cat_neutering_price' => $data['cat_neutering_price'] ?? null,
                    'puppy_vaccination_package_price' => $data['puppy_vaccination_package_price'] ?? $data['dog_vaccination_male_package_price'] ?? $data['dog_vaccination_package_price'] ?? null,
                    'adult_dog_vaccination_package_price' => $data['adult_dog_vaccination_package_price'] ?? $data['dog_vaccination_female_package_price'] ?? $data['dog_vaccination_package_price'] ?? null,
                    'kitten_vaccination_package_price' => $data['kitten_vaccination_package_price'] ?? $data['cat_vaccination_male_package_price'] ?? $data['cat_vaccination_package_price'] ?? null,
                    'adult_cat_vaccination_package_price' => $data['adult_cat_vaccination_package_price'] ?? $data['cat_vaccination_female_package_price'] ?? $data['cat_vaccination_package_price'] ?? null,
                    'dog_vaccination_male_package_price' => $data['dog_vaccination_male_package_price'] ?? $data['dog_vaccination_package_price'] ?? null,
                    'dog_vaccination_female_package_price' => $data['dog_vaccination_female_package_price'] ?? $data['dog_vaccination_package_price'] ?? null,
                    'cat_vaccination_male_package_price' => $data['cat_vaccination_male_package_price'] ?? $data['cat_vaccination_package_price'] ?? null,
                    'cat_vaccination_female_package_price' => $data['cat_vaccination_female_package_price'] ?? $data['cat_vaccination_package_price'] ?? null,
                    'dog_neutering_male_price' => $data['dog_neutering_male_price'] ?? $data['dog_neutering_price'] ?? null,
                    'dog_neutering_female_price' => $data['dog_neutering_female_price'] ?? $data['dog_neutering_price'] ?? null,
                    'cat_neutering_male_price' => $data['cat_neutering_male_price'] ?? $data['cat_neutering_price'] ?? null,
                    'cat_neutering_female_price' => $data['cat_neutering_female_price'] ?? $data['cat_neutering_price'] ?? null,
                ]
            );

            $vetAtHome = VetAtHomeService::updateOrCreate(
                [
                    'clinic_id' => $data['clinic_id'],
                    'doctor_id' => $data['doctor_id'],
                ],
                [
                    'is_enabled' => (bool) ($data['vet_at_home_enabled'] ?? $data['is_enabled'] ?? false),
                    'service_hours' => $data['service_hours'] ?? null,
                    'response_time' => $data['response_time'] ?? null,
                    'base_payout' => $data['base_payout'] ?? null,
                    'protocol_label' => $data['protocol_label'] ?? 'Doorstep Protocol',
                ]
            );

            $clinic = VetRegisterationTemp::query()->findOrFail((int) $data['clinic_id']);
            $mediaUpdates = [];
            foreach (['clinic_image', 'clinic_video'] as $field) {
                if (! Schema::hasColumn('vet_registerations_temp', $field)) {
                    continue;
                }

                $blob = $this->decodeBlobInput($request, $field);
                if ($blob !== null) {
                    $mediaUpdates[$field] = $blob;
                }
            }

            if (! empty($mediaUpdates)) {
                $clinic->forceFill($mediaUpdates)->save();
            }

            $videoAvailabilityCount = $this->syncVideoAvailability($data);
            $freshClinic = $clinic->fresh();

            return [
                'package' => $package,
                'vet_at_home_service' => $vetAtHome,
                'video_availability_rows' => $videoAvailabilityCount,
                'media' => [
                    'clinic_id' => $freshClinic->id,
                    'updated_fields' => array_keys($mediaUpdates),
                    'has_clinic_image' => ! empty($freshClinic->clinic_image),
                    'has_clinic_video' => ! empty($freshClinic->clinic_video),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Clinic onboarding extras saved successfully.',
            'data' => $result,
        ]);
    }

    private function syncVideoAvailability(array $data): int
    {
        $availabilityRows = $data['video_availability'] ?? [];
        if (empty($availabilityRows)) {
            return 0;
        }

        $doctorId = (int) $data['doctor_id'];
        $doctorUpdates = [];

        if (array_key_exists('video_day_rate', $data)) {
            $doctorUpdates['video_day_rate'] = $data['video_day_rate'] === null ? null : (float) $data['video_day_rate'];
        }
        if (array_key_exists('video_night_rate', $data)) {
            $doctorUpdates['video_night_rate'] = $data['video_night_rate'] === null ? null : (float) $data['video_night_rate'];
        }
        if (Schema::hasColumn('doctors', 'exported_from_excell')) {
            $doctorUpdates['exported_from_excell'] = 1;
        }
        if (! empty($doctorUpdates)) {
            DB::table('doctors')->where('id', $doctorId)->update($doctorUpdates);
        }

        if (! Schema::hasTable('doctor_video_availability')) {
            return 0;
        }

        DB::table('doctor_video_availability')
            ->where('doctor_id', $doctorId)
            ->delete();

        $insertRows = [];
        foreach ($availabilityRows as $row) {
            $insertRows[] = [
                'doctor_id' => $doctorId,
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
}
