<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'video_day_rate')) {
                $table->decimal('video_day_rate', 10, 2)->nullable()->after('doctors_price');
            }
            if (!Schema::hasColumn('doctors', 'video_night_rate')) {
                $table->decimal('video_night_rate', 10, 2)->nullable()->after('video_day_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'video_day_rate')) {
                $table->dropColumn('video_day_rate');
            }
            if (Schema::hasColumn('doctors', 'video_night_rate')) {
                $table->dropColumn('video_night_rate');
            }
        });
    }
};
