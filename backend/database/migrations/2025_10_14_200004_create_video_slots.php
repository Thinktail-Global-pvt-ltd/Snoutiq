<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('strip_id');
            $table->date('slot_date'); // UTC date for the hour window
            $table->unsignedTinyInteger('hour_24'); // 0..23 (UTC hour)
            $table->enum('role', ['primary', 'bench']);
            $table->enum('status', ['open','held','committed','in_progress','done','cancelled'])->default('open');
            $table->integer('payout_offer'); // paise
            $table->decimal('demand_score', 5, 2)->default(0);
            $table->unsignedBigInteger('committed_doctor_id')->nullable();
            $table->dateTimeTz('checkin_due_at', 0)->nullable(); // UTC
            $table->dateTimeTz('checked_in_at', 0)->nullable();  // UTC
            $table->dateTimeTz('in_progress_at', 0)->nullable(); // UTC
            $table->dateTimeTz('finished_at', 0)->nullable();    // UTC
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['strip_id','slot_date','hour_24','role'], 'uniq_strip_date_hour_role');
            $table->index(['status','slot_date','hour_24']);
            $table->foreign('strip_id')->references('id')->on('geo_strips')->onDelete('cascade');
            $table->foreign('committed_doctor_id')->references('id')->on('doctors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_slots');
    }
};

