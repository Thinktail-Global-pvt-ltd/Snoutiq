<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('geo_pincodes', function (Blueprint $t) {
            $t->id();
            $t->string('pincode', 6)->index();
            $t->string('label')->nullable();    // friendly area name
            $t->decimal('lat', 9, 6)->nullable();
            $t->decimal('lon', 9, 6)->nullable();
            $t->string('city')->index();        // e.g. 'Gurugram'
            $t->string('state')->nullable();    // 'Haryana'
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('geo_pincodes'); }
};
