<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->string('microchip_number')->nullable()->after('pet_gender');
            $table->string('mcd_registration_number')->nullable()->after('microchip_number');
            $table->enum('is_neutered', ['Y', 'N'])->nullable()->after('mcd_registration_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn([
                'microchip_number',
                'mcd_registration_number',
                'is_neutered',
            ]);
        });
    }
};

