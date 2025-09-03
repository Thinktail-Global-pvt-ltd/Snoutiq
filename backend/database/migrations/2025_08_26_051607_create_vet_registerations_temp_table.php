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
        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('pincode', 20);
            $table->string('license_no')->nullable();
            $table->json('coordinates')->nullable(); // storing lat/lng as JSON
            $table->text('address')->nullable();
            $table->decimal('chat_price', 10, 2)->nullable();
            $table->text('bio')->nullable();
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vet_registerations_temp');
    }
};
