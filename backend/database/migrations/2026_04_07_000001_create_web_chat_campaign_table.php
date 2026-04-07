<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('web_chat_campaign')) {
            return;
        }

        Schema::create('web_chat_campaign', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedSmallInteger('turn')->default(1)->index();
            $table->string('routing', 30)->nullable()->index();
            $table->string('severity', 30)->nullable()->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('pet_name', 120)->nullable();
            $table->string('species', 30)->nullable();
            $table->string('breed', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->text('user_message')->nullable();
            $table->longText('assistant_message')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->longText('state_payload_json')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'id'], 'idx_web_chat_campaign_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_chat_campaign');
    }
};
