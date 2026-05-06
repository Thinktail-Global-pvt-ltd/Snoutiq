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
        Schema::create('user_button_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('page_visit_id')->nullable()->constrained('user_page_visits')->nullOnDelete();
            $table->string('page_name');
            $table->string('button_name');
            $table->string('button_id')->nullable();
            $table->string('button_text')->nullable();
            $table->string('action_name')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->string('route_path')->nullable();
            $table->string('url', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('clicked_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'page_name', 'button_name']);
            $table->index(['page_name', 'button_name']);
            $table->index(['user_id', 'clicked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_button_clicks');
    }
};
