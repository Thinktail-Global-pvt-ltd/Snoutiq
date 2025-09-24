<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            // Slug column
            $table->string('slug')->unique()->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('vet_registrations_temp', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
