<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (!Schema::hasColumn('vet_registerations_temp', 'website_title')) {
                $table->string('website_title')->nullable();
            }
            if (!Schema::hasColumn('vet_registerations_temp', 'website_subtitle')) {
                $table->text('website_subtitle')->nullable();
            }
            if (!Schema::hasColumn('vet_registerations_temp', 'website_about')) {
                $table->text('website_about')->nullable();
            }
            if (!Schema::hasColumn('vet_registerations_temp', 'website_gallery')) {
                $table->json('website_gallery')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            if (Schema::hasColumn('vet_registerations_temp', 'website_title')) {
                $table->dropColumn('website_title');
            }
            if (Schema::hasColumn('vet_registerations_temp', 'website_subtitle')) {
                $table->dropColumn('website_subtitle');
            }
            if (Schema::hasColumn('vet_registerations_temp', 'website_about')) {
                $table->dropColumn('website_about');
            }
            if (Schema::hasColumn('vet_registerations_temp', 'website_gallery')) {
                $table->dropColumn('website_gallery');
            }
        });
    }
};
