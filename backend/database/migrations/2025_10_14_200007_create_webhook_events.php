<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source');
            $table->string('event');
            $table->string('signature')->nullable();
            $table->longText('payload');
            $table->dateTimeTz('processed_at', 0)->nullable();
            $table->unsignedSmallInteger('retries')->default(0);
            $table->timestamps();

            // Composite unique; MySQL allows multiple NULLs for signature, which is acceptable here
            $table->unique(['source','event','signature'], 'uniq_webhook_source_event_sig');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};

