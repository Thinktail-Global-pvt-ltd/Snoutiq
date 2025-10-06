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
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->longText('content')->nullable();
        $table->text('excerpt')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->string('featured_image')->nullable();
        $table->string('status')->default('published'); // published|draft
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('posts'); }

};
