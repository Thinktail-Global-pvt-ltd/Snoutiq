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
            if (! Schema::hasColumn('legacy_qr_redirects', 'qr_image_hash')) {
                $table->string('qr_image_hash', 128)->nullable()->unique()->after('qr_image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legacy_qr_redirects', function (Blueprint $table) {
            if (Schema::hasColumn('legacy_qr_redirects', 'qr_image_hash')) {
                $table->dropUnique(['qr_image_hash']);
                $table->dropColumn('qr_image_hash');
            }
        });
    }
};

