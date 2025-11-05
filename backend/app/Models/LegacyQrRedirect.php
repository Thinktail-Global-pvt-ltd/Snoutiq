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

        // Use COALESCE to handle legacy NULL values correctly.
        // Also make the status active on first scan and update timestamps.
        try {
            $updated = static::whereKey($this->getKey())
                ->update([
                    'scan_count' => \Illuminate\Support\Facades\DB::raw('COALESCE(scan_count, 0) + 1'),
                    'last_scanned_at' => $now,
                    'status' => $this->status === 'active'
                        ? \Illuminate\Support\Facades\DB::raw("status")
                        : 'active',
                    'updated_at' => $now,
                ]);

            if ($updated === 0) {
                // Fallback to increment API (rare)
                $this->increment('scan_count', 1, [
                    'last_scanned_at' => $now,
                    'status' => 'active',
                ]);
            }

            // Sync in-memory model for consistency in this request
            $this->scan_count = (int) (($this->scan_count ?? 0) + 1);
            $this->last_scanned_at = $now;
            if ($this->status !== 'active') {
                $this->status = 'active';
            }
        } catch (\Throwable $e) {
            \Log::warning('LegacyQrRedirect recordScan failed', [
                'id' => $this->id,
                'code' => $this->code,
                'public_id' => $this->public_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getScanUrlAttribute(): string
    {
        return route('legacy-qr.redirect', ['code' => $this->code]);
    }

    /**
     * Robust fallback: increment scan for a given identifier (public_id or code).
     * If no mapping exists yet, create a minimal one and mark it active.
     */
    public static function recordScanForIdentifier(string $identifier): void
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return;
        }

        $redirect = static::query()
            ->where('public_id', $identifier)
            ->orWhere('code', $identifier)
            ->first();

        if (! $redirect) {
            $redirect = static::create([
                'code' => $identifier,
                'public_id' => $identifier,
                'status' => 'inactive',
                'scan_count' => 0,
            ]);
        }

        $redirect->recordScan();
    }

    public static function findByPublicId(?string $publicId): ?self
    {
        if (! $publicId) {
            return null;
        }

        return static::where('public_id', $publicId)->first();
    }

    public static function scanUrlForPublicId(?string $publicId): string
    {
        if (! $publicId) {
            return url('/backend/legacy-qr');
        }

        $redirect = static::findByPublicId($publicId);

        if ($redirect) {
            return $redirect->scan_url;
        }

        return route('legacy-qr.redirect', ['code' => $publicId]);
    }
}
