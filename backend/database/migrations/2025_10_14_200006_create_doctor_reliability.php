<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_reliability', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_id')->primary();
            $table->decimal('reliability_score', 3, 2)->default(0.80); // 0.00 - 9.99 (we use 0-1)
            $table->integer('no_show_count')->default(0);
            $table->decimal('on_time_rate', 4, 3)->default(0.950); // 0-1
            $table->integer('median_connect_ms')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_reliability');
    }
};

