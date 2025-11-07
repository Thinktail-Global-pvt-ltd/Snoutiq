<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('schedule_id')
                ->nullable()
                ->constrained('scheduled_push_notifications')
                ->nullOnDelete();
            $table->string('trigger', 32);
            $table->string('title');
            $table->text('body')->nullable();
            $table->unsignedInteger('targeted_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('code_path')->nullable();
            $table->string('log_file')->nullable();
            $table->string('job_id')->nullable();
            $table->json('sample_device_ids')->nullable();
            $table->json('sample_errors')->nullable();
            $table->timestamps();

            $table->index('schedule_id');
            $table->index('trigger');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_runs');
    }
};

