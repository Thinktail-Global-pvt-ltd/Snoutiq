<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_daily_cares')) {
            return;
        }

        Schema::create('pet_daily_cares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id');
            $table->date('care_date');
            $table->string('task_key', 100)->nullable();
            $table->string('title', 255);
            $table->string('scheduled_time', 40)->nullable();
            $table->string('icon', 32)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pet_id', 'care_date']);
            $table->index(['user_id', 'care_date']);
            $table->index(['pet_id', 'care_date', 'sort_order']);
            $table->unique(['pet_id', 'care_date', 'title', 'scheduled_time'], 'pet_daily_care_unique_task');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pet_daily_cares')) {
            return;
        }

        Schema::dropIfExists('pet_daily_cares');
    }
};
