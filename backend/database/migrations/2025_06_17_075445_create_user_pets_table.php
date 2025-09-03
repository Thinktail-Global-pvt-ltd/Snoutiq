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
        Schema::create('user_pets', function (Blueprint $table) {
            $table->id();
              $table->string("name");
            $table->string("type");
            $table->string("breed");
            $table->string("dob");
            $table->string("gender");
            $table->string("pic_link")->nullable();
            $table->string("medical_history")->default("[]");
            $table->string("vaccination_log")->default("[]");
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pets');
    }
};
