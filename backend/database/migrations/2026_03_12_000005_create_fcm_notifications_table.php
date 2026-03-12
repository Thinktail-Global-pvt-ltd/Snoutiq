<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();
            $table->string('target_type', 32)->index();
            $table->string('notification_type', 64)->nullable()->index();
            $table->string('delivery_mode', 32)->nullable();

            $table->string('from_source', 255)->nullable()->index();
            $table->string('from_file', 255)->nullable();
            $table->unsignedInteger('from_line')->nullable();

            $table->string('to_target', 512)->nullable();
            $table->string('to_topic', 191)->nullable()->index();
            $table->unsignedBigInteger('device_token_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('owner_model', 255)->nullable();

            $table->string('title', 255)->nullable();
            $table->text('notification_text')->nullable();

            $table->string('provider_message_id', 191)->nullable()->index();
            $table->string('error_code', 120)->nullable()->index();
            $table->text('error_message')->nullable();

            $table->json('data_payload')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_notifications');
    }
};
