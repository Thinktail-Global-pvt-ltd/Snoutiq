<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_vet_slug')) {
                return;
            }

            $column = $table->string('last_vet_slug', 255)->nullable();

            if (Schema::hasColumn('users', 'referral_code')) {
                $column->after('referral_code');
            } elseif (Schema::hasColumn('users', 'email')) {
                $column->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_vet_slug')) {
                return;
            }

            $table->dropColumn('last_vet_slug');
        });
    }
};
