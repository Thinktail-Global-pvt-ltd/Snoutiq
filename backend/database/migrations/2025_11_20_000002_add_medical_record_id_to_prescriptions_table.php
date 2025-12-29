<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'medical_record_id')) {
                $table->unsignedBigInteger('medical_record_id')->nullable()->after('id');
                $table->index('medical_record_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'medical_record_id')) {
                $table->dropIndex(['medical_record_id']);
                $table->dropColumn('medical_record_id');
            }
        });
    }
};
