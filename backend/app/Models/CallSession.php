<?php

namespace App\Models;

use App\Support\CallSessionUrlBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CallSession extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'channel_name',
        'call_identifier',
        'doctor_join_url',
        'patient_payment_url',
        'status',
        'payment_status',
        'accepted_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'payment_id',
        'amount_paid',
        'currency',
        'qr_scanner_id',
    ];

    protected $casts = [
        'accepted_at'       => 'datetime',
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
        'duration_seconds'  => 'integer',
        'amount_paid'       => 'integer',
    ];

    protected static array $columnSupportCache = [];
    protected ?string $pendingCallIdentifier = null;

    public static function supportsColumn(string $column): bool
    {
        if (!array_key_exists($column, self::$columnSupportCache)) {
            $table = (new self())->getTable();
            self::$columnSupportCache[$column] = Schema::hasColumn($table, $column);
        }

        return self::$columnSupportCache[$column];
    }

    public function useCallIdentifier(string $identifier): self
    {
        $this->pendingCallIdentifier = $identifier;

        if (self::supportsColumn('call_identifier')) {
            $this->setAttribute('call_identifier', $identifier);
        }

        return $this;
    }

    public function resolveIdentifier(): string
    {
        if (!empty($this->pendingCallIdentifier)) {
            return $this->pendingCallIdentifier;
        }

        $stored = $this->getAttribute('call_identifier');
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $channel = (string) ($this->channel_name ?? '');
        if ($channel !== '' && Str::startsWith($channel, 'channel_')) {
            return substr($channel, strlen('channel_'));
        }

        if ($this->getAttribute('id')) {
            return 'call_' . $this->getAttribute('id');
        }

        return CallSessionUrlBuilder::generateIdentifier();
    }

    public function refreshComputedLinks(?string $frontendBase = null): self
    {
        $doctorUrl = $this->buildDoctorJoinUrl($frontendBase);
        $paymentUrl = $this->buildPatientPaymentUrl($frontendBase);

        if (self::supportsColumn('doctor_join_url')) {
            $this->setAttribute('doctor_join_url', $doctorUrl);
        }

        if (self::supportsColumn('patient_payment_url')) {
            $this->setAttribute('patient_payment_url', $paymentUrl);
        }

        return $this;
    }

    protected function buildDoctorJoinUrl(?string $frontendBase = null): ?string
    {
        $ids = $this->normalizedParticipantIds();

        return CallSessionUrlBuilder::doctorJoinUrl(
            $this->channel_name,
            $this->resolveIdentifier(),
            $ids['doctor'],
            $ids['patient'],
            $frontendBase
        );
    }

    protected function buildPatientPaymentUrl(?string $frontendBase = null): ?string
    {
        $ids = $this->normalizedParticipantIds();

        return CallSessionUrlBuilder::patientPaymentUrl(
            $this->channel_name,
            $this->resolveIdentifier(),
            $ids['doctor'],
            $ids['patient'],
            $frontendBase
        );
    }

    protected function normalizedParticipantIds(): array
    {
        $doctorId = $this->doctor_id !== null ? (int) $this->doctor_id : null;
        if ($doctorId === 0 && $this->doctor_id !== 0) {
            $doctorId = null;
        }

        $patientId = $this->patient_id !== null ? (int) $this->patient_id : null;
        if ($patientId === 0 && $this->patient_id !== 0) {
            $patientId = null;
        }

        return ['doctor' => $doctorId, 'patient' => $patientId];
    }

    public function resolvedDoctorJoinUrl(?string $frontendBase = null): ?string
    {
        if (self::supportsColumn('doctor_join_url')) {
            $stored = $this->getAttribute('doctor_join_url');
            if (!empty($stored)) {
                return $stored;
            }
        }

        return $this->buildDoctorJoinUrl($frontendBase ?? CallSessionUrlBuilder::frontendBase());
    }

    public function resolvedPatientPaymentUrl(?string $frontendBase = null): ?string
    {
        if (self::supportsColumn('patient_payment_url')) {
            $stored = $this->getAttribute('patient_payment_url');
            if (!empty($stored)) {
                return $stored;
            }
        }

        return $this->buildPatientPaymentUrl($frontendBase ?? CallSessionUrlBuilder::frontendBase());
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function qrScanner(): BelongsTo
    {
        return $this->belongsTo(LegacyQrRedirect::class, 'qr_scanner_id');
    }
}
