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
        Schema::create('groomer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("bio");
            $table->string("address");
            $table->string("coordinates");
            $table->string("city");
            $table->string("pincode");
            $table->string("license_no");
            $table->string("profile_picture")->nullable();
                        $table->integer("inhome_grooming_services")->default(0);
                         $table->integer("status")->default(0);
$table->string("type");
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groomer_profiles');
    }
};
