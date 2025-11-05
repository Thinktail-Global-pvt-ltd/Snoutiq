<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LegacyQrRedirect;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Zxing\QrReader;

class SalesCrmController extends Controller
{
    public function index()
    {
        $redirects = LegacyQrRedirect::orderByDesc('created_at')->paginate(50);
        $clinics = VetRegisterationTemp::select('id', 'name', 'public_id', 'slug')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('backend.sales.crm', [
            'apiEndpoint' => url('/api/clinics/drafts'),
            'redirects' => $redirects,
            'clinics' => $clinics,
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
            'public_id' => 'nullable|string|max:26',
            'clinic_id' => 'nullable|integer|exists:vet_registerations_temp,id',
            'slug' => 'nullable|string|max:255',
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

            if (empty($data['legacy_url']) && filter_var($decodedValue, FILTER_VALIDATE_URL)) {
                $data['legacy_url'] = $decodedValue;
            }
        }

        $publicId = $data['public_id'] ?? null;

        if (! $publicId && ! empty($data['clinic_id'])) {
            $publicId = VetRegisterationTemp::where('id', $data['clinic_id'])->value('public_id');
        }

        if (! $publicId && $request->filled('slug')) {
            $clinic = VetRegisterationTemp::where('slug', $request->input('slug'))->first();
            if ($clinic) {
                $publicId = $clinic->public_id;
                $data['clinic_id'] = $clinic->id;
            }
        }

        if (! $publicId && $request->filled('legacy_url')) {
            if (preg_match('~/c/([0-9A-Z]+)~i', $request->input('legacy_url'), $matches)) {
                $publicId = $matches[1];
            }
        }

        if (! $publicId && $decodedValue && preg_match('~/c/([0-9A-Z]+)~i', $decodedValue, $matches)) {
            $publicId = $matches[1];
        }

        if (! $publicId) {
            return redirect()->back()->withInput()->withErrors([
                'public_id' => 'Unable to resolve a clinic public ID. Provide a public_id, clinic_id, or existing slug.',
            ]);
        }

        $data['public_id'] = $publicId;
        unset($data['slug']);

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

        LegacyQrRedirect::create($data);

        $status = 'Legacy QR mapping saved.';
        if ($rawDecodedValue) {
            $status .= ' Decoded value: '.Str::limit($rawDecodedValue, 140, '…');
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
}
