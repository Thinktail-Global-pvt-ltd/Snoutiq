<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_session_id')
                ->nullable()
                ->constrained('call_sessions')
                ->cascadeOnDelete();
            $table->string('call_identifier', 64)->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->string('recording_disk')->default('s3');
            $table->string('recording_path')->nullable()->index();
            $table->string('recording_name')->nullable();
            $table->text('recording_url')->nullable();
            $table->string('recording_status')->nullable();
            $table->unsignedBigInteger('recording_size')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_recordings');
    }
};
