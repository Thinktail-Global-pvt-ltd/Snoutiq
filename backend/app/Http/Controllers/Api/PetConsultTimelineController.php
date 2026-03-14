<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PetConsultTimelineController extends Controller
{
    /**
     * GET /api/pets/lifeline-timeline?pet_id={pet_id}
     */
    public function lifeline(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer'],
        ]);

        $petId = (int) $data['pet_id'];
        $pet = Pet::query()->find($petId);
        if (! $pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found.',
            ], 404);
        }

        $appointments = Appointment::query()
            ->where('pet_id', $petId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $consultTypes = ['excell_export_campaign', 'video_consult'];
        $videoConsultTransactions = Transaction::query()
            ->where('pet_id', $petId)
            ->where(function ($query) use ($consultTypes) {
                $query->whereIn('type', $consultTypes)
                    ->orWhereIn('metadata->order_type', $consultTypes);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $prescriptions = collect();
        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $prescriptions = Prescription::query()
                ->where('pet_id', $petId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();
        }

        $appointmentTimelineItems = $appointments->map(function (Appointment $appointment) {
            return [
                'source' => 'appointments',
                'event_type' => 'appointment',
                'record_id' => $appointment->id,
                'event_at' => $this->resolveAppointmentEventAt($appointment),
                'created_at' => optional($appointment->created_at)->toIso8601String(),
                'record' => $this->mapAppointment($appointment),
            ];
        });

        $consultTimelineItems = $videoConsultTransactions->map(function (Transaction $transaction) {
            $payload = $this->mapModel($transaction);

            return [
                'source' => 'transactions',
                'event_type' => 'video_consultation',
                'record_id' => $transaction->id,
                'event_at' => $payload['created_at'] ?? optional($transaction->created_at)->toIso8601String(),
                'created_at' => $payload['created_at'] ?? optional($transaction->created_at)->toIso8601String(),
                'record' => $payload,
            ];
        });

        $prescriptionTimelineItems = $prescriptions->map(function (Prescription $prescription) {
            $payload = $this->mapModel($prescription);

            return [
                'source' => 'prescriptions',
                'event_type' => 'prescription',
                'record_id' => $prescription->id,
                'event_at' => $payload['created_at'] ?? optional($prescription->created_at)->toIso8601String(),
                'created_at' => $payload['created_at'] ?? optional($prescription->created_at)->toIso8601String(),
                'record' => $payload,
            ];
        });

        $vaccinationTimelineItems = $this->buildVaccinationTimelineItems($pet);
        $dewormingTimelineItems = $this->buildDewormingTimelineItems($pet);

        $timeline = collect()
            ->concat($appointmentTimelineItems)
            ->concat($consultTimelineItems)
            ->concat($prescriptionTimelineItems)
            ->concat($vaccinationTimelineItems)
            ->concat($dewormingTimelineItems)
            ->sortByDesc(function (array $item) {
                return $item['event_at'] ?? $item['created_at'] ?? '';
            })
            ->values()
            ->map(function (array $item) {
                $eventAt = $item['event_at'] ?? $item['created_at'] ?? null;
                $year = null;
                if (is_string($eventAt) && trim($eventAt) !== '') {
                    try {
                        $year = Carbon::parse($eventAt)->format('Y');
                    } catch (\Throwable $e) {
                        $year = null;
                    }
                }
                $item['year'] = $year;

                return $item;
            });

        return response()->json([
            'success' => true,
            'filters' => [
                'pet_id' => $petId,
            ],
            'counts' => [
                'appointments' => $appointments->count(),
                'video_consultations' => $videoConsultTransactions->count(),
                'prescriptions' => $prescriptions->count(),
                'vaccinations' => $vaccinationTimelineItems->count(),
                'deworming' => $dewormingTimelineItems->count(),
                'timeline' => $timeline->count(),
            ],
            'data' => [
                'pet' => $this->buildPetSummary($pet),
                'appointments' => $appointments->map(fn (Appointment $appointment) => $this->mapAppointment($appointment))->values(),
                'video_consultations' => $videoConsultTransactions->map(fn (Transaction $transaction) => $this->mapModel($transaction))->values(),
                'prescriptions' => $prescriptions->map(fn (Prescription $prescription) => $this->mapModel($prescription))->values(),
                'vaccinations' => $vaccinationTimelineItems->pluck('record')->values(),
                'deworming' => $dewormingTimelineItems->pluck('record')->values(),
                'timeline' => $timeline,
            ],
        ]);
    }

    /**
     * GET /api/pets/consult-timeline?pet_id={pet_id}&user_id={user_id}
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'transaction_scope' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'integer'],
        ]);

        $petId = (int) $data['pet_id'];
        $userId = (int) $data['user_id'];
        $anchorTransactionId = isset($data['transaction_id']) ? (int) $data['transaction_id'] : null;
        $transactionScope = strtolower((string) ($data['transaction_scope'] ?? 'consult'));
        if (!in_array($transactionScope, ['consult', 'all'], true)) {
            $transactionScope = 'consult';
        }

        $appointments = Appointment::query()
            ->where('pet_id', $petId)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Appointment $appointment) use ($userId) {
                return $this->extractPatientUserId($appointment->notes) === $userId;
            })
            ->values();

        $transactionsQuery = Transaction::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($petId, $anchorTransactionId) {
                $query->where('pet_id', $petId);
                if ($anchorTransactionId) {
                    $query->orWhere('id', $anchorTransactionId);
                }
            })
            ->orderByDesc('created_at');

        if ($transactionScope === 'consult') {
            $transactionsQuery->where('type', 'video_consult');
        }

        $transactions = $transactionsQuery->get();

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
                'transaction_scope' => $transactionScope,
                'transaction_id' => $anchorTransactionId,
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

    private function resolveAppointmentEventAt(Appointment $appointment): ?string
    {
        $date = trim((string) ($appointment->appointment_date ?? ''));
        $time = trim((string) ($appointment->appointment_time ?? ''));
        $candidate = trim($date . ' ' . $time);

        if ($candidate !== '') {
            try {
                return Carbon::parse($candidate)->toIso8601String();
            } catch (\Throwable $e) {
                // fall back to created_at below
            }
        }

        return optional($appointment->created_at)->toIso8601String();
    }

    private function buildPetSummary(Pet $pet): array
    {
        $vaccinationPayload = $this->resolveVaccinationPayloadFromPet($pet);

        return [
            'id' => $pet->id,
            'user_id' => $pet->user_id,
            'name' => $pet->name,
            'breed' => $pet->breed,
            'pet_type' => $pet->pet_type ?? $pet->type ?? null,
            'pet_gender' => $pet->pet_gender ?? $pet->gender ?? null,
            'deworming_yes_no' => $pet->deworming_yes_no ?? null,
            'last_deworming_date' => $this->normalizeDateString($pet->last_deworming_date ?? null),
            'next_deworming_date' => $this->normalizeDateString($pet->next_deworming_date ?? null),
            'vaccination_payload' => $vaccinationPayload,
        ];
    }

    private function buildVaccinationTimelineItems(Pet $pet): Collection
    {
        $payload = $this->resolveVaccinationPayloadFromPet($pet);
        if (!is_array($payload)) {
            return collect();
        }

        $vaccinationNode = data_get($payload, 'vaccination');
        if (!is_array($vaccinationNode) || $vaccinationNode === []) {
            return collect();
        }

        $items = [];
        $index = 0;

        foreach ($vaccinationNode as $key => $entry) {
            $record = is_array($entry) ? $entry : ['value' => $entry];
            $vaccineName = $this->resolveVaccinationName($key, $record, $index);

            $lastDate = $this->normalizeDateString(
                $record['last_date']
                    ?? $record['lastDate']
                    ?? $record['date']
                    ?? $record['date_given']
                    ?? null
            );
            $nextDue = $this->normalizeDateString(
                $record['next_due']
                    ?? $record['nextDue']
                    ?? $record['next_due_date']
                    ?? $record['due_date']
                    ?? null
            );

            $eventAt = $this->normalizeDateToIso8601($lastDate)
                ?? $this->normalizeDateToIso8601($nextDue)
                ?? optional($pet->updated_at)->toIso8601String()
                ?? optional($pet->created_at)->toIso8601String();

            $items[] = [
                'source' => 'vaccination',
                'event_type' => 'vaccination',
                'record_id' => sprintf('pet-%d-vaccination-%d', (int) $pet->id, $index + 1),
                'event_at' => $eventAt,
                'created_at' => $eventAt,
                'record' => [
                    'pet_id' => (int) $pet->id,
                    'vaccine_name' => $vaccineName,
                    'status' => $record['status'] ?? null,
                    'last_date' => $lastDate,
                    'next_due' => $nextDue,
                    'raw' => $record,
                ],
            ];
            $index++;
        }

        return collect($items)->sortByDesc(function (array $item) {
            return $item['event_at'] ?? '';
        })->values();
    }

    private function buildDewormingTimelineItems(Pet $pet): Collection
    {
        $dewormingYesNo = $pet->deworming_yes_no ?? null;
        $lastDate = $this->normalizeDateString($pet->last_deworming_date ?? null);
        $nextDate = $this->normalizeDateString($pet->next_deworming_date ?? null);
        $status = $pet->deworming_status ?? null;

        if ($dewormingYesNo === null && $lastDate === null && $nextDate === null && $status === null) {
            return collect();
        }

        $eventAt = $this->normalizeDateToIso8601($lastDate)
            ?? $this->normalizeDateToIso8601($nextDate)
            ?? optional($pet->updated_at)->toIso8601String()
            ?? optional($pet->created_at)->toIso8601String();

        return collect([[
            'source' => 'deworming',
            'event_type' => 'deworming',
            'record_id' => sprintf('pet-%d-deworming', (int) $pet->id),
            'event_at' => $eventAt,
            'created_at' => $eventAt,
            'record' => [
                'pet_id' => (int) $pet->id,
                'deworming_yes_no' => $dewormingYesNo,
                'last_deworming_date' => $lastDate,
                'next_deworming_date' => $nextDate,
                'deworming_status' => $status,
            ],
        ]]);
    }

    private function resolveVaccinationPayloadFromPet(Pet $pet): ?array
    {
        $candidates = [
            $pet->getAttribute('dog_disease'),
            $pet->getAttribute('dog_disease_payload'),
        ];

        foreach ($candidates as $candidate) {
            $decoded = $this->decodeArrayPayload($candidate);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return null;
    }

    private function decodeArrayPayload($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true);
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function resolveVaccinationName($key, array $record, int $index): string
    {
        $name = $record['name']
            ?? $record['vaccine_name']
            ?? $record['title']
            ?? null;

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        if (is_string($key) && trim($key) !== '' && !is_numeric($key)) {
            return trim($key);
        }

        return 'vaccination_' . ($index + 1);
    }

    private function normalizeDateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    private function normalizeDateToIso8601(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
