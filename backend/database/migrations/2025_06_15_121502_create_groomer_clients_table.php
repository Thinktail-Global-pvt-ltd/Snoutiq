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
        Schema::create('groomer_clients', function (Blueprint $table) {
            $table->id();
            $table->string("tag");
            $table->string("name");
            $table->string("address")->nullable();
            $table->string("city")->nullable();
            $table->string("email")->nullable();
            $table->string("phone")->nullable();
            $table->string("pincode")->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_clients');
    }
};
