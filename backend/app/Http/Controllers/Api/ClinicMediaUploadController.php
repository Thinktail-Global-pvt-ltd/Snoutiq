<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ClinicMediaUploadController extends Controller
{
    public function store(Request $request, VetRegisterationTemp $clinic)
    {
        $request->validate([
            'clinic_image' => ['nullable'],
            'clinic_video' => ['nullable'],
        ]);

        $updates = [];
        foreach (['clinic_image', 'clinic_video'] as $field) {
            if (! Schema::hasColumn('vet_registerations_temp', $field)) {
                continue;
            }

            $blob = $this->decodeBlobInput($request, $field);
            if ($blob !== null) {
                $updates[$field] = $blob;
            }
        }

        if (empty($updates)) {
            throw ValidationException::withMessages([
                'clinic_image' => ['Upload clinic_image, clinic_video, or both.'],
            ]);
        }

        $clinic->forceFill($updates)->save();

        return response()->json([
            'success' => true,
            'message' => 'Clinic media saved successfully.',
            'data' => [
                'clinic_id' => $clinic->id,
                'has_clinic_image' => ! empty($clinic->fresh()->clinic_image),
                'has_clinic_video' => ! empty($clinic->fresh()->clinic_video),
            ],
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
