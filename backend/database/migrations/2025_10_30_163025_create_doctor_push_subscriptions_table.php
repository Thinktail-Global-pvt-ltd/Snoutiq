<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('endpoint')->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('platform')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_push_subscriptions');
    }
};
