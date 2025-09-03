<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // basic extra fields
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('pet_owner')->after('phone');
            }

            // pet related fields
            if (!Schema::hasColumn('users', 'pet_name')) {
                $table->string('pet_name', 120)->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'pet_gender')) {
                $table->string('pet_gender', 20)->nullable()->after('pet_name');
            }
            if (!Schema::hasColumn('users', 'pet_age')) {
                $table->unsignedTinyInteger('pet_age')->nullable()->after('pet_gender');
            }
            if (!Schema::hasColumn('users', 'pet_doc1')) {
                $table->string('pet_doc1')->nullable()->after('pet_age');
            }
            if (!Schema::hasColumn('users', 'pet_doc2')) {
                $table->string('pet_doc2')->nullable()->after('pet_doc1');
            }

            // token field
            if (!Schema::hasColumn('users', 'api_token_hash')) {
                $table->string('api_token_hash', 64)->nullable()->unique()->index()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'api_token_hash')) $table->dropColumn('api_token_hash');
            if (Schema::hasColumn('users', 'pet_doc2')) $table->dropColumn('pet_doc2');
            if (Schema::hasColumn('users', 'pet_doc1')) $table->dropColumn('pet_doc1');
            if (Schema::hasColumn('users', 'pet_age')) $table->dropColumn('pet_age');
            if (Schema::hasColumn('users', 'pet_gender')) $table->dropColumn('pet_gender');
            if (Schema::hasColumn('users', 'pet_name')) $table->dropColumn('pet_name');
            if (Schema::hasColumn('users', 'role')) $table->dropColumn('role');
            if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
        });
    }
};
