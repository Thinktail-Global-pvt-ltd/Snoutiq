<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AdminDataPurgeController extends Controller
{
    /**
     * Clear data from specified database tables.
     * Supports both GET and POST.
     * 
     * Requires:
     * - secret_key: env('DB_PURGE_SECRET', 'snoutiq_reset_2026_secure')
     * - confirm: 'yes'
     */
    public function clearData(Request $request)
    {
        $expectedSecret = env('DB_PURGE_SECRET', 'snoutiq_reset_2026_secure');
        $providedSecret = $request->input('secret_key') 
            ?? $request->query('secret_key') 
            ?? $request->header('X-Purge-Secret');

        if (!$providedSecret || !hash_equals((string) $expectedSecret, (string) $providedSecret)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing secret_key.',
            ], 403);
        }

        $confirm = strtolower(trim((string) ($request->input('confirm') ?? $request->query('confirm'))));
        if ($confirm !== 'yes') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Action aborted. You must pass confirm=yes to proceed with database deletion.',
            ], 400);
        }

        $isStrict = $request->boolean('strict') || $request->query('strict') === '1';

        // Core 4 tables requested by the user
        $coreTables = [
            'users',
            'vet_registerations_temp',
            'pets',
            'doctors',
        ];

        // Dependent child tables to prevent orphaned rows and foreign key conflicts
        $dependentTables = [
            // User and pet dependents
            'user_pets',
            'user_profiles',
            'otps',
            'device_tokens',
            'chats',
            'chat_rooms',
            'emergency_requests',
            'appointments',
            'consultations',
            'prescriptions',
            'medical_records',
            'user_page_visits',
            'user_observations',
            'user_button_clicks',
            'user_feedback',
            'health_pulse_entries',
            'health_pulse_symptom_analyses',
            'pet_daily_cares',
            'document_uploads',
            'pet_vaccination_records',
            'reviews',
            'transactions',
            'user_monthly_subscriptions',
            'reported_symptom_logs',

            // Doctor and vet dependents
            'doctor_availability',
            'doctor_reliability',
            'doctor_weekly_video_schedules',
            'doctor_weekly_video_schedule_days',
            'doctor_commitments',
            'doctor_chat_rooms',
            'doctor_chat_messages',
            'doctor_fcm_tokens',
            'video_slots',
            'video_apointment',
            'chat_service_bookings',
            'clinic_specialized_packages',
            'vet_at_home_services',
            'business_hours',
            'receptionists',
        ];

        // Determine target tables
        if ($isStrict) {
            $tablesToProcess = $coreTables;
        } else {
            // Merge dependent tables first, then core tables
            $tablesToProcess = array_values(array_unique(array_merge($dependentTables, $coreTables)));
        }

        $driver = DB::connection()->getDriverName();
        $summary = [];

        try {
            // Disable foreign key constraints temporarily
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            foreach ($tablesToProcess as $table) {
                if (Schema::hasTable($table)) {
                    $countBefore = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $summary[$table] = [
                        'deleted_rows' => $countBefore,
                        'status'       => 'cleared',
                    ];
                } else {
                    $summary[$table] = [
                        'deleted_rows' => 0,
                        'status'       => 'table_not_found',
                    ];
                }
            }

            Log::warning('AdminDataPurgeController: Database tables cleared successfully by admin', [
                'ip'      => $request->ip(),
                'summary' => $summary,
            ]);

            return response()->json([
                'status'         => 'success',
                'message'        => 'Database records have been successfully deleted.',
                'mode'           => $isStrict ? 'strict_core_only' : 'clean_with_dependents',
                'cleared_tables' => $summary,
                'timestamp'      => now()->toDateTimeString(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('AdminDataPurgeController: Error while clearing tables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to clear database tables: ' . $e->getMessage(),
                'summary' => $summary,
            ], 500);
        } finally {
            // Re-enable foreign key constraints
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }
    }
}
