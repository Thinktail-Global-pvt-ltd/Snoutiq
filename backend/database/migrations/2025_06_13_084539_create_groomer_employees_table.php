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
        Schema::create('groomer_employees', function (Blueprint $table) {
            $table->id();
                     $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->date('dob');
            $table->string('calendar_color');
            $table->string('job_title');
            $table->text('notes')->nullable();
            $table->json('services'); // Array of groomer_service IDs
            $table->enum('type', ['salary', 'commission']);
            $table->decimal('monthly_salary', 8, 2)->nullable();
            $table->json('commissions')->nullable(); // Object mapping service IDs to commission percentages
            $table->text('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_employees');
    }
};
