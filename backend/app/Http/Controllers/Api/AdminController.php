<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // GET /api/admin/tasks?status=open
    public function tasks(Request $request)
    {
        $status = $request->query('status', 'open');
        $tasks = DB::table('recruitment_tasks')
            ->where('status', $status)
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->limit(100)
            ->get();
        return response()->json(['tasks' => $tasks]);
    }

    // GET /api/admin/alerts?resolved=0
    public function alerts(Request $request)
    {
        $resolved = $request->query('resolved', '0');
        $alerts = DB::table('system_alerts')
            ->where('resolved', (bool) $resolved)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        return response()->json(['alerts' => $alerts]);
    }

    // POST /api/admin/resolve-alert/{id}
    public function resolveAlert(string $id)
    {
        DB::table('system_alerts')->where('id', $id)->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
        return response()->json(['message' => 'Alert resolved']);
    }

    // GET /api/admin/providers-queue
    public function providersQueue()
    {
        $providers = DB::table('providers')->where('status', 'registered')->orderBy('created_at')->get();
        return response()->json(['providers' => $providers]);
    }

    // GET /api/admin/analytics?period=30
    public function analytics(Request $request)
    {
        $period = (int) $request->query('period', 30);
        $from = now()->subDays($period);
        $stats = DB::table('bookings')
            ->selectRaw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                          COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_bookings,
                          AVG(rating) as avg_rating,
                          SUM(final_price) as total_revenue")
            ->where('booking_created_at', '>=', $from)
            ->first();
        $activeProviders = DB::table('bookings')->where('booking_created_at', '>=', $from)->whereNotNull('assigned_provider_id')->distinct('assigned_provider_id')->count('assigned_provider_id');
        return response()->json(['analytics' => array_merge((array) $stats, ['active_providers' => $activeProviders])]);
    }
}

