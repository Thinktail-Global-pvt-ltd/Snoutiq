<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_vaccination_documents')) {
            return;
        }

        Schema::create('pet_vaccination_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pet_id')->unique();
            $table->string('document_mime', 120)->nullable();
            $table->string('document_name')->nullable();
            $table->unsignedInteger('document_size')->nullable();
            $table->timestamps();
        });

        // Add binary/blob column based on the driver
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE pet_vaccination_documents ADD COLUMN document_blob LONGBLOB NULL AFTER pet_id");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pet_vaccination_documents ADD COLUMN document_blob BYTEA NULL');
        } else {
            Schema::table('pet_vaccination_documents', function (Blueprint $table) {
                $table->binary('document_blob')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_vaccination_documents');
    }
};
