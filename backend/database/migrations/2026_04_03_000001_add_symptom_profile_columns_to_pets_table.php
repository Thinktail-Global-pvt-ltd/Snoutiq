<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'dob')) {
                $table->date('dob')->nullable();
            }

            if (!Schema::hasColumn('pets', 'neutered')) {
                $table->enum('neutered', ['yes', 'no', 'unknown'])->default('unknown');
            }

            if (!Schema::hasColumn('pets', 'species')) {
                $table->string('species', 20)->default('dog');
            }

            if (!Schema::hasColumn('pets', 'location')) {
                $table->string('location', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pets')) {
            return;
        }

        Schema::table('pets', function (Blueprint $table) {
            if (Schema::hasColumn('pets', 'location')) {
                $table->dropColumn('location');
            }

            if (Schema::hasColumn('pets', 'species')) {
                $table->dropColumn('species');
            }

            if (Schema::hasColumn('pets', 'neutered')) {
                $table->dropColumn('neutered');
            }

            if (Schema::hasColumn('pets', 'dob')) {
                $table->dropColumn('dob');
            }
        });
    }
};

