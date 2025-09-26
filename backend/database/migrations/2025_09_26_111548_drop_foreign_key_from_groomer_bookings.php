<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('groomer_bookings', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign('groomer_bookings_groomer_employees_id_foreign');
        });
    }

    public function down()
    {
        Schema::table('groomer_bookings', function (Blueprint $table) {
            // Re-add the foreign key if rollback
            $table->foreign('groomer_employees_id')
                  ->references('id')->on('groomer_employees')
                  ->onDelete('cascade');
        });
    }
};
