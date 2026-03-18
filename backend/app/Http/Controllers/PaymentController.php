<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Doctor;
use App\Models\DeviceToken;
use App\Models\CallSession;
use App\Models\User;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\VideoApointment;
use Illuminate\Support\Str;
use App\Services\WhatsAppService;
use App\Services\Push\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class PaymentController extends Controller
{
    private const FREE_VIDEO_CONSULT_COUPON_CODE = 'FIRST_VIDEO_FREE';
    private const USER_FREE_VIDEO_CONSULT_FLAG_COLUMN = 'has_used_free_video_consult_coupon';

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
            'doctor_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'service_id' => 'nullable|string',
            'order_type' => 'nullable|string',
            'vet_slug' => 'nullable|string',
            'call_session_id' => 'nullable|string',
            'call_session' => 'nullable|string',
            'channel_name' => 'nullable|string',
            'pet_id' => 'nullable|integer',
            'coupon_code' => 'nullable|string|max:100',
            'couponCode' => 'nullable|string|max:100',
            'coupon' => 'nullable|string|max:100',
        ]);

        $amountInInr = (int) ($request->input('amount', 500));
        $notes = $this->mergeClientNotes($request, [
            'via' => 'snoutiq',
        ]);
        $transactionType = $this->resolveTransactionType($notes);
        $notes['order_type'] = $transactionType;
        $context = $this->resolveTransactionContext($request, $notes);
        $this->persistUserGstDetails($context['user_id'] ?? null, $notes);
        $callSession = null;
        $requestedCouponCode = $this->resolveCouponCode($notes);

        if ($requestedCouponCode !== null && $requestedCouponCode !== self::FREE_VIDEO_CONSULT_COUPON_CODE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid coupon code',
                'details' => 'Only FIRST_VIDEO_FREE coupon is supported on this API.',
            ], 422);
        }

        if (
            $this->isVideoConsultTransactionType($transactionType)
            || $transactionType === 'excell_export_campaign'
        ) {
            $callSession = $this->createCallSessionIfMissing($context);
            if ($callSession) {
                $notes['call_session_id'] = $callSession->resolveIdentifier();
                $notes['channel_name'] = $callSession->channel_name;
                $context['call_identifier'] = $callSession->resolveIdentifier();
                $context['channel_name'] = $callSession->channel_name;
            }
        }

        $freeCouponResponse = $this->applyFirstVideoConsultCouponIfEligible(
            request: $request,
            notes: $notes,
            context: $context,
            callSession: $callSession,
            requestedAmountInInr: $amountInInr
        );
        if ($freeCouponResponse !== null) {
            $statusCode = (int) ($freeCouponResponse['status_code'] ?? 200);
            unset($freeCouponResponse['status_code']);

            return response()->json($freeCouponResponse, $statusCode);
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
            $videoApointment = $this->recordVideoApointmentOrder(
                request: $request,
                order: $orderArr,
                context: $context,
                callSession: $callSession,
                notes: $notes
            );

            $doctorOrderPushMeta = $this->notifyDoctorOrderCreated(
                doctorId: $context['doctor_id'] ?? null,
                notes: $notes,
                amountInInr: $amountInInr
            );

            // WhatsApp for video consult is intentionally deferred to payment verify success.
            $whatsAppMeta = null;
            $vetWhatsAppMeta = null;
            $prescriptionDocMeta = null;

            return response()->json([
                'success'  => true,
                'key'      => $this->key,
                'order'    => $orderArr,
                'order_id' => $orderArr['id'],
                'doctor_push' => $doctorOrderPushMeta,
                'whatsapp' => $whatsAppMeta,
                'vet_whatsapp' => $vetWhatsAppMeta,
                'prescription_doc' => $prescriptionDocMeta,
                'video_appointment' => $videoApointment ? [
                    'id' => $videoApointment->id,
                ] : null,
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

    protected function applyFirstVideoConsultCouponIfEligible(
        Request $request,
        array $notes,
        array $context,
        ?CallSession $callSession,
        int $requestedAmountInInr
    ): ?array {
        $transactionType = $this->resolveTransactionType($notes);
        if (! $this->isVideoConsultTransactionType($transactionType)) {
            return null;
        }
        $requestedCouponCode = $this->resolveCouponCode($notes);
        if ($requestedCouponCode !== self::FREE_VIDEO_CONSULT_COUPON_CODE) {
            return null;
        }

        $userId = $context['user_id'] ?? null;
        if (! $userId) {
            return null;
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', self::USER_FREE_VIDEO_CONSULT_FLAG_COLUMN)) {
            return null;
        }

        $result = DB::transaction(function () use (
            $request,
            $notes,
            $context,
            $callSession,
            $requestedAmountInInr,
            $transactionType,
            $userId
        ) {
            $userRow = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first(['id', self::USER_FREE_VIDEO_CONSULT_FLAG_COLUMN]);

            if (! $userRow) {
                return ['applied' => false];
            }

            if ((int) ($userRow->{self::USER_FREE_VIDEO_CONSULT_FLAG_COLUMN} ?? 0) === 1) {
                return [
                    'applied' => false,
                    'rejection_reason' => 'coupon_already_used',
                ];
            }

            $couponReference = 'coupon_free_' . Str::lower(Str::random(20));
            $originalAmountPaise = max(0, $requestedAmountInInr * 100);

            $context['clinic_id'] = $this->resolveClinicId($request, $notes, $context);
            $doctorId = $context['doctor_id'] ?? null;
            $clinicId = $context['clinic_id'] ?? null;
            $petId = $context['pet_id'] ?? null;
            $channelName = $context['channel_name'] ?? ($notes['channel_name'] ?? null);
            $hasChannelNameColumn = Schema::hasColumn('transactions', 'channel_name');
            $payoutColumns = $this->transactionPayoutColumnMap();

            $couponNotes = array_merge($notes, [
                'coupon_code' => self::FREE_VIDEO_CONSULT_COUPON_CODE,
                'coupon_applied' => '1',
                'coupon_discount_paise' => (string) $originalAmountPaise,
                'coupon_original_amount_paise' => (string) $originalAmountPaise,
                'coupon_final_amount_paise' => '0',
            ]);

            $transactionPayload = [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'user_id' => $userId,
                'pet_id' => $petId,
                'amount_paise' => 0,
                'status' => 'captured',
                'type' => $transactionType,
                'payment_method' => 'coupon_free',
                'reference' => $couponReference,
                'metadata' => [
                    'order_type' => $transactionType,
                    'order_id' => $couponReference,
                    'currency' => 'INR',
                    'notes' => $couponNotes,
                    'call_id' => $context['call_identifier'] ?? null,
                    'channel_name' => $channelName,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                    'pet_id' => $petId,
                    'is_coupon_payment' => true,
                    'coupon' => [
                        'applied' => true,
                        'code' => self::FREE_VIDEO_CONSULT_COUPON_CODE,
                        'scope' => 'first_video_consultation',
                        'original_amount_paise' => $originalAmountPaise,
                        'discount_paise' => $originalAmountPaise,
                        'final_amount_paise' => 0,
                    ],
                ],
            ];

            if ($hasChannelNameColumn) {
                $transactionPayload['channel_name'] = $channelName;
            }

            foreach ([
                'actual_amount_paid_by_consumer_paise',
                'payment_to_snoutiq_paise',
                'payment_to_doctor_paise',
            ] as $column) {
                if ($payoutColumns[$column] ?? false) {
                    $transactionPayload[$column] = 0;
                }
            }

            $transaction = Transaction::create($transactionPayload);

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    self::USER_FREE_VIDEO_CONSULT_FLAG_COLUMN => 1,
                    'updated_at' => now(),
                ]);

            if ($callSession) {
                try {
                    $callSession->payment_status = 'paid';
                    $callSession->save();
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $couponOrder = [
                'id' => $couponReference,
                'amount' => 0,
                'currency' => 'INR',
                'notes' => $couponNotes,
            ];

            $videoApointment = $this->recordVideoApointmentOrder(
                request: $request,
                order: $couponOrder,
                context: $context,
                callSession: $callSession,
                notes: $couponNotes
            );

            return [
                'applied' => true,
                'coupon_order' => $couponOrder,
                'coupon_notes' => $couponNotes,
                'transaction' => $transaction,
                'video_appointment' => $videoApointment,
                'context' => $context,
                'call_session' => $callSession,
            ];
        });

        if (! ($result['applied'] ?? false)) {
            if (($result['rejection_reason'] ?? null) === 'coupon_already_used') {
                return [
                    'success' => false,
                    'coupon_applied' => false,
                    'error' => 'Coupon expired/used',
                    'details' => 'FIRST_VIDEO_FREE can be used only once per user.',
                    'status_code' => 422,
                ];
            }

            return null;
        }

        /** @var \App\Models\Transaction $transaction */
        $transaction = $result['transaction'];
        $couponOrder = $result['coupon_order'];
        $couponNotes = $result['coupon_notes'];
        $couponContext = $result['context'];
        $videoApointment = $result['video_appointment'] ?? null;
        $couponCallSession = $result['call_session'] ?? null;

        $doctorOrderPushMeta = $this->notifyDoctorOrderCreated(
            doctorId: $couponContext['doctor_id'] ?? null,
            notes: $couponNotes,
            amountInInr: 0
        );

        return [
            'success' => true,
            'payment_required' => false,
            'coupon_applied' => true,
            'coupon' => [
                'code' => self::FREE_VIDEO_CONSULT_COUPON_CODE,
                'original_amount_inr' => $requestedAmountInInr,
                'discount_inr' => $requestedAmountInInr,
                'final_amount_inr' => 0,
            ],
            'key' => null,
            'order' => $couponOrder,
            'order_id' => $couponOrder['id'],
            'doctor_push' => $doctorOrderPushMeta,
            'whatsapp' => null,
            'vet_whatsapp' => null,
            'prescription_doc' => null,
            'transaction' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'status' => $transaction->status,
                'type' => $transaction->type,
                'payment_method' => $transaction->payment_method,
            ],
            'video_appointment' => $videoApointment ? [
                'id' => $videoApointment->id,
            ] : null,
            'call_session' => $couponCallSession ? [
                'id' => $couponCallSession->id,
                'call_identifier' => $couponCallSession->resolveIdentifier(),
                'channel_name' => $couponCallSession->channel_name,
                'doctor_id' => $couponCallSession->doctor_id,
                'patient_id' => $couponCallSession->patient_id,
                'status' => $couponCallSession->status,
                'payment_status' => $couponCallSession->payment_status,
            ] : null,
        ];
    }

    protected function notifyDoctorOrderCreated(?int $doctorId, array $notes, int $amountInInr): array
    {
        if (! $doctorId) {
            return ['sent' => false, 'reason' => 'doctor_missing', 'doctor_id' => null];
        }

        $latestToken = DeviceToken::query()
            ->where('user_id', $doctorId)
            ->where('meta->owner_model', Doctor::class)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderByRaw('COALESCE(last_seen_at, updated_at, created_at) DESC')
            ->value('token');

        if (! $latestToken) {
            $latestToken = DeviceToken::query()
                ->where('user_id', $doctorId)
                ->whereNotNull('token')
                ->where('token', '!=', '')
                ->orderByRaw('COALESCE(last_seen_at, updated_at, created_at) DESC')
                ->value('token');
        }

        if (! $latestToken) {
            return ['sent' => false, 'reason' => 'token_missing', 'doctor_id' => $doctorId];
        }

        $title = 'New order created';
        $body = 'A new consultation order is created and awaiting payment confirmation.';

        $data = [
            'type' => 'payment_order_created',
            'order_type' => (string) ($notes['order_type'] ?? ''),
            'doctor_id' => (string) $doctorId,
            'amount_inr' => (string) $amountInInr,
            'deepLink' => '/vet-dashboard',
        ];

        try {
            $baseUrl = rtrim((string) config('app.url'), '/');
            $candidates = array_values(array_unique(array_filter([
                $baseUrl !== '' ? $baseUrl . '/api/push/test' : null,
                $baseUrl !== '' ? $baseUrl . '/backend/api/push/test' : null,
            ])));

            $lastFailure = null;
            foreach ($candidates as $endpoint) {
                $apiResponse = Http::acceptJson()
                    ->asJson()
                    ->timeout(8)
                    ->post($endpoint, [
                        'token' => (string) $latestToken,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                    ]);

                $payload = $apiResponse->json();
                $pushMarkedSent = is_array($payload)
                    && (($payload['sent'] ?? false) === true || ($payload['success'] ?? false) === true);

                if ($apiResponse->successful() && $pushMarkedSent) {
                    return [
                        'sent' => true,
                        'doctor_id' => $doctorId,
                        'doctor_name' => Doctor::where('id', $doctorId)->value('doctor_name'),
                        'fcm_token' => (string) $latestToken,
                        'push_endpoint' => $endpoint,
                    ];
                }

                $lastFailure = [
                    'status_code' => $apiResponse->status(),
                    'response' => $payload,
                    'push_endpoint' => $endpoint,
                ];
            }

            return [
                'sent' => false,
                'reason' => 'push_api_failed',
                'doctor_id' => $doctorId,
                'fcm_token' => (string) $latestToken,
                'status_code' => $lastFailure['status_code'] ?? null,
                'response' => $lastFailure['response'] ?? null,
                'push_endpoint' => $lastFailure['push_endpoint'] ?? null,
            ];
        } catch (\Throwable $e) {
            report($e);
            return [
                'sent' => false,
                'reason' => 'exception',
                'doctor_id' => $doctorId,
                'fcm_token' => (string) $latestToken,
                'message' => $e->getMessage(),
            ];
        }
    }

    // POST /api/rzp/verify
    // { razorpay_order_id, razorpay_payment_id, razorpay_signature }
    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_signature'  => 'nullable|string',
        ]);

        $couponBypassPayload = $this->buildCouponVerifyBypassPayload($request, $data['razorpay_order_id']);
        if ($couponBypassPayload !== null) {
            return response()->json($couponBypassPayload);
        }

        if (empty($data['razorpay_payment_id']) || empty($data['razorpay_signature'])) {
            return response()->json([
                'success' => false,
                'error' => 'razorpay_payment_id and razorpay_signature are required for non-coupon verification',
            ], 422);
        }

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

            try {
                $order = $api->order->fetch($data['razorpay_order_id']);
                $orderNotes = $order->toArray()['notes'] ?? [];
                if (is_array($orderNotes) && $orderNotes !== []) {
                    // Keep payment notes preferred when both are present.
                    $notes = array_replace($orderNotes, $notes);
                }
            } catch (\Throwable $e) {
                // ignore network failure
            }

            // Merge client-provided tags to ensure clinic linkage even if fetch fails
            $notes = $this->mergeClientNotes($request, $notes);
            $context = $this->resolveTransactionContext($request, $notes);
            $context = $this->enrichVerificationContext(
                context: $context,
                notes: $notes,
                orderId: $data['razorpay_order_id'],
                paymentId: $data['razorpay_payment_id']
            );
            $notes = $this->mergeContextIntoNotes($notes, $context);
            $this->persistUserGstDetails($context['user_id'] ?? null, $notes);

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
                $orderType = $this->normalizeOrderType(
                    $notes['order_type']
                    ?? ($record->notes['order_type'] ?? null)
                    ?? ($record->raw_response['notes']['order_type'] ?? null)
                    ?? ($record->raw_response['notes']['orderType'] ?? null)
                    ?? ($record->raw_response['notes']['type'] ?? null)
                );

                if ($this->isVideoConsultTransactionType($orderType) && $this->isSuccessfulPaymentStatus($status)) {
                    // On successful payment, send all video-consult WhatsApp messages.
                    $whatsAppMeta = $this->notifyExcelExportCampaignBooked(
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
                } elseif ($orderType === 'excell_export_campaign' && $this->isSuccessfulPaymentStatus($status)) {
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

    protected function buildCouponVerifyBypassPayload(Request $request, string $orderId): ?array
    {
        $orderId = trim($orderId);
        if ($orderId === '' || !Schema::hasTable('transactions')) {
            return null;
        }

        $transaction = Transaction::query()
            ->where('reference', $orderId)
            ->latest('id')
            ->first();

        if (! $transaction) {
            return null;
        }

        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $metadataNotes = data_get($metadata, 'notes', []);
        if (!is_array($metadataNotes)) {
            $metadataNotes = [];
        }

        $couponCode = $this->resolveCouponCode([
            'coupon_code' => data_get($metadata, 'coupon.code')
                ?? data_get($metadata, 'coupon_code')
                ?? ($metadataNotes['coupon_code'] ?? null),
        ]);

        $couponApplied = (bool) data_get($metadata, 'coupon.applied', false)
            || (bool) data_get($metadata, 'is_coupon_payment', false)
            || ($this->toBoolInt($metadataNotes['coupon_applied'] ?? 0) === 1);
        $isCouponPaymentMethod = strtolower(trim((string) ($transaction->payment_method ?? ''))) === 'coupon_free';

        if (! $couponApplied && ! $isCouponPaymentMethod) {
            return null;
        }
        if ($couponCode !== null && $couponCode !== self::FREE_VIDEO_CONSULT_COUPON_CODE) {
            return null;
        }

        $updatedMetadata = $metadata;
        $updatedMetadata['coupon_verify_bypassed_at'] = now()->toIso8601String();
        $updatedMetadata['coupon_verify_bypassed_via'] = '/api/rzp/verify';
        if (!isset($updatedMetadata['is_coupon_payment'])) {
            $updatedMetadata['is_coupon_payment'] = true;
        }
        $transaction->metadata = $updatedMetadata;

        if (!$this->isSuccessfulPaymentStatus((string) ($transaction->status ?? ''))) {
            $transaction->status = 'captured';
        }

        try {
            $transaction->save();
        } catch (\Throwable $e) {
            report($e);
        }

        $context = $this->resolveTransactionContext($request, $metadataNotes);
        $context = $this->enrichVerificationContext($context, $metadataNotes, $orderId, null);
        $callIdentifier = $context['call_identifier'] ?? $context['channel_name'] ?? null;
        if ($callIdentifier) {
            try {
                $callSession = $this->findCallSession($callIdentifier);
                if ($callSession && strtolower((string) ($callSession->payment_status ?? '')) !== 'paid') {
                    $callSession->payment_status = 'paid';
                    $callSession->save();
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'success' => true,
            'verified' => true,
            'stored' => true,
            'verify_bypassed' => true,
            'coupon_applied' => true,
            'coupon' => [
                'code' => self::FREE_VIDEO_CONSULT_COUPON_CODE,
            ],
            'payment' => [
                'id' => null,
                'rzp_pid' => null,
                'status' => $transaction->status,
                'amount' => (int) ($transaction->amount_paise ?? 0),
                'currency' => 'INR',
                'db_id' => $transaction->id,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'payment_method' => $transaction->payment_method,
                'type' => $transaction->type,
                'status' => $transaction->status,
            ],
            'message' => 'Coupon order detected. Razorpay verify bypassed.',
        ];
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
        $channelName = $context['channel_name'] ?? ($notes['channel_name'] ?? null);
        $hasChannelNameColumn = Schema::hasColumn('transactions', 'channel_name');

        $transactionType = $this->resolveTransactionType($notes);
        $payoutBreakup = $this->buildExcelExportPayoutBreakup(
            grossPaise: (int) ($order['amount'] ?? 0),
            transactionType: $transactionType,
            notes: $notes
        );
        $payoutColumns = $this->transactionPayoutColumnMap();

        try {
            $payload = [
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
                    'channel_name' => $channelName,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                    'pet_id' => $context['pet_id'] ?? null,
                ],
            ];

            if ($payoutBreakup) {
                $payload['metadata']['payout_breakup'] = $payoutBreakup;

                foreach ([
                    'actual_amount_paid_by_consumer_paise',
                    'payment_to_snoutiq_paise',
                    'payment_to_doctor_paise',
                ] as $column) {
                    if ($payoutColumns[$column] ?? false) {
                        $payload[$column] = (int) $payoutBreakup[$column];
                    }
                }
            }

            if ($hasChannelNameColumn) {
                $payload['channel_name'] = $channelName;
            }

            Transaction::updateOrCreate(['reference' => $orderId], $payload);
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
        $channelName = $context['channel_name'] ?? ($notes['channel_name'] ?? null);
        $hasChannelNameColumn = Schema::hasColumn('transactions', 'channel_name');
        $transactionType = $this->resolveTransactionType($notes);
        $payoutBreakup = $this->buildExcelExportPayoutBreakup(
            grossPaise: (int) ($amount ?? 0),
            transactionType: $transactionType,
            notes: $notes
        );
        $payoutColumns = $this->transactionPayoutColumnMap();

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
                    'channel_name' => $channelName,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                    'pet_id' => $petId,
                ],
            ];

            if ($payoutBreakup) {
                $payload['metadata']['payout_breakup'] = $payoutBreakup;

                foreach ([
                    'actual_amount_paid_by_consumer_paise',
                    'payment_to_snoutiq_paise',
                    'payment_to_doctor_paise',
                ] as $column) {
                    if ($payoutColumns[$column] ?? false) {
                        $payload[$column] = (int) $payoutBreakup[$column];
                    }
                }
            }

            if ($hasChannelNameColumn) {
                $payload['channel_name'] = $channelName;
            }

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

    protected function transactionPayoutColumnMap(): array
    {
        if (!Schema::hasTable('transactions')) {
            return [
                'actual_amount_paid_by_consumer_paise' => false,
                'payment_to_snoutiq_paise' => false,
                'payment_to_doctor_paise' => false,
            ];
        }

        return [
            'actual_amount_paid_by_consumer_paise' => Schema::hasColumn('transactions', 'actual_amount_paid_by_consumer_paise'),
            'payment_to_snoutiq_paise' => Schema::hasColumn('transactions', 'payment_to_snoutiq_paise'),
            'payment_to_doctor_paise' => Schema::hasColumn('transactions', 'payment_to_doctor_paise'),
        ];
    }

    protected function buildExcelExportPayoutBreakup(int $grossPaise, string $transactionType, array $notes = []): ?array
    {
        if (strtolower(trim($transactionType)) !== 'excell_export_campaign') {
            return null;
        }

        $grossPaise = max(0, (int) $grossPaise);
        // Payout split must happen on base amount (after removing GST from paid gross).
        $amountBeforeGstPaise = (int) round($grossPaise / 1.18);
        $gstPaise = max(0, $grossPaise - $amountBeforeGstPaise);

        $doctorSharePaise = $this->resolveExcelDoctorSharePaise($amountBeforeGstPaise, $grossPaise);
        $snoutiqSharePaise = max(0, $amountBeforeGstPaise - $doctorSharePaise);

        return [
            'actual_amount_paid_by_consumer_paise' => $grossPaise,
            'gst_paise' => $gstPaise,
            'amount_after_gst_paise' => $amountBeforeGstPaise,
            'amount_before_gst_paise' => $amountBeforeGstPaise,
            'gst_deducted_from_amount' => true,
            'payment_to_snoutiq_paise' => $snoutiqSharePaise,
            'payment_to_doctor_paise' => $doctorSharePaise,
        ];
    }

    protected function resolveExcelDoctorSharePaise(int $amountBeforeGstPaise, int $grossPaise): int
    {
        $amountBeforeGstPaise = max(0, $amountBeforeGstPaise);
        $grossPaise = max(0, (int) $grossPaise);

        // Base split slabs:
        // 399 => 350 doctor + 49 snoutiq
        // 549 => 450 doctor + 99 snoutiq
        // Gross equivalents (with 18% GST): 471, 648
        if (
            abs($amountBeforeGstPaise - 39900) <= 400
            || abs($grossPaise - 47100) <= 500
            || abs($grossPaise - 39900) <= 400
            || abs($amountBeforeGstPaise - 50000) <= 400 // legacy base
            || abs($grossPaise - 59000) <= 500 // legacy gross
        ) {
            return min($amountBeforeGstPaise, 35000);
        }

        if (
            abs($amountBeforeGstPaise - 54900) <= 400
            || abs($grossPaise - 64800) <= 500
            || abs($grossPaise - 54900) <= 400
            || abs($amountBeforeGstPaise - 65000) <= 400 // legacy base
            || abs($grossPaise - 76700) <= 500 // legacy gross
        ) {
            return min($amountBeforeGstPaise, 45000);
        }

        // Fallback for unexpected values: keep a conservative doctor share on base amount.
        return min($amountBeforeGstPaise, 45000);
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
            'coupon_code' => ['coupon_code', 'couponCode', 'coupon'],
            'service_id' => ['service_id'],
            'call_session_id' => ['call_session', 'call_session_id', 'callSessionId', 'call_id', 'callId'],
            'channel_name' => ['channel_name', 'channelName'],
            'clinic_id' => ['clinic_id', 'clinicId'],
            'doctor_id' => ['doctor_id', 'doctorId'],
            'user_id' => ['user_id', 'userId', 'patient_id', 'patientId'],
            'pet_id' => ['pet_id', 'petId'],
            'gst_number' => ['gst_number', 'gstNumber'],
            'gst_number_given' => ['gst_number_given', 'gstNumberGiven'],
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
            $channelName = $this->resolveChannelNameForWhatsApp($context, $notes);

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
                'en',
                $channelName
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
            $channelName = $this->resolveChannelNameForWhatsApp($context, $notes);

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
                'en',
                $channelName
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
                return ['sent' => false, 'reason' => 'doctor_missing', 'doctor_id' => null];
            }

            $doctor = Doctor::find($doctorId);
            if (! $doctor) {
                return ['sent' => false, 'reason' => 'doctor_missing', 'doctor_id' => $doctorId];
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
                return ['sent' => false, 'reason' => 'doctor_phone_missing', 'doctor_id' => $doctorId];
            }

            $normalizedDoctorPhone = $this->normalizePhone($doctorPhone);
            if (! $normalizedDoctorPhone) {
                return ['sent' => false, 'reason' => 'doctor_phone_invalid', 'doctor_id' => $doctorId];
            }

            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;

            $petName = $pet?->name ?? 'Pet';
            $species = $pet?->pet_type ?? $pet?->type ?? 'Pet';
            $breed = $pet?->breed ?? $species;

            $ageText = null;
            if ($pet?->pet_age !== null) {
                $ageText = $pet->pet_age . ' yrs';
            } elseif ($pet?->pet_age_months !== null) {
                $ageText = $pet->pet_age_months . ' months';
            } elseif ($pet?->pet_dob) {
                try {
                    $months = \Carbon\Carbon::parse($pet->pet_dob)->diffInMonths(now());
                    $ageText = $months >= 12 ? floor($months / 12) . ' yrs' : $months . ' months';
                } catch (\Throwable $e) {
                    $ageText = null;
                }
            }
            $ageText = $ageText ?: '-';

            $parentName = $user?->name ?? 'Pet Parent';
            $parentPhone = $user?->phone ?? 'N/A';

            $issue = $notes['issue'] ?? $notes['concern'] ?? $pet?->reported_symptom ?? 'N/A';
            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));
            $channelName = $this->resolveChannelNameForWhatsApp($context, $notes);

            // Keep old template as last fallback; primary path uses the currently approved template family.
            $requestedTemplate = strtolower(trim((string) ($notes['vet_template'] ?? '')));
            if (in_array($requestedTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $requestedTemplate = 'appointment_confirmation_v2';
            }

            $configuredTemplate = strtolower(trim((string) (config('services.whatsapp.templates.vet_new_video_consult') ?? '')));
            if (in_array($configuredTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $configuredTemplate = 'appointment_confirmation_v2';
            }

            $templateCandidates = array_values(array_unique(array_filter([
                $requestedTemplate ?: null,
                $configuredTemplate ?: null,
                'appointment_confirmation_v2',
                'vet_new_consultation_assigned',
            ])));

            $configuredLanguage = trim((string) (config('services.whatsapp.templates.vet_new_video_consult_language') ?? 'en'));
            $languageCandidates = array_values(array_unique(array_filter([
                $configuredLanguage !== '' ? $configuredLanguage : null,
                'en',
                'en_US',
            ])));

            $lastError = null;
            foreach ($templateCandidates as $tpl) {
                foreach ($languageCandidates as $lang) {
                    try {
                        if ($tpl === 'vet_new_consultation_assigned') {
                            // Legacy template expects 8 body params.
                            $userId = $context['user_id'] ?? null;
                            $petId = $context['pet_id'] ?? null;
                            $base = rtrim((string) config('app.url'), '/');
                            if (! str_ends_with($base, '/backend')) {
                                $base .= '/backend';
                            }
                            $mediaString = $base . '/api/consultation/prescription/pdf?user_id=' . ($userId ?? '0') . '&pet_id=' . ($petId ?? '0');

                            $components = [[
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $doctor->doctor_name ?: 'Doctor'],
                                    ['type' => 'text', 'text' => $petName],
                                    ['type' => 'text', 'text' => $breed],
                                    ['type' => 'text', 'text' => $parentName],
                                    ['type' => 'text', 'text' => $parentPhone],
                                    ['type' => 'text', 'text' => $issue],
                                    ['type' => 'text', 'text' => $mediaString],
                                    ['type' => 'text', 'text' => (string) $responseMinutes],
                                ],
                            ]];
                        } else {
                            $components = $this->buildVetTemplateComponents(
                                template: $tpl,
                                doctorName: $doctor->doctor_name ?: 'Doctor',
                                parentName: $parentName,
                                petName: $petName,
                                breed: $breed,
                                species: $species,
                                ageText: $ageText,
                                issue: $issue,
                                amountInInr: $amountInInr,
                                responseMinutes: $responseMinutes
                            );
                        }

                        $this->whatsApp->sendTemplate(
                            $normalizedDoctorPhone,
                            $tpl,
                            $components,
                            $lang,
                            $channelName
                        );

                        return [
                            'sent' => true,
                            'to' => $normalizedDoctorPhone,
                            'template' => $tpl,
                            'language' => $lang,
                            'doctor_id' => $doctorId,
                        ];
                    } catch (\RuntimeException $ex) {
                        $lastError = $ex->getMessage();
                    }
                }
            }

            return [
                'sent' => false,
                'reason' => 'template_failed',
                'doctor_id' => $doctorId,
                'to' => $normalizedDoctorPhone,
                'message' => $lastError ?: 'No WhatsApp template candidate succeeded',
                'attempted_templates' => $templateCandidates,
                'attempted_languages' => $languageCandidates,
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
            $breed = $pet?->breed ?? $species;

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
            $channelName = $this->resolveChannelNameForWhatsApp($context, $notes);

            $requestedTemplate = strtolower(trim((string) ($notes['vet_template'] ?? '')));
            if (in_array($requestedTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $requestedTemplate = 'appointment_confirmation_v2';
            }

            $configuredTemplate = strtolower(trim((string) (config('services.whatsapp.templates.vet_new_video_consult') ?? '')));
            if (in_array($configuredTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $configuredTemplate = 'appointment_confirmation_v2';
            }

            // Force to new approved template family only.
            $templateCandidates = array_values(array_unique(array_filter([
                $requestedTemplate ?: null,
                $configuredTemplate ?: null,
                'appointment_confirmation_v2',
            ])));

            // Approved translation is available in "en".
            $languageCandidates = ['en'];

            $lastError = null;
            foreach ($templateCandidates as $tpl) {
                foreach ($languageCandidates as $lang) {
                    try {
                        $components = $this->buildVetTemplateComponents(
                            template: $tpl,
                            doctorName: $doctor->doctor_name ?: 'Doctor',
                            parentName: $parentName,
                            petName: $petName,
                            breed: $breed,
                            species: $species,
                            ageText: $ageText,
                            issue: $issue,
                            amountInInr: $amountInInr,
                            responseMinutes: $responseMinutes
                        );

                        $this->whatsApp->sendTemplate(
                            $this->normalizePhone($doctorPhone),
                            $tpl,
                            $components,
                            $lang,
                            $channelName
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

    protected function buildVetTemplateComponents(
        string $template,
        string $doctorName,
        string $parentName,
        string $petName,
        string $breed,
        string $species,
        string $ageText,
        string $issue,
        int $amountInInr,
        int $responseMinutes
    ): array {
        $templateKey = strtolower(trim($template));

        // appointment_confirmation_v2 expects 4 body params.
        if ($templateKey === 'appointment_confirmation_v2') {
            return [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $breed],
                ],
            ]];
        }

        // vet_sla_reminder expects 5 body params.
        if ($templateKey === 'vet_sla_reminder') {
            return [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $breed],
                    ['type' => 'text', 'text' => (string) $responseMinutes],
                ],
            ]];
        }

        // Legacy vet_new_video_consult style with 7 params.
        return [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $petName],
                ['type' => 'text', 'text' => $species],
                ['type' => 'text', 'text' => $ageText],
                ['type' => 'text', 'text' => $parentName],
                ['type' => 'text', 'text' => $issue],
                ['type' => 'text', 'text' => (string) $amountInInr],
                ['type' => 'text', 'text' => (string) $responseMinutes],
            ],
        ]];
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

        $tokens = DeviceToken::query()
            ->where('user_id', $doctorId)
            ->pluck('token')
            ->filter()
            ->map(fn ($token) => trim(trim((string) $token), "\"'"))
            ->filter(fn (string $token) => $token !== '')
            ->unique()
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
            'call_identifier' => $this->firstFilled($request, ['call_session', 'call_session_id', 'callSessionId', 'call_id', 'callId'], $notes),
            'channel_name' => $this->firstFilled($request, ['channel_name', 'channelName'], $notes),
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
            $context['channel_name'] ??= $session->channel_name ?: null;

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

    protected function enrichVerificationContext(array $context, array $notes, ?string $orderId, ?string $paymentId): array
    {
        $context = $this->hydrateContextFromTransactionReference($context, $orderId);
        $context = $this->hydrateContextFromTransactionReference($context, $paymentId);
        $context = $this->hydrateContextFromVideoApointment($context, $orderId);

        if (! ($context['doctor_id'] ?? null)) {
            $context['doctor_id'] = $this->toNullableInt($notes['doctor_id'] ?? $notes['doctorId'] ?? null);
        }
        if (! ($context['user_id'] ?? null)) {
            $context['user_id'] = $this->toNullableInt($notes['user_id'] ?? $notes['userId'] ?? $notes['patient_id'] ?? $notes['patientId'] ?? null);
        }
        if (! ($context['pet_id'] ?? null)) {
            $context['pet_id'] = $this->toNullableInt($notes['pet_id'] ?? $notes['petId'] ?? null);
        }

        if ((! ($context['doctor_id'] ?? null) || ! ($context['user_id'] ?? null))
            && ($context['call_identifier'] ?? null || $context['channel_name'] ?? null)) {
            $sessionIdentifier = $context['call_identifier'] ?? $context['channel_name'] ?? null;
            $session = $this->findCallSession($sessionIdentifier);

            if ($session) {
                $context['doctor_id'] ??= $session->doctor_id ? (int) $session->doctor_id : null;
                $context['user_id'] ??= $session->patient_id ? (int) $session->patient_id : null;
                $context['channel_name'] ??= $session->channel_name ?: null;

                if (! ($context['clinic_id'] ?? null) && $session->relationLoaded('doctor') && $session->doctor) {
                    $context['clinic_id'] = $session->doctor->vet_registeration_id
                        ? (int) $session->doctor->vet_registeration_id
                        : null;
                }
            }
        }

        if (! ($context['clinic_id'] ?? null) && ($context['doctor_id'] ?? null)) {
            $context['clinic_id'] = $this->lookupDoctorClinicId($context['doctor_id']);
        }

        return $context;
    }

    protected function hydrateContextFromTransactionReference(array $context, ?string $reference): array
    {
        $reference = trim((string) $reference);
        if ($reference === '' || !Schema::hasTable('transactions')) {
            return $context;
        }

        try {
            $transaction = Transaction::query()
                ->where('reference', $reference)
                ->latest('id')
                ->first();

            if (! $transaction) {
                return $context;
            }

            $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
            $metadataNotes = isset($metadata['notes']) && is_array($metadata['notes'])
                ? $metadata['notes']
                : [];

            if (! ($context['doctor_id'] ?? null)) {
                $context['doctor_id'] = $this->toNullableInt(
                    $transaction->doctor_id
                    ?? ($metadata['doctor_id'] ?? null)
                    ?? ($metadataNotes['doctor_id'] ?? $metadataNotes['doctorId'] ?? null)
                );
            }

            if (! ($context['user_id'] ?? null)) {
                $context['user_id'] = $this->toNullableInt(
                    $transaction->user_id
                    ?? ($metadata['user_id'] ?? null)
                    ?? ($metadataNotes['user_id'] ?? $metadataNotes['userId'] ?? $metadataNotes['patient_id'] ?? $metadataNotes['patientId'] ?? null)
                );
            }

            if (! ($context['pet_id'] ?? null)) {
                $context['pet_id'] = $this->toNullableInt(
                    $transaction->pet_id
                    ?? ($metadata['pet_id'] ?? null)
                    ?? ($metadataNotes['pet_id'] ?? $metadataNotes['petId'] ?? null)
                );
            }

            if (! ($context['clinic_id'] ?? null)) {
                $context['clinic_id'] = $this->toNullableInt(
                    $transaction->clinic_id
                    ?? ($metadata['clinic_id'] ?? null)
                    ?? ($metadataNotes['clinic_id'] ?? $metadataNotes['clinicId'] ?? null)
                );
            }

            if (! ($context['call_identifier'] ?? null)) {
                $context['call_identifier'] = $metadata['call_id']
                    ?? ($metadata['call_identifier'] ?? null)
                    ?? ($metadataNotes['call_session_id'] ?? $metadataNotes['call_session'] ?? $metadataNotes['callSessionId'] ?? $metadataNotes['call_id'] ?? $metadataNotes['callId'] ?? null);
            }

            if (! ($context['channel_name'] ?? null)) {
                $context['channel_name'] = $transaction->channel_name
                    ?? ($metadata['channel_name'] ?? null)
                    ?? ($metadataNotes['channel_name'] ?? $metadataNotes['channelName'] ?? null);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $context;
    }

    protected function hydrateContextFromVideoApointment(array $context, ?string $orderId): array
    {
        $orderId = trim((string) $orderId);
        if ($orderId === '' || !Schema::hasTable('video_apointment')) {
            return $context;
        }

        try {
            $videoApointment = VideoApointment::query()
                ->where('order_id', $orderId)
                ->latest('id')
                ->first();

            if (! $videoApointment) {
                return $context;
            }

            if (! ($context['doctor_id'] ?? null)) {
                $context['doctor_id'] = $this->toNullableInt($videoApointment->doctor_id);
            }
            if (! ($context['user_id'] ?? null)) {
                $context['user_id'] = $this->toNullableInt($videoApointment->user_id);
            }
            if (! ($context['pet_id'] ?? null)) {
                $context['pet_id'] = $this->toNullableInt($videoApointment->pet_id);
            }
            if (! ($context['clinic_id'] ?? null)) {
                $context['clinic_id'] = $this->toNullableInt($videoApointment->clinic_id);
            }
            if (! ($context['call_identifier'] ?? null) && !empty($videoApointment->call_session)) {
                $context['call_identifier'] = (string) $videoApointment->call_session;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $context;
    }

    protected function mergeContextIntoNotes(array $notes, array $context): array
    {
        foreach (['clinic_id', 'doctor_id', 'user_id', 'pet_id'] as $key) {
            $value = $context[$key] ?? null;
            if (($notes[$key] ?? null) === null || ($notes[$key] ?? '') === '') {
                if ($value !== null && $value !== '') {
                    $notes[$key] = (string) $value;
                }
            }
        }

        if (($notes['call_session_id'] ?? null) === null || ($notes['call_session_id'] ?? '') === '') {
            $callIdentifier = $context['call_identifier'] ?? null;
            if ($callIdentifier !== null && $callIdentifier !== '') {
                $notes['call_session_id'] = (string) $callIdentifier;
            }
        }

        if (($notes['channel_name'] ?? null) === null || ($notes['channel_name'] ?? '') === '') {
            $channelName = $context['channel_name'] ?? null;
            if ($channelName !== null && $channelName !== '') {
                $notes['channel_name'] = (string) $channelName;
            }
        }

        return $notes;
    }

    protected function persistUserGstDetails(?int $userId, array $notes): void
    {
        if (! $userId || !Schema::hasTable('users') || !Schema::hasColumn('users', 'gst_number')) {
            return;
        }

        $gstNumber = trim((string) ($notes['gst_number'] ?? $notes['gstNumber'] ?? ''));
        if ($gstNumber === '') {
            return;
        }

        try {
            $updates = ['gst_number' => $gstNumber];

            if (Schema::hasColumn('users', 'gst_number_given')) {
                $gstNumberGiven = $notes['gst_number_given'] ?? $notes['gstNumberGiven'] ?? null;
                if ($gstNumberGiven !== null && $gstNumberGiven !== '') {
                    $updates['gst_number_given'] = $this->toBoolInt($gstNumberGiven);
                } else {
                    $updates['gst_number_given'] = 1;
                }
            }

            DB::table('users')
                ->where('id', $userId)
                ->update($updates);
        } catch (\Throwable $e) {
            report($e);
        }
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

    protected function toBoolInt($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
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
        $candidate = $this->normalizeOrderType($notes['order_type'] ?? null);

        if ($candidate !== null && $candidate !== '') {
            return $candidate;
        }

        if (! empty($notes['service_id'])) {
            return 'service';
        }

        return 'payment';
    }

    protected function resolveCouponCode(array $notes = []): ?string
    {
        return $this->normalizeCouponCode(
            $notes['coupon_code']
            ?? $notes['couponCode']
            ?? $notes['coupon']
            ?? null
        );
    }

    protected function normalizeCouponCode(?string $couponCode): ?string
    {
        if (!is_string($couponCode)) {
            return null;
        }

        $trimmed = trim($couponCode);
        if ($trimmed === '') {
            return null;
        }

        return strtoupper(str_replace([' ', '-'], '_', $trimmed));
    }

    protected function normalizeOrderType(?string $orderType): ?string
    {
        if (!is_string($orderType)) {
            return null;
        }

        $trimmed = trim($orderType);
        if ($trimmed === '') {
            return null;
        }

        $normalized = strtolower(str_replace(['-', ' '], '_', $trimmed));

        return match ($normalized) {
            'video_consultation', 'video_consult', 'video_call', 'appointment' => 'video_consult',
            'excel_export_campaign' => 'excell_export_campaign',
            default => $normalized,
        };
    }

    protected function isVideoConsultTransactionType(?string $transactionType): bool
    {
        $normalized = $this->normalizeOrderType($transactionType);
        return $normalized === 'video_consult';
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
            $channelName = $this->resolveChannelNameForWhatsApp($context);
            $result = $this->whatsApp->sendTextWithResult($to, $text, $channelName);

            return ['sent' => true, 'to' => $to, 'url' => $url, 'meta' => $result];
        } catch (\Throwable $e) {
            report($e);
            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    protected function resolveChannelNameForWhatsApp(array $context, array $notes = []): ?string
    {
        $candidate = trim((string) (
            $context['channel_name']
            ?? $notes['channel_name']
            ?? $notes['call_session_id']
            ?? $notes['call_session']
            ?? ''
        ));

        if ($candidate !== '') {
            if (str_starts_with($candidate, 'channel_')) {
                return $candidate;
            }

            try {
                $session = $this->findCallSession($candidate);
                if ($session && !empty($session->channel_name)) {
                    return trim((string) $session->channel_name);
                }
            } catch (\Throwable $e) {
                // Best-effort resolver; ignore and return raw candidate.
            }

            return $candidate;
        }

        $userId = $context['user_id'] ?? null;
        $doctorId = $context['doctor_id'] ?? null;
        if (!Schema::hasTable('call_sessions') || !$userId || !$doctorId) {
            return null;
        }

        try {
            $row = DB::table('call_sessions')
                ->where('patient_id', (int) $userId)
                ->where('doctor_id', (int) $doctorId)
                ->whereNotNull('channel_name')
                ->where('channel_name', '!=', '')
                ->orderByDesc('id')
                ->value('channel_name');

            if (is_string($row) && trim($row) !== '') {
                return trim($row);
            }
        } catch (\Throwable $e) {
            // Best-effort resolver.
        }

        return null;
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

    protected function recordVideoApointmentOrder(
        Request $request,
        array $order,
        array $context,
        ?CallSession $callSession = null,
        array $notes = []
    ): ?VideoApointment {
        if (!Schema::hasTable('video_apointment')) {
            return null;
        }

        $orderType = strtolower((string) $this->resolveTransactionType($notes));
        if (!in_array($orderType, ['video_consult', 'excell_export_campaign'], true)) {
            return null;
        }

        $callSessionIdentifier = $callSession?->resolveIdentifier()
            ?: ($context['call_identifier'] ?? null)
            ?: $this->firstFilled($request, ['call_session', 'call_session_id', 'callSessionId', 'call_id', 'callId'], $notes);

        $payload = [
            'order_id' => $order['id'] ?? null,
            'pet_id' => $context['pet_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'doctor_id' => $context['doctor_id'] ?? null,
            'clinic_id' => $context['clinic_id'] ?? null,
            'call_session' => $callSessionIdentifier ?: null,
            'is_completed' => false,
        ];

        $hasAnyLink = $payload['pet_id']
            || $payload['user_id']
            || $payload['doctor_id']
            || $payload['clinic_id']
            || $payload['call_session'];

        if (!$hasAnyLink) {
            return null;
        }

        try {
            return VideoApointment::create($payload);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
