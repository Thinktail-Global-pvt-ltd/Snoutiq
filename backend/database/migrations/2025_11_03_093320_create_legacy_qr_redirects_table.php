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
        Schema::create('legacy_qr_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('legacy_url')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->string('public_id', 26)->nullable()->index();
            $table->string('target_url')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_qr_redirects');
    }
};
