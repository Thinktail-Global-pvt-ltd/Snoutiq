<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->string('mobile')->nullable()->after('email');
            $table->string('image')->nullable()->after('mobile'); // store image filename/path
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'image']);
        });
    }
};
