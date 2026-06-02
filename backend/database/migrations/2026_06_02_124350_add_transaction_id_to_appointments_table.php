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
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'transaction_id')) {
            return;
        }

        $hasTransactionsTable = Schema::hasTable('transactions');

        Schema::table('appointments', function (Blueprint $table) use ($hasTransactionsTable) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('pet_id')->index();

            if ($hasTransactionsTable) {
                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('transactions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'transaction_id')) {
            return;
        }

        $hasTransactionsTable = Schema::hasTable('transactions');

        Schema::table('appointments', function (Blueprint $table) use ($hasTransactionsTable) {
            if ($hasTransactionsTable) {
                $table->dropForeign(['transaction_id']);
            }
            $table->dropColumn('transaction_id');
        });
    }
};
