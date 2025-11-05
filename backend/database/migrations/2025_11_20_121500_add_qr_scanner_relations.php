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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'qr_scanner_id')) {
                $table->foreignId('qr_scanner_id')
                    ->nullable()
                    ->constrained('legacy_qr_redirects')
                    ->nullOnDelete();
            }
        });

        Schema::table('call_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('call_sessions', 'qr_scanner_id')) {
                $afterColumn = Schema::hasColumn('call_sessions', 'doctor_id') ? 'doctor_id' : null;

                $column = $table->foreignId('qr_scanner_id')
                    ->nullable()
                    ->constrained('legacy_qr_redirects')
                    ->nullOnDelete();

                if ($afterColumn) {
                    $column->after($afterColumn);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('call_sessions', 'qr_scanner_id')) {
                $table->dropConstrainedForeignId('qr_scanner_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'qr_scanner_id')) {
                $table->dropConstrainedForeignId('qr_scanner_id');
            }
        });
    }
};

