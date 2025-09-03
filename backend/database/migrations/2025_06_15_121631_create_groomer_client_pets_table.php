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
        Schema::create('groomer_client_pets', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("type");
            $table->string("breed");
            $table->string("dob");
            $table->string("gender");
            $table->string("medicalHistory")->nullable();
            $table->string("vaccinationLog")->nullable();

                            $table->foreignId('user_id')->constrained()->onDelete('cascade');
                            $table->foreignId('groomer_client_id')->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_client_pets');
    }
};
