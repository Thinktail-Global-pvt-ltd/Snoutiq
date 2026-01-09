<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_service_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->string('name', 120);
            $table->timestamps();

            $table->index('clinic_id');
            $table->unique(['clinic_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service_presets');
    }
};
