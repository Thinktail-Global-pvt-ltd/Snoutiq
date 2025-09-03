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
        Schema::create('groomer_bookings', function (Blueprint $table) {
            $table->id();
            $table->integer("serial_number");
            $table->string("customer_type"); // walk-in // groomer // online
            $table->integer("customer_id");
            $table->integer("customer_pet_id");
            $table->date("date");
            $table->string("start_time");
            $table->string("end_time");
            $table->longtext("services");
            $table->float("total")->default(0);
            $table->float("paid")->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('groomer_employees_id')->constrained('groomer_employees')->onDelete('cascade');
            $table->string("status");

              $table->integer('is_inhome')->default(0); // inhome, atlocation, clinic
            $table->string('location')->default(json_encode(['lat' => null, 'lng' => null]));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_bookings');
    }
};
