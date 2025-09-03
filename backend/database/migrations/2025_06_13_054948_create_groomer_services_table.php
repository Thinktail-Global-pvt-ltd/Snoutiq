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
        Schema::create('groomer_services', function (Blueprint $table) {
            $table->id();
             // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('groomer_service_category_id')->constrained('groomer_service_categories')->onDelete('cascade');

           
            $table->string('name');
            $table->text('description')->nullable();
            // $table->enum('pet_type', ['Dog', 'Cat', 'Other']); 
                        $table->string('pet_type');

            $table->decimal('price', 8, 2); 
            $table->integer('duration'); // in minutes
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
             $table->string('service_pic')->nullable();  


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_services');
    }
};
