<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorCommitment;
use App\Models\VideoSlot;
use Illuminate\Database\Query\Exception;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommitmentService
{
    /**
     * Transition: open -> held (5m) -> committed. Idempotent per (slot,doctor).
     */
    public function claimSlot(VideoSlot $slot, int $doctorId): VideoSlot
    {
        return DB::transaction(function () use ($slot, $doctorId) {
            // Reload with lock
            $lock = VideoSlot::query()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            // If already committed to same doctor, return as-is
            if ($lock->status === 'committed' && (int)$lock->committed_doctor_id === $doctorId) {
                return $lock;
            }

            if (!in_array($lock->status, ['open','held'], true)) {
                throw new RuntimeException('Slot not available');
            }

            // If HELD by someone else and not expired, reject
            $holdExpires = $lock->meta['hold_expires_at'] ?? null;
            if ($lock->status === 'held' && $holdExpires && now('UTC')->lt((new \Carbon\Carbon($holdExpires)))) {
                throw new RuntimeException('Slot temporarily held');
            }

            // Mark held for 5 minutes, then commit to doctor
            $expiresAt = now('UTC')->addMinutes(5);
            $lock->status = 'held';
            $meta = $lock->meta ?? [];
            $meta['held_by'] = $doctorId;
            $meta['hold_expires_at'] = $expiresAt->toIso8601String();
            $lock->meta = $meta;
            $lock->save();

            // Upsert commitment row
            DoctorCommitment::query()->updateOrCreate(
                ['slot_id' => $lock->id, 'doctor_id' => $doctorId],
                ['committed_at' => now('UTC'), 'released_at' => null]
            );

            // Transition to committed immediately (non-push model)
            $lock->status = 'committed';
            $lock->committed_doctor_id = $doctorId;
            $lock->checkin_due_at = now('UTC')->addMinutes((int) config('video_coverage.checkin.hard_checkin_minutes', 5));
            $lock->save();

            return $lock;
        });
    }

    public function releaseSlot(VideoSlot $slot, int $doctorId, ?string $reason = null): VideoSlot
    {
        return DB::transaction(function () use ($slot, $doctorId, $reason) {
            $lock = VideoSlot::query()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            if ((int) $lock->committed_doctor_id !== $doctorId) {
                throw new RuntimeException('Not your slot');
            }
            if (!in_array($lock->status, ['committed'], true)) {
                throw new RuntimeException('Slot cannot be released in its current state');
            }

            $lock->status = 'open';
            $lock->committed_doctor_id = null;
            $lock->checkin_due_at = null;
            $lock->checked_in_at = null;
            $lock->in_progress_at = null;
            $lock->finished_at = null;

            $meta = $lock->meta ?? [];
            $meta['released_at'] = now('UTC')->toIso8601String();
            $meta['released_by_doctor'] = $doctorId;
            if ($reason) {
                $meta['release_reason'] = $reason;
            }
            $lock->meta = $meta;
            $lock->save();

            DoctorCommitment::query()
                ->where('slot_id', $lock->id)
                ->where('doctor_id', $doctorId)
                ->update([
                    'released_at' => now('UTC'),
                    'cancel_reason' => $reason,
                ]);

            return $lock->fresh();
        });
    }

    public function confirmCheckin(VideoSlot $slot, int $doctorId): void
    {
        DB::transaction(function () use ($slot, $doctorId) {
            $lock = VideoSlot::query()->whereKey($slot->id)->lockForUpdate()->firstOrFail();
            if ((int) $lock->committed_doctor_id !== $doctorId) {
                throw new RuntimeException('Not your slot');
            }
            if (!in_array($lock->status, ['committed','in_progress'], true)) {
                throw new RuntimeException('Invalid status');
            }
            $lock->checked_in_at = now('UTC');
            $lock->status = 'in_progress';
            $lock->in_progress_at = $lock->in_progress_at ?: now('UTC');
            $lock->save();

            DoctorCommitment::query()
                ->where('slot_id', $lock->id)
                ->where('doctor_id', $doctorId)
                ->update(['fulfilled' => true]);
        });
    }

    /**
     * Promote bench if primary no-show by H:05 IST.
     */
    public function autoPromoteBenchIfPrimaryNoShow(VideoSlot $slot): void
    {
        DB::transaction(function () use ($slot) {
            $lock = VideoSlot::query()->whereKey($slot->id)->lockForUpdate()->firstOrFail();
            if ($lock->role !== 'primary' || $lock->status !== 'committed') {
                return; // nothing
            }
            if ($lock->checked_in_at) {
                return; // already checked in
            }

            // Find bench for same strip & window
            $bench = VideoSlot::query()
                ->forWindow($lock->strip_id, (string)$lock->slot_date, (int)$lock->hour_24, ['bench'])
                ->lockForUpdate()
                ->first();
            if (!$bench) {
                return;
            }

            // Promote if bench is committed or open/held
            if (in_array($bench->status, ['committed','open','held'], true) && $bench->committed_doctor_id) {
                // Cancel primary
                $lock->status = 'cancelled';
                $lock->save();

                // Promote bench to primary role logically by routing preference, but keep bench record
                $bench->status = 'committed';
                $bench->save();
            }
        });
    }
}
