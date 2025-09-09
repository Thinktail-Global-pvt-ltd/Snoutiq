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
       Schema::create('veterinary_practitioners', function (Blueprint $table) {
    $table->id();
    $table->integer('serial_no');
    $table->string('full_name');
    $table->string('father_or_husband_name')->nullable();
    $table->string('registration_no');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinary_practitioners');
    }
};
