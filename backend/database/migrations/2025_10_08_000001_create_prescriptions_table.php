<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('doctor_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->longText('content_html');
            $table->timestamps();

            // If your users table holds doctors and patients, you can
            // uncomment foreign keys. Kept as indexes to avoid conflicts.
            // $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};

