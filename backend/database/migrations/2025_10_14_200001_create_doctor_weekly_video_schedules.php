<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_weekly_video_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedTinyInteger('avg_consult_minutes')->default(20);
            $table->unsignedTinyInteger('max_bookings_per_hour')->default(3);
            $table->boolean('is_247')->default(false);
            $table->timestamps();

            $table->unique(['doctor_id'], 'uniq_doctor_weekly_video_schedule');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_weekly_video_schedules');
    }
};

