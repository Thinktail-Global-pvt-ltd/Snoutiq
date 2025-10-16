<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_commitments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('doctor_id');
            $table->dateTimeTz('committed_at', 0);
            $table->dateTimeTz('released_at', 0)->nullable();
            $table->boolean('fulfilled')->default(false);
            $table->string('cancel_reason')->nullable();
            $table->json('raw_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['slot_id','doctor_id'], 'uniq_slot_doctor');
            $table->foreign('slot_id')->references('id')->on('video_slots')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->index(['doctor_id','fulfilled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_commitments');
    }
};

