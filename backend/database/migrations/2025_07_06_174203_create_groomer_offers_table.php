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
        Schema::create('groomer_offers', function (Blueprint $table) {
            $table->id();
               $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 100);
            $table->decimal('discount', 5, 2);
            $table->enum('type', ['service', 'category']);
            $table->foreignId('service_id')->nullable()->constrained('groomer_services')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('groomer_service_categories')->onDelete('set null');
            $table->dateTime('expiry');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_offers');
    }
};
