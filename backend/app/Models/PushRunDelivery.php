<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $push_run_id
 * @property string|null $device_id
 * @property string|null $platform
 * @property string $status
 * @property string|null $error_code
 * @property string|null $error_message
 */
class PushRunDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'push_run_id',
        'device_id',
        'platform',
        'status',
        'error_code',
        'error_message',
        'fcm_response_snippet',
    ];

    protected $casts = [
        'fcm_response_snippet' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PushRun::class, 'push_run_id');
    }
}

