<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class VideoSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'strip_id', 'slot_date', 'slot_day_of_week', 'hour_24', 'role', 'status', 'payout_offer', 'demand_score',
        'committed_doctor_id', 'checkin_due_at', 'checked_in_at', 'in_progress_at', 'finished_at', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'slot_date' => 'date:Y-m-d',
        'slot_day_of_week' => 'string',
        'checkin_due_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
    // Always include IST helpers in API responses
    protected $appends = [
        'ist_date',        // IST calendar date of the window
        'ist_hour',        // 0..23 IST hour of the window
        'ist_window_start' // ISO string of the window start in IST
    ];

    public function strip(): BelongsTo
    {
        return $this->belongsTo(GeoStrip::class, 'strip_id');
    }

    public function scopeOpenForMarketplace(Builder $q, ?string $date = null, ?int $stripId = null, ?string $slotDayOfWeek = null): Builder
    {
        if ($slotDayOfWeek !== null) {
            $q->where('slot_day_of_week', strtolower($slotDayOfWeek));
        } elseif ($date !== null) {
            $q->where('slot_date', $date);
        }

        $q->whereIn('status', ['open', 'held']) // held may expire soon
          ->whereIn('role', ['primary','bench']);
        if ($stripId) {
            $q->where('strip_id', $stripId);
        }
        return $q;
    }

    public function scopeForNightDay(Builder $q, string $slotDayOfWeek): Builder
    {
        return $q->where('slot_day_of_week', strtolower($slotDayOfWeek));
    }

    public function scopeForWindow(Builder $q, int $stripId, string $slotDate, int $hour, array $roles = ['primary','bench']): Builder
    {
        return $q->where('strip_id', $stripId)
                 ->where('slot_date', $slotDate)
                 ->where('hour_24', $hour)
                 ->whereIn('role', $roles);
    }

    private function windowStartUtc(): ?CarbonImmutable
    {
        $dateStr = $this->attributes['slot_date'] ?? null;
        $hour = $this->attributes['hour_24'] ?? $this->hour_24 ?? null;
        if (!$dateStr || $hour === null) {
            return null;
        }
        $h = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $h . ':00:00', 'UTC');
    }

    public function getIstDateAttribute(): ?string
    {
        $start = $this->windowStartUtc();
        return $start ? $start->setTimezone('Asia/Kolkata')->toDateString() : null;
    }

    public function getIstHourAttribute(): ?int
    {
        $start = $this->windowStartUtc();
        return $start ? (int) $start->setTimezone('Asia/Kolkata')->format('G') : null;
    }

    public function getIstWindowStartAttribute(): ?string
    {
        $start = $this->windowStartUtc();
        return $start ? $start->setTimezone('Asia/Kolkata')->toIso8601String() : null;
    }}





