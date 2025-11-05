<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DraftClinicController extends Controller
{
    public function index(Request $request)
    {
        $query = VetRegisterationTemp::query()
            ->where('status', 'draft');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhere('slug', 'LIKE', '%'.$search.'%')
                    ->orWhere('public_id', 'LIKE', '%'.$search.'%');
            });
        }

        $limit = (int) $request->input('limit', 200);
        $limit = max(1, min($limit, 500));

        $clinics = $query
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get([
                'id',
                'name',
                'slug',
                'public_id',
                'city',
                'pincode',
                'draft_expires_at',
                'created_at',
            ]);

        return response()->json([
            'clinics' => $clinics->map(function (VetRegisterationTemp $clinic) {
                return [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                    'public_id' => $clinic->public_id,
                    'city' => $clinic->city,
                    'pincode' => $clinic->pincode,
                    'draft_expires_at' => optional($clinic->draft_expires_at)->toIso8601String(),
                    'created_at' => optional($clinic->created_at)->toIso8601String(),
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'pincode' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:255',
            'created_by_user_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            'draft_expires_in_days' => 'nullable|integer|min:1|max:180',
        ]);

        $mobile = $validated['mobile'] ?? $validated['phone'] ?? null;
        $address = $validated['address'] ?? $validated['area'] ?? null;
        $pincode = trim((string) ($validated['pincode'] ?? $request->input('pincode') ?? '000000'));
        if ($pincode === '') {
            $pincode = '000000';
        }

        $employeeIdRaw = $validated['employee_id'] ?? $request->input('employee_id');
        $employeeId = $employeeIdRaw !== null ? trim((string) $employeeIdRaw) : null;
        if ($employeeId === '') {
            $employeeId = null;
        }

        $duplicate = null;
        if ($mobile || $address) {
            $duplicate = VetRegisterationTemp::query()
                ->where(function ($query) use ($mobile, $address) {
                    if ($mobile) {
                        $query->orWhere('mobile', $mobile);
                    }
                    if ($address) {
                        $query->orWhere('address', $address);
                    }
                })
                ->latest('id')
                ->first();

            if ($duplicate && in_array($duplicate->status, ['draft', 'pending'], true) && $duplicate->draft_expires_at && $duplicate->draft_expires_at->isPast()) {
                $duplicate->status = 'expired';
                $duplicate->draft_expires_at = null;
                $duplicate->save();
                $duplicate = null;
            }

            if ($duplicate && $duplicate->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Clinic already active for this phone/address',
                    'clinic' => $this->formatClinic($duplicate),
                ], 409);
            }
        }

        $clinic = $duplicate;
        $created = false;

        if (! $clinic) {
            $expiryInput = $validated['draft_expires_in_days'] ?? $request->input('draft_expires_in_days');
            $days = (int) ($expiryInput ?: $this->defaultExpiryDays());
            if ($days <= 0) {
                $days = $this->defaultExpiryDays();
            }
            $days = min(max($days, 1), 180);

            $clinic = new VetRegisterationTemp();
            $clinic->fill([
                'name' => $validated['name'] ?? null,
                'mobile' => $mobile,
                'city' => $validated['city'] ?? null,
                'pincode' => $pincode,
                'address' => $address,
                'employee_id' => $employeeId,
                'status' => 'draft',
                'draft_created_by_user_id' => $request->user()?->id ?? $validated['created_by_user_id'] ?? null,
            ]);

            $clinic->draft_expires_at = now()->addDays($days);
            $clinic->save();
            $created = true;
        }

        $claimUrl = $this->claimUrl($clinic);
        $qrPayload = $this->ensureQrCode($clinic, $claimUrl);

        return response()->json([
            'success' => true,
            'created' => $created,
            'clinic' => $this->formatClinic($clinic),
            'public_url' => $this->publicUrl($clinic),
            'claim_url' => $claimUrl,
            'qr_png_url' => $qrPayload['url'],
            'qr_png_data_uri' => $qrPayload['data_uri'],
            'expires_at' => optional($clinic->draft_expires_at)->toIso8601String(),
            'claim_token' => $clinic->claim_token,
        ]);
    }

    private function ensureQrCode(VetRegisterationTemp $clinic, string $claimUrl): array
    {
        $path = $clinic->qr_code_path;
        $disk = Storage::disk('public');
        if (! $path) {
            $path = 'clinic-qr/'.$clinic->public_id.'.png';
        }

        $disk->makeDirectory('clinic-qr');

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'margin' => 2,
            'eccLevel' => QRCode::ECC_M,
            'imageTransparent' => false,
            'outputBase64' => false,
        ]);

        $pngBinary = (new QRCode($options))->render($claimUrl);

        $disk->put($path, $pngBinary);

        if ($clinic->qr_code_path !== $path) {
            $clinic->qr_code_path = $path;
            $clinic->save();
        }

        return [
            'url' => url(Storage::url($path)),
            'data_uri' => 'data:image/png;base64,'.base64_encode($pngBinary),
        ];
    }

    private function claimUrl(VetRegisterationTemp $clinic): string
    {
        $url = $this->publicUrl($clinic);

        if ($clinic->status === 'draft' && $clinic->claim_token) {
            $separator = str_contains($url, '?') ? '&' : '?';
            return $url.$separator.'claim_token='.$clinic->claim_token;
        }

        return $url;
    }

    private function publicUrl(VetRegisterationTemp $clinic): string
    {
        return LegacyQrRedirect::scanUrlForPublicId($clinic->public_id);
    }

    private function formatClinic(VetRegisterationTemp $clinic): array
    {
        return [
            'id' => $clinic->id,
            'public_id' => $clinic->public_id,
            'name' => $clinic->name,
            'mobile' => $clinic->mobile,
            'status' => $clinic->status,
            'city' => $clinic->city,
            'address' => $clinic->address,
            'draft_expires_at' => optional($clinic->draft_expires_at)->toIso8601String(),
        ];
    }

    private function defaultExpiryDays(): int
    {
        return 60;
    }
}
