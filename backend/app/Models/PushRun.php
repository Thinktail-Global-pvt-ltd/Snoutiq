<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int|null $schedule_id
 * @property string $trigger
 * @property string $title
 * @property string|null $body
 * @property int $targeted_count
 * @property int $success_count
 * @property int $failure_count
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $code_path
 * @property string|null $log_file
 * @property string|null $job_id
 * @property array|null $sample_device_ids
 * @property array|null $sample_errors
 */
class PushRun extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'schedule_id',
        'trigger',
        'title',
        'body',
        'targeted_count',
        'success_count',
        'failure_count',
        'started_at',
        'finished_at',
        'duration_ms',
        'code_path',
        'log_file',
        'job_id',
        'sample_device_ids',
        'sample_errors',
    ];

    protected $casts = [
        'targeted_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'sample_device_ids' => 'array',
        'sample_errors' => 'array',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduledPushNotification::class, 'schedule_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushRunDelivery::class);
    }
}

