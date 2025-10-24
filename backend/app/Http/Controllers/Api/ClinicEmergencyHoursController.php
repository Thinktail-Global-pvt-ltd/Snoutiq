<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicEmergencyHour;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicEmergencyHoursController extends Controller
{
    /**
     * Resolve clinic ID from multiple possible sources.
     */
    protected function resolveClinicId(Request $request): ?int
    {
        $clinicId = $request->input('clinic_id')
            ?? $request->query('clinic_id')
            ?? $request->session()->get('user_id')
            ?? data_get($request->session()->get('user'), 'id');

        $clinicId = is_numeric($clinicId) ? (int) $clinicId : null;
        return $clinicId && $clinicId > 0 ? $clinicId : null;
    }

    /**
     * Ensure the emergency table exists dynamically (for first-time setup).
     */
    protected function ensureTableExists(): void
    {
        if (Schema::hasTable('clinic_emergency_hours')) {
            return;
        }

        Schema::create('clinic_emergency_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->json('doctor_ids')->nullable();
            $table->json('night_slots')->nullable();
            $table->decimal('consultation_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
            $table->foreign('clinic_id')
                ->references('id')
                ->on('vet_registerations_temp')
                ->onDelete('cascade');
        });
    }

    /**
     * Show existing emergency hour data for a clinic.
     */
    public function show(Request $request)
    {
        $this->ensureTableExists();

        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Clinic context missing',
            ], 422);
        }

        $record = ClinicEmergencyHour::where('clinic_id', $clinicId)->first();

        $doctorMap = Doctor::query()
            ->whereIn('id', $record?->doctor_ids ?? [])
            ->pluck('doctor_name', 'id');

        return response()->json([
            'success' => true,
            'clinic_id' => $clinicId,
            'data' => [
                'doctor_ids' => $record?->doctor_ids ?? [],
                'night_slots' => $record?->night_slots ?? [],
                'consultation_price' => $record?->consultation_price,
                'updated_at' => optional($record?->updated_at)->toDateTimeString(),
                'doctor_details' => $doctorMap,
            ],
        ]);
    }

    /**
     * Create or update emergency hour coverage for a clinic.
     */
    public function upsert(Request $request)
    {
        $this->ensureTableExists();

        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'success' => false,
                'error' => 'Clinic context missing',
            ], 422);
        }

        $payload = $request->validate([
            'doctor_ids' => 'required|array|min:1',
            'doctor_ids.*' => 'integer|exists:doctors,id',
            'night_slots' => 'required|array|min:1',
            'night_slots.*' => 'string|max:20',
            'consultation_price' => 'required|numeric|min:0|max:1000000',
        ]);

        // Optional: verify selected doctors belong to clinic
        $clinicDoctorIds = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->pluck('id')
            ->all();

        if (!empty($clinicDoctorIds)) {
            $diff = array_diff($payload['doctor_ids'], $clinicDoctorIds);
            if (!empty($diff)) {
                return response()->json([
                    'success' => false,
                    'error' => 'One or more doctors are not linked to this clinic.',
                ], 422);
            }
        }

        $record = DB::transaction(function () use ($clinicId, $payload) {
            return ClinicEmergencyHour::updateOrCreate(
                ['clinic_id' => $clinicId],
                [
                    'doctor_ids' => array_values(array_unique($payload['doctor_ids'])),
                    'night_slots' => array_values(array_unique($payload['night_slots'])),
                    'consultation_price' => $payload['consultation_price'],
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Emergency coverage saved successfully',
            'data' => [
                'clinic_id' => $clinicId,
                'doctor_ids' => $record->doctor_ids,
                'night_slots' => $record->night_slots,
                'consultation_price' => $record->consultation_price,
                'updated_at' => optional($record->updated_at)->toDateTimeString(),
            ],
        ]);
    }
}
