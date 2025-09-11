<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_call_sessions_table.php
public function up()
{
    Schema::create('call_sessions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('patient_id');
        $table->unsignedBigInteger('doctor_id')->nullable();
        $table->string('channel_name');
        $table->enum('status', ['pending','accepted','ended'])->default('pending');
        $table->enum('payment_status', ['unpaid','paid'])->default('unpaid');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
    }
};
