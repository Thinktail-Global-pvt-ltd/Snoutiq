<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicEmergencyHour;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

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
        if (!Schema::hasTable('clinic_emergency_hours')) {
            Schema::create('clinic_emergency_hours', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('clinic_id');
                $table->json('doctor_ids')->nullable();
                $table->json('doctor_slot_map')->nullable();
                $table->json('night_slots')->nullable();
                $table->decimal('consultation_price', 10, 2)->nullable();
                $table->timestamps();

                $table->unique('clinic_id');
                $table->foreign('clinic_id')
                    ->references('id')
                    ->on('vet_registerations_temp')
                    ->onDelete('cascade');
            });
            return;
        }

        if (!Schema::hasColumn('clinic_emergency_hours', 'doctor_slot_map')) {
            Schema::table('clinic_emergency_hours', function (Blueprint $table) {
                $table->json('doctor_slot_map')->nullable()->after('doctor_ids');
            });
        }
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
        $doctorIds = $record?->doctor_ids ?? [];
        $doctorSlotMap = is_array($record?->doctor_slot_map) ? $record->doctor_slot_map : [];
        $normalizedSlotMap = [];

        if (empty($doctorSlotMap) && !empty($doctorIds)) {
            $fallbackSlots = is_array($record?->night_slots) ? $record->night_slots : [];
            foreach ($doctorIds as $doctorId) {
                $doctorSlotMap[$doctorId] = $fallbackSlots;
            }
        }

        $doctorSchedules = [];
        foreach ($doctorIds as $doctorId) {
            $slots = Arr::wrap($doctorSlotMap[$doctorId] ?? []);
            $normalizedSlots = array_values(array_unique(array_filter($slots, static fn ($v) => $v !== null && $v !== '')));
            $normalizedSlotMap[$doctorId] = $normalizedSlots;
            $doctorSchedules[] = [
                'doctor_id' => $doctorId,
                'night_slots' => $normalizedSlots,
            ];
        }

        $allSlots = collect($doctorSchedules)
            ->pluck('night_slots')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $doctorMap = Doctor::query()
            ->whereIn('id', $record?->doctor_ids ?? [])
            ->pluck('doctor_name', 'id');

        return response()->json([
            'success' => true,
            'clinic_id' => $clinicId,
            'data' => [
                'doctor_ids' => $doctorIds,
                'night_slots' => $allSlots,
                'consultation_price' => $record?->consultation_price,
                'updated_at' => optional($record?->updated_at)->toDateTimeString(),
                'doctor_details' => $doctorMap,
                'doctor_slot_map' => $normalizedSlotMap,
                'doctor_schedules' => $doctorSchedules,
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
            'doctor_schedules' => 'sometimes|array',
            'doctor_schedules.*.doctor_id' => 'required_with:doctor_schedules|integer|exists:doctors,id',
            'doctor_schedules.*.night_slots' => 'required_with:doctor_schedules|array|min:1',
            'doctor_schedules.*.night_slots.*' => 'string|max:20',
            'night_slots' => 'sometimes|array',
            'night_slots.*' => 'string|max:20',
            'consultation_price' => 'required|numeric|min:0|max:1000000',
        ]);

        $doctorIds = array_values(array_unique($payload['doctor_ids']));

        // Optional: verify selected doctors belong to clinic
        $clinicDoctorIds = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->pluck('id')
            ->all();

        if (!empty($clinicDoctorIds)) {
            $diff = array_diff($doctorIds, $clinicDoctorIds);
            if (!empty($diff)) {
                return response()->json([
                    'success' => false,
                    'error' => 'One or more doctors are not linked to this clinic.',
                ], 422);
            }
        }

        $doctorSchedules = collect($payload['doctor_schedules'] ?? [])
            ->filter(fn ($row) => isset($row['doctor_id']))
            ->map(function ($row) {
                $slots = array_values(array_unique(array_filter($row['night_slots'] ?? [], static fn ($v) => $v !== null && $v !== '')));
                return [
                    'doctor_id' => (int) $row['doctor_id'],
                    'night_slots' => $slots,
                ];
            })
            ->values();

        $doctorSlotMap = [];
        foreach ($doctorSchedules as $schedule) {
            if (!in_array($schedule['doctor_id'], $doctorIds, true)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Availability provided for a doctor that is not selected.',
                ], 422);
            }
            $doctorSlotMap[$schedule['doctor_id']] = $schedule['night_slots'];
        }

        if (empty($doctorSlotMap)) {
            $fallbackSlots = array_values(array_unique($payload['night_slots'] ?? []));
            if (empty($fallbackSlots)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Please select at least one slot for the chosen doctors.',
                ], 422);
            }
            foreach ($doctorIds as $doctorId) {
                $doctorSlotMap[$doctorId] = $fallbackSlots;
            }
        }

        // Ensure every selected doctor has at least one slot
        $missing = [];
        foreach ($doctorIds as $doctorId) {
            $slots = array_values(array_unique($doctorSlotMap[$doctorId] ?? []));
            if (empty($slots)) {
                $missing[] = $doctorId;
            }
            $doctorSlotMap[$doctorId] = $slots;
        }

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'error' => 'Each selected doctor needs at least one night slot.',
                'missing_doctors' => $missing,
            ], 422);
        }

        $allSlots = collect($doctorSlotMap)
            ->filter(fn ($slots) => is_array($slots))
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $doctorSchedules = collect($doctorIds)->map(function ($doctorId) use ($doctorSlotMap) {
            return [
                'doctor_id' => $doctorId,
                'night_slots' => $doctorSlotMap[$doctorId] ?? [],
            ];
        })->values();

        $record = DB::transaction(function () use ($clinicId, $doctorIds, $doctorSlotMap, $allSlots, $payload) {
            return ClinicEmergencyHour::updateOrCreate(
                ['clinic_id' => $clinicId],
                [
                    'doctor_ids' => $doctorIds,
                    'doctor_slot_map' => $doctorSlotMap,
                    'night_slots' => $allSlots,
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
                'doctor_slot_map' => $record->doctor_slot_map ?? [],
                'doctor_schedules' => $doctorSchedules->toArray(),
                'consultation_price' => $record->consultation_price,
                'updated_at' => optional($record->updated_at)->toDateTimeString(),
            ],
        ]);
    }
}
