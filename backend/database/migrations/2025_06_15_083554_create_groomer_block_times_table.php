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
        Schema::create('groomer_block_times', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->date("date");
            $table->string("start_time");
            $table->string("end_time");
                     $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('groomer_employees_id')->constrained('groomer_employees')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_block_times');
    }
};
