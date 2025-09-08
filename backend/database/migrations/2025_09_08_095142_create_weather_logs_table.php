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
      Schema::create('weather_logs', function (Blueprint $table) {
    $table->id();
    $table->decimal('lat', 9, 6);
    $table->decimal('lon', 9, 6);
    $table->string('temperature')->nullable();
    $table->string('feels_like')->nullable();
    $table->string('humidity')->nullable();
    $table->string('weather')->nullable();
    $table->timestamp('time')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_logs');
    }
};
