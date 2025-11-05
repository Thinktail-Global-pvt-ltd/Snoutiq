<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('legacy_qr_redirects') && Schema::hasColumn('legacy_qr_redirects', 'scan_count')) {
            DB::table('legacy_qr_redirects')->whereNull('scan_count')->update(['scan_count' => 0]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};

