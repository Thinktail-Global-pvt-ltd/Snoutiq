<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\Transaction;
use Dompdf\Dompdf;
use Dompdf\Options;
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
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) <> ?', ['pending']);
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

        $prescriptionByAppointment = collect();
        if ($prescriptions->isNotEmpty() && Schema::hasColumn('prescriptions', 'in_clinic_appointment_id')) {
            $prescriptionByAppointment = $prescriptions
                ->filter(fn (Prescription $prescription) => !empty($prescription->in_clinic_appointment_id))
                ->sortByDesc('id')
                ->groupBy('in_clinic_appointment_id')
                ->map(fn (Collection $items) => $items->first());
        }

        $appointmentTimelineItems = $appointments->map(function (Appointment $appointment) use ($prescriptionByAppointment, $pet) {
            $payload = $this->mapAppointment($appointment);
            if (Schema::hasColumn('pets', 'reported_symptom')) {
                $payload['pet_reported_symptom'] = $pet->reported_symptom ?? null;
            }

            $linkedPrescription = $prescriptionByAppointment->get($appointment->id);
            if ($linkedPrescription instanceof Prescription) {
                $payload['clinic_prescription'] = $this->mapModel($linkedPrescription);
            }

            return [
                'source' => 'appointments',
                'event_type' => 'appointment',
                'record_id' => $appointment->id,
                'event_at' => $this->resolveAppointmentEventAt($appointment),
                'created_at' => optional($appointment->created_at)->toIso8601String(),
                'record' => $payload,
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
     * GET /api/pets/lifeline-timeline/pdf?pet_id={pet_id}&download=1
     */
    public function lifelinePdf(Request $request)
    {
        $lifelineResponse = $this->lifeline($request);
        $statusCode = $lifelineResponse->getStatusCode();
        $payload = $lifelineResponse->getData(true);

        if ($statusCode !== 200 || !is_array($payload) || !($payload['success'] ?? false)) {
            return $lifelineResponse;
        }

        $html = $this->buildLifelinePdfHtml($payload);
        $pdf = $this->renderPdf($html);

        $petId = (int) data_get($payload, 'filters.pet_id', 0);
        $petName = trim((string) data_get($payload, 'data.pet.name', 'pet'));
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $petName) ?? 'pet');
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'pet';
        }

        $filename = sprintf('pet-lifeline-%s-%d.pdf', $slug, max($petId, 0));
        $download = filter_var($request->query('download', true), FILTER_VALIDATE_BOOL);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
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
        $hasAgeInput = !empty($pet->pet_dob)
            || !empty($pet->dob)
            || $pet->pet_age !== null
            || $pet->pet_age_months !== null;
        [$ageYears, $ageMonths] = $this->resolveAgeParts(
            $pet->pet_dob ?? $pet->dob ?? null,
            $pet->pet_age ?? null,
            $pet->pet_age_months ?? null
        );

        $owner = null;
        try {
            $owner = $pet->owner;
        } catch (\Throwable $e) {
            $owner = null;
        }

        return [
            'id' => $pet->id,
            'user_id' => $pet->user_id,
            'name' => $pet->name,
            'breed' => $pet->breed,
            'pet_type' => $pet->pet_type ?? $pet->type ?? null,
            'pet_gender' => $pet->pet_gender ?? $pet->gender ?? null,
            'pet_age' => $hasAgeInput ? $ageYears : null,
            'pet_age_months' => $hasAgeInput ? $ageMonths : null,
            'pet_dob' => $this->normalizeDateString($pet->pet_dob ?? $pet->dob ?? null),
            'weight' => $pet->weight ?? null,
            'is_neutered' => $pet->is_neutered ?? $pet->is_nuetered ?? null,
            'reported_symptom' => $pet->reported_symptom ?? null,
            'owner_name' => $owner?->name ?? null,
            'owner_city' => $owner?->city ?? null,
            'deworming_yes_no' => $pet->deworming_yes_no ?? null,
            'last_deworming_date' => $this->normalizeDateString($pet->last_deworming_date ?? null),
            'next_deworming_date' => $this->normalizeDateString($pet->next_deworming_date ?? null),
            'last_vaccenated_date' => $this->normalizeDateString($pet->last_vaccenated_date ?? null),
            'vaccination_date' => $this->normalizeDateString($pet->vaccination_date ?? null),
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

    private function formatPetAge(array $pet): string
    {
        $dob = $pet['pet_dob'] ?? $pet['dob'] ?? null;
        if ($dob) {
            try {
                $dobDate = Carbon::parse($dob)->startOfDay();
                $today = Carbon::today();
                if ($dobDate->lte($today)) {
                    $years = $dobDate->diffInYears($today);
                    $months = $dobDate->copy()->addYears($years)->diffInMonths($today);
                    $anchor = $dobDate->copy()->addYears($years)->addMonths($months);
                    $days = $anchor->diffInDays($today);

                    if ($years > 0 || $months > 0) {
                        $parts = [];
                        if ($years > 0) {
                            $parts[] = $years . ' year' . ($years === 1 ? '' : 's');
                        }
                        if ($months > 0) {
                            $parts[] = $months . ' month' . ($months === 1 ? '' : 's');
                        }
                        return implode(' ', $parts);
                    }

                    if ($days > 0) {
                        return $days . ' day' . ($days === 1 ? '' : 's');
                    }
                }
            } catch (\Throwable $e) {
                // Ignore invalid DOB formats
            }
        }

        [$years, $months] = $this->resolveAgeParts(
            null,
            $pet['pet_age'] ?? null,
            $pet['pet_age_months'] ?? null
        );

        if ($years <= 0 && $months <= 0) {
            return '—';
        }

        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' year' . ($years === 1 ? '' : 's');
        }
        if ($months > 0) {
            $parts[] = $months . ' month' . ($months === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }

    private function resolveAgeParts($dob, $ageYears, $ageMonths): array
    {
        $years = 0;
        $months = 0;
        $dobParsed = false;

        if ($dob) {
            try {
                $dobDate = Carbon::parse($dob)->startOfDay();
                $today = Carbon::today();
                if ($dobDate->lte($today)) {
                    $years = $dobDate->diffInYears($today);
                    $months = $dobDate->copy()->addYears($years)->diffInMonths($today);
                    $dobParsed = true;
                }
            } catch (\Throwable $e) {
                // Ignore invalid DOB formats
            }
        }

        if (!$dobParsed && $years <= 0 && $months <= 0) {
            $yearsValue = $this->normalizeNumericValue($ageYears);
            $monthsValue = $this->normalizeNumericValue($ageMonths);

            if ($yearsValue !== null && $yearsValue > 0) {
                $totalMonths = (int) round($yearsValue * 12);
                $years = intdiv($totalMonths, 12);
                $months = $totalMonths % 12;
            } elseif ($monthsValue !== null && $monthsValue > 0) {
                $totalMonths = (int) round($monthsValue);
                $years = intdiv($totalMonths, 12);
                $months = $totalMonths % 12;
            }
        }

        return [$years, $months];
    }

    private function normalizeNumericValue($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            if (preg_match('/-?\d+(?:\.\d+)?/', $value, $matches)) {
                return (float) $matches[0];
            }
        }

        return null;
    }

    private function formatPetWeight($weight): string
    {
        if ($weight === null || $weight === '') {
            return '—';
        }

        if (is_numeric($weight)) {
            $value = (float) $weight;
            if ($value <= 0) {
                return '—';
            }
            $formatted = fmod($value, 1.0) === 0.0 ? number_format($value, 0) : number_format($value, 1);
            return $formatted . ' kg';
        }

        $raw = trim((string) $weight);
        return $raw !== '' ? $raw : '—';
    }

    private function formatYesNo($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 'Yes' : 'No';
        }

        $normalized = strtolower(trim((string) $value, " \t\n\r\0\x0B\"'"));
        if (in_array($normalized, ['yes', 'y', 'true', '1'], true)) {
            return 'Yes';
        }
        if (in_array($normalized, ['no', 'n', 'false', '0'], true)) {
            return 'No';
        }

        $raw = trim((string) $value);
        return $raw !== '' ? $raw : '—';
    }

    private function resolveLastVaccinationSummary(array $pet): array
    {
        $payload = $pet['vaccination_payload'] ?? null;
        $vaccinationNode = null;

        if (is_array($payload)) {
            $vaccinationNode = $payload['vaccination'] ?? $payload;
        }

        $latest = null;
        $latestTimestamp = null;
        $index = 0;

        if (is_array($vaccinationNode)) {
            foreach ($vaccinationNode as $key => $entry) {
                $record = is_array($entry) ? $entry : ['value' => $entry];
                $lastDate = $this->normalizeDateString(
                    $record['last_date']
                        ?? $record['lastDate']
                        ?? $record['date']
                        ?? $record['date_given']
                        ?? null
                );

                if (!$lastDate) {
                    $index++;
                    continue;
                }

                try {
                    $timestamp = Carbon::parse($lastDate)->getTimestamp();
                } catch (\Throwable $e) {
                    $timestamp = null;
                }

                if ($timestamp === null) {
                    $index++;
                    continue;
                }

                if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                    $latestTimestamp = $timestamp;
                    $latest = [
                        'name' => $this->resolveVaccinationName($key, $record, $index),
                        'date' => $lastDate,
                        'doctor' => $record['doctor']
                            ?? $record['doctor_name']
                            ?? $record['vet']
                            ?? $record['administered_by']
                            ?? null,
                    ];
                }
                $index++;
            }
        }

        if ($latest === null) {
            $fallbackDate = $this->normalizeDateString($pet['last_vaccenated_date'] ?? $pet['vaccination_date'] ?? null);
            if ($fallbackDate) {
                $latest = [
                    'name' => 'Vaccination',
                    'date' => $fallbackDate,
                    'doctor' => null,
                ];
            }
        }

        return $latest ?? [
            'name' => '—',
            'date' => null,
            'doctor' => null,
        ];
    }

    private function resolveLastDewormingSummary(array $pet): array
    {
        $lastDate = $this->normalizeDateString($pet['last_deworming_date'] ?? null);

        return [
            'name' => 'Deworming',
            'date' => $lastDate,
            'doctor' => null,
        ];
    }

    private function normalizeMixedValue($value, int $limit = 4): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $parts = [];
                foreach ($value as $entry) {
                    $piece = $this->normalizeMixedValue($entry, $limit);
                    if ($piece !== '') {
                        $parts[] = $piece;
                    }
                    if (count($parts) >= $limit) {
                        break;
                    }
                }
                return implode(', ', $parts);
            }

            foreach (['value', 'label', 'text', 'name', 'title'] as $key) {
                if (isset($value[$key])) {
                    $candidate = $this->normalizeMixedValue($value[$key], $limit);
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }

            $parts = [];
            foreach ($value as $entry) {
                $piece = $this->normalizeMixedValue($entry, $limit);
                if ($piece !== '') {
                    $parts[] = $piece;
                }
                if (count($parts) >= $limit) {
                    break;
                }
            }

            return implode(', ', $parts);
        }

        return '';
    }

    private function extractStringFromArray(array $source, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }

            $value = $this->normalizeMixedValue($source[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function formatMedicationsSummary($medications): string
    {
        if (is_string($medications)) {
            return trim($medications);
        }

        if (is_array($medications)) {
            $items = [];
            if (!array_is_list($medications)) {
                $single = $this->normalizeMixedValue($medications);
                if ($single !== '') {
                    return $single;
                }
                $medications = [$medications];
            }

            foreach ($medications as $medication) {
                if (is_string($medication)) {
                    $items[] = trim($medication);
                    continue;
                }
                if (!is_array($medication)) {
                    $candidate = $this->normalizeMixedValue($medication);
                    if ($candidate !== '') {
                        $items[] = $candidate;
                    }
                    continue;
                }

                $name = $this->extractStringFromArray($medication, ['name', 'medicine', 'drug', 'title']);
                $dosage = $this->extractStringFromArray($medication, ['dosage', 'dose', 'strength']);
                $item = $name !== '' ? $name : $this->normalizeMixedValue($medication);
                if ($dosage !== '') {
                    $item .= ' (' . $dosage . ')';
                }
                if ($item !== '') {
                    $items[] = $item;
                }
            }

            $items = array_values(array_filter($items, fn ($item) => $item !== ''));
            if ($items !== []) {
                return implode(', ', array_slice($items, 0, 4));
            }
        }

        return '';
    }

    private function formatMedicationDetails($medications): string
    {
        if (is_string($medications)) {
            return trim($medications);
        }

        if (!is_array($medications) || $medications === []) {
            return '';
        }

        if (!array_is_list($medications)) {
            $medications = [$medications];
        }

        $items = [];
        foreach ($medications as $medication) {
            if (is_string($medication)) {
                $value = trim($medication);
                if ($value !== '') {
                    $items[] = $value;
                }
                continue;
            }

            if (!is_array($medication)) {
                $value = $this->normalizeMixedValue($medication);
                if ($value !== '') {
                    $items[] = $value;
                }
                continue;
            }

            $name = $this->extractStringFromArray($medication, ['name', 'medicine', 'drug', 'title']);
            $dose = $this->extractStringFromArray($medication, ['dose', 'dosage', 'strength']);
            $frequency = $this->extractStringFromArray($medication, ['frequency', 'timing', 'schedule']);
            $duration = $this->extractStringFromArray($medication, ['duration', 'days']);
            $route = $this->extractStringFromArray($medication, ['route']);
            $notes = $this->extractStringFromArray($medication, ['notes', 'note', 'instructions']);
            $foodRelation = $this->extractStringFromArray($medication, ['food_relation', 'foodRelation', 'food']);

            $timingsRaw = $medication['timings'] ?? null;
            $timings = '';
            if (is_array($timingsRaw)) {
                $timings = implode(', ', array_values(array_filter(array_map('strval', $timingsRaw), fn ($value) => trim($value) !== '')));
            } elseif (is_string($timingsRaw)) {
                $timings = trim($timingsRaw);
            }

            $details = [];
            if ($dose !== '') {
                $details[] = 'Dose: ' . $dose;
            }
            if ($frequency !== '') {
                $details[] = 'Frequency: ' . $frequency;
            }
            if ($duration !== '') {
                $details[] = 'Duration: ' . $duration;
            }
            if ($route !== '') {
                $details[] = 'Route: ' . $route;
            }
            if ($timings !== '') {
                $details[] = 'Timings: ' . $timings;
            }
            if ($foodRelation !== '') {
                $details[] = 'Food: ' . $foodRelation;
            }
            if ($notes !== '') {
                $details[] = 'Notes: ' . $notes;
            }

            $label = $name !== '' ? $name : 'Medication';
            if ($details !== []) {
                $label .= ' · ' . implode(' · ', $details);
            }

            $items[] = $label;
            if (count($items) >= 3) {
                break;
            }
        }

        return implode(' ; ', $items);
    }

    private function resolveOngoingTreatments(array $payload): array
    {
        $prescriptions = collect(data_get($payload, 'data.prescriptions', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($prescriptions->isEmpty()) {
            return [];
        }

        $items = [];

        foreach ($prescriptions as $prescription) {
            $diagnosisStatus = strtolower(trim((string) ($prescription['diagnosis_status'] ?? '')));
            $isChronic = filter_var($prescription['is_chronic'] ?? false, FILTER_VALIDATE_BOOL);
            $followUpRequired = filter_var($prescription['follow_up_required'] ?? false, FILTER_VALIDATE_BOOL);

            $hasTreatmentInfo = $this->extractStringFromArray($prescription, [
                'treatment_plan',
                'home_care',
                'doctor_treatment',
                'follow_up_notes',
            ]) !== '' || $this->formatMedicationsSummary($prescription['medications_json'] ?? null) !== '';

            $isOngoing = $isChronic || $diagnosisStatus === 'chronic' || $followUpRequired || $hasTreatmentInfo;

            if (!$isOngoing) {
                continue;
            }

            $items[] = $this->mapPrescriptionToOngoingTreatment($prescription);
            if (count($items) >= 3) {
                break;
            }
        }

        if ($items === [] && $prescriptions->isNotEmpty()) {
            $items[] = $this->mapPrescriptionToOngoingTreatment($prescriptions->first());
        }

        return $items;
    }

    private function mapPrescriptionToOngoingTreatment(array $prescription): array
    {
        $reason = $this->extractStringFromArray($prescription, [
            'visit_notes',
            'exam_notes',
            'history_snapshot',
            'follow_up_notes',
        ]);
        $diagnosis = $this->extractStringFromArray($prescription, ['diagnosis', 'disease_name']);
        $medications = $this->formatMedicationsSummary($prescription['medications_json'] ?? null);
        $advice = $this->extractStringFromArray($prescription, [
            'home_care',
            'treatment_plan',
            'doctor_treatment',
            'follow_up_notes',
        ]);

        $lastFollowUp = $this->formatPdfDate(
            $prescription['follow_up_date']
                ?? $prescription['updated_at']
                ?? $prescription['created_at']
                ?? null,
            '—'
        );

        $doctor = $prescription['doctor_name'] ?? $prescription['doctor_id'] ?? null;

        return [
            'record_id' => $prescription['id'] ?? null,
            'type' => 'On-going Treatment',
            'status' => 'Active',
            'last_follow_up' => $lastFollowUp,
            'doctor' => $doctor,
            'reason' => $reason !== '' ? $reason : '—',
            'diagnosis' => $diagnosis !== '' ? $diagnosis : '—',
            'medications' => $medications !== '' ? $medications : '—',
            'advice' => $advice !== '' ? $advice : '—',
        ];
    }

    private function buildOngoingTreatmentCardsHtml(array $items): string
    {
        if ($items === []) {
            return '';
        }

        $html = '';

        foreach ($items as $item) {
            $doctorLabel = '';
            if (!empty($item['doctor'])) {
                $doctorLabel = '<div class="event-meta"><span class="event-meta-label">Doctor ID</span><div class="event-meta-value">'
                    . $this->e((string) $item['doctor']) . '</div></div>';
            }

            $html .= '<div class="ongoing-card">';
            $html .= '<div class="ongoing-header"><table class="full-table"><tr>';
            $html .= '<td><table class="icon-table"><tr>';
            $html .= '<td class="icon-cell"><div class="icon-badge amber-bg amber-text">Rx</div></td>';
            $html .= '<td><div class="event-type amber-text">'.$this->e((string) ($item['type'] ?? 'On-going Treatment')).'</div>';
            $html .= '<div class="event-date amber-text">Status: '.$this->e((string) ($item['status'] ?? 'Active')).'</div>';
            if (!empty($item['last_follow_up'])) {
                $html .= '<div class="event-date muted">Last Follow-up: '.$this->e((string) $item['last_follow_up']).'</div>';
            }
            $html .= '</td></tr></table></td>';
            $html .= '<td class="right-cell">'.$doctorLabel.'</td>';
            $html .= '</tr></table></div>';
            $html .= '<div class="card-body">';
            $html .= '<table class="grid-table"><tr>';
            $html .= '<td><div class="event-label">Reported symptom</div><div class="event-value">'.$this->e((string) ($item['reason'] ?? '—')).'</div></td>';
            $html .= '<td><div class="event-label">Diagnosis</div><div class="event-value">'.$this->e((string) ($item['diagnosis'] ?? '—')).'</div></td>';
            $html .= '<td><div class="event-label">Medications</div><div class="event-value">'.$this->e((string) ($item['medications'] ?? '—')).'</div></td>';
            $html .= '<td><div class="event-label">Vet Advice & Home Care</div><div class="event-value">'.$this->e((string) ($item['advice'] ?? '—')).'</div></td>';
            $html .= '</tr></table></div></div>';
        }

        return $html;
    }

    private function buildLifelinePdfHtml(array $payload): string
    {
        $pet = is_array(data_get($payload, 'data.pet')) ? data_get($payload, 'data.pet') : [];
        $timeline = collect(data_get($payload, 'data.timeline', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        $petNameRaw = trim((string) ($pet['name'] ?? 'Pet'));
        $petName = $petNameRaw !== '' ? $petNameRaw : 'Pet';
        $species = trim((string) ($pet['pet_type'] ?? $pet['type'] ?? ''));
        $breed = trim((string) ($pet['breed'] ?? ''));
        $age = $this->formatPetAge($pet);
        $weight = $this->formatPetWeight($pet['weight'] ?? null);
        $neutered = $this->formatYesNo($pet['is_neutered'] ?? $pet['is_nuetered'] ?? null);
        $ownerName = trim((string) ($pet['owner_name'] ?? ''));
        $ownerCity = trim((string) ($pet['owner_city'] ?? ''));

        $lastVaccination = $this->resolveLastVaccinationSummary($pet);
        $lastVaccinationName = $lastVaccination['name'] ?? '—';
        $lastVaccinationDate = $this->formatPdfDate($lastVaccination['date'] ?? null);
        $lastVaccinationDoctor = trim((string) ($lastVaccination['doctor'] ?? ''));
        if ($lastVaccinationDoctor === '') {
            $lastVaccinationDoctor = 'Not recorded';
        }

        $lastDeworming = $this->resolveLastDewormingSummary($pet);
        $lastDewormingName = $lastDeworming['name'] ?? 'Deworming';
        $lastDewormingDate = $this->formatPdfDate($lastDeworming['date'] ?? null);
        $lastDewormingDoctor = trim((string) ($lastDeworming['doctor'] ?? ''));
        if ($lastDewormingDoctor === '') {
            $lastDewormingDoctor = 'Not recorded';
        }

        $ongoingTreatments = $this->resolveOngoingTreatments($payload);
        $ongoingHtml = $this->buildOngoingTreatmentCardsHtml($ongoingTreatments);
        $excludeRecordIds = array_values(array_filter(array_map(
            fn ($item) => $item['record_id'] ?? null,
            $ongoingTreatments
        ), fn ($id) => $id !== null && $id !== ''));

        $timelineHtml = $this->buildTimelineCardsHtml($timeline, $excludeRecordIds);
        $generatedAt = $this->e(now('Asia/Kolkata')->format('d M Y, h:i A'));

        $ongoingSection = '';
        if ($ongoingHtml !== '') {
            $ongoingSection = <<<HTML
            <div class="section-title"><span class="section-bar section-amber"></span>On-going Treatment</div>
            <div class="section-block">
                {$ongoingHtml}
            </div>
HTML;
        }

        $style = <<<CSS
@page { margin: 24px; }
body {
    margin: 0;
    padding: 0;
    background: #f1f5f9;
    font-family: DejaVu Sans, sans-serif;
    color: #0f172a;
    font-size: 11px;
}
.container {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
}
.header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 16px;
    margin-bottom: 14px;
}
.header-table {
    width: 100%;
    border-collapse: collapse;
}
.logo {
    width: 34px;
    height: 34px;
    background: #2563eb;
    color: #ffffff;
    border-radius: 10px;
    text-align: center;
    font-weight: 800;
    font-size: 16px;
    line-height: 34px;
}
.pet-name {
    font-size: 18px;
    font-weight: 800;
}
.badge {
    background: #f1f5f9;
    color: #64748b;
    border-radius: 999px;
    font-size: 9px;
    padding: 2px 6px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
}
.subtitle {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: #94a3b8;
    font-weight: 700;
    margin-top: 2px;
}
.meta {
    font-size: 9px;
    color: #64748b;
    text-align: right;
}
.card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 14px;
}
.timeline {
    margin-bottom: 10px;
}
.info-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    font-weight: 700;
    margin-bottom: 3px;
}
.info-value {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}
.info-muted {
    font-size: 9px;
    color: #94a3b8;
}
.divider {
    height: 1px;
    background: #e2e8f0;
    margin: 12px 0;
}
.grid-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.grid-table td {
    vertical-align: top;
    padding-right: 10px;
}
.mini-card {
    background: #f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 12px;
    padding: 10px;
}
.icon-badge {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    text-align: center;
    line-height: 26px;
    font-weight: 800;
    font-size: 10px;
}
.icon-table {
    border-collapse: collapse;
}
.icon-cell {
    width: 32px;
}
.section-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #1e293b;
    margin: 6px 0 8px;
}
.section-bar {
    display: inline-block;
    width: 6px;
    height: 16px;
    border-radius: 4px;
    margin-right: 6px;
    vertical-align: middle;
}
.section-amber { background: #f59e0b; }
.section-blue { background: #3b82f6; }
.ongoing-card {
    background: #ffffff;
    border: 2px solid #fef3c7;
    border-radius: 16px;
    margin-bottom: 8px;
    overflow: hidden;
    page-break-inside: avoid;
}
.ongoing-header {
    background: #fffbeb;
    border-bottom: 1px solid #fde68a;
    padding: 10px 12px;
}
.event-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 10px;
    overflow: hidden;
    page-break-inside: avoid;
}
.event-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 12px;
}
.event-type {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}
.event-date {
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-top: 2px;
}
.event-meta {
    text-align: right;
}
.event-meta-label {
    font-size: 9px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
}
.event-meta-value {
    font-size: 10px;
    font-weight: 800;
    color: #1e293b;
}
.event-label {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    color: #94a3b8;
    letter-spacing: 0.05em;
    margin-bottom: 2px;
}
.event-value {
    font-size: 10px;
    color: #0f172a;
    line-height: 1.4;
}
.card-body {
    padding: 10px 12px;
}
.year-badge {
    display: inline-block;
    background: #0f172a;
    color: #ffffff;
    font-size: 9px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 999px;
    margin: 6px 0;
}
.muted { color: #94a3b8; }
.right-cell { text-align: right; vertical-align: top; }
.full-table { width: 100%; border-collapse: collapse; }
.blue-text { color: #2563eb; }
.blue-bg { background: #dbeafe; }
.cyan-text { color: #0891b2; }
.cyan-bg { background: #cffafe; }
.green-text { color: #059669; }
.green-bg { background: #dcfce7; }
.purple-text { color: #7c3aed; }
.purple-bg { background: #ede9fe; }
.amber-text { color: #b45309; }
.amber-bg { background: #fef3c7; }
.red-text { color: #dc2626; }
.red-bg { background: #fee2e2; }
.slate-text { color: #475569; }
.slate-bg { background: #e2e8f0; }
.footer {
    text-align: center;
    color: #cbd5e1;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    margin-top: 18px;
}
CSS;

        $petNameEsc = $this->e($petName);
        $speciesEsc = $this->e($species !== '' ? $species : 'Pet');
        $breedEsc = $this->e($breed !== '' ? $breed : '—');
        $ageEsc = $this->e($age);
        $weightEsc = $this->e($weight);
        $neuteredEsc = $this->e($neutered);
        $ownerNameEsc = $this->e($ownerName !== '' ? $ownerName : '—');
        $ownerCityEsc = $this->e($ownerCity !== '' ? $ownerCity : '—');
        $lastVaccinationNameEsc = $this->e($lastVaccinationName);
        $lastVaccinationDateEsc = $this->e($lastVaccinationDate);
        $lastVaccinationDoctorEsc = $this->e($lastVaccinationDoctor);
        $lastDewormingNameEsc = $this->e($lastDewormingName);
        $lastDewormingDateEsc = $this->e($lastDewormingDate);
        $lastDewormingDoctorEsc = $this->e($lastDewormingDoctor);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>{$style}</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <table class="icon-table">
                            <tr>
                                <td class="icon-cell"><div class="logo">S</div></td>
                                <td>
                                    <div class="pet-name">{$petNameEsc}</div>
                                    <div class="subtitle">Medical Lifeline</div>
                                </td>
                                <td style="padding-left:8px;">
                                    <div class="badge">Species: {$speciesEsc}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="meta">Generated on {$generatedAt}</td>
                </tr>
            </table>
        </div>

        <div class="card">
            <table class="grid-table">
                <tr>
                    <td>
                        <div class="info-label">Breed</div>
                        <div class="info-value">{$breedEsc}</div>
                    </td>
                    <td>
                        <div class="info-label">Age</div>
                        <div class="info-value">{$ageEsc}</div>
                    </td>
                    <td>
                        <div class="info-label">Weight</div>
                        <div class="info-value">{$weightEsc}</div>
                    </td>
                    <td>
                        <div class="info-label">Neutering</div>
                        <div class="info-value">{$neuteredEsc}</div>
                    </td>
                    <td class="right-cell">
                        <div class="info-label">Pet Parent</div>
                        <div class="info-value">{$ownerNameEsc}</div>
                        <div class="info-muted">{$ownerCityEsc}</div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <table class="grid-table">
                <tr>
                    <td>
                        <div class="mini-card">
                            <table class="icon-table">
                                <tr>
                                    <td class="icon-cell"><div class="icon-badge green-bg green-text">V</div></td>
                                    <td>
                                        <div class="info-label">Last Vaccination</div>
                                        <div class="info-value">{$lastVaccinationNameEsc}</div>
                                        <div class="info-muted">{$lastVaccinationDateEsc}</div>
                                        <div class="info-muted">Administered by {$lastVaccinationDoctorEsc}</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td>
                        <div class="mini-card">
                            <table class="icon-table">
                                <tr>
                                    <td class="icon-cell"><div class="icon-badge purple-bg purple-text">D</div></td>
                                    <td>
                                        <div class="info-label">Last Deworming Date</div>
                                        <div class="info-value">{$lastDewormingDateEsc}</div>
                                        <div class="info-muted">Administered by {$lastDewormingDoctorEsc}</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {$ongoingSection}

        <div class="section-title"><span class="section-bar section-blue"></span>Medical History</div>
        <div class="timeline">
            {$timelineHtml}
        </div>

        <div class="footer">Pet Lifeline</div>
    </div>
</body>
</html>
HTML;
    }

    private function buildTimelineCardsHtml(Collection $timeline, array $excludeRecordIds = []): string
    {
        if ($excludeRecordIds !== []) {
            $timeline = $timeline->filter(function (array $item) use ($excludeRecordIds) {
                $recordId = $item['record_id'] ?? null;
                return $recordId === null || $recordId === '' || !in_array($recordId, $excludeRecordIds, true);
            })->values();
        }

        if ($timeline->isEmpty()) {
            return '<div class="card"><div class="info-muted">No timeline events available for this pet.</div></div>';
        }

        $grouped = $timeline
            ->groupBy(function (array $item) {
                $year = trim((string) ($item['year'] ?? ''));
                return $year !== '' ? $year : 'Earlier';
            })
            ->sortByDesc(function (Collection $items, string $year) {
                return is_numeric($year) ? (int) $year : -1;
            });

        $html = '';

        foreach ($grouped as $year => $items) {
            $html .= '<div class="year-badge">'.$this->e((string) $year).'</div>';

            foreach ($items as $item) {
                $event = $this->mapTimelineItemForPdf($item);
                $iconClass = $event['badge_class'] ?? 'slate-bg slate-text';
                $textClass = $event['text_class'] ?? 'slate-text';

                $doctorHtml = '';
                if (!empty($event['doctor'])) {
                    $doctorHtml = '<div class="event-meta"><div class="event-meta-label">Doctor ID</div>'
                        . '<div class="event-meta-value">'.$this->e((string) $event['doctor']).'</div></div>';
                }

                $header = '<div class="event-header"><table class="full-table"><tr>'
                    . '<td><table class="icon-table"><tr>'
                    . '<td class="icon-cell"><div class="icon-badge '.$iconClass.'">'.$this->e((string) $event['icon_text']).'</div></td>'
                    . '<td><div class="event-type '.$textClass.'">'.$this->e((string) $event['type_label']).'</div>'
                    . '<div class="event-date">'.$this->e((string) $event['date']).'</div></td>'
                    . '</tr></table></td>'
                    . '<td class="right-cell">'.$doctorHtml.'</td>'
                    . '</tr></table></div>';

                if (($event['layout'] ?? '') === 'consult') {
                    $html .= '<div class="event-card">'.$header;
                    $html .= '<div class="card-body"><table class="grid-table"><tr>';
                    $html .= '<td><div class="event-label">Reported symptom</div><div class="event-value">'.$this->e((string) $event['reason']).'</div></td>';
                    $html .= '<td><div class="event-label">Diagnosis</div><div class="event-value">'.$this->e((string) $event['diagnosis']).'</div></td>';
                    $html .= '<td><div class="event-label">Medications</div><div class="event-value">'.$this->e((string) $event['medications']).'</div></td>';
                    $html .= '<td><div class="event-label">Vet Advice & Home Care</div><div class="event-value">'.$this->e((string) $event['advice']).'</div></td>';
                    $html .= '</tr></table></div></div>';
                    continue;
                }

                $nextDue = $event['next_due'] ?? '';
                $result = $event['result'] ?? '';
                $sideHtml = '';
                if ($nextDue !== '' || $result !== '') {
                    $sideHtml .= '<td class="right-cell">';
                    if ($nextDue !== '') {
                        $sideHtml .= '<div class="event-label">Next Due</div><div class="event-value">'.$this->e((string) $nextDue).'</div>';
                    }
                    if ($result !== '') {
                        $sideHtml .= '<div class="event-label">Result</div><div class="event-value">'.$this->e((string) $result).'</div>';
                    }
                    $sideHtml .= '</td>';
                }

                $details = $event['details'] ?? '';
                $html .= '<div class="event-card">'.$header;
                $html .= '<div class="card-body"><table class="full-table"><tr>';
                $html .= '<td><div class="info-value">'.$this->e((string) ($event['title'] ?? 'Timeline Event')).'</div>';
                if ($details !== '') {
                    $html .= '<div class="event-value muted">'.$this->e((string) $details).'</div>';
                }
                $html .= '</td>'.$sideHtml.'</tr></table></div></div>';
            }
        }

        return $html;
    }

    private function mapTimelineItemForPdf(array $item): array
    {
        $eventType = strtolower(trim((string) ($item['event_type'] ?? $item['source'] ?? 'event')));
        $record = is_array($item['record'] ?? null) ? $item['record'] : [];
        $visual = $this->resolveTimelineVisual($eventType);

        $doctor = $record['doctor_name'] ?? $record['doctor'] ?? null;
        if (($doctor === null || $doctor === '') && isset($record['doctor_id'])) {
            $doctor = (string) $record['doctor_id'];
        }

        $date = $this->formatPdfDate($item['event_at'] ?? $item['created_at'] ?? null);

        if (in_array($eventType, ['appointment', 'video_consultation', 'prescription'], true)) {
            $reason = '';
            $diagnosis = '';
            $medications = '';
            $advice = '';

            if ($eventType === 'appointment') {
                $notes = is_array($record['notes_decoded'] ?? null) ? $record['notes_decoded'] : [];
                $clinicPrescription = is_array($record['clinic_prescription'] ?? null) ? $record['clinic_prescription'] : [];

                $reportedSymptom = $this->normalizeMixedValue($record['pet_reported_symptom'] ?? null);
                if ($reportedSymptom === '') {
                    $reportedSymptom = $this->extractStringFromArray($notes, ['symptoms', 'reason', 'complaint', 'notes']);
                }

                $diagnosis = $this->extractStringFromArray($clinicPrescription, ['diagnosis', 'diagnosys', 'disease_name']);
                if ($diagnosis === '') {
                    $diagnosis = $this->extractStringFromArray($notes, ['diagnosis', 'diagnosis_summary', 'disease']);
                }

                $medications = $this->formatMedicationDetails($clinicPrescription['medications_json'] ?? null);
                if ($medications === '') {
                    $medications = $this->extractStringFromArray($notes, ['medications', 'medicines']);
                }

                $advice = $this->extractStringFromArray($clinicPrescription, ['visit_notes', 'follow_up_notes', 'notes']);
                if ($advice === '') {
                    $advice = $this->extractStringFromArray($notes, ['advice', 'instructions', 'home_care', 'follow_up']);
                }

                $reason = $reportedSymptom;
            } elseif ($eventType === 'video_consultation') {
                $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];
                $reason = $this->extractStringFromArray($metadata, ['symptoms', 'reason', 'complaint', 'notes']);
                $diagnosis = $this->extractStringFromArray($metadata, ['diagnosis', 'diagnosis_summary', 'disease']);
                $medications = $this->formatMedicationsSummary($metadata['medications'] ?? null);
                $advice = $this->extractStringFromArray($metadata, ['advice', 'instructions', 'home_care', 'follow_up']);
            } else {
                $reason = $this->extractStringFromArray($record, ['visit_notes', 'exam_notes', 'history_snapshot', 'follow_up_notes']);
                $diagnosis = $this->extractStringFromArray($record, ['diagnosis', 'disease_name']);
                $medications = $this->formatMedicationsSummary($record['medications_json'] ?? null);
                $advice = $this->extractStringFromArray($record, ['home_care', 'treatment_plan', 'doctor_treatment', 'follow_up_notes']);
            }

            $reason = $reason !== '' ? $this->truncateText($reason, 180) : '—';
            $diagnosis = $diagnosis !== '' ? $this->truncateText($diagnosis, 160) : '—';
            $medications = $medications !== '' ? $this->truncateText($medications, 360) : '—';
            $advice = $advice !== '' ? $this->truncateText($advice, 220) : '—';

            return [
                'layout' => 'consult',
                'type_label' => $visual['label'],
                'icon_text' => $visual['icon'],
                'badge_class' => $visual['badge_class'],
                'text_class' => $visual['text_class'],
                'date' => $date,
                'doctor' => $doctor,
                'reason' => $reason,
                'diagnosis' => $diagnosis,
                'medications' => $medications,
                'advice' => $advice,
            ];
        }

        $title = 'Timeline Event';
        $details = '';
        $nextDue = '';
        $result = '';

        if ($eventType === 'vaccination') {
            $title = trim((string) ($record['vaccine_name'] ?? 'Vaccination')) ?: 'Vaccination';
            $last = $this->formatPdfDate($record['last_date'] ?? null, '');
            $next = $this->formatPdfDate($record['next_due'] ?? null, '');
            $status = trim((string) ($record['status'] ?? ''));
            $detailsParts = [];
            if ($last !== '') {
                $detailsParts[] = 'Last: '.$last;
            }
            if ($next !== '') {
                $detailsParts[] = 'Next due: '.$next;
                $nextDue = $next;
            }
            if ($status !== '') {
                $detailsParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
                $result = ucwords(str_replace(['_', '-'], ' ', $status));
            }
            $details = implode(' · ', $detailsParts);
        } elseif ($eventType === 'deworming') {
            $title = 'Deworming';
            $last = $this->formatPdfDate($record['last_deworming_date'] ?? null, '');
            $next = $this->formatPdfDate($record['next_deworming_date'] ?? null, '');
            $status = trim((string) ($record['deworming_status'] ?? ''));
            $detailsParts = [];
            if ($last !== '') {
                $detailsParts[] = 'Last: '.$last;
            }
            if ($next !== '') {
                $detailsParts[] = 'Next due: '.$next;
                $nextDue = $next;
            }
            if ($status !== '') {
                $detailsParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
                $result = ucwords(str_replace(['_', '-'], ' ', $status));
            }
            $details = implode(' · ', $detailsParts);
        } else {
            $title = $this->extractStringFromArray($record, ['title', 'name', 'summary']);
            if ($title === '') {
                $title = ucwords(str_replace(['_', '-'], ' ', $eventType));
            }
            $details = $this->extractStringFromArray($record, ['details', 'description', 'notes']);
        }

        return [
            'layout' => 'simple',
            'type_label' => $visual['label'],
            'icon_text' => $visual['icon'],
            'badge_class' => $visual['badge_class'],
            'text_class' => $visual['text_class'],
            'date' => $date,
            'doctor' => $doctor,
            'title' => $title,
            'details' => $this->truncateText($details, 220),
            'next_due' => $nextDue,
            'result' => $result,
        ];
    }

    private function resolveTimelineVisual(string $eventType): array
    {
        return match ($eventType) {
            'vaccination' => ['label' => 'Vaccination', 'icon' => 'V', 'badge_class' => 'green-bg green-text', 'text_class' => 'green-text'],
            'deworming' => ['label' => 'Deworming', 'icon' => 'D', 'badge_class' => 'purple-bg purple-text', 'text_class' => 'purple-text'],
            'video_consultation' => ['label' => 'Video Consult', 'icon' => 'VC', 'badge_class' => 'cyan-bg cyan-text', 'text_class' => 'cyan-text'],
            'appointment' => ['label' => 'Clinic Appointment', 'icon' => 'A', 'badge_class' => 'blue-bg blue-text', 'text_class' => 'blue-text'],
            'prescription' => ['label' => 'Prescription', 'icon' => 'Rx', 'badge_class' => 'amber-bg amber-text', 'text_class' => 'amber-text'],
            'lab_report', 'lab', 'lab_reports' => ['label' => 'Lab Report', 'icon' => 'L', 'badge_class' => 'slate-bg slate-text', 'text_class' => 'slate-text'],
            'surgery' => ['label' => 'Surgery', 'icon' => 'S', 'badge_class' => 'red-bg red-text', 'text_class' => 'red-text'],
            default => ['label' => 'Timeline', 'icon' => '•', 'badge_class' => 'slate-bg slate-text', 'text_class' => 'slate-text'],
        };
    }

    private function resolveNextDueDetails(array $payload): ?array
    {
        $candidates = [];
        $today = Carbon::today();

        $vaccinations = collect(data_get($payload, 'data.vaccinations', []))->filter(fn ($item) => is_array($item));
        foreach ($vaccinations as $vaccination) {
            $nextDueRaw = $vaccination['next_due'] ?? null;
            if (!is_string($nextDueRaw) || trim($nextDueRaw) === '') {
                continue;
            }

            try {
                $nextDue = Carbon::parse($nextDueRaw)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            if ($nextDue->lt($today)) {
                continue;
            }

            $name = trim((string) ($vaccination['vaccine_name'] ?? 'Vaccination'));
            if ($name === '') {
                $name = 'Vaccination';
            }

            $candidates[] = [
                'date' => $nextDue,
                'title' => $name.' due',
            ];
        }

        $deworming = collect(data_get($payload, 'data.deworming', []))->filter(fn ($item) => is_array($item));
        foreach ($deworming as $row) {
            $nextDueRaw = $row['next_deworming_date'] ?? null;
            if (!is_string($nextDueRaw) || trim($nextDueRaw) === '') {
                continue;
            }

            try {
                $nextDue = Carbon::parse($nextDueRaw)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            if ($nextDue->lt($today)) {
                continue;
            }

            $candidates[] = [
                'date' => $nextDue,
                'title' => 'Deworming due',
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b) => $a['date']->getTimestamp() <=> $b['date']->getTimestamp());
        $nearest = $candidates[0];
        $days = $today->diffInDays($nearest['date'], false);

        $when = $days === 0
            ? 'Today'
            : ($days === 1 ? 'in 1 day' : 'in '.$days.' days');

        return [
            'title' => $nearest['title'],
            'subtitle' => $nearest['date']->format('d M Y').' · '.$when,
        ];
    }

    private function resolveActiveTreatmentSummary(array $payload): ?string
    {
        $prescriptions = collect(data_get($payload, 'data.prescriptions', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($prescriptions->isEmpty()) {
            return null;
        }

        $latest = $prescriptions->first();
        $prescriptionId = $latest['id'] ?? null;
        $label = $prescriptionId !== null ? 'Prescription #'.$prescriptionId.': ' : '';

        foreach (['prescription', 'prescription_text', 'diagnosis', 'advice', 'notes'] as $key) {
            if (isset($latest[$key]) && is_string($latest[$key]) && trim($latest[$key]) !== '') {
                return $label.$this->truncateText(trim($latest[$key]), 140);
            }
        }

        return $label.'Latest prescription is available in timeline details.';
    }

    private function formatPdfDate($value, string $fallback = '—'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($raw)->format('d M Y');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    private function truncateText(string $text, int $maxLength): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        $length = function_exists('mb_strlen') ? mb_strlen($normalized) : strlen($normalized);

        if ($normalized === '' || $length <= $maxLength) {
            return $normalized;
        }

        $sliceLength = max($maxLength - 3, 1);
        $truncated = function_exists('mb_substr')
            ? mb_substr($normalized, 0, $sliceLength)
            : substr($normalized, 0, $sliceLength);

        return rtrim($truncated).'...';
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
