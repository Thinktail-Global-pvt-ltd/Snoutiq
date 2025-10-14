<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_provider')) {
                $table->string('payment_provider', 50)->nullable()->after('final_price');
            }
            if (!Schema::hasColumn('bookings', 'payment_order_id')) {
                $table->string('payment_order_id', 191)->nullable()->after('payment_provider')->index();
            }
            if (!Schema::hasColumn('bookings', 'payment_id')) {
                $table->string('payment_id', 191)->nullable()->after('payment_order_id')->index();
            }
            if (!Schema::hasColumn('bookings', 'payment_signature')) {
                $table->string('payment_signature', 191)->nullable()->after('payment_id');
            }
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_signature');
            }
            if (!Schema::hasColumn('bookings', 'payment_email')) {
                $table->string('payment_email', 191)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'payment_contact')) {
                $table->string('payment_contact', 30)->nullable()->after('payment_email');
            }
            if (!Schema::hasColumn('bookings', 'payment_currency')) {
                $table->string('payment_currency', 10)->nullable()->default('INR')->after('payment_contact');
            }
            if (!Schema::hasColumn('bookings', 'payment_raw')) {
                $table->json('payment_raw')->nullable()->after('payment_currency');
            }
            if (!Schema::hasColumn('bookings', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach ([
                'payment_verified_at','payment_raw','payment_currency','payment_contact','payment_email',
                'payment_method','payment_signature','payment_id','payment_order_id','payment_provider'
            ] as $col) {
                if (Schema::hasColumn('bookings', $col)) { $table->dropColumn($col); }
            }
        });
    }
};

