<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->unsignedTinyInteger('points'); // 1..5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

