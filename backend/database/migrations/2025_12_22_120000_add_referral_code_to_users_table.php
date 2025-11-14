<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referral_code')) {
                return;
            }

            $after = null;
            if (Schema::hasColumn('users', 'role')) {
                $after = 'role';
            } elseif (Schema::hasColumn('users', 'phone')) {
                $after = 'phone';
            } elseif (Schema::hasColumn('users', 'email')) {
                $after = 'email';
            }

            $column = $table->string('referral_code', 6)
                ->nullable()
                ->unique();

            if ($after) {
                $column->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referral_code')) {
                return;
            }

            $table->dropUnique('users_referral_code_unique');
            $table->dropColumn('referral_code');
        });
    }
};
