<?php

namespace App\Http\Controllers;

use App\Models\VetRegisterationTemp;
use App\Services\OnboardingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClinicWebsiteContentController extends Controller
{
    public function edit(Request $request, OnboardingProgressService $progress)
    {
        $clinic = $this->resolveClinic($request, $progress);
        if (!$clinic) {
            return redirect()->route('custom-doctor-login');
        }

        return view('clinic.website-content', [
            'clinic' => $clinic,
            'gallery' => $this->normalizeGallery($clinic->website_gallery),
        ]);
    }

    public function update(Request $request, OnboardingProgressService $progress)
    {
        $clinic = $this->resolveClinic($request, $progress);
        if (!$clinic) {
            return redirect()->route('custom-doctor-login');
        }

        $validated = $request->validate([
            'website_title' => 'nullable|string|max:120',
            'website_subtitle' => 'nullable|string|max:500',
            'website_about' => 'nullable|string|max:2000',
            'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'string',
        ]);

        $gallery = $this->normalizeGallery($clinic->website_gallery);
        $remove = $validated['remove_gallery'] ?? [];
        if (!empty($remove)) {
            $gallery = array_values(array_filter($gallery, function ($path) use ($remove) {
                return !in_array($path, $remove, true);
            }));

            foreach ($remove as $path) {
                $this->deleteGalleryFile($path);
            }
        }

        if ($request->hasFile('gallery_images')) {
            $targetDir = public_path('photo/clinic-website');
            File::ensureDirectoryExists($targetDir);

            foreach ((array) $request->file('gallery_images') as $file) {
                if (!$file) {
                    continue;
                }
                $fileName = 'clinic_' . $clinic->id . '_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(6)) . '.' . $file->extension();
                $file->move($targetDir, $fileName);
                $gallery[] = 'photo/clinic-website/' . $fileName;
            }
        }

        $clinic->website_title = $validated['website_title'] ?? null;
        $clinic->website_subtitle = $validated['website_subtitle'] ?? null;
        $clinic->website_about = $validated['website_about'] ?? null;
        $clinic->website_gallery = $gallery;
        $clinic->save();

        return back()->with('status', 'Website content updated.');
    }

    protected function resolveClinic(Request $request, OnboardingProgressService $progress): ?VetRegisterationTemp
    {
        $clinicId = $progress->resolveClinicId($request);
        if (!$clinicId) {
            return null;
        }

        return VetRegisterationTemp::find($clinicId);
    }

    protected function normalizeGallery($value): array
    {
        if (is_array($value)) {
            $gallery = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            $gallery = is_array($decoded) ? $decoded : [];
        } else {
            $gallery = [];
        }

        return array_values(array_filter($gallery, function ($item) {
            return is_string($item) && trim($item) !== '';
        }));
    }

    protected function deleteGalleryFile(string $path): void
    {
        if (!Str::startsWith($path, 'photo/clinic-website/')) {
            return;
        }

        $absolute = public_path($path);
        if (File::exists($absolute)) {
            File::delete($absolute);
        }
    }
}
