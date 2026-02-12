<?php

namespace App\Services;

use App\Events\CallRequested;
use App\Events\CallStatusUpdated;
use App\Jobs\RingTimeoutJob;
use App\Models\Call;
use App\Models\DoctorFcmToken;
use App\Services\Push\FcmService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class CallRoutingService
{
    private const ONLINE_SET = 'doctors:online';
    private const BUSY_KEY = 'doctor:%d:busy';

    public function __construct(private readonly FcmService $fcm)
    {
    }

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
        $this->notifyDoctorCallRequested($call);
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

    private function notifyDoctorCallRequested(Call $call): void
    {
        try {
            $tokens = DoctorFcmToken::query()
                ->where('doctor_id', $call->doctor_id)
                ->pluck('token')
                ->filter()
                ->values()
                ->all();

            if (empty($tokens)) {
                Log::info('call.push.no_tokens', [
                    'doctor_id' => $call->doctor_id,
                    'call_id' => $call->id,
                ]);
                return;
            }

            $title = 'Snoutiq Incoming Call';
            $body = 'A pet parent is requesting a consultation.';
            $data = [
                'type' => 'call_request',
                'call_id' => (string) $call->id,
                'doctor_id' => (string) $call->doctor_id,
                'patient_id' => (string) $call->patient_id,
                'channel' => (string) ($call->channel ?? ''),
                'deepLink' => '/vet-dashboard',
            ];

            $this->fcm->sendMulticast($tokens, $title, $body, $data);
        } catch (Throwable $e) {
            Log::error('call.push.failed', [
                'doctor_id' => $call->doctor_id,
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
