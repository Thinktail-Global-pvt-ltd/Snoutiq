<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_push_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheduled_push_notification_id');
            $table->unsignedBigInteger('device_token_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('token', 512);
            $table->json('payload')->nullable();
            $table->timestamp('dispatched_at')->useCurrent();
            $table->timestamps();

            $table->index(['scheduled_push_notification_id', 'dispatched_at'], 'spn_dispatch_idx');

            $table->foreign('scheduled_push_notification_id', 'spn_dispatch_notification_fk')
                ->references('id')
                ->on('scheduled_push_notifications')
                ->cascadeOnDelete();
            $table->foreign('device_token_id', 'spn_dispatch_device_fk')
                ->references('id')
                ->on('device_tokens')
                ->nullOnDelete();
            $table->foreign('user_id', 'spn_dispatch_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_push_dispatch_logs');
    }
};
