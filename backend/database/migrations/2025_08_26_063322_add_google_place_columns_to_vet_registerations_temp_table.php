<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            // Google Place fields
            $table->string('place_id')->nullable()->unique();
            $table->string('business_status')->nullable();
            $table->string('formatted_address')->nullable();

            // Location
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Viewport
            $table->decimal('viewport_ne_lat', 10, 7)->nullable();
            $table->decimal('viewport_ne_lng', 10, 7)->nullable();
            $table->decimal('viewport_sw_lat', 10, 7)->nullable();
            $table->decimal('viewport_sw_lng', 10, 7)->nullable();

            // Icons
            $table->string('icon')->nullable();
            $table->string('icon_background_color')->nullable();
            $table->string('icon_mask_base_uri')->nullable();

            // Opening Hours
            $table->boolean('open_now')->nullable();

            // Photos + Types (store JSON arrays)
            $table->json('photos')->nullable();
            $table->json('types')->nullable();

            // Codes
            $table->string('compound_code')->nullable();
            $table->string('global_code')->nullable();

            // Ratings
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('user_ratings_total')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn([
                'place_id',
                'business_status',
                'formatted_address',
                'lat', 'lng',
                'viewport_ne_lat', 'viewport_ne_lng',
                'viewport_sw_lat', 'viewport_sw_lng',
                'icon', 'icon_background_color', 'icon_mask_base_uri',
                'open_now',
                'photos', 'types',
                'compound_code', 'global_code',
                'rating', 'user_ratings_total'
            ]);
        });
    }
};
