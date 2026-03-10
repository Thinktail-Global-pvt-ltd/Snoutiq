<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_status_conversion_logs')) {
            return;
        }

        Schema::create('transaction_status_conversion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->string('previous_status', 50);
            $table->string('new_status', 50);
            $table->unsignedBigInteger('changed_by_user_id')->nullable()->index();
            $table->string('changed_by_name')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_status_conversion_logs')) {
            return;
        }

        Schema::table('transaction_status_conversion_logs', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
        });

        Schema::dropIfExists('transaction_status_conversion_logs');
    }
};

