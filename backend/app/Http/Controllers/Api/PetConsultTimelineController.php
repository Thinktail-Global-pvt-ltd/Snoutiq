<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PetConsultTimelineController extends Controller
{
    /**
     * GET /api/pets/consult-timeline?pet_id={pet_id}&user_id={user_id}
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
        ]);

        $petId = (int) $data['pet_id'];
        $userId = (int) $data['user_id'];

        $appointments = Appointment::query()
            ->where('pet_id', $petId)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Appointment $appointment) use ($userId) {
                return $this->extractPatientUserId($appointment->notes) === $userId;
            })
            ->values();

        $transactions = Transaction::query()
            ->where('type', 'video_consult')
            ->where('pet_id', $petId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $prescriptionsQuery = Prescription::query()
            ->where('user_id', $userId);

        if (Schema::hasColumn('prescriptions', 'pet_id')) {
            $prescriptionsQuery->where('pet_id', $petId);
        } else {
            // If schema has no pet_id, request cannot satisfy the match condition.
            $prescriptionsQuery->whereRaw('1 = 0');
        }

        $prescriptions = $prescriptionsQuery
            ->orderByDesc('created_at')
            ->get();

        $timeline = $this->buildTimeline($appointments, $transactions, $prescriptions);

        return response()->json([
            'success' => true,
            'filters' => [
                'pet_id' => $petId,
                'user_id' => $userId,
            ],
            'counts' => [
                'appointments' => $appointments->count(),
                'transactions' => $transactions->count(),
                'prescriptions' => $prescriptions->count(),
                'timeline' => $timeline->count(),
            ],
            'data' => [
                'appointments' => $appointments->map(fn (Appointment $appointment) => $this->mapAppointment($appointment))->values(),
                'transactions' => $transactions->map(fn (Transaction $transaction) => $this->mapModel($transaction))->values(),
                'prescriptions' => $prescriptions->map(fn (Prescription $prescription) => $this->mapModel($prescription))->values(),
                'timeline' => $timeline->values(),
            ],
        ]);
    }

    private function extractPatientUserId(?string $notes): ?int
    {
        if (!$notes) {
            return null;
        }

        $decoded = json_decode($notes, true);
        if (!is_array($decoded)) {
            return null;
        }

        $candidate = $decoded['patient_user_id'] ?? null;

        return is_numeric($candidate) ? (int) $candidate : null;
    }

    private function mapAppointment(Appointment $appointment): array
    {
        $payload = $this->mapModel($appointment);
        $payload['notes_decoded'] = $this->decodeNotes($appointment->notes);
        $payload['patient_user_id_from_notes'] = $this->extractPatientUserId($appointment->notes);

        return $payload;
    }

    private function mapModel($model): array
    {
        $payload = $model->toArray();
        $payload['created_at'] = optional($model->created_at)->toIso8601String();
        $payload['updated_at'] = optional($model->updated_at)->toIso8601String();

        return $payload;
    }

    private function decodeNotes(?string $notes): ?array
    {
        if (!$notes) {
            return null;
        }

        $decoded = json_decode($notes, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function buildTimeline(
        Collection $appointments,
        Collection $transactions,
        Collection $prescriptions
    ): Collection {
        $appointmentItems = $appointments->map(function (Appointment $appointment) {
            return [
                'source' => 'appointments',
                'record_id' => $appointment->id,
                'created_at' => optional($appointment->created_at)->toIso8601String(),
                'record' => $this->mapAppointment($appointment),
            ];
        });

        $transactionItems = $transactions->map(function (Transaction $transaction) {
            return [
                'source' => 'transactions',
                'record_id' => $transaction->id,
                'created_at' => optional($transaction->created_at)->toIso8601String(),
                'record' => $this->mapModel($transaction),
            ];
        });

        $prescriptionItems = $prescriptions->map(function (Prescription $prescription) {
            return [
                'source' => 'prescriptions',
                'record_id' => $prescription->id,
                'created_at' => optional($prescription->created_at)->toIso8601String(),
                'record' => $this->mapModel($prescription),
            ];
        });

        return collect()
            ->concat($appointmentItems)
            ->concat($transactionItems)
            ->concat($prescriptionItems)
            ->sortByDesc(function (array $item) {
                return $item['created_at'] ?? '';
            })
            ->values();
    }
}
