<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'vet_registerations_temp',
            'doctors',
            'receptionists',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'api_token_expires_at')) {
                    $table->timestamp('api_token_expires_at')->nullable()->after('api_token_hash');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'vet_registerations_temp',
            'doctors',
            'receptionists',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'api_token_expires_at')) {
                    $table->dropColumn('api_token_expires_at');
                }
            });
        }
    }
};
