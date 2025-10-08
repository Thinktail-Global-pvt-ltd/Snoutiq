<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon .. 7=Sun
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['vet_registeration_id','day_of_week']);
            $table->foreign('vet_registeration_id')->references('id')->on('vet_registerations_temp')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};

