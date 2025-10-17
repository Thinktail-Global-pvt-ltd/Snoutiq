<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_weekly_video_schedule_days', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedTinyInteger('dow'); // 0=Sunday..6=Saturday
            $table->boolean('active')->default(true);
            $table->time('start_time')->nullable(); // stored in UTC as time-of-day
            $table->time('end_time')->nullable();   // stored in UTC as time-of-day
            $table->time('break_start_time')->nullable(); // stored in UTC
            $table->time('break_end_time')->nullable();   // stored in UTC
            $table->timestamps();

            $table->unique(['schedule_id', 'dow'], 'uniq_schedule_dow');
            $table->foreign('schedule_id')->references('id')->on('doctor_weekly_video_schedules')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_weekly_video_schedule_days');
    }
};

