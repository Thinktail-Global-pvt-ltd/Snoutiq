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

            $freshClinic = $clinic->fresh();

            return [
                'package' => $package,
                'vet_at_home_service' => $vetAtHome,
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
