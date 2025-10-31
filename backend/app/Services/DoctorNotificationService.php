<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DoctorNotificationService
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    public function sendPendingCallAlert(Doctor $doctor, string $message): void
    {
        $phone = $this->formatPhone($doctor->doctor_mobile);
        if (!$phone) {
            throw new RuntimeException('Doctor mobile number unavailable');
        }

        if (!$this->whatsApp->isConfigured()) {
            Log::warning('doctor.notification.skipped', [
                'doctor_id' => $doctor->id,
                'reason' => 'whatsapp_not_configured',
            ]);
            return;
        }

        try {
            $this->whatsApp->sendText($phone, $message);
        } catch (RuntimeException $exception) {
            Log::error('doctor.notification.failed', [
                'doctor_id' => $doctor->id,
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    private function formatPhone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
            return $digits;
        }

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        return $digits;
    }
}
