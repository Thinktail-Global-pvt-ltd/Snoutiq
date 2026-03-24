<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rag_symptom_checker_logs')) {
            return;
        }

        Schema::create('rag_symptom_checker_logs', function (Blueprint $table) {
            $table->id();
            $table->boolean('success')->default(false)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->string('pet_name', 255)->nullable()->index();
            $table->string('endpoint', 191)->nullable();
            $table->string('http_method', 16)->nullable();
            $table->longText('input_payload_json')->nullable();
            $table->longText('prefill_data_json')->nullable();
            $table->longText('form_values_json')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_data_json')->nullable();
            $table->longText('symptom_data_json')->nullable();
            $table->longText('full_response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable()->index();
            $table->unsignedSmallInteger('external_status_code')->nullable();
            $table->timestamp('logged_at')->nullable()->index();
            $table->timestamps();

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('pets')) {
                $table->foreign('pet_id')
                    ->references('id')
                    ->on('pets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_symptom_checker_logs');
    }
};

