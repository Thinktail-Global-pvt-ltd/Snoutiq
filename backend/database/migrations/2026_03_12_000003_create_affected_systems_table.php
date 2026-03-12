<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affected_systems')) {
            Schema::create('affected_systems', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 64)->unique();
                $table->string('name', 120)->unique();
                $table->timestamps();
            });
        }

        $now = now();
        $rows = [
            ['code' => 'integumentary', 'name' => 'Integumentary system (skin & nails)', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'gastrointestinal', 'name' => 'Gastrointestinal system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hepatobiliary', 'name' => 'Hepatobiliary system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'urinary', 'name' => 'Urinary system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'genital', 'name' => 'Genital system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'nervous', 'name' => 'Nervous system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'musculoskeletal', 'name' => 'Musculoskeletal system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'endocrine', 'name' => 'Endocrine system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'muscular', 'name' => 'Muscular system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'respiratory', 'name' => 'Respiratory system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cardiovascular', 'name' => 'Cardiovascular system', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'visual', 'name' => 'Visual system (Eyes)', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'auditory_vestibular', 'name' => 'Auditory & vestibular system (Ear)', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'dental', 'name' => 'Dental system', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($rows as $row) {
            DB::table('affected_systems')->updateOrInsert(
                ['code' => $row['code']],
                ['name' => $row['name'], 'updated_at' => $row['updated_at'], 'created_at' => $row['created_at']]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affected_systems');
    }
};

