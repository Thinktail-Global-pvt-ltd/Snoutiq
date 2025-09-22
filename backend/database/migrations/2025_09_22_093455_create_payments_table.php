<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('razorpay_order_id', 191)->index();
            $table->string('razorpay_payment_id', 191)->unique();
            $table->string('razorpay_signature', 191);

            $table->unsignedBigInteger('amount')->nullable(); // in paisa
            $table->string('currency', 10)->default('INR');
            $table->string('status', 50)->nullable();          // created/authorized/captured/failed...
            $table->string('method', 50)->nullable();          // card/netbanking/upi...
            $table->string('email', 191)->nullable();
            $table->string('contact', 30)->nullable();

            $table->json('notes')->nullable();
            $table->json('raw_response')->nullable();          // full payment->toArray()

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
