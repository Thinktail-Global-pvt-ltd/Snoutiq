<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Snoutiq\RoutingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $rzKey    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_live_RGBIfjaGxq1Ma4';
        $rzSecret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'WypJ2plLEmScSrVjrLzixWyN';
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

        $rzKey    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_live_RGBIfjaGxq1Ma4';
        $rzSecret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'WypJ2plLEmScSrVjrLzixWyN';

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
        $pet = null; $pets = collect(); $user = null; $petParentName = null;
        try {
            // User (pet parent)
            if (Schema::hasTable('users')) {
                $user = DB::table('users')
                    ->where('id', $booking->user_id)
                    ->select('id','name','email')
                    ->first();
                if ($user) { $petParentName = $user->name ?? null; }
            }

            if (Schema::hasTable('pets')) {
                if (!empty($booking->pet_id)) {
                    $pet = DB::table('pets')->where('id', $booking->pet_id)->first();
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
                }
                $pets = DB::table('user_pets')->where('user_id', $booking->user_id)->orderByDesc('id')->get();
            }
        } catch (\Throwable $e) { /* ignore */ }

        return response()->json([
            'booking' => $booking,
            'user'    => $user,
            'pet_parent_name' => $petParentName,
            'pet'     => $pet,
            'pets'    => $pets,
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

        // Decode JSON symptoms to array for convenience
        $rows = $rows->map(function($r){
            if (isset($r->symptoms) && $r->symptoms) {
                try { $r->symptoms = is_array($r->symptoms) ? $r->symptoms : json_decode($r->symptoms, true); } catch (\Throwable $e) { /* ignore */ }
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
}
