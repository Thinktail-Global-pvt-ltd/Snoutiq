<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reported_symptom_logs')) {
            return;
        }

        Schema::table('reported_symptom_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('reported_symptom_logs', 'image_blob')) {
                $table->longBlob('image_blob')->nullable()->after('reported_symptom');
            }
            if (! Schema::hasColumn('reported_symptom_logs', 'image_mime')) {
                $table->string('image_mime')->nullable()->after('image_blob');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reported_symptom_logs')) {
            return;
        }

        Schema::table('reported_symptom_logs', function (Blueprint $table) {
            if (Schema::hasColumn('reported_symptom_logs', 'image_mime')) {
                $table->dropColumn('image_mime');
            }
            if (Schema::hasColumn('reported_symptom_logs', 'image_blob')) {
                $table->dropColumn('image_blob');
            }
        });
    }
};
