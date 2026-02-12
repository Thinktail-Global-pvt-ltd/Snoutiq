<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Doctor;
use App\Models\DoctorFcmToken;
use App\Models\CallSession;
use App\Models\User;
use App\Models\Pet;
use App\Models\Prescription;
use Illuminate\Support\Str;
use App\Services\WhatsAppService;
use App\Services\Push\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class PaymentController extends Controller
{
    // Prefer env/config keys; fallback to test keys in dev
    private string $key;
    private string $secret;
    private array $doctorClinicCache = [];
    private ?WhatsAppService $whatsApp = null;
    private FcmService $fcm;

    public function __construct(WhatsAppService $whatsApp, FcmService $fcm)
    {
        $this->key    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_test_1nhE9190sR3rkP';
        $this->secret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'L6CPZlUwrKQpdC9N3TRX8gIh';
        $this->whatsApp = $whatsApp;
        $this->fcm = $fcm;

    }

    public function testView()
    {
        return view('rzp-test'); // resources/views/rzp-test.blade.php
    }

    // POST /api/create-order  { "amount": 999 }
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'nullable|integer|min:1',
            'clinic_id' => 'nullable|integer',
            'service_id' => 'nullable|string',
            'order_type' => 'nullable|string',
            'vet_slug' => 'nullable|string',
            'call_session_id' => 'nullable|string',
            'pet_id' => 'nullable|integer',
        ]);

        $amountInInr = (int) ($request->input('amount', 500));
        $notes = $this->mergeClientNotes($request, [
            'via' => 'snoutiq',
        ]);
        $context = $this->resolveTransactionContext($request, $notes);
        $callSession = null;

        if (($notes['order_type'] ?? null) === 'video_consult') {
            $callSession = $this->createCallSessionIfMissing($context);
            if ($callSession) {
                $notes['call_session_id'] = $callSession->resolveIdentifier();
                $notes['channel_name'] = $callSession->channel_name;
                $context['call_identifier'] = $callSession->resolveIdentifier();
            }
        }

        try {
            $api = new Api($this->key, $this->secret);

            $order = $api->order->create([
                'receipt'  => 'rcpt_' . bin2hex(random_bytes(6)),
                'amount'   => $amountInInr * 100, // paisa
                'currency' => 'INR',
                'notes'    => $notes,
            ]);
            $orderArr = $order->toArray();

            $this->recordPendingTransaction(
                request: $request,
                order: $orderArr,
                notes: $notes,
                context: $context
            );

            // Fire WhatsApp notification only for video consult orders (best-effort)
            $whatsAppMeta = null;
            $vetWhatsAppMeta = null;
            $prescriptionDocMeta = null;
            if (($notes['order_type'] ?? null) === 'video_consult') {
                $whatsAppMeta = $this->notifyVideoConsultBooked(
                    context: $context,
                    notes: $notes,
                    amountInInr: $amountInInr
                );
                $vetWhatsAppMeta = $this->notifyVetVideoConsultBooked(
                    context: $context,
                    notes: $notes,
                    amountInInr: $amountInInr
                );
                $prescriptionDocMeta = $this->sendDoctorPrescriptionDocument($context);
            } elseif (($notes['order_type'] ?? null) === 'excell_export_campaign') {
                $whatsAppMeta = $this->notifyExcelExportCampaignBooked(
                    context: $context,
                    notes: $notes,
                    amountInInr: $amountInInr
                );
                $vetWhatsAppMeta = $this->notifyVetExcelExportCampaignAssigned(
                    context: $context,
                    notes: $notes,
                    amountInInr: $amountInInr
                );
            }

            return response()->json([
                'success'  => true,
                'key'      => $this->key,
                'order'    => $orderArr,
                'order_id' => $orderArr['id'],
                'whatsapp' => $whatsAppMeta,
                'vet_whatsapp' => $vetWhatsAppMeta,
                'prescription_doc' => $prescriptionDocMeta,
                'call_session' => $callSession ? [
                    'id' => $callSession->id,
                    'call_identifier' => $callSession->resolveIdentifier(),
                    'channel_name' => $callSession->channel_name,
                    'doctor_id' => $callSession->doctor_id,
                    'patient_id' => $callSession->patient_id,
                    'status' => $callSession->status,
                ] : null,
            ]);
        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: ' . $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /api/rzp/verify
    // { razorpay_order_id, razorpay_payment_id, razorpay_signature }
    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $api = new Api($this->key, $this->secret);

            // Signature verify (local HMAC)
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);

            // Try to fetch payment details (optional)
            $paymentArr = null;
            $status   = 'verified';
            $amount   = null;
            $currency = 'INR';
            $method   = null;
            $email    = null;
            $contact  = null;
            $notes    = [];

            try {
                $p = $api->payment->fetch($data['razorpay_payment_id']);
                $paymentArr = $p->toArray();
                $status   = $paymentArr['status']   ?? $status;
                $amount   = $paymentArr['amount']   ?? null;
                $currency = $paymentArr['currency'] ?? 'INR';
                $method   = $paymentArr['method']   ?? null;
                $email    = $paymentArr['email']    ?? null;
                $contact  = $paymentArr['contact']  ?? null;
                $fetchedNotes = $paymentArr['notes'] ?? [];
                if (is_array($fetchedNotes)) { $notes = $fetchedNotes; }
            } catch (\Throwable $e) {
                // ignore network failure
            }

            // Merge client-provided tags to ensure clinic linkage even if fetch fails
            $notes = $this->mergeClientNotes($request, $notes);
            $context = $this->resolveTransactionContext($request, $notes);

            // Upsert into DB (idempotent on payment_id)
            $record = Payment::updateOrCreate(
                ['razorpay_payment_id' => $data['razorpay_payment_id']],
                [
                    'razorpay_order_id'  => $data['razorpay_order_id'],
                    'razorpay_signature' => $data['razorpay_signature'],
                    'amount'             => $amount,
                    'currency'           => $currency,
                    'status'             => $status,
                    'method'             => $method,
                    'email'              => $email,
                    'contact'            => $contact,
                    'notes'              => $notes ?: null,
                    'raw_response'       => $paymentArr,
                ]
            );

            $this->recordTransaction(
                request: $request,
                payment: $record,
                amount: $amount,
                status: $status,
                method: $method,
                notes: $notes,
                currency: $currency,
                email: $email,
                contact: $contact,
                context: $context
            );

            // Send WhatsApp after successful payment verification
            $amountInInr = $amount !== null ? (int) round(((int) $amount) / 100) : 0;
            $whatsAppMeta = null;
            $vetWhatsAppMeta = null;
            $prescriptionDocMeta = null;
            $vetPushMeta = null;
            try {
                // Derive order type from notes or stored payment/transaction data
                $orderType = $notes['order_type']
                    ?? ($record->notes['order_type'] ?? null)
                    ?? ($record->raw_response['notes']['order_type'] ?? null)
                    ?? ($record->raw_response['notes']['orderType'] ?? null)
                    ?? ($record->raw_response['notes']['type'] ?? null);

                if ($orderType === 'video_consult') {
                    $whatsAppMeta = $this->notifyVideoConsultBooked(
                        context: $context,
                        notes: $notes,
                        amountInInr: $amountInInr
                    );
                    $vetWhatsAppMeta = $this->notifyVetVideoConsultBooked(
                        context: $context,
                        notes: $notes,
                        amountInInr: $amountInInr
                    );
                    // Best-effort: send latest prescription PDF to the doctor
                    $prescriptionDocMeta = $this->sendDoctorPrescriptionDocument($context);
                } elseif ($orderType === 'excell_export_campaign') {
                    $whatsAppMeta = $this->notifyExcelExportCampaignBooked(
                        context: $context,
                        notes: $notes,
                        amountInInr: $amountInInr
                    );
                    $vetWhatsAppMeta = $this->notifyVetExcelExportCampaignAssigned(
                        context: $context,
                        notes: $notes,
                        amountInInr: $amountInInr
                    );
                }

                $vetPushMeta = $this->notifyDoctorPaymentCaptured(
                    context: $context,
                    notes: $notes,
                    amountInInr: $amountInInr,
                    status: $status,
                    orderType: $orderType
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success'  => true,
                'verified' => true,
                'stored'   => true,
                'payment'  => [
                    'id'       => $record->id,
                    'rzp_pid'  => $record->razorpay_payment_id,
                    'status'   => $record->status,
                    'amount'   => $record->amount,
                    'currency' => $record->currency,
                    'db_id'    => $record->id,
                ],
                'whatsapp' => $whatsAppMeta,
                'vet_whatsapp' => $vetWhatsAppMeta,
                'prescription_doc' => $prescriptionDocMeta,
                'vet_push' => $vetPushMeta,
            ]);

        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: ' . $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    protected function recordPendingTransaction(Request $request, array $order, array $notes, array $context): void
    {
        $orderId = $order['id'] ?? null;
        if (! $orderId) {
            return;
        }

        $clinicId = $context['clinic_id'] ?? null;

        try {
            $clinicId = $this->resolveClinicId($request, $notes, $context);
        } catch (\Throwable $e) {
            report($e);
        }

        $context['clinic_id'] = $clinicId;
        $doctorId = $context['doctor_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        $transactionType = $this->resolveTransactionType($notes);

        try {
            Transaction::updateOrCreate(
                ['reference' => $orderId],
                [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctorId,
                    'user_id' => $userId,
                    'pet_id' => $context['pet_id'] ?? null,
                    'amount_paise' => (int) ($order['amount'] ?? 0),
                    'status' => 'pending',
                    'type' => $transactionType,
                    'payment_method' => null,
                    'reference' => $orderId,
                    'metadata' => [
                        'order_type' => $transactionType,
                        'order_id' => $orderId,
                        'currency' => $order['currency'] ?? 'INR',
                        'notes' => $notes,
                        'receipt' => $order['receipt'] ?? null,
                        'call_id' => $context['call_identifier'] ?? null,
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                        'user_id' => $userId,
                        'pet_id' => $context['pet_id'] ?? null,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function recordTransaction(Request $request, Payment $payment, ?int $amount, ?string $status, ?string $method, array $notes, ?string $currency, ?string $email, ?string $contact, array $context = []): void
    {
        $clinicId = $context['clinic_id'] ?? null;

        try {
            $clinicId = $this->resolveClinicId($request, $notes, $context);
        } catch (\Throwable $e) {
            report($e);
        }

        $context['clinic_id'] = $clinicId;
        $doctorId = $context['doctor_id'] ?? null;
        $userId = $context['user_id'] ?? null;
        $petId = $context['pet_id'] ?? null;
        $callId = $context['call_identifier'] ?? null;
        $transactionType = $this->resolveTransactionType($notes);

        try {
            $reference = $payment->razorpay_payment_id ?? $payment->razorpay_order_id;
            if (! $reference) {
                return;
            }

            $payload = [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'user_id' => $userId,
                'pet_id' => $petId,
                'amount_paise' => (int) ($amount ?? 0),
                'status' => $status ?? 'pending',
                'type' => $transactionType,
                'payment_method' => $method,
                'reference' => $reference,
                'metadata' => [
                    'order_type' => $transactionType,
                    'order_id' => $payment->razorpay_order_id,
                    'payment_id' => $payment->razorpay_payment_id,
                    'currency' => $currency,
                    'email' => $email,
                    'contact' => $contact,
                    'notes' => $notes,
                    'call_id' => $callId,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                    'pet_id' => $petId,
                ],
            ];

            $transaction = null;

            if ($payment->razorpay_payment_id) {
                $transaction = Transaction::where('reference', $payment->razorpay_payment_id)->first();
            }

            if (! $transaction && $payment->razorpay_order_id) {
                $transaction = Transaction::where('reference', $payment->razorpay_order_id)->first();
            }

            if ($transaction) {
                $transaction->fill($payload);
                $transaction->save();
            } else {
                Transaction::create($payload);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function resolveClinicId(Request $request, array $notes, array $context = []): ?int
    {
        $directId = $context['clinic_id']
            ?? $request->input('clinic_id')
            ?? $request->input('clinicId')
            ?? ($notes['clinic_id'] ?? null);

        if ($directId !== null && $directId !== '') {
            return (int) $directId;
        }

        $doctorId = $context['doctor_id'] ?? null;
        if (! $doctorId && $request->filled('doctor_id')) {
            $doctorId = (int) $request->input('doctor_id');
        } elseif (! $doctorId && $request->filled('doctorId')) {
            $doctorId = (int) $request->input('doctorId');
        } elseif (! $doctorId && isset($notes['doctor_id'])) {
            $doctorId = (int) $notes['doctor_id'];
        }

        if ($doctorId) {
            $clinicFromDoctor = $this->lookupDoctorClinicId($doctorId);
            if ($clinicFromDoctor) {
                return $clinicFromDoctor;
            }
        }

        // Fallbacks only if clinics table exists
        if (Schema::hasTable('clinics')) {
            try {
                $clinic = DB::table('clinics')->select('id')->first();
                if ($clinic) {
                    return (int) $clinic->id;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    protected function mergeClientNotes(Request $request, array $notes = []): array
    {
        $mapping = [
            'vet_slug' => ['vet_slug'],
            'order_type' => ['order_type', 'orderType', 'type', 'payment_type'],
            'service_id' => ['service_id'],
            'call_session_id' => ['call_session_id', 'callSessionId', 'call_id', 'callId'],
            'clinic_id' => ['clinic_id', 'clinicId'],
            'doctor_id' => ['doctor_id', 'doctorId'],
            'user_id' => ['user_id', 'userId', 'patient_id', 'patientId'],
            'pet_id' => ['pet_id', 'petId'],
            'vet_template' => ['vet_template', 'vetTemplate', 'vet_template_name'],
            'vet_template_language' => ['vet_template_language', 'vetTemplateLanguage'],
        ];

        foreach ($mapping as $noteKey => $keys) {
            foreach ($keys as $key) {
                if ($request->filled($key)) {
                    $notes[$noteKey] = (string) $request->input($key);
                    break;
                }
            }
        }

        return $notes;
    }

    /**
     * Send WhatsApp notification to pet parent when a video consultation is booked.
     * Best-effort: silently ignores failures.
     */
    protected function notifyVideoConsultBooked(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp?->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            if (! $user || empty($user->phone)) {
                return ['sent' => false, 'reason' => 'user_or_phone_missing'];
            }

            $doctorName = null;
            if ($context['doctor_id']) {
                $doctorName = Doctor::where('id', $context['doctor_id'])->value('doctor_name');
            }

            $clinicName = null;
            $clinicId = $context['clinic_id'] ?? null;
            if (! $clinicId) {
                $clinicId = $this->resolveClinicId(request(), $notes, $context);
            }
            if ($clinicId) {
                $clinicName = DB::table('vet_registerations_temp')->where('id', $clinicId)->value('name');
                // fallback to clinics table only if present
                if (!$clinicName && Schema::hasTable('clinics')) {
                    $clinicName = DB::table('clinics')->where('id', $clinicId)->value('name');
                }
            }

            $petName = null;
            if ($context['pet_id']) {
                $petName = Pet::where('id', $context['pet_id'])->value('name');
            }

            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));

            // Use approved template: pp_video_consult_booked (language: en)
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $user->name ?: 'Pet Parent'],                  // {{1}} PetParentName
                        ['type' => 'text', 'text' => $doctorName ?: 'Doctor'],                       // {{2}} VetName
                        ['type' => 'text', 'text' => $clinicName ?: 'Clinic'],                       // {{3}} ClinicName
                        ['type' => 'text', 'text' => (string) $amountInInr],                         // {{4}} Amount (numbers only)
                        ['type' => 'text', 'text' => (string) $responseMinutes],                     // {{5}} ResponseTime minutes
                        ['type' => 'text', 'text' => $petName ?: 'your pet'],                        // {{6}} PetName
                    ],
                ],
            ];

            $this->whatsApp->sendTemplate(
                $this->normalizePhone($user->phone),
                'pp_video_consult_booked',
                $components,
                'en'
            );
            return [
                'sent' => true,
                'to' => $user->phone,
                'template' => 'pp_video_consult_booked',
                'language' => 'en',
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp when an Excel export campaign order is created.
     */
    protected function notifyExcelExportCampaignBooked(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp?->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            if (! $user || empty($user->phone)) {
                return ['sent' => false, 'reason' => 'user_or_phone_missing'];
            }

            $doctorName = null;
            if ($context['doctor_id']) {
                $doctorName = Doctor::where('id', $context['doctor_id'])->value('doctor_name');
            }
            $doctorName ??= 'Doctor';

            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;
            $petName = $pet?->name ?: 'your pet';
            $petType = $pet?->pet_type ?? $pet?->type ?? 'Pet';

            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));

            // Template: pp_booking_confirmed (language: en)
            // {{1}} Pet parent name
            // {{2}} Pet name
            // {{3}} Pet type
            // {{4}} Vet name
            // {{5}} Response time (minutes)
            // {{6}} Amount paid
            // {{7}} Vet name (duplicate of {{4}})
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $user->name ?: 'Pet Parent'], // {{1}}
                        ['type' => 'text', 'text' => $petName],                    // {{2}}
                        ['type' => 'text', 'text' => $petType],                    // {{3}}
                        ['type' => 'text', 'text' => $doctorName],                 // {{4}}
                        ['type' => 'text', 'text' => (string) $responseMinutes],   // {{5}}
                        ['type' => 'text', 'text' => (string) $amountInInr],       // {{6}}
                        ['type' => 'text', 'text' => $doctorName],                 // {{7}}
                    ],
                ],
            ];

            $this->whatsApp->sendTemplate(
                $this->normalizePhone($user->phone),
                'pp_booking_confirmed',
                $components,
                'en'
            );

            return [
                'sent' => true,
                'to' => $user->phone,
                'template' => 'pp_booking_confirmed',
                'language' => 'en',
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp to the assigned vet for Excel export campaign orders.
     */
    protected function notifyVetExcelExportCampaignAssigned(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp?->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $doctorId = $context['doctor_id'] ?? null;
            if (! $doctorId) {
                return ['sent' => false, 'reason' => 'doctor_missing'];
            }

            $doctor = Doctor::find($doctorId);
            if (! $doctor) {
                return ['sent' => false, 'reason' => 'doctor_missing'];
            }

            // Prefer doctor_mobile; fall back to doctor_phone/phone if those columns exist,
            // then clinic mobile if present.
            $doctorPhone = $doctor->doctor_mobile ?? null;
            if (! $doctorPhone && isset($doctor->doctor_phone)) {
                $doctorPhone = $doctor->doctor_phone;
            }
            if (! $doctorPhone && isset($doctor->phone)) {
                $doctorPhone = $doctor->phone;
            }
            if (! $doctorPhone && $doctor->vet_registeration_id) {
                try {
                    $doctorPhone = DB::table('vet_registerations_temp')
                        ->where('id', $doctor->vet_registeration_id)
                        ->value('mobile');
                } catch (\Throwable $e) {
                    $doctorPhone = null;
                }
            }

            if (empty($doctorPhone)) {
                return ['sent' => false, 'reason' => 'doctor_phone_missing'];
            }

            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;

            $petName = $pet?->name ?? 'Pet';
            $petType = $pet?->pet_type ?? $pet?->type ?? $pet?->breed ?? 'Pet';
            $parentName = $user?->name ?? 'Pet Parent';
            $parentPhone = $user?->phone ?? 'N/A';

            $issue = $notes['issue'] ?? $notes['concern'] ?? $pet?->reported_symptom ?? 'N/A';

            // Always set media to the prescription PDF link (latest)
            $userId = $context['user_id'] ?? null;
            $petId = $context['pet_id'] ?? null;
            $base = rtrim((string) config('app.url'), '/');
            if (! str_ends_with($base, '/backend')) {
                $base .= '/backend';
            }
            $mediaString = $base . '/api/consultation/prescription/pdf?user_id=' . ($userId ?? '0') . '&pet_id=' . ($petId ?? '0');

            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));

            // Template: vet_new_consultation_assigned (language: en)
            // {{1}} Vet name
            // {{2}} Pet name
            // {{3}} Pet type/breed
            // {{4}} Pet parent name
            // {{5}} WhatsApp number
            // {{6}} Issue/concern
            // {{7}} Media attached (string)
            // {{8}} Response time
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $doctor->doctor_name ?: 'Doctor'], // {{1}}
                        ['type' => 'text', 'text' => $petName],                         // {{2}}
                        ['type' => 'text', 'text' => $petType],                         // {{3}}
                        ['type' => 'text', 'text' => $parentName],                      // {{4}}
                        ['type' => 'text', 'text' => $parentPhone],                     // {{5}}
                        ['type' => 'text', 'text' => $issue],                           // {{6}}
                        ['type' => 'text', 'text' => $mediaString],                     // {{7}}
                        ['type' => 'text', 'text' => (string) $responseMinutes],        // {{8}}
                    ],
                ],
            ];

            $this->whatsApp->sendTemplate(
                $this->normalizePhone($doctorPhone),
                'vet_new_consultation_assigned',
                $components,
                'en'
            );

            return [
                'sent' => true,
                'to' => $this->normalizePhone($doctorPhone),
                'template' => 'vet_new_consultation_assigned',
                'language' => 'en',
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp to the vet for video consult bookings.
     */
    protected function notifyVetVideoConsultBooked(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp?->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $doctorId = $context['doctor_id'] ?? null;
            if (! $doctorId) {
                return ['sent' => false, 'reason' => 'doctor_missing'];
            }

            $doctor = Doctor::find($doctorId);
            if (! $doctor) {
                return ['sent' => false, 'reason' => 'doctor_missing'];
            }

            $doctorPhone = $doctor->doctor_mobile ?? null;
            if (! $doctorPhone && isset($doctor->doctor_phone)) {
                $doctorPhone = $doctor->doctor_phone;
            }
            if (! $doctorPhone && isset($doctor->phone)) {
                $doctorPhone = $doctor->phone;
            }
            if (! $doctorPhone && $doctor->vet_registeration_id) {
                try {
                    $doctorPhone = DB::table('vet_registerations_temp')
                        ->where('id', $doctor->vet_registeration_id)
                        ->value('mobile');
                } catch (\Throwable $e) {
                    $doctorPhone = null;
                }
            }

            if (empty($doctorPhone)) {
                return ['sent' => false, 'reason' => 'doctor_phone_missing'];
            }

            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;

            $petName = $pet?->name ?? 'Pet';
            $species = $pet?->pet_type ?? $pet?->type ?? 'Pet';

            $ageText = null;
            if ($pet?->pet_age !== null) {
                $ageText = $pet->pet_age . ' yrs';
            } elseif ($pet?->pet_age_months !== null) {
                $ageText = $pet->pet_age_months . ' months';
            } elseif ($pet?->pet_dob) {
                try {
                    $months = \Carbon\Carbon::parse($pet->pet_dob)->diffInMonths(now());
                    $ageText = $months >= 12 ? floor($months/12).' yrs' : $months.' months';
                } catch (\Throwable $e) {
                    $ageText = null;
                }
            }
            $ageText = $ageText ?: '-';

            $parentName = $user?->name ?? 'Pet Parent';
            $issue = $notes['summary'] ?? $notes['reason'] ?? $notes['concern'] ?? 'Video consult';
            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 20));

            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $petName],                 // {{1}} PetName
                        ['type' => 'text', 'text' => $species],                 // {{2}} Species
                        ['type' => 'text', 'text' => $ageText],                 // {{3}} Age
                        ['type' => 'text', 'text' => $parentName],              // {{4}} PetParentName
                        ['type' => 'text', 'text' => $issue],                   // {{5}} ShortIssueSummary
                        ['type' => 'text', 'text' => (string) $amountInInr],    // {{6}} Amount
                        ['type' => 'text', 'text' => (string) $responseMinutes] // {{7}} ResponseTime minutes
                    ],
                ],
            ];

            // Try provided + configured + common template name variants to avoid translation-name mismatch
            $templateCandidates = array_values(array_filter([
                $notes['vet_template'] ?? null,
                config('services.whatsapp.templates.vet_new_video_consult') ?? null,
                'VET_NEW_VIDEO_CONSULT',
                'vet_new_video_consult',
            ]));

            // language fallbacks: provided -> config -> en_US -> en_GB -> en
            $languageCandidates = array_values(array_filter([
                $notes['vet_template_language'] ?? null,
                config('services.whatsapp.templates.vet_new_video_consult_language') ?? null,
                'en_US',
                'en_GB',
                'en',
            ]));

            $lastError = null;
            foreach ($templateCandidates as $tpl) {
                foreach ($languageCandidates as $lang) {
                    try {
                        $this->whatsApp->sendTemplate(
                            $this->normalizePhone($doctorPhone),
                            $tpl,
                            $components,
                            $lang
                        );

                        return [
                            'sent' => true,
                            'to' => $this->normalizePhone($doctorPhone),
                            'template' => $tpl,
                            'language' => $lang,
                        ];
                    } catch (\RuntimeException $ex) {
                        $lastError = $ex->getMessage();
                        // try next language/template combo
                    }
                }
            }

            if ($lastError) {
                throw new \RuntimeException($lastError);
            }

            // Should not reach
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a push notification to the doctor after successful payment.
     */
    protected function notifyDoctorPaymentCaptured(
        array $context,
        array $notes,
        int $amountInInr,
        ?string $status,
        ?string $orderType = null
    ): array {
        if (! $this->isSuccessfulPaymentStatus($status)) {
            return ['sent' => false, 'reason' => 'payment_not_captured'];
        }

        $doctorId = $context['doctor_id'] ?? null;
        if (! $doctorId) {
            return ['sent' => false, 'reason' => 'doctor_missing'];
        }

        $tokens = DoctorFcmToken::query()
            ->where('doctor_id', $doctorId)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return ['sent' => false, 'reason' => 'token_missing'];
        }

        $petName = null;
        if (! empty($context['pet_id'])) {
            $petName = Pet::where('id', $context['pet_id'])->value('name');
        }

        $parentName = null;
        if (! empty($context['user_id'])) {
            $parentName = User::where('id', $context['user_id'])->value('name');
        }

        $title = 'Payment received';
        $bodyParts = ['Consultation payment confirmed'];
        if ($petName) {
            $bodyParts[] = "for {$petName}";
        }
        if ($parentName) {
            $bodyParts[] = "by {$parentName}";
        }
        $body = implode(' ', $bodyParts);

        $data = [
            'type' => 'payment_received',
            'order_type' => (string) ($orderType ?? $notes['order_type'] ?? ''),
            'doctor_id' => (string) $doctorId,
            'user_id' => (string) ($context['user_id'] ?? ''),
            'pet_id' => (string) ($context['pet_id'] ?? ''),
            'amount_inr' => (string) $amountInInr,
            'call_id' => (string) ($context['call_identifier'] ?? ''),
            'deepLink' => '/vet-dashboard',
        ];

        try {
            $this->fcm->sendMulticast($tokens, $title, $body, $data);
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }

        return [
            'sent' => true,
            'token_count' => count($tokens),
        ];
    }

    protected function isSuccessfulPaymentStatus(?string $status): bool
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['captured', 'authorized', 'paid', 'success', 'verified'], true);
    }

    protected function resolveTransactionContext(Request $request, array $notes = []): array
    {
        $context = [
            'call_identifier' => $this->firstFilled($request, ['call_session_id', 'callSessionId', 'call_id', 'callId'], $notes),
            'clinic_id' => $this->toNullableInt($this->firstFilled($request, ['clinic_id', 'clinicId'], $notes)),
            'doctor_id' => $this->toNullableInt($this->firstFilled($request, ['doctor_id', 'doctorId'], $notes)),
            'user_id' => $this->toNullableInt($this->firstFilled($request, ['user_id', 'userId', 'patient_id', 'patientId'], $notes)),
            'pet_id' => $this->toNullableInt($this->firstFilled($request, ['pet_id', 'petId'], $notes)),
        ];

        if (! $context['user_id'] && $request->user()) {
            $context['user_id'] = (int) $request->user()->getAuthIdentifier();
        }

        $session = $this->findCallSession($context['call_identifier']);

        if ($session) {
            $context['doctor_id'] ??= $session->doctor_id ? (int) $session->doctor_id : null;
            $context['user_id'] ??= $session->patient_id ? (int) $session->patient_id : null;

            if ($session->relationLoaded('doctor') && $session->doctor) {
                $context['clinic_id'] ??= $session->doctor->vet_registeration_id
                    ? (int) $session->doctor->vet_registeration_id
                    : null;
            }
        }

        if (! $context['clinic_id'] && $context['doctor_id']) {
            $context['clinic_id'] = $this->lookupDoctorClinicId($context['doctor_id']);
        }

        return $context;
    }

    protected function firstFilled(Request $request, array $keys, array $notes = [])
    {
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                return $request->input($key);
            }

            if (array_key_exists($key, $notes) && $notes[$key] !== null && $notes[$key] !== '') {
                return $notes[$key];
            }
        }

        return null;
    }

    protected function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function lookupDoctorClinicId(?int $doctorId): ?int
    {
        if (! $doctorId) {
            return null;
        }

        if (array_key_exists($doctorId, $this->doctorClinicCache)) {
            return $this->doctorClinicCache[$doctorId];
        }

        try {
            $doctor = Doctor::find($doctorId);
            $clinicId = $doctor && $doctor->vet_registeration_id
                ? (int) $doctor->vet_registeration_id
                : null;
        } catch (\Throwable $e) {
            report($e);
            $clinicId = null;
        }

        return $this->doctorClinicCache[$doctorId] = $clinicId;
    }

    protected function resolveTransactionType(array $notes = []): string
    {
        $candidate = $notes['order_type'] ?? null;
        if (is_string($candidate)) {
            $candidate = trim($candidate);
        }

        if ($candidate !== null && $candidate !== '') {
            return $candidate;
        }

        if (! empty($notes['service_id'])) {
            return 'service';
        }

        return 'payment';
    }

    /**
     * Generate and send latest prescription PDF to the doctor (best-effort).
     */
    protected function sendDoctorPrescriptionDocument(array $context): ?array
    {
        try {
            $doctorId = $context['doctor_id'] ?? null;
            $userId = $context['user_id'] ?? null;
            $petId = $context['pet_id'] ?? null;

            if (! $doctorId || ! $userId || ! $petId) {
                return null;
            }

            $doctor = Doctor::find($doctorId);
            if (! $doctor) {
                return ['sent' => false, 'reason' => 'doctor_missing'];
            }

            $doctorPhone = $doctor->doctor_mobile ?? null;
            if (! $doctorPhone && isset($doctor->doctor_phone)) {
                $doctorPhone = $doctor->doctor_phone;
            }
            if (! $doctorPhone && isset($doctor->phone)) {
                $doctorPhone = $doctor->phone;
            }
            if (! $doctorPhone && $doctor->vet_registeration_id) {
                $doctorPhone = DB::table('vet_registerations_temp')
                    ->where('id', $doctor->vet_registeration_id)
                    ->value('mobile');
            }
            if (! $doctorPhone) {
                return ['sent' => false, 'reason' => 'doctor_phone_missing'];
            }

            $prescription = \App\Models\Prescription::query()
                ->where('user_id', $userId)
                ->where('pet_id', $petId)
                ->orderByDesc('id')
                ->first();
            if (! $prescription) {
                return ['sent' => false, 'reason' => 'prescription_not_found'];
            }

            $user = User::find($userId);
            $pet = Pet::find($petId);

            // Send link (not attachment) to PDF endpoint (always generated fresh)
            $base = rtrim((string) config('app.url'), '/');
            if (! str_ends_with($base, '/backend')) {
                $base .= '/backend';
            }
            $url = $base . '/api/consultation/prescription/pdf?user_id=' . $userId . '&pet_id=' . $petId;
            $text = "Prescription PDF: " . $url;

            $to = $this->normalizePhone($doctorPhone);
            $result = $this->whatsApp->sendTextWithResult($to, $text);

            return ['sent' => true, 'to' => $to, 'url' => $url, 'meta' => $result];
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    private function buildPrescriptionHtml($prescription, $user, $pet, $doctor): string
    {
        $parentName = $user?->name ?? 'Pet Parent';
        $petName = $pet?->name ?? 'Pet';
        $petType = $pet?->pet_type ?? $pet?->type ?? $pet?->breed ?? '';
        $doctorName = $doctor?->doctor_name ?? 'Doctor';

        $meds = [];
        if (is_array($prescription->medications_json)) {
            foreach ($prescription->medications_json as $idx => $med) {
                $label = $med['name'] ?? ('Medicine '.($idx + 1));
                $dose = $med['dosage'] ?? $med['dose'] ?? null;
                $freq = $med['frequency'] ?? null;
                $note = $med['note'] ?? null;
                $parts = array_filter([$label, $dose, $freq]);
                $meds[] = implode(' - ', $parts) . ($note ? (' ('.$note.')') : '');
            }
        }

        $style = <<<CSS
        body { font-family: DejaVu Sans, sans-serif; color: #111; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .meta { font-size: 12px; margin-bottom: 8px; }
        .card { border: 1px solid #ddd; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .label { font-weight: 600; }
        ul { margin: 6px 0 0 18px; padding: 0; }
        CSS;

        $visit = array_filter([
            $prescription->visit_category,
            $prescription->case_severity,
        ]);

        $vitals = array_filter([
            $prescription->temperature ? ('Temp: '.$prescription->temperature.($prescription->temperature_unit ?: '')) : null,
            $prescription->weight ? ('Weight: '.$prescription->weight.' kg') : null,
            $prescription->heart_rate ? ('Heart: '.$prescription->heart_rate.' bpm') : null,
        ]);

        $follow = array_filter([
            $prescription->follow_up_date ? ('Date: '.$prescription->follow_up_date) : null,
            $prescription->follow_up_type ? ('Type: '.$prescription->follow_up_type) : null,
            $prescription->follow_up_notes ? ('Notes: '.$prescription->follow_up_notes) : null,
        ]);

        $home = $prescription->home_care ?: '';
        $notes = $prescription->visit_notes ?: $prescription->content_html ?: '';

        $medList = $meds ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $meds)).'</li></ul>' : '<p>—</p>';
        $followHtml = $follow ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $follow)).'</li></ul>' : '<p>—</p>';
        $vitalsHtml = $vitals ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $vitals)).'</li></ul>' : '<p>—</p>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>{$style}</style>
</head>
<body>
  <h1>Prescription</h1>
  <div class="meta">
    <div><span class="label">Pet parent:</span> {$this->e($parentName)}</div>
    <div><span class="label">Pet:</span> {$this->e($petName)} {$this->e($petType)}</div>
    <div><span class="label">Doctor:</span> {$this->e($doctorName)}</div>
  </div>

  <div class="card">
    <div class="label">Visit</div>
    <div>{$this->e(implode(' | ', $visit) ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Notes</div>
    <div>{$this->e($notes)}</div>
  </div>

  <div class="card">
    <div class="label">Vitals</div>
    {$vitalsHtml}
  </div>

  <div class="card">
    <div class="label">Diagnosis</div>
    <div>{$this->e($prescription->diagnosis ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Treatment plan</div>
    <div>{$this->e($prescription->treatment_plan ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Medicines</div>
    {$medList}
  </div>

  <div class="card">
    <div class="label">Home care</div>
    <div>{$this->e($home ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Follow-up</div>
    {$followHtml}
  </div>
</body>
</html>
HTML;
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function e(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function normalizePhone(?string $raw): ?string
    {
        if (!$raw) return null;
        $digits = preg_replace('/\\D+/', '', $raw);
        if (!$digits) return null;
        if (strlen($digits) === 10) {
            return '91' . $digits;
        }
        return $digits;
    }

    private function inferMediaString(array $notes, ?Pet $pet): string
    {
        // Priority: explicit notes values
        foreach (['media_attached', 'media', 'files'] as $key) {
            if (isset($notes[$key]) && $notes[$key] !== '') {
                return is_array($notes[$key])
                    ? (count($notes[$key]) ? 'Images' : 'None')
                    : (string) $notes[$key];
            }
        }

        $hasImages = false;
        $hasVideo = false;

        if ($pet) {
            $hasImages = !empty($pet->pet_doc1) || !empty($pet->pet_doc2);
            $hasVideo = !empty($pet->video_calling_upload_file);
        }

        if ($hasImages && $hasVideo) {
            return 'Images + Video';
        }
        if ($hasImages) {
            return 'Images';
        }
        if ($hasVideo) {
            return 'Video';
        }

        return 'None';
    }

    protected function findCallSession($identifier): ?CallSession
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        try {
            $query = CallSession::query()->with('doctor');

            $query->where(function ($inner) use ($identifier) {
                if (CallSession::supportsColumn('call_identifier')) {
                    $inner->where('call_identifier', $identifier);
                }

                if (is_numeric($identifier)) {
                    $inner->orWhere('id', (int) $identifier);
                }

                if (!is_numeric($identifier)) {
                    $inner->orWhere('channel_name', $identifier);
                }
            });

            return $query->latest('id')->first();
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    protected function createCallSessionIfMissing(array $context): ?CallSession
    {
        $patientId = $context['user_id'] ?? null;
        $doctorId = $context['doctor_id'] ?? null;

        if (!$patientId || !$doctorId) {
            return null;
        }

        // Always create a fresh session for each order to avoid reusing prior pending sessions
        $channel = 'channel_' . Str::random(12);
        $session = new CallSession([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'channel_name' => $channel,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $session->useCallIdentifier(Str::random(16));
        $session->refreshComputedLinks();
        $session->save();

        return $session;
    }
}
