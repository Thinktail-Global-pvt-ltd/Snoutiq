<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerOffer;
use App\Models\GroomerCoupon;
use App\Models\GroomerFeaturedService;
use App\Models\GroomerNotification;
use App\Models\GroomerService;
use App\Models\GroomerServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MarketingController extends Controller
{
    /**
     * Get all offers for the authenticated groomer.
     */
    public function getOffers(Request $request)
    {
        $offers = GroomerOffer::where('user_id', $request->user()->id)
            ->with(['service', 'category'])
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'discount' => $offer->discount,
                    'type' => $offer->type,
                    'targetId' => $offer->type === 'service' ? $offer->service_id : $offer->category_id,
                    'targetName' => $offer->type === 'service' ? ($offer->service->name ?? 'N/A') : ($offer->category->name ?? 'N/A'),
                    'expiry' => $offer->expiry,
                ];
            });

        return response()->json([
            'data' => $offers
        ], 200);
    }

    /**
     * Create a new offer.
     */
    public function storeOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'discount' => 'required|numeric|min:1|max:100',
            'type' => 'required|in:service,category',
            'targetId' => 'required|string|exists:'.($request->type === 'service' ? 'groomer_services,id' : 'groomer_service_categories,id'),
            'expiry' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'discount' => $request->discount,
            'type' => $request->type,
            'service_id' => $request->type === 'service' ? $request->targetId : null,
            'category_id' => $request->type === 'category' ? $request->targetId : null,
            'expiry' => Carbon::parse($request->expiry),
        ];

        $offer = GroomerOffer::create($data);

        return response()->json([
            'message' => 'Offer created successfully',
            'data' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'discount' => $offer->discount,
                'type' => $offer->type,
                'targetId' => $offer->type === 'service' ? $offer->service_id : $offer->category_id,
                'targetName' => $offer->type === 'service' ? ($offer->service->name ?? 'N/A') : ($offer->category->name ?? 'N/A'),
                'expiry' => $offer->expiry,
            ]
        ], 201);
    }

    /**
     * Get all coupons for the authenticated groomer.
     */
    public function getCoupons(Request $request)
    {
        $coupons = GroomerCoupon::where('user_id', $request->user()->id)->get();

        return response()->json([
            'data' => $coupons->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'expiry' => $coupon->expiry,
                    'isOnline' => $coupon->is_online,
                    'isOffline' => $coupon->is_offline,
                ];
            })
        ], 200);
    }

    /**
     * Create a new coupon.
     */
    public function storeCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:groomer_coupons,code',
            'discount' => 'required|numeric|min:1|max:100',
            'expiry' => 'required|date|after:today',
            'isOnline' => 'required|boolean',
            'isOffline' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
            'code' => strtoupper($request->code),
            'discount' => $request->discount,
            'expiry' => Carbon::parse($request->expiry),
            'is_online' => $request->isOnline,
            'is_offline' => $request->isOffline,
        ];

        $coupon = GroomerCoupon::create($data);

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount' => $coupon->discount,
                'expiry' => $coupon->expiry,
                'isOnline' => $coupon->is_online,
                'isOffline' => $coupon->is_offline,
            ]
        ], 201);
    }

    /**
     * Get all push notifications for the authenticated groomer.
     */
    public function getNotifications(Request $request)
    {
        $notifications = GroomerNotification::where('user_id', $request->user()->id)->get();

        return response()->json([
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'image' => $notification->image ? Storage::url($notification->image) : null,
                    'ctaText' => $notification->cta_text,
                    'ctaUrl' => $notification->cta_url,
                ];
            })
        ], 200);
    }

    /**
     * Create a new push notification.
     */
    public function storeNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ctaText' => 'nullable|string|max:50',
            'ctaUrl' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'message' => $request->message,
            'cta_text' => $request->ctaText,
            'cta_url' => $request->ctaUrl,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('notifications', 'public');
            $data['image'] = $path;
        }

        $notification = GroomerNotification::create($data);

        return response()->json([
            'message' => 'Notification created successfully',
            'data' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'image' => $notification->image ? Storage::url($notification->image) : null,
                'ctaText' => $notification->cta_text,
                'ctaUrl' => $notification->cta_url,
            ]
        ], 201);
    }

    /**
     * Get all featured services for the authenticated groomer.
     */
    public function getFeaturedServices(Request $request)
    {
        $featuredServices = GroomerFeaturedService::where('user_id', $request->user()->id)
            ->with('service.category')
            ->get()
            ->map(function ($featured) {
                return [
                    'id' => $featured->service->id,
                    'name' => $featured->service->name,
                    'category' => $featured->service->category ? [
                        'id' => $featured->service->category->id,
                        'name' => $featured->service->category->name,
                    ] : null,
                    'price' => $featured->service->price,
                ];
            });

        return response()->json([
            'data' => $featuredServices
        ], 200);
    }

    /**
     * Update featured services (max 3).
     */
    public function storeFeaturedServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serviceIds' => 'required|array|max:3',
            'serviceIds.*' => 'exists:groomer_services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Delete existing featured services for the user
        GroomerFeaturedService::where('user_id', $request->user()->id)->delete();

        // Create new featured services
        $data = collect($request->serviceIds)->map(function ($serviceId) use ($request) {
            return [
                'user_id' => $request->user()->id,
                'service_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        GroomerFeaturedService::insert($data);

        // Fetch updated featured services
        $featuredServices = GroomerFeaturedService::where('user_id', $request->user()->id)
            ->with('service.category')
            ->get()
            ->map(function ($featured) {
                return [
                    'id' => $featured->service->id,
                    'name' => $featured->service->name,
                    'category' => $featured->service->category ? [
                        'id' => $featured->service->category->id,
                        'name' => $featured->service->category->name,
                    ] : null,
                    'price' => $featured->service->price,
                ];
            });

        return response()->json([
            'message' => 'Featured services updated successfully',
            'data' => $featuredServices
        ], 200);
    }
}