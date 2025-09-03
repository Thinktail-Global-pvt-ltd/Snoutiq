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
        Schema::create('groomer_coupons', function (Blueprint $table) {
             
   $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code', 20)->unique();
            $table->decimal('discount', 5, 2);
            $table->dateTime('expiry');
            $table->boolean('is_online')->default(true);
            $table->boolean('is_offline')->default(true);
            $table->timestamps();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_coupons');
    }
};
