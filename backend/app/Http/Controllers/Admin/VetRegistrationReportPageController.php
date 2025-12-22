<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VetRegistrationReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class VetRegistrationReportPageController extends Controller
{
    public function __invoke(Request $request, VetRegistrationReportService $service): View
    {
        $summary = $service->monthlySummary();
        $defaultMonth = null; // show all data by default
        $isPublic = (bool) $request->query('public', false);

        return view('admin.vet-registration-report', [
            'months' => $summary,
            'defaultMonth' => $defaultMonth,
            // Use the public API alias so the page works without admin auth too.
            'apiUrl' => route('api.vet-registrations.report.public'),
            'isPublic' => $isPublic,
        ]);
    }
}
