<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Zxing\QrReader;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SalesCrmController extends Controller
{
    public function index()
    {
        $redirects = LegacyQrRedirect::orderByDesc('created_at')->paginate(50);
        return view('backend.sales.crm', [
            'apiEndpoint' => url('/api/clinics/drafts'),
            'redirects' => $redirects,
            'legacyStoreRoute' => route('sales.legacy-qr.store'),
            'legacyDestroyRouteName' => 'sales.legacy-qr.destroy',
        ]);
    }

    public function storeLegacyQr(Request $request)
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:255|unique:legacy_qr_redirects,code',
            'legacy_url' => 'nullable|url|max:2048',
            'target_url' => 'nullable|url|max:2048',
            'clinic_name' => 'required|string|max:255',
            'clinic_slug' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'qr_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        $decodedValue = null;
        $rawDecodedValue = null;
        $qrImagePath = null;

        if ($request->hasFile('qr_image')) {
            $uploaded = $request->file('qr_image');
            $qrImagePath = $uploaded->store('legacy-qr', 'public');

            try {
                $reader = new QrReader(Storage::disk('public')->path($qrImagePath));
                $rawDecodedValue = trim((string) $reader->text());
                $decodedValue = $rawDecodedValue;
            } catch (\Throwable $th) {
                Storage::disk('public')->delete($qrImagePath);
                return redirect()->back()->withInput()->withErrors([
                    'qr_image' => 'Could not decode QR image. Please ensure it is clear and try again.',
                ]);
            }

            if (! $decodedValue) {
                Storage::disk('public')->delete($qrImagePath);
                return redirect()->back()->withInput()->withErrors([
                    'qr_image' => 'QR image uploaded but no data was detected. Please try another image.',
                ]);
            }

            $duplicateQr = LegacyQrRedirect::query()
                ->where('qr_image_hash', hash('sha256', $rawDecodedValue))
                ->orWhere('legacy_url', $decodedValue)
                ->first();

            if ($duplicateQr) {
                Storage::disk('public')->delete($qrImagePath);

                return redirect()->back()->withInput()->withErrors([
                    'qr_image' => 'This QR code has already been registered for '.$duplicateQr->code.'.',
                ]);
            }

            if (empty($data['legacy_url']) && filter_var($decodedValue, FILTER_VALIDATE_URL)) {
                $data['legacy_url'] = $decodedValue;
            }
        }

        $publicId = null;
        if ($request->filled('legacy_url')) {
            if (preg_match('~/c/([0-9A-Z]+)~i', $request->input('legacy_url'), $matches)) {
                $publicId = $matches[1];
            }
        }

        if (! $publicId && $decodedValue && preg_match('~/c/([0-9A-Z]+)~i', $decodedValue, $matches)) {
            $publicId = $matches[1];
        }

        if (! empty($data['code'])) {
            $data['code'] = trim($data['code']);
        }

        if (empty($data['code'])) {
            $fallbackSeed = $decodedValue ?? ($data['legacy_url'] ?? Str::uuid()->toString());
            $candidate = Str::slug(substr($fallbackSeed, 0, 40));
            if ($candidate === '') {
                $candidate = 'qr-'.Str::lower(Str::random(8));
            }
            $originalCandidate = $candidate;
            $suffix = 1;
            while (LegacyQrRedirect::where('code', $candidate)->exists()) {
                $candidate = $originalCandidate.'-'.$suffix++;
            }
            $data['code'] = $candidate;
        }

        if ($qrImagePath) {
            $data['qr_image_path'] = $qrImagePath;
        }

        $clinic = $this->resolveClinic(
            $data['clinic_name'],
            $request->input('clinic_slug'),
            $publicId
        );

        if (! $clinic) {
            return redirect()->back()->withInput()->withErrors([
                'clinic_name' => 'Unable to create or locate a clinic for this QR. Please try a different name.',
            ]);
        }

        $data['clinic_id'] = $clinic->id;
        $data['public_id'] = $clinic->public_id;

        unset($data['clinic_name'], $data['clinic_slug']);

        if ($rawDecodedValue) {
            $data['qr_image_hash'] = hash('sha256', $rawDecodedValue);
        }

        $mapping = LegacyQrRedirect::create($data);

        if (! empty($mapping->clinic_id)) {
            $this->prepareClinicDraft((int) $mapping->clinic_id, $mapping->public_id);
        }

        $status = 'Legacy QR mapping saved.';
        if ($rawDecodedValue) {
            $status .= ' Decoded value: '.Str::limit($rawDecodedValue, 140, '…');
        }
        if (! empty($mapping->clinic_id)) {
            $status .= ' Draft clinic page refreshed.';
        }

        return redirect()->route('sales.crm')->with('status', $status);
    }

    public function destroyLegacyQr(LegacyQrRedirect $legacyQrRedirect)
    {
        if ($legacyQrRedirect->qr_image_path) {
            Storage::disk('public')->delete($legacyQrRedirect->qr_image_path);
        }

        $legacyQrRedirect->delete();

        return redirect()->route('sales.crm')->with('status', 'Mapping removed.');
    }

    private function prepareClinicDraft(int $clinicId, ?string $publicId): void
    {
        $clinic = VetRegisterationTemp::find($clinicId);
        if (! $clinic) {
            return;
        }

        $needsSave = false;

        if ($publicId && empty($clinic->public_id)) {
            $clinic->public_id = $publicId;
            $needsSave = true;
        }

        if ($clinic->status !== 'active') {
            if ($clinic->status !== 'draft') {
                $clinic->status = 'draft';
                $needsSave = true;
            }

            if (empty($clinic->claim_token)) {
                $clinic->claim_token = Str::random(32);
                $needsSave = true;
            }

            if (! $clinic->draft_expires_at || $clinic->draft_expires_at->isPast()) {
                $clinic->draft_expires_at = now()->addDays(60);
                $needsSave = true;
            }
        }

        if ($needsSave) {
            $clinic->save();
            $clinic->refresh();
        }

        $this->ensureClinicQr($clinic);
    }

    private function ensureClinicQr(VetRegisterationTemp $clinic): void
    {
        if (empty($clinic->public_id)) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->makeDirectory('clinic-qr');

        $path = $clinic->qr_code_path ?: 'clinic-qr/'.$clinic->public_id.'.png';

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'margin' => 2,
            'eccLevel' => QRCode::ECC_M,
            'imageTransparent' => false,
            'outputBase64' => false,
        ]);

        $claimUrl = $this->claimUrlForClinic($clinic);
        $pngBinary = (new QRCode($options))->render($claimUrl);

        $disk->put($path, $pngBinary);

        if ($clinic->qr_code_path !== $path) {
            $clinic->qr_code_path = $path;
            $clinic->save();
        }
    }

    private function claimUrlForClinic(VetRegisterationTemp $clinic): string
    {
        $url = url('/c/'.$clinic->public_id);

        if ($clinic->status === 'draft' && $clinic->claim_token) {
            $separator = str_contains($url, '?') ? '&' : '?';
            return $url.$separator.'claim_token='.$clinic->claim_token;
        }

        return $url;
    }

    private function resolveClinic(string $name, ?string $slugInput, ?string $publicId): ?VetRegisterationTemp
    {
        $slugCandidate = $slugInput ? Str::slug($slugInput) : Str::slug($name);
        if ($slugCandidate === '') {
            $slugCandidate = 'clinic-'.Str::lower(Str::random(8));
        }

        $clinic = null;

        if ($publicId) {
            $clinic = VetRegisterationTemp::where('public_id', $publicId)->first();
        }

        if (! $clinic) {
            $clinic = VetRegisterationTemp::where('slug', $slugCandidate)->first();
        }

        if (! $clinic) {
            $clinic = VetRegisterationTemp::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
        }

        if (! $clinic) {
            return VetRegisterationTemp::create([
                'name' => $name,
                'slug' => $slugCandidate,
                'public_id' => $publicId,
                'city' => 'Pending',
                'pincode' => '000000',
                'status' => 'draft',
            ]);
        }

        $updated = false;

        if ($clinic->name !== $name) {
            $clinic->name = $name;
            $updated = true;
        }

        if ($slugCandidate && $clinic->slug !== $slugCandidate) {
            $clinic->slug = $slugCandidate;
            $updated = true;
        }

        if ($publicId && $clinic->public_id !== $publicId) {
            $clinic->public_id = $publicId;
            $updated = true;
        }

        if ($updated) {
            $clinic->save();
        }

        return $clinic->fresh();
    }
}
