<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'recording_resource_id')) {
                $table->string('recording_resource_id')->nullable()->after('qr_scanner_id');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_sid')) {
                $table->string('recording_sid')->nullable()->after('recording_resource_id');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_status')) {
                $table->string('recording_status')->nullable()->after('recording_sid');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_started_at')) {
                $table->timestamp('recording_started_at')->nullable()->after('recording_status');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_ended_at')) {
                $table->timestamp('recording_ended_at')->nullable()->after('recording_started_at');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_file_list')) {
                $table->json('recording_file_list')->nullable()->after('recording_ended_at');
            }

            if (!Schema::hasColumn('call_sessions', 'recording_url')) {
                $table->text('recording_url')->nullable()->after('recording_file_list');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_status')) {
                $table->string('transcript_status')->nullable()->after('recording_url');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_text')) {
                $table->longText('transcript_text')->nullable()->after('transcript_status');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_url')) {
                $table->text('transcript_url')->nullable()->after('transcript_text');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_requested_at')) {
                $table->timestamp('transcript_requested_at')->nullable()->after('transcript_url');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_completed_at')) {
                $table->timestamp('transcript_completed_at')->nullable()->after('transcript_requested_at');
            }

            if (!Schema::hasColumn('call_sessions', 'transcript_error')) {
                $table->text('transcript_error')->nullable()->after('transcript_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            foreach ([
                'recording_resource_id',
                'recording_sid',
                'recording_status',
                'recording_started_at',
                'recording_ended_at',
                'recording_file_list',
                'recording_url',
                'transcript_status',
                'transcript_text',
                'transcript_url',
                'transcript_requested_at',
                'transcript_completed_at',
                'transcript_error',
            ] as $column) {
                if (Schema::hasColumn('call_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
