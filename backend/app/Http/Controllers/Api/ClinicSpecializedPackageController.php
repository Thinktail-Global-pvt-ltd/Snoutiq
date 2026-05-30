<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSpecializedPackage;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClinicSpecializedPackageController extends Controller
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);

        $package = ClinicSpecializedPackage::query()
            ->where('clinic_id', $data['clinic_id'])
            ->where('doctor_id', $data['doctor_id'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }

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
        ]);

        $doctorClinicId = Doctor::query()
            ->whereKey($data['doctor_id'])
            ->value('vet_registeration_id');

        if ((int) $doctorClinicId !== (int) $data['clinic_id']) {
            throw ValidationException::withMessages([
                'doctor_id' => ['The selected doctor does not belong to the selected clinic.'],
            ]);
        }

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

        return response()->json([
            'success' => true,
            'message' => 'Specialized package prices saved successfully.',
            'data' => $package,
        ], $package->wasRecentlyCreated ? 201 : 200);
    }
}
