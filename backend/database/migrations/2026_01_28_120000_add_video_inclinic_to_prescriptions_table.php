<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('prescriptions', 'video_inclinic')) {
                    $table->string('video_inclinic')->nullable()->after('home_care');
                }
                if (!Schema::hasColumn('prescriptions', 'call_session')) {
                    $table->string('call_session')->nullable()->after('video_inclinic');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                if (Schema::hasColumn('prescriptions', 'call_session')) {
                    $table->dropColumn('call_session');
                }
                if (Schema::hasColumn('prescriptions', 'video_inclinic')) {
                    $table->dropColumn('video_inclinic');
                }
            });
        }
    }
};
