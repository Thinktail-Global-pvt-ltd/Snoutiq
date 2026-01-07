<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VetUserConnectionReportPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $hasUsersLastVet = Schema::hasColumn('users', 'last_vet_id');
        $hasVetTable = Schema::hasTable('vet_registerations_temp');

        $metrics = [
            'total_connections' => 0,
            'unique_users' => 0,
            'unique_vets' => 0,
        ];

        $connections = collect();
        $clinicCounts = collect();

        if ($hasUsersLastVet && $hasVetTable) {
            $baseQuery = DB::table('users as u')
                ->join('vet_registerations_temp as v', 'u.last_vet_id', '=', 'v.id');

            $metrics['total_connections'] = (clone $baseQuery)->count();
            $metrics['unique_users'] = (clone $baseQuery)->distinct('u.id')->count('u.id');
            $metrics['unique_vets'] = (clone $baseQuery)->distinct('v.id')->count('v.id');

            $clinicCounts = (clone $baseQuery)
                ->select([
                    'v.id',
                    'v.name',
                    'v.city',
                    'v.pincode',
                    'v.status',
                    DB::raw('COUNT(DISTINCT u.id) as user_count'),
                ])
                ->groupBy('v.id', 'v.name', 'v.city', 'v.pincode', 'v.status')
                ->orderByDesc('user_count')
                ->get();

            $connections = (clone $baseQuery)
                ->select([
                    'u.id as user_id',
                    'u.name as user_name',
                    'u.phone as user_phone',
                    'u.email as user_email',
                    'u.last_vet_id',
                    'u.updated_at as user_updated_at',
                    'v.name as clinic_name',
                    'v.city as clinic_city',
                    'v.pincode as clinic_pincode',
                    'v.status as clinic_status',
                ])
                ->orderByDesc('u.updated_at')
                ->limit(500)
                ->get();
        }

        return view('admin.vet-user-connections', [
            'metrics' => $metrics,
            'connections' => $connections,
            'clinicCounts' => $clinicCounts,
            'hasUsersLastVet' => $hasUsersLastVet,
            'hasVetTable' => $hasVetTable,
            'isPublic' => (bool) $request->query('public', true),
            'maxRows' => 500,
        ]);
    }
}
