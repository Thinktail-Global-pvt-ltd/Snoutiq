<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vet_feedback')) {
            return;
        }

        Schema::create('vet_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->string('source', 50)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('vet_id');
            $table->index('user_id');
            $table->index('pet_id');
            $table->index(['vet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vet_feedback')) {
            return;
        }

        Schema::dropIfExists('vet_feedback');
    }
};
