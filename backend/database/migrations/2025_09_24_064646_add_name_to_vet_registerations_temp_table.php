<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'name' after 'image' (change position if you prefer)
        if (!Schema::hasColumn('vet_registerations_temp', 'name')) {
            Schema::table('vet_registerations_temp', function (Blueprint $table) {
                $table->string('name')->nullable()->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vet_registerations_temp', 'name')) {
            Schema::table('vet_registerations_temp', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
