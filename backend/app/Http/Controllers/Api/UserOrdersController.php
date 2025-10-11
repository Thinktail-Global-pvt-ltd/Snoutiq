<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserOrdersController extends Controller
{
    /**
     * GET /api/users/{id}/orders
     *
     * Query params (all optional):
     *  - status: pending|routing|accepted|in_progress|completed|cancelled|failed
     *  - since:  YYYY-MM-DD   (filters by booking_created_at >= since)
     *  - date_from / date_to: YYYY-MM-DD (filters by scheduled_for range)
     *  - limit: 1..100 (default 50)
     *  - page:  1..N   (default 1)
     */
    public function index(Request $request, string $id)
    {
        $userId = (int) $id;

        // Basic validation of query params
        $request->validate([
            'status'    => 'nullable|string|in:pending,routing,accepted,in_progress,completed,cancelled,failed',
            'since'     => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'limit'     => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $limit = (int) $request->query('limit', 50);
        $page  = (int) $request->query('page', 1);
        $offset = ($page - 1) * $limit;

        $q = DB::table('bookings as b')
            ->leftJoin('doctors as d', 'd.id', '=', 'b.assigned_doctor_id')
            ->leftJoin('vet_registerations_temp as c', 'c.id', '=', 'b.clinic_id')
            ->where('b.user_id', $userId);

        if ($status = $request->query('status')) {
            $q->where('b.status', $status);
        }

        if ($since = $request->query('since')) {
            $q->whereDate('b.booking_created_at', '>=', $since);
        }

        if ($df = $request->query('date_from')) {
            $q->whereDate('b.scheduled_for', '>=', $df);
        }
        if ($dt = $request->query('date_to')) {
            $q->whereDate('b.scheduled_for', '<=', $dt);
        }

        // Count total BEFORE pagination
        $total = (clone $q)->count();

        $rows = $q->select([
                'b.id',
                'b.user_id',
                'b.clinic_id',
                DB::raw('COALESCE(c.name, c.slug, CONCAT("Clinic #", c.id)) as clinic_name'),
                'b.assigned_doctor_id as doctor_id',
                DB::raw('COALESCE(d.doctor_name, CONCAT("Doctor #", d.id)) as doctor_name'),
                'b.service_type',
                'b.urgency',
                'b.status',
                'b.payment_status',
                'b.final_price',
                'b.scheduled_for',
                DB::raw('DATE(b.scheduled_for) as scheduled_date'),
                DB::raw('TIME_FORMAT(b.scheduled_for, "%H:%i:%s") as scheduled_time'),
                'b.booking_created_at as created_at',
            ])
            ->orderByDesc('b.booking_created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success'  => true,
            'user_id'  => $userId,
            'filters'  => [
                'status'    => $status ?? null,
                'since'     => $since ?? null,
                'date_from' => $request->query('date_from'),
                'date_to'   => $request->query('date_to'),
                'limit'     => $limit,
                'page'      => $page,
            ],
            'total'    => $total,
            'count'    => $rows->count(),
            'orders'   => $rows,
        ]);
    }
}
