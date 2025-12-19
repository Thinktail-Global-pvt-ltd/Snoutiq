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
        Schema::create('user_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('eating')->nullable();
            $table->string('appetite', 50)->nullable();
            $table->string('energy', 50)->nullable();
            $table->string('mood', 50)->nullable();
            $table->json('symptoms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('observed_at')->nullable()->index();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_observations');
    }
};
