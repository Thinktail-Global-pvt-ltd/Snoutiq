<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->string('api_token_hash', 255)
                  ->nullable()
                  ->after('password'); // 👈 password ke baad column add hoga
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn('api_token_hash');
        });
    }
};
