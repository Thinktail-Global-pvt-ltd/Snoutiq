<?php

namespace App\Services;

use App\Events\CallRequested;
use App\Events\CallStatusUpdated;
use App\Jobs\RingTimeoutJob;
use App\Models\Call;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CallRoutingService
{
    private const ONLINE_SET = 'doctors:online';
    private const BUSY_KEY = 'doctor:%d:busy';

    public function markDoctorOnline(int $doctorId): void
    {
        $ttl = (int) config('calls.presence_ttl', 70);
        Redis::sadd(self::ONLINE_SET, $doctorId);
        Redis::expire(self::ONLINE_SET, $ttl);
        // Dev-friendly: each heartbeat resets busy=0 so doctors become available again even if a prior call was stuck
        Redis::setex(sprintf(self::BUSY_KEY, $doctorId), $ttl, '0');
    }

    public function markDoctorBusy(int $doctorId, int $seconds): void
    {
        $busyTtl = max($seconds, (int) config('calls.ring_timeout', 30) + 5);
        Redis::setex(sprintf(self::BUSY_KEY, $doctorId), $busyTtl, '1');
    }

    public function markDoctorFree(int $doctorId): void
    {
        $ttl = (int) config('calls.busy_ttl', config('calls.presence_ttl', 70));
        Redis::setex(sprintf(self::BUSY_KEY, $doctorId), $ttl, '0');
    }

    public function isDoctorBusy(int $doctorId): bool
    {
        return Redis::get(sprintf(self::BUSY_KEY, $doctorId)) === '1';
    }

    public function assignDoctor(): ?int
    {
        $doctorIds = Redis::smembers(self::ONLINE_SET) ?: [];
        foreach ($doctorIds as $doctorId) {
            $doctorId = (int) $doctorId;
            $lock = Cache::lock("lock:doctor:{$doctorId}", 5);
            try {
                if (! $lock->get()) {
                    continue;
                }
                if ($this->isDoctorBusy($doctorId)) {
                    continue;
                }

                return $doctorId;
            } catch (LockTimeoutException) {
                continue;
            } finally {
                optional($lock)->release();
            }
        }

        return null;
    }

    public function createCall(int $doctorId, int $patientId, ?string $channel, ?array $rtc = null): Call
    {
        $call = Call::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'status' => Call::STATUS_RINGING,
            'channel' => $channel,
            'rtc' => $rtc,
        ]);

        $this->markDoctorBusy($doctorId, (int) config('calls.busy_ttl', 300));
        event(new CallRequested($call));
        RingTimeoutJob::dispatch($call->id)->delay(now()->addSeconds(config('calls.ring_timeout', 30)));

        return $call;
    }

    public function markAccepted(Call $call): void
    {
        $call->update([
            'status' => Call::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
        event(new CallStatusUpdated($call));
    }

    public function markRejected(Call $call): void
    {
        $call->update([
            'status' => Call::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
        $this->markDoctorFree($call->doctor_id);
        event(new CallStatusUpdated($call));
    }

    public function markEnded(Call $call): void
    {
        $call->update([
            'status' => Call::STATUS_ENDED,
            'ended_at' => now(),
        ]);
        $this->markDoctorFree($call->doctor_id);
        event(new CallStatusUpdated($call));
    }

    public function markCancelled(Call $call): void
    {
        $call->update([
            'status' => Call::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $this->markDoctorFree($call->doctor_id);
        event(new CallStatusUpdated($call));
    }

    public function markMissed(Call $call): void
    {
        $call->update([
            'status' => Call::STATUS_MISSED,
            'missed_at' => now(),
        ]);
        $this->markDoctorFree($call->doctor_id);
    }
}
