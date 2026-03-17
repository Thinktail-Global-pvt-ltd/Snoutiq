<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_feedback')) {
            return;
        }

        Schema::create('pet_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id');
            $table->unsignedBigInteger('vet_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->string('source', 50)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('pet_id');
            $table->index('vet_id');
            $table->index('user_id');
            $table->index(['pet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pet_feedback')) {
            return;
        }

        Schema::dropIfExists('pet_feedback');
    }
};
