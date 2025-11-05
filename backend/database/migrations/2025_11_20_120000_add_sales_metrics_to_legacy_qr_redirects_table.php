<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('legacy_qr_redirects', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('legacy_qr_redirects', 'qr_image_path') ? 'qr_image_path' : 'notes';

            if (!Schema::hasColumn('legacy_qr_redirects', 'status')) {
                $table->string('status', 32)->default('inactive')->index()->after($afterColumn);
            }

            if (!Schema::hasColumn('legacy_qr_redirects', 'scan_count')) {
                $table->unsignedInteger('scan_count')->default(0)->after('status');
            }

            if (!Schema::hasColumn('legacy_qr_redirects', 'last_scanned_at')) {
                $table->timestamp('last_scanned_at')->nullable()->after('scan_count');
            }

            if (!Schema::hasColumn('legacy_qr_redirects', 'last_registration_at')) {
                $table->timestamp('last_registration_at')->nullable()->after('last_scanned_at');
            }

            if (!Schema::hasColumn('legacy_qr_redirects', 'last_transaction_at')) {
                $table->timestamp('last_transaction_at')->nullable()->after('last_registration_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legacy_qr_redirects', function (Blueprint $table) {
            $columns = [
                'last_transaction_at',
                'last_registration_at',
                'last_scanned_at',
                'scan_count',
                'status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('legacy_qr_redirects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
