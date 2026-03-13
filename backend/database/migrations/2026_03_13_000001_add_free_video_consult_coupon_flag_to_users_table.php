<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'has_used_free_video_consult_coupon')) {
                $table->boolean('has_used_free_video_consult_coupon')
                    ->default(false);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'has_used_free_video_consult_coupon')) {
                $table->dropColumn('has_used_free_video_consult_coupon');
            }
        });
    }
};
