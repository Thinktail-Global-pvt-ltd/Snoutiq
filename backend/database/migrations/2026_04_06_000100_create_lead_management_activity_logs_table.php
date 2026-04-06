<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_management_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('event_type', 32)->index();
            $table->string('action_type', 120)->nullable();
            $table->string('outcome', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('event_at')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->string('assigned_to', 120)->nullable();
            $table->text('blocker')->nullable();
            $table->string('done_by', 120)->nullable();
            $table->string('created_by', 191)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type', 'event_at'], 'lead_mgmt_activity_user_type_event_idx');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('lead_management_activity_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex('lead_mgmt_activity_user_type_event_idx');
        });

        Schema::dropIfExists('lead_management_activity_logs');
    }
};
