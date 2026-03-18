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

    private function buildLifelinePdfHtml(array $payload): string
    {
        $pet = is_array(data_get($payload, 'data.pet')) ? data_get($payload, 'data.pet') : [];
        $timeline = collect(data_get($payload, 'data.timeline', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        $petNameRaw = trim((string) ($pet['name'] ?? 'Pet'));
        $petName = $petNameRaw !== '' ? $petNameRaw : 'Pet';
        $petSummaryParts = array_values(array_filter([
            trim((string) ($pet['breed'] ?? '')),
            trim((string) ($pet['pet_type'] ?? '')),
            trim((string) ($pet['pet_gender'] ?? '')),
        ], fn (string $value) => $value !== ''));
        $petSummary = $petSummaryParts !== []
            ? implode(' · ', $petSummaryParts)
            : 'Pet health history';

        $counts = is_array(data_get($payload, 'counts')) ? data_get($payload, 'counts') : [];
        $timelineCount = (int) ($counts['timeline'] ?? $timeline->count());
        $consultCount = (int) ($counts['video_consultations'] ?? 0);
        $prescriptionCount = (int) ($counts['prescriptions'] ?? 0);

        $nextDue = $this->resolveNextDueDetails($payload);
        $nextTitle = $nextDue['title'] ?? 'No upcoming reminder';
        $nextSubtitle = $nextDue['subtitle'] ?? 'Your upcoming vaccine/deworming reminders will appear here.';

        $activeTreatment = $this->resolveActiveTreatmentSummary($payload);
        $activeTreatmentHtml = $activeTreatment !== null
            ? '<div class="active-chip"><div class="active-dot"></div><div><div class="active-title">Ongoing treatment</div><div class="active-sub">'.$this->e($activeTreatment).'</div></div></div>'
            : '';

        $timelineHtml = $this->buildTimelineCardsHtml($timeline);
        $generatedAt = $this->e(now('Asia/Kolkata')->format('d M Y, h:i A'));

        $style = <<<CSS
body {
    margin: 0;
    padding: 0;
    background: #d8d6cf;
    font-family: DejaVu Sans, sans-serif;
    color: #18180f;
    font-size: 12px;
}
.wrap {
    padding: 20px;
}
.phone {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    background: #f5f4f0;
    border-radius: 22px;
    border: 1px solid #d8d6cf;
    overflow: hidden;
}
.topbar {
    background: #ffffff;
    border-bottom: 1px solid #e8e7e3;
    padding: 16px 18px;
}
.title {
    font-size: 18px;
    font-weight: 700;
    color: #18180f;
}
.subtitle {
    font-size: 11px;
    color: #9c9a94;
    margin-top: 2px;
}
.meta-strip {
    margin-top: 10px;
    font-size: 10px;
    color: #6b6a66;
}
.pet-card {
    margin: 12px;
    background: #ffffff;
    border: 1px solid #ecebe7;
    border-radius: 14px;
    padding: 12px 14px;
}
.pet-name {
    font-size: 16px;
    font-weight: 700;
}
.pet-sub {
    font-size: 11px;
    color: #6b6a66;
    margin-top: 3px;
}
.stats {
    margin-top: 8px;
    font-size: 10px;
    color: #5c5b57;
}
.next-banner {
    margin: 0 12px 12px;
    background: #1a6fb5;
    color: #ffffff;
    border-radius: 14px;
    padding: 12px 14px;
}
.next-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #d7e8f8;
}
.next-title {
    font-size: 14px;
    font-weight: 700;
    margin-top: 3px;
}
.next-sub {
    font-size: 11px;
    color: #dbe8f4;
    margin-top: 2px;
}
.active-chip {
    margin: 0 12px 14px;
    background: #fdf2e0;
    border: 1px solid #f0c060;
    border-radius: 10px;
    padding: 10px 12px;
}
.active-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #d97706;
    margin-right: 8px;
}
.active-title {
    display: inline;
    font-size: 12px;
    font-weight: 700;
    color: #a06010;
}
.active-sub {
    margin-top: 4px;
    font-size: 11px;
    color: #5c5b57;
}
.section-label {
    padding: 0 12px 8px;
    font-size: 10px;
    font-weight: 700;
    color: #9c9a94;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.timeline {
    padding: 0 12px 14px;
}
.year-badge {
    display: inline-block;
    background: #18180f;
    color: #ffffff;
    border-radius: 18px;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    margin: 4px 0 8px;
}
.event-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 8px;
}
.event-icon-col {
    width: 48px;
    vertical-align: top;
    padding-top: 2px;
}
.event-icon {
    width: 36px;
    height: 36px;
    line-height: 36px;
    text-align: center;
    border-radius: 18px;
    font-size: 12px;
    font-weight: 700;
}
.event-body {
    background: #ffffff;
    border: 1px solid #ecebe7;
    border-radius: 10px;
    padding: 10px 12px;
}
.event-type {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.event-title {
    font-size: 13px;
    font-weight: 700;
    color: #18180f;
    margin-top: 2px;
}
.event-date {
    font-size: 11px;
    color: #6b6a66;
    margin-top: 2px;
}
.event-meta {
    font-size: 11px;
    color: #5c5b57;
    margin-top: 3px;
}
.event-note {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid #ecebe7;
    font-size: 10px;
    color: #6b6a66;
    line-height: 1.45;
}
CSS;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>{$style}</style>
</head>
<body>
    <div class="wrap">
        <div class="phone">
            <div class="topbar">
                <div class="title">{$this->e($petName)}'s Lifeline</div>
                <div class="subtitle">Pet health history</div>
                <div class="meta-strip">Generated on {$generatedAt}</div>
            </div>

            <div class="pet-card">
                <div class="pet-name">{$this->e($petName)}</div>
                <div class="pet-sub">{$this->e($petSummary)}</div>
                <div class="stats">
                    Total events: {$this->e((string) $timelineCount)} · Consults: {$this->e((string) $consultCount)} · Prescriptions: {$this->e((string) $prescriptionCount)}
                </div>
            </div>

            <div class="next-banner">
                <div class="next-label">Coming up next</div>
                <div class="next-title">{$this->e($nextTitle)}</div>
                <div class="next-sub">{$this->e($nextSubtitle)}</div>
            </div>

            {$activeTreatmentHtml}

            <div class="section-label">Full Medical History</div>
            <div class="timeline">
                {$timelineHtml}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function buildTimelineCardsHtml(Collection $timeline): string
    {
        if ($timeline->isEmpty()) {
            return '<div class="event-body">No timeline events available for this pet.</div>';
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

                $metaHtml = $event['meta'] !== ''
                    ? '<div class="event-meta">'.$this->e($event['meta']).'</div>'
                    : '';
                $noteHtml = $event['note'] !== ''
                    ? '<div class="event-note">'.$this->e($event['note']).'</div>'
                    : '';

                $html .= '<table class="event-table"><tr>';
                $html .= '<td class="event-icon-col"><div class="event-icon" style="background:'.$this->e($event['icon_bg']).';color:'.$this->e($event['icon_color']).';">'.$this->e($event['icon_text']).'</div></td>';
                $html .= '<td><div class="event-body">';
                $html .= '<div class="event-type" style="color:'.$this->e($event['icon_color']).';">'.$this->e($event['type_label']).'</div>';
                $html .= '<div class="event-title">'.$this->e($event['title']).'</div>';
                $html .= '<div class="event-date">'.$this->e($event['date']).'</div>';
                $html .= $metaHtml.$noteHtml;
                $html .= '</div></td>';
                $html .= '</tr></table>';
            }
        }

        return $html;
    }

    private function mapTimelineItemForPdf(array $item): array
    {
        $eventType = strtolower(trim((string) ($item['event_type'] ?? $item['source'] ?? 'event')));
        $record = is_array($item['record'] ?? null) ? $item['record'] : [];
        $visual = $this->resolveTimelineVisual($eventType);

        $title = 'Timeline Event';
        $metaParts = [];
        $note = '';

        if ($eventType === 'vaccination') {
            $title = trim((string) ($record['vaccine_name'] ?? 'Vaccination'));
            if ($title === '') {
                $title = 'Vaccination';
            }

            $last = $this->formatPdfDate($record['last_date'] ?? null, '');
            $next = $this->formatPdfDate($record['next_due'] ?? null, '');
            if ($last !== '') {
                $metaParts[] = 'Last: '.$last;
            }
            if ($next !== '') {
                $metaParts[] = 'Next due: '.$next;
            }

            $status = trim((string) ($record['status'] ?? ''));
            if ($status !== '') {
                $metaParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
            }
        } elseif ($eventType === 'deworming') {
            $title = 'Deworming';
            $last = $this->formatPdfDate($record['last_deworming_date'] ?? null, '');
            $next = $this->formatPdfDate($record['next_deworming_date'] ?? null, '');
            if ($last !== '') {
                $metaParts[] = 'Last: '.$last;
            }
            if ($next !== '') {
                $metaParts[] = 'Next due: '.$next;
            }

            $status = trim((string) ($record['deworming_status'] ?? ''));
            if ($status !== '') {
                $metaParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
            }
        } elseif ($eventType === 'video_consultation') {
            $title = 'Online Vet Consultation';
            $transactionId = $record['id'] ?? $item['record_id'] ?? null;
            if ($transactionId !== null && $transactionId !== '') {
                $metaParts[] = 'Transaction #'.$transactionId;
            }

            $status = trim((string) ($record['status'] ?? ''));
            if ($status !== '') {
                $metaParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
            }

            $amountPaise = $record['actual_amount_paid_by_consumer_paise'] ?? $record['amount_paise'] ?? null;
            if (is_numeric($amountPaise) && (int) $amountPaise > 0) {
                $metaParts[] = 'Amount: INR '.number_format(((int) $amountPaise) / 100, 2, '.', ',');
            }
        } elseif ($eventType === 'appointment') {
            $title = 'Clinic Appointment';

            $status = trim((string) ($record['status'] ?? ''));
            if ($status !== '') {
                $metaParts[] = 'Status: '.ucwords(str_replace(['_', '-'], ' ', $status));
            }

            $doctor = $record['doctor_name'] ?? $record['doctor_id'] ?? null;
            if ($doctor !== null && $doctor !== '') {
                $metaParts[] = 'Doctor: '.$doctor;
            }

            $notes = is_array($record['notes_decoded'] ?? null) ? $record['notes_decoded'] : [];
            foreach (['symptoms', 'reason', 'complaint', 'notes'] as $key) {
                if (isset($notes[$key]) && is_string($notes[$key]) && trim($notes[$key]) !== '') {
                    $note = trim($notes[$key]);
                    break;
                }
            }
        } elseif ($eventType === 'prescription') {
            $title = 'Prescription Added';
            $prescriptionId = $record['id'] ?? $item['record_id'] ?? null;
            if ($prescriptionId !== null && $prescriptionId !== '') {
                $metaParts[] = 'Prescription #'.$prescriptionId;
            }

            $doctor = $record['doctor_name'] ?? $record['doctor_id'] ?? null;
            if ($doctor !== null && $doctor !== '') {
                $metaParts[] = 'Doctor: '.$doctor;
            }

            foreach (['prescription', 'prescription_text', 'diagnosis', 'advice', 'notes'] as $key) {
                if (isset($record[$key]) && is_string($record[$key]) && trim($record[$key]) !== '') {
                    $note = trim($record[$key]);
                    break;
                }
            }
        } else {
            $title = ucwords(str_replace(['_', '-'], ' ', $eventType));
            $recordId = $item['record_id'] ?? null;
            if ($recordId !== null && $recordId !== '') {
                $metaParts[] = 'Record #'.$recordId;
            }
        }

        $note = $this->truncateText($note, 220);

        return [
            'type_label' => $visual['label'],
            'icon_text' => $visual['icon'],
            'icon_color' => $visual['color'],
            'icon_bg' => $visual['bg'],
            'title' => $title,
            'date' => $this->formatPdfDate($item['event_at'] ?? $item['created_at'] ?? null),
            'meta' => implode(' · ', $metaParts),
            'note' => $note,
        ];
    }

    private function resolveTimelineVisual(string $eventType): array
    {
        return match ($eventType) {
            'vaccination' => ['label' => 'Vaccination', 'icon' => 'V', 'color' => '#2d7a4f', 'bg' => '#e8f5ef'],
            'deworming' => ['label' => 'Deworming', 'icon' => 'D', 'color' => '#5e4bb5', 'bg' => '#eeecfd'],
            'video_consultation' => ['label' => 'Video Consult', 'icon' => 'VC', 'color' => '#1a6fb5', 'bg' => '#e6f1fb'],
            'appointment' => ['label' => 'Clinic Visit', 'icon' => 'A', 'color' => '#0d4a82', 'bg' => '#d8eaf8'],
            'prescription' => ['label' => 'Prescription', 'icon' => 'Rx', 'color' => '#a06010', 'bg' => '#fdf2e0'],
            default => ['label' => 'Timeline', 'icon' => '•', 'color' => '#6b6a66', 'bg' => '#f3f2ef'],
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
