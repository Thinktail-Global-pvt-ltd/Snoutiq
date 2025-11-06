<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ScheduledPushNotification extends Model
{
    use HasFactory;

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_TEN_SECONDS = 'ten_seconds';

    /**
     * @var array<int,string>
     */
    public const FREQUENCIES = [
        self::FREQUENCY_TEN_SECONDS,
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

    public static function frequencyLabels(): array
    {
        return [
            self::FREQUENCY_TEN_SECONDS => 'Every 10 seconds',
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
        ];
    }

    public function computeNextRun(Carbon $reference): Carbon
    {
        return match ($this->frequency) {
            self::FREQUENCY_TEN_SECONDS => $reference->copy()->addSeconds(10),
            self::FREQUENCY_DAILY => $reference->copy()->addDay(),
            self::FREQUENCY_WEEKLY => $reference->copy()->addWeek(),
            self::FREQUENCY_MONTHLY => $reference->copy()->addMonth(),
            default => $reference->copy()->addMinute(),
        };
    }
}
