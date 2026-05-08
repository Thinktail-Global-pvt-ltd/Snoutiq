<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfReady('fcm_notifications', ['user_id', 'notification_type', 'id'], 'fcm_user_type_id_idx');
        $this->addIndexIfReady('fcm_notifications', ['user_id', 'sent_at', 'id'], 'fcm_user_sent_id_idx');
        $this->addIndexIfReady('prescriptions', ['user_id', 'call_session', 'follow_up_date', 'id'], 'presc_user_session_follow_idx');
        $this->addIndexIfReady('transactions', ['user_id', 'type', 'call_session', 'id'], 'txn_user_type_call_idx');
        $this->addIndexIfReady('transactions', ['user_id', 'channel_name', 'id'], 'txn_user_channel_idx');
        $this->addIndexIfReady('lead_management_activity_logs', ['user_id', 'event_at', 'id'], 'lead_logs_user_event_idx');
        $this->addIndexIfReady('pets', ['user_id', 'created_at', 'id'], 'pets_user_created_idx');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('fcm_notifications', 'fcm_user_type_id_idx');
        $this->dropIndexIfExists('fcm_notifications', 'fcm_user_sent_id_idx');
        $this->dropIndexIfExists('prescriptions', 'presc_user_session_follow_idx');
        $this->dropIndexIfExists('transactions', 'txn_user_type_call_idx');
        $this->dropIndexIfExists('transactions', 'txn_user_channel_idx');
        $this->dropIndexIfExists('lead_management_activity_logs', 'lead_logs_user_event_idx');
        $this->dropIndexIfExists('pets', 'pets_user_created_idx');
    }

    private function addIndexIfReady(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return ! empty($rows);
    }
};
