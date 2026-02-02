<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->string('record_type', 100)->nullable();
            $table->string('record_label', 150)->nullable();
            $table->string('source', 60)->nullable();
            $table->unsignedInteger('file_count')->default(0);
            $table->json('files_json')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('pets')) {
                $table->foreign('pet_id')
                    ->references('id')->on('pets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_uploads');
    }
};
