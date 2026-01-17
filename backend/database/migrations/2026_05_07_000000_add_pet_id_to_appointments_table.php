<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'pet_id')) {
            return;
        }

        $hasPetsTable = Schema::hasTable('pets');

        Schema::table('appointments', function (Blueprint $table) use ($hasPetsTable) {
            $table->unsignedBigInteger('pet_id')->nullable()->after('doctor_id');
            if ($hasPetsTable) {
                $table->foreign('pet_id')
                    ->references('id')
                    ->on('pets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'pet_id')) {
            return;
        }

        $hasPetsTable = Schema::hasTable('pets');

        Schema::table('appointments', function (Blueprint $table) use ($hasPetsTable) {
            if ($hasPetsTable) {
                $table->dropForeign(['pet_id']);
            }
            $table->dropColumn('pet_id');
        });
    }
};
