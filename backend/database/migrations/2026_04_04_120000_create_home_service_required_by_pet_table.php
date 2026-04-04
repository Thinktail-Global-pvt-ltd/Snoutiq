<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_service_required_by_pet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained('pets')->nullOnDelete();

            $table->unsignedTinyInteger('latest_completed_step')->default(1);

            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('pet_type', 50)->nullable();
            $table->string('area')->nullable();
            $table->string('reason_for_visit')->nullable();

            $table->text('concern_description')->nullable();
            $table->json('symptoms')->nullable();
            $table->string('vaccination_status')->nullable();
            $table->string('last_deworming')->nullable();
            $table->text('past_illnesses_or_surgeries')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('known_allergies')->nullable();
            $table->text('vet_notes')->nullable();

            $table->string('payment_status', 20)->default('pending');
            $table->decimal('amount_payable', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('booking_reference')->nullable()->unique();

            $table->timestamp('step1_completed_at')->nullable();
            $table->timestamp('step2_completed_at')->nullable();
            $table->timestamp('step3_completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'latest_completed_step'], 'home_service_user_step_idx');
            $table->index('pet_id', 'home_service_pet_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_service_required_by_pet');
    }
};
