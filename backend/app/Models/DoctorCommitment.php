<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id', 'doctor_id', 'committed_at', 'released_at', 'fulfilled', 'cancel_reason', 'raw_snapshot',
    ];

    protected $casts = [
        'raw_snapshot' => 'array',
        'committed_at' => 'datetime',
        'released_at' => 'datetime',
        'fulfilled' => 'boolean',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(VideoSlot::class, 'slot_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}

