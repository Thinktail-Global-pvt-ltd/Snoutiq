<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions') && !Schema::hasColumn('prescriptions', 'video_inclinic')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->string('video_inclinic')->nullable()->after('home_care');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'video_inclinic')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('video_inclinic');
            });
        }
    }
};
