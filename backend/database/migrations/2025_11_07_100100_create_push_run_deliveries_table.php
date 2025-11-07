<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_run_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('push_run_id');
            $table->string('device_id')->nullable();
            $table->string('platform', 16)->nullable();
            $table->string('status', 16);
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->json('fcm_response_snippet')->nullable();
            $table->timestamps();

            $table->foreign('push_run_id')
                ->references('id')
                ->on('push_runs')
                ->cascadeOnDelete();

            $table->index('push_run_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_run_deliveries');
    }
};

