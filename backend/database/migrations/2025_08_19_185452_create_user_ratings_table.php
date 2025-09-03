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
        Schema::create('user_ratings', function (Blueprint $table) {
            $table->id();
                       $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

                         $table->foreignId('servicer_id')->constrained('users')->onDelete('cascade');
                        $table->foreignId('groomer_booking_id')->constrained('groomer_bookings')->onDelete('cascade');
                        $table->string("review");
                        $table->integer("rating");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ratings');
    }
};
