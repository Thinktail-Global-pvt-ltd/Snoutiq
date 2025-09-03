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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');         // user ke sath relation
            $table->string('context_token')->index();      // ek session track karne ke liye
            $table->text('question');                      // user ka sawal
            $table->longText('answer')->nullable();        // AI ka jawab
            $table->string('pet_name')->nullable();        // pet ka naam
            $table->string('pet_breed')->nullable();       // pet ka breed
            $table->string('pet_age')->nullable();         // pet ka age
            $table->string('pet_location')->nullable();    // pet ka location
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
