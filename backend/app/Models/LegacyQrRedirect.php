<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LegacyQrRedirect extends Model
{
    protected $fillable = [
        'code',
        'legacy_url',
        'clinic_id',
        'public_id',
        'target_url',
        'notes',
        'qr_image_path',
        'status',
        'scan_count',
        'last_scanned_at',
        'last_registration_at',
        'last_transaction_at',
        'qr_image_hash',
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime',
        'last_registration_at' => 'datetime',
        'last_transaction_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(VetRegisterationTemp::class, 'clinic_id');
    }

    public function petParents(): HasMany
    {
        return $this->hasMany(User::class, 'qr_scanner_id');
    }

    public function callSessions(): HasMany
    {
        return $this->hasMany(CallSession::class, 'qr_scanner_id');
    }

    public function recordScan(): void
    {
        $now = Carbon::now();
        $attributes = [
            'last_scanned_at' => $now,
        ];

        if ($this->status !== 'active') {
            $attributes['status'] = 'active';
        }

        $this->increment('scan_count', 1, $attributes);
        $this->forceFill(array_merge(
            ['scan_count' => (int) $this->scan_count],
            $attributes
        ));
    }
}
