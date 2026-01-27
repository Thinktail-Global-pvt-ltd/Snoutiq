<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'video_calling_upload_file')) {
                $table->text('video_calling_upload_file')->nullable()->after('pet_card_for_ai');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'video_calling_upload_file')) {
                $table->dropColumn('video_calling_upload_file');
            }
        });
    }
};
