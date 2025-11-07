<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledPushNotification extends Model
{
    use HasFactory;

    public const FREQUENCY_ONE_MINUTE = 'one_minute';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_TEN_SECONDS = 'ten_seconds';

    /**
     * @var array<int,string>
     */
    public const FREQUENCIES = [
        self::FREQUENCY_TEN_SECONDS,
        self::FREQUENCY_ONE_MINUTE,
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
    ];

    protected $fillable = [
        'title',
        'body',
        'frequency',
        'data',
        'is_active',
        'next_run_at',
        'last_run_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function frequencyLabels(): array
    {
        return [
            self::FREQUENCY_TEN_SECONDS => 'Every 10 seconds',
            self::FREQUENCY_ONE_MINUTE => 'Every 1 minute',
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
        ];
    }

    /**
     * Compute the next run from a reference time for minute+ cadences.
     */
    public function computeNextRun(\Illuminate\Support\Carbon $ref): \Illuminate\Support\Carbon
    {
        return match ($this->frequency) {
            self::FREQUENCY_ONE_MINUTE => $ref->copy()->addMinute(),
            self::FREQUENCY_DAILY => $ref->copy()->addDay(),
            self::FREQUENCY_WEEKLY => $ref->copy()->addWeek(),
            self::FREQUENCY_MONTHLY => $ref->copy()->addMonth(),
            default => $ref->copy()->addMinute(),
        };
    }

    public function pushRuns(): HasMany
    {
        return $this->hasMany(PushRun::class, 'schedule_id');
    }
}
