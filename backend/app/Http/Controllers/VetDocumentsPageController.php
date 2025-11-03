<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VetDocumentsPageController extends Controller
{
    private function clinicId(Request $request): ?int
    {
        return $request->session()->get('user_id')
            ?? data_get($request->session()->get('user'), 'id');
    }

    public function index(Request $request)
    {
        $vetId = $this->clinicId($request);
        if (!$vetId) {
            return redirect()->route('custom-doctor-login');
        }

        $clinic = DB::table('vet_registerations_temp')
            ->where('id', $vetId)
            ->select('id', 'name', 'license_no', 'license_document', 'updated_at')
            ->first();

        $doctors = DB::table('doctors')
            ->where('vet_registeration_id', $vetId)
            ->orderBy('doctor_name')
            ->get(['id', 'doctor_name', 'doctor_license', 'doctor_document', 'updated_at']);

        $page_title = 'Documents & Compliance';

        return view('snoutiq.vet-documents', [
            'clinic' => $clinic,
            'doctors' => $doctors,
            'page_title' => $page_title,
            'vetId' => $vetId,
        ]);
    }

    public function updateClinic(Request $request)
    {
        $vetId = $this->clinicId($request);
        if (!$vetId) {
            return redirect()->route('custom-doctor-login');
        }

        $validated = $request->validate([
            'license_no' => 'required|string|max:255',
            'license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $updates = [
            'license_no' => $validated['license_no'],
            'updated_at' => now(),
        ];

        if ($request->hasFile('license_document')) {
            File::ensureDirectoryExists(public_path('documents'));
            $file = $request->file('license_document');
            $filename = sprintf(
                'clinic_license_%d_%s_%s.%s',
                $vetId,
                now()->format('YmdHis'),
                Str::random(6),
                $file->extension()
            );
            $file->move(public_path('documents'), $filename);
            $updates['license_document'] = 'documents/' . $filename;
        }

        DB::table('vet_registerations_temp')
            ->where('id', $vetId)
            ->update($updates);

        return back()->with('status', 'Clinic documents saved successfully.');
    }

    public function updateDoctor(Request $request, int $doctor)
    {
        $vetId = $this->clinicId($request);
        if (!$vetId) {
            return redirect()->route('custom-doctor-login');
        }

        $doctorRow = DB::table('doctors')
            ->where('id', $doctor)
            ->where('vet_registeration_id', $vetId)
            ->first();

        if (!$doctorRow) {
            return back()->withErrors(['doctor' => 'Doctor not found for this clinic.']);
        }

        $validator = Validator::make($request->all(), [
            'doctor_license' => 'nullable|string|max:255',
            'doctor_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'doctor_' . $doctor)
                ->withInput()
                ->with('highlight_doctor', $doctor);
        }

        $updates = [
            'updated_at' => now(),
        ];

        if ($request->has('doctor_license')) {
            $licenseValue = $request->input('doctor_license');
            $updates['doctor_license'] = $licenseValue !== null && $licenseValue !== '' ? $licenseValue : null;
        }

        if ($request->hasFile('doctor_document')) {
            File::ensureDirectoryExists(public_path('documents/doctors'));
            $file = $request->file('doctor_document');
            $filename = sprintf(
                'doctor_document_%d_%d_%s_%s.%s',
                $vetId,
                $doctor,
                now()->format('YmdHis'),
                Str::random(6),
                $file->extension()
            );
            $file->move(public_path('documents/doctors'), $filename);
            $updates['doctor_document'] = 'documents/doctors/' . $filename;
        }

        DB::table('doctors')
            ->where('id', $doctor)
            ->update($updates);

        return back()
            ->with('status', 'Doctor credentials updated successfully.')
            ->with('highlight_doctor', $doctor);
    }
}
