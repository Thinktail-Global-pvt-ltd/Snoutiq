<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'dog_vaccination_male_package_price' => 'dog_vaccination_package_price',
        'dog_vaccination_female_package_price' => 'dog_vaccination_package_price',
        'cat_vaccination_male_package_price' => 'cat_vaccination_package_price',
        'cat_vaccination_female_package_price' => 'cat_vaccination_package_price',
        'dog_neutering_male_price' => 'dog_neutering_price',
        'dog_neutering_female_price' => 'dog_neutering_price',
        'cat_neutering_male_price' => 'cat_neutering_price',
        'cat_neutering_female_price' => 'cat_neutering_price',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('clinic_specialized_packages')) {
            return;
        }

        Schema::table('clinic_specialized_packages', function (Blueprint $table) {
            foreach ($this->columns as $column => $after) {
                if (! Schema::hasColumn('clinic_specialized_packages', $column)) {
                    $table->decimal($column, 10, 2)->nullable()->after($after);
                }
            }
        });

        foreach ($this->columns as $column => $legacyColumn) {
            if (
                Schema::hasColumn('clinic_specialized_packages', $column)
                && Schema::hasColumn('clinic_specialized_packages', $legacyColumn)
            ) {
                DB::table('clinic_specialized_packages')
                    ->whereNull($column)
                    ->update([$column => DB::raw($legacyColumn)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('clinic_specialized_packages')) {
            return;
        }

        Schema::table('clinic_specialized_packages', function (Blueprint $table) {
            foreach (array_keys($this->columns) as $column) {
                if (Schema::hasColumn('clinic_specialized_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
