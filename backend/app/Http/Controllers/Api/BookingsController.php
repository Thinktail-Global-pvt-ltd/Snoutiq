<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Snoutiq\RoutingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;

class BookingsController extends Controller
{
    // POST /api/bookings/create
    public function create(Request $request, RoutingEngine $routing)
    {
        $payload = $request->validate([
            'user_id' => 'required|integer',
            'pet_id' => 'required|integer',
            'service_type' => 'required|string|in:video,in_clinic,home_visit',
            'urgency' => 'required|string|in:low,medium,high,emergency',
            'ai_summary' => 'nullable|string',
            'ai_urgency_score' => 'nullable|numeric',
            'symptoms' => 'nullable|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string',
            // clinic + doctor centric fields
            'clinic_id' => 'nullable|integer',
            'doctor_id' => 'nullable|integer',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable',
        ]);

        $scheduledFor = null;
        if (!empty($payload['scheduled_date']) && !empty($payload['scheduled_time'])) {
            $scheduledFor = $payload['scheduled_date'] . ' ' . $payload['scheduled_time'];
        }

        // Fixed price for now
        $amountInInr = 800;

        $id = DB::table('bookings')->insertGetId([
            'user_id' => $payload['user_id'],
            'pet_id' => $payload['pet_id'],
            'service_type' => $payload['service_type'],
            'urgency' => $payload['urgency'],
            'ai_summary' => $payload['ai_summary'] ?? null,
            'ai_urgency_score' => $payload['ai_urgency_score'] ?? null,
            'symptoms' => isset($payload['symptoms']) ? json_encode($payload['symptoms']) : null,
            'user_latitude' => $payload['latitude'] ?? null,
            'user_longitude' => $payload['longitude'] ?? null,
            'user_address' => $payload['address'] ?? null,
            'status' => 'pending',
            // new optional associations
            'clinic_id' => $payload['clinic_id'] ?? null,
            'assigned_doctor_id' => $payload['doctor_id'] ?? null,
            'scheduled_for' => $scheduledFor,
            // price & payment fields
            'quoted_price'  => $amountInInr,
            'final_price'   => $amountInInr,
            'payment_status'=> 'pending',
        ]);

        // Create Razorpay order for payment
        $rzKey    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_test_1nhE9190sR3rkP';
        $rzSecret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'L6CPZlUwrKQpdC9N3TRX8gIh';
        $orderId = null; $orderArr = null; $currency = 'INR';
        try {
            $api = new Api($rzKey, $rzSecret);
            $order = $api->order->create([
                'receipt'  => 'booking_'.$id,
                'amount'   => $amountInInr * 100, // paisa
                'currency' => $currency,
                'notes'    => ['booking_id' => (string) $id, 'service_type' => $payload['service_type']],
            ]);
            $orderArr = $order->toArray();
            $orderId  = $order['id'] ?? null;
        } catch (RazorpayError $e) {
            Log::warning('Razorpay order create failed: '.$e->getMessage());
        } catch (\Throwable $e) {
            Log::warning('Razorpay order create exception: '.$e->getMessage());
        }

        // Kick off routing (minimal stub)
        $routing->routeBooking($id);

        return response()->json([
            'success' => true,
            'booking_id' => $id,
            'message' => 'Booking created, proceed to payment.',
            'status' => 'routing',
            'payment' => [
                'provider' => 'razorpay',
                'key'      => $rzKey,
                'order_id' => $orderId,
                'order'    => $orderArr,
                'amount'   => $amountInInr,
                'currency' => $currency,
            ],
        ]);
    }

    // POST /api/bookings/{id}/verify-payment
    public function verifyPayment(Request $request, string $id)
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $booking = DB::table('bookings')->where('id', $id)->first();
        if (!$booking) {
            return response()->json(['success' => false, 'error' => 'Booking not found'], 404);
        }

        $rzKey    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_test_1nhE9190sR3rkP';
        $rzSecret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'L6CPZlUwrKQpdC9N3TRX8gIh';

        try {
            $api = new Api($rzKey, $rzSecret);

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);

            $paymentArr = null; $method = null; $email = null; $contact = null; $currency = 'INR'; $status = 'verified';
            try {
                $p = $api->payment->fetch($data['razorpay_payment_id']);
                $paymentArr = $p->toArray();
                $method   = $paymentArr['method']   ?? null;
                $email    = $paymentArr['email']    ?? null;
                $contact  = $paymentArr['contact']  ?? null;
                $currency = $paymentArr['currency'] ?? 'INR';
                $status   = $paymentArr['status']   ?? $status;
            } catch (\Throwable $e) { /* ignore */ }

            DB::table('bookings')->where('id', $id)->update([
                'payment_provider'    => 'razorpay',
                'payment_order_id'    => $data['razorpay_order_id'],
                'payment_id'          => $data['razorpay_payment_id'],
                'payment_signature'   => $data['razorpay_signature'],
                'payment_method'      => $method,
                'payment_email'       => $email,
                'payment_contact'     => $contact,
                'payment_currency'    => $currency,
                'payment_raw'         => $paymentArr ? json_encode($paymentArr) : null,
                'payment_status'      => 'paid',
                'payment_verified_at' => now(),
                'updated_at'          => now(),
            ]);

            return response()->json(['success' => true, 'verified' => true]);
        } catch (RazorpayError $e) {
            return response()->json(['success' => false, 'error' => 'Razorpay: '.$e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    // GET /api/bookings/details/{id}
    public function details(string $id)
    {
        $booking = DB::table('bookings as b')
            ->leftJoin('doctors as d', 'b.assigned_doctor_id', '=', 'd.id')
            ->select('b.*', DB::raw('COALESCE(d.doctor_name, "") as doctor_name'))
            ->where('b.id', $id)
            ->first();
        if (!$booking) {
            return response()->json(['error' => 'Not found', 'success' => false], 404);
        }

        // Fetch selected pet + all pets for the user (prefer `pets`, fallback to `user_pets`)
        $pet = null;
        $pets = collect();
        $user = null;
        $petParentName = null;
        $userDocuments = [];
        $userSummary = null;
        $userHistory = collect();
        try {
            // User (pet parent)
            if (Schema::hasTable('users')) {
                $userColumns = $this->discoverUserColumns();
                if ($userColumns) {
                    $user = DB::table('users')
                        ->where('id', $booking->user_id)
                        ->select($userColumns)
                        ->first();
                }
                if ($user) {
                    $petParentName = $user->name ?? null;
                    $userSummary = $user->summary ?? null;
                    $userDocuments = array_merge($userDocuments, $this->extractDocumentPayloads($user, [
                        'pet_doc1' => 'Medical Upload 1',
                        'pet_doc2' => 'Medical Upload 2',
                    ], 'user'));
                }
            }

            if (Schema::hasTable('pets')) {
                if (!empty($booking->pet_id)) {
                    $pet = DB::table('pets')->where('id', $booking->pet_id)->first();
                    if ($pet) {
                        $userDocuments = array_merge($userDocuments, $this->extractDocumentPayloads($pet, [
                            'pet_doc1' => 'Pet Document 1',
                            'pet_doc2' => 'Pet Document 2',
                        ], 'pet'));
                    }
                }
                $petsQ = DB::table('pets');
                if (Schema::hasColumn('pets', 'user_id')) {
                    $petsQ->where('user_id', $booking->user_id);
                } elseif (Schema::hasColumn('pets', 'owner_id')) {
                    $petsQ->where('owner_id', $booking->user_id);
                }
                $pets = $petsQ->orderByDesc('id')->get();
            } elseif (Schema::hasTable('user_pets')) {
                if (!empty($booking->pet_id)) {
                    $pet = DB::table('user_pets')->where('id', $booking->pet_id)->first();
                    if ($pet) {
                        $userDocuments = array_merge($userDocuments, $this->extractDocumentPayloads($pet, [
                            'pet_doc1' => 'Pet Document 1',
                            'pet_doc2' => 'Pet Document 2',
                        ], 'pet'));
                    }
                }
                $pets = DB::table('user_pets')->where('user_id', $booking->user_id)->orderByDesc('id')->get();
            }

            if ($booking->user_id && Schema::hasTable('bookings')) {
                $history = DB::table('bookings')
                    ->select(
                        'id',
                        'service_type',
                        'status',
                        'scheduled_for',
                        'booking_created_at',
                        'ai_summary',
                        'urgency',
                        'assigned_doctor_id',
                        'pet_id'
                    )
                    ->where('user_id', $booking->user_id)
                    ->orderByRaw('COALESCE(scheduled_for, booking_created_at) DESC')
                    ->limit(25)
                    ->get();
                $userHistory = $history->map(function ($row) use ($booking) {
                    $row->is_current = ((int) $row->id) === ((int) $booking->id);
                    $row->timeline_label = $row->scheduled_for ?? $row->booking_created_at;
                    return $row;
                });
            }
        } catch (\Throwable $e) { /* ignore */ }

        return response()->json([
            'booking' => $booking,
            'user'    => $user,
            'pet_parent_name' => $petParentName,
            'pet'     => $pet,
            'pets'    => $pets,
            'documents' => $userDocuments,
            'user_summary' => $userSummary,
            'user_history' => $userHistory,
        ]);
    }

    // GET /api/doctors/{id}/bookings?since=YYYY-MM-DD
    public function doctorBookings(string $id, Request $request)
    {
        $since = $request->query('since');
        $q = DB::table('bookings as b')
            ->leftJoin('user_pets as p', 'b.pet_id', '=', 'p.id')
            ->select(
                'b.*',
                DB::raw('COALESCE(p.name, "") as pet_name'),
                DB::raw('COALESCE(p.breed, "") as pet_breed')
            )
            ->where('b.assigned_doctor_id', (int) $id);
        if ($since) {
            $q->where(function($sub) use ($since) {
                $sub->whereDate('b.scheduled_for', '>=', $since)
                    ->orWhereDate('b.booking_created_at', '>=', $since);
            });
        }
        $rows = $q->orderByRaw('COALESCE(b.scheduled_for, b.booking_created_at) DESC')->limit(200)->get();

        $userProfiles = collect();
        if ($rows->isNotEmpty() && Schema::hasTable('users')) {
            $ids = $rows->pluck('user_id')->filter()->unique()->all();
            if (!empty($ids)) {
                $userColumns = $this->discoverUserColumns();
                if ($userColumns) {
                    $userProfiles = DB::table('users')
                        ->select($userColumns)
                        ->whereIn('id', $ids)
                        ->get()
                        ->keyBy('id');
                }
            }
        }

        // Decode JSON symptoms to array for convenience
        $rows = $rows->map(function($r) use ($userProfiles){
            if (isset($r->symptoms) && $r->symptoms) {
                try { $r->symptoms = is_array($r->symptoms) ? $r->symptoms : json_decode($r->symptoms, true); } catch (\Throwable $e) { /* ignore */ }
            }
            $profile = $userProfiles[$r->user_id] ?? null;
            if ($profile) {
                $r->pet_parent_name  = $profile->name ?? null;
                $r->pet_parent_phone = $profile->phone ?? null;
                $r->pet_parent_city  = $profile->city ?? null;
                $r->pet_parent_state = $profile->state ?? null;
                $r->user_summary     = $profile->summary ?? null;
            }
            return $r;
        });

        return response()->json(['bookings' => $rows]);
    }

    // PUT /api/bookings/{id}/status
    public function updateStatus(Request $request, string $id)
    {
        $data = $request->validate(['status' => 'required|string']);
        DB::table('bookings')->where('id', $id)->update(['status' => $data['status']]);
        return response()->json(['message' => 'Status updated']);
    }

    // POST /api/bookings/{id}/rate
    public function rate(Request $request, string $id)
    {
        $data = $request->validate(['rating' => 'required|integer|min:1|max:5', 'review' => 'nullable|string']);
        DB::table('bookings')->where('id', $id)->update(['rating' => $data['rating'], 'review' => $data['review'] ?? null]);
        return response()->json(['message' => 'Thanks for your feedback']);
    }

    /**
     * Discover available user columns once (guards against missing schema fields).
     */
    protected function discoverUserColumns(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        if (!Schema::hasTable('users')) {
            return $cache = [];
        }
        $base = ['id', 'name', 'email'];
        $optional = [
            'phone',
            'pet_name',
            'pet_gender',
            'pet_age',
            'pet_doc1',
            'pet_doc2',
            'summary',
            'address',
            'city',
            'state',
            'pincode',
            'latitude',
            'longitude',
        ];
        $columns = [];
        foreach (array_merge($base, $optional) as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return $cache = $columns;
    }

    /**
     * Extract normalized document metadata from a model/stdClass instance.
     */
    protected function extractDocumentPayloads($source, array $fieldMap, string $category): array
    {
        if (!$source) {
            return [];
        }
        $documents = [];
        foreach ($fieldMap as $field => $label) {
            $value = data_get($source, $field);
            $payload = $this->formatDocumentPayload($value, $label, $category);
            if ($payload) {
                $documents[] = $payload;
            }
        }
        return $documents;
    }

    /**
     * Build a single document payload with helpful metadata.
     */
    protected function formatDocumentPayload(?string $path, string $label, string $category = 'user'): ?array
    {
        if (!$path) {
            return null;
        }
        $url = $this->normalizeDocumentUrl($path);
        $rawPath = $path;
        $parsedPath = parse_url($rawPath, PHP_URL_PATH);
        $ext = strtolower(pathinfo($parsedPath ?: $rawPath, PATHINFO_EXTENSION));
        if ($ext === '' && str_contains($rawPath, '.')) {
            $ext = strtolower(pathinfo($rawPath, PATHINFO_EXTENSION));
        }
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'heic', 'heif'];

        return [
            'label' => $label,
            'category' => $category,
            'path' => $path,
            'url' => $url,
            'extension' => $ext,
            'is_image' => in_array($ext, $imageExts, true),
        ];
    }

    /**
     * Turn any stored relative asset path into an absolute URL for the dashboard.
     */
    protected function normalizeDocumentUrl(string $value): string
    {
        if ($value === '') {
            return $value;
        }
        if (preg_match('#^https?://#i', $value) || str_starts_with($value, '//')) {
            return $value;
        }
        $clean = ltrim($value, '/');
        return url($clean);
    }
}
