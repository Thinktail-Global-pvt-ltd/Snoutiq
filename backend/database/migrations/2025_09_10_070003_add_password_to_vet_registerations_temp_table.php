<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            // Add password column (plain text)
            $table->string('password')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
