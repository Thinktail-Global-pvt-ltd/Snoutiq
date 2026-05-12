<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pet_id')->nullable()->constrained('pets')->nullOnDelete();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->longText('summary');
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pet_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['pet_id', 'created_at']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE document_ai_analyses ADD COLUMN document_blob LONGBLOB NULL AFTER file_size');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE document_ai_analyses ADD COLUMN document_blob BYTEA NULL');
        } else {
            Schema::table('document_ai_analyses', function (Blueprint $table) {
                $table->binary('document_blob')->nullable()->after('file_size');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_analyses');
    }
};
