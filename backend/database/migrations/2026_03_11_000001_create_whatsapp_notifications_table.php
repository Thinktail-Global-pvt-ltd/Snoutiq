<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 32)->nullable()->index();
            $table->string('message_type', 32)->nullable()->index();
            $table->string('template_name', 120)->nullable()->index();
            $table->string('language_code', 32)->nullable();
            $table->string('status', 20)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('provider_message_id', 191)->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->text('error_details')->nullable();
            $table->string('source', 255)->nullable();
            $table->string('source_file', 255)->nullable();
            $table->unsignedInteger('source_line')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notifications');
    }
};
