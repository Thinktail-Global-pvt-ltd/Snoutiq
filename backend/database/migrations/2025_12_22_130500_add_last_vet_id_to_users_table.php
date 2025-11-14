<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_vet_id')) {
                return;
            }

            $column = $table->unsignedBigInteger('last_vet_id')->nullable();

            if (Schema::hasColumn('users', 'last_vet_slug')) {
                $column->after('last_vet_slug');
            } elseif (Schema::hasColumn('users', 'referral_code')) {
                $column->after('referral_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_vet_id')) {
                return;
            }

            $table->dropColumn('last_vet_id');
        });
    }
};
