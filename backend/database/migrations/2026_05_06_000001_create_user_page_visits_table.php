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
        Schema::create('user_page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('page_name');
            $table->string('session_id')->nullable()->index();
            $table->string('route_path')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('exited_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'page_name', 'exited_at']);
            $table->index(['user_id', 'entered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_page_visits');
    }
};
